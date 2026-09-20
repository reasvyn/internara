<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Auth\Domain\Login\Events\LoginSucceeded;
use App\Modules\Auth\Domain\Login\Listeners\LogLoginFailed;
use App\Modules\Auth\Domain\Login\Listeners\SendRoleWelcomeNotification;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: login identity, events and logging', function (): void {
    test('YB7RG-FR-AUTH-002: email-shaped strings query the email column, plain strings query username', function (): void {
        Event::fake([LoginFailed::class]);

        $user = User::factory()->withPassword('secret-123')->create([
            'email' => 'real-'.uniqid().'@example.test',
            'username' => 'shaped-'.uniqid().'@example.test',
        ]);

        expect((bool) filter_var($user->username, FILTER_VALIDATE_EMAIL))->toBeTrue();

        try {
            app(LoginAction::class)->execute(new LoginData(identifier: $user->username, password: 'secret-123'));
            $this->fail('Expected user_not_found: an email-shaped identifier must query the email column.');
        } catch (RejectedException) {
        }

        Event::assertDispatched(LoginFailed::class, fn (LoginFailed $e) => $e->identifier === $user->username && $e->reason === 'user_not_found');

        $plain = User::factory()->withPassword('secret-123')->create();

        expect((bool) filter_var($plain->username, FILTER_VALIDATE_EMAIL))->toBeFalse();

        $result = app(LoginAction::class)->execute(new LoginData(identifier: $plain->username, password: 'secret-123'));

        expect($result->id)->toBe($plain->id)
            ->and(auth()->id())->toBe($plain->id);
    });

    test('YB7RG-FR-AUTH-018: successful login dispatches LoginSucceeded carrying the user and identifier', function (): void {
        Event::fake([LoginSucceeded::class]);

        $user = User::factory()->withPassword('secret-123')->create();

        $result = app(LoginAction::class)->execute(new LoginData(identifier: $user->email, password: 'secret-123'));

        expect($result->id)->toBe($user->id);

        Event::assertDispatched(LoginSucceeded::class, fn (LoginSucceeded $e) => $e->user instanceof User
            && $e->user->id === $user->id
            && $e->identifier === $user->email);
    });

    test('YB7RG-FR-AUTH-019: first login sends a role welcome once, then stays silent', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $user->assignRole('student');

        expect($user->fresh()->first_login_at)->toBeNull();

        $sent = new class implements SendsNotifications
        {
            /** @var list<NotificationData> */
            public array $calls = [];

            public function execute(NotificationData $data): mixed
            {
                $this->calls[] = $data;

                return null;
            }
        };

        $listener = new SendRoleWelcomeNotification($sent);
        $listener->handle(new LoginSucceeded($user->fresh(), $user->email));

        expect($sent->calls)->toHaveCount(1)
            ->and($sent->calls[0]->userId)->toBe($user->id)
            ->and($sent->calls[0]->type)->toBe('welcome')
            ->and($sent->calls[0]->title)->toBe(__('notifications.welcome_to_dashboard.title'))
            ->and($sent->calls[0]->message)->toBe(__('notifications.welcome_to_dashboard.student'))
            ->and($sent->calls[0]->link)->toBe(route('dashboard'))
            ->and($user->fresh()->first_login_at)->not->toBeNull();

        $listener->handle(new LoginSucceeded($user->fresh(), $user->email));

        expect($sent->calls)->toHaveCount(1);
    });

    test('YB7RG-FR-AUTH-017: failed-login listener masks the identifier in the system audit record', function (): void {
        $captured = captureLogs();
        $identifier = 'budi.santoso@example.sch.id';

        (new LogLoginFailed)->handle(new LoginFailed($identifier, 'invalid_password'));

        $record = $captured->firstWhere('message', 'login_failed');

        expect($record)->not->toBeNull()
            ->and($record->context['payload']['identifier'])->toContain('***')
            ->and($record->context['payload']['identifier'])->not->toContain('budi.santoso')
            ->and($record->context['payload']['reason'])->toBe('invalid_password');
    });

    test('YB7RG-FR-AUTH-016 + YB7RG-FR-AUTH-017: failed login event reaches masked logging with its reason', function (): void {
        $captured = captureLogs();
        $user = User::factory()->withPassword('secret-123')->create();
        $identifier = $user->email;

        try {
            app(LoginAction::class)->execute(new LoginData(identifier: $identifier, password: 'wrong-password'));
        } catch (RejectedException) {
        }

        $record = $captured->firstWhere('message', 'login_failed');

        expect($record)->not->toBeNull()
            ->and($record->context['payload']['identifier'])->not->toContain($identifier)
            ->and($record->context['payload']['reason'])->toBe('invalid_password');
    });

    test('YB7RG-FR-AUTH-020: login success is recorded with the user as subject', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();

        app(LoginAction::class)->execute(new LoginData(identifier: $user->email, password: 'secret-123'));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'login_success',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    });

    test('YB7RG-FR-AUTH-045: login keeps the stored role and stores no proxy identity', function (): void {
        $teacher = User::factory()->withPassword('secret-123')->create();
        $teacher->assignRole('teacher');

        $result = app(LoginAction::class)->execute(new LoginData(identifier: $teacher->email, password: 'secret-123'));

        expect($result->id)->toBe($teacher->id)
            ->and(auth()->user()->hasRole('teacher'))->toBeTrue()
            ->and(auth()->user()->roles)->toHaveCount(1);

        $sessionKeys = array_keys(session()->all());

        foreach (['proxy', 'acting_as', 'impersonator', 'proxy_user'] as $forbidden) {
            expect($sessionKeys)->not->toContain($forbidden);
        }
    });

    test('YB7RG-NFR-AUTH-003 + YB7RG-NFR-AUTH-008: every failure mode speaks the same two sentences', function (): void {
        $messageFor = function (callable $attempt): string {
            try {
                $attempt();
            } catch (RejectedException $e) {
                return $e->getMessage();
            }

            $this->fail('Expected a RejectedException from the login attempt.');
        };

        $action = app(LoginAction::class);

        $unknown = $messageFor(fn () => $action->execute(new LoginData(
            identifier: 'nobody-'.uniqid().'@example.test',
            password: 'whatever-123',
        )));

        $password = 'secret-123';
        $victim = User::factory()->withPassword($password)->create();

        $wrongPassword = $messageFor(fn () => $action->execute(new LoginData(
            identifier: $victim->email,
            password: 'not-the-password',
        )));

        $locked = $messageFor(fn () => $action->execute(new LoginData(
            identifier: User::factory()->locked()->withPassword($password)->create()->email,
            password: $password,
        )));

        $suspended = $messageFor(fn () => $action->execute(new LoginData(
            identifier: User::factory()->withPassword($password)->create(['status' => 'suspended'])->email,
            password: $password,
        )));

        $provisioned = $messageFor(fn () => $action->execute(new LoginData(
            identifier: User::factory()->withPassword($password)->create(['status' => 'provisioned'])->email,
            password: $password,
        )));

        $archived = $messageFor(fn () => $action->execute(new LoginData(
            identifier: User::factory()->withPassword($password)->create(['status' => 'archived'])->email,
            password: $password,
        )));

        $setup = $messageFor(fn () => $action->execute(new LoginData(
            identifier: User::factory()->requiresSetup()->withPassword($password)->create()->email,
            password: $password,
        )));

        expect($unknown)->toBe(__('auth.failed'))
            ->and($wrongPassword)->toBe(__('auth.failed'))
            ->and($unknown)->toBe($wrongPassword)
            ->and($locked)->toBe(__('auth.blocked'))
            ->and($suspended)->toBe(__('auth.blocked'))
            ->and($provisioned)->toBe(__('auth.blocked'))
            ->and($archived)->toBe(__('auth.blocked'))
            ->and($setup)->toBe(__('auth.blocked'));
    });
});
