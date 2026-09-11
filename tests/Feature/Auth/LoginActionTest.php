<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: LoginAction', function (): void {
    test('YB7RG-FR-AUTH-001: login with email returns the authenticated user', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();

        $result = app(LoginAction::class)->execute(new LoginData(
            identifier: $user->email,
            password: 'secret-123',
        ));

        expect($result)->toBeInstanceOf(User::class)
            ->and($result->id)->toBe($user->id)
            ->and(auth()->id())->toBe($user->id);
    });

    test('YB7RG-FR-AUTH-001: login with username returns the authenticated user', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();

        $result = app(LoginAction::class)->execute(new LoginData(
            identifier: $user->username,
            password: 'secret-123',
        ));

        expect($result->id)->toBe($user->id)
            ->and(auth()->id())->toBe($user->id);
    });

    test('YB7RG-FR-AUTH-008: successful login regenerates the session', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $before = session()->getId();

        app(LoginAction::class)->execute(new LoginData(
            identifier: $user->email,
            password: 'secret-123',
        ));

        expect(session()->getId())->not->toBe($before);
    });

    test('YB7RG-FR-AUTH-016: wrong password throws RejectedException and dispatches LoginFailed', function (): void {
        Event::fake([LoginFailed::class]);
        $user = User::factory()->withPassword('secret-123')->create();

        expect(fn () => app(LoginAction::class)->execute(new LoginData(
            identifier: $user->email,
            password: 'wrong-password',
        )))->toThrow(RejectedException::class);

        Event::assertDispatched(LoginFailed::class);
        expect(auth()->check())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-003: unknown identifier throws RejectedException', function (): void {
        expect(fn () => app(LoginAction::class)->execute(new LoginData(
            identifier: 'nobody-here-'.uniqid().'@example.com',
            password: 'whatever-123',
        )))->toThrow(RejectedException::class);
    });

    test('YB7RG-FR-AUTH-006: locked account throws RejectedException before checking credentials', function (): void {
        $user = User::factory()->locked()->withPassword('secret-123')->create();

        expect(fn () => app(LoginAction::class)->execute(new LoginData(
            identifier: $user->email,
            password: 'secret-123',
        )))->toThrow(RejectedException::class);

        expect(auth()->check())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-004: suspended status throws RejectedException', function (): void {
        $user = User::factory()->withPassword('secret-123')->create(['status' => 'suspended']);

        expect(fn () => app(LoginAction::class)->execute(new LoginData(
            identifier: $user->email,
            password: 'secret-123',
        )))->toThrow(RejectedException::class);

        expect(auth()->check())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-007: account requiring setup throws RejectedException', function (): void {
        $user = User::factory()->requiresSetup()->withPassword('secret-123')->create();

        expect(fn () => app(LoginAction::class)->execute(new LoginData(
            identifier: $user->email,
            password: 'secret-123',
        )))->toThrow(RejectedException::class);

        expect(auth()->check())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-010: ten failures arm the lockout and the next attempt is throttled', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $action = app(LoginAction::class);

        for ($i = 0; $i < 10; $i++) {
            try {
                $action->execute(new LoginData(identifier: $user->email, password: 'wrong-'.$i));
            } catch (RejectedException) {
            }
        }

        $hash = hash('crc32b', $user->email);
        expect(Cache::get(config('cache-keys.auth_login_lockout').$hash))->not->toBeNull();

        expect(fn () => $action->execute(new LoginData(
            identifier: $user->email,
            password: 'secret-123',
        )))->toThrow(RejectedException::class);

        expect(auth()->check())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-004: superadmin bypasses status checks and is never lockable', function (): void {
        $admin = User::factory()->withPassword('secret-123')->create(['status' => 'suspended']);
        $admin->assignRole('super_admin');

        $result = app(LoginAction::class)->execute(new LoginData(
            identifier: $admin->email,
            password: 'secret-123',
        ));

        expect($result->id)->toBe($admin->id)
            ->and(auth()->id())->toBe($admin->id);
    });
});
