<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Password\Actions\UpdateUserPasswordAction;
use App\Modules\Auth\Domain\Password\Events\PasswordUpdated;
use App\Modules\Auth\Domain\Password\Listeners\InvalidateSessionOnPasswordChange;
use App\Modules\Auth\Domain\Password\Listeners\SendPasswordChangedMail;
use App\Modules\Auth\Notifications\CredentialChangedNotification;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Setting\Models\Setting;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: credential-change notify', function (): void {
    test('YB7RG-FR-AUTH-041: password change commits the hash and fires PasswordUpdated', function (): void {
        Event::fake([PasswordUpdated::class]);

        $user = User::factory()->withPassword('Old-Secret-123')->create();

        $response = app(UpdateUserPasswordAction::class)->execute($user, 'Updated-Secret-456');

        expect($response)->toBeInstanceOf(ActionResponse::class)
            ->and($response->failed())->toBeFalse()
            ->and(Hash::check('Updated-Secret-456', $user->fresh()->password))->toBeTrue();

        Event::assertDispatched(PasswordUpdated::class, fn (PasswordUpdated $e) => $e->user->is($user));
    });

    test('YB7RG-FR-AUTH-042: mail listener notifies users with an address and skips the rest quietly', function (): void {
        Notification::fake();

        $user = User::factory()->create();

        (new SendPasswordChangedMail)->handle(new PasswordUpdated($user));

        Notification::assertSentTo($user, CredentialChangedNotification::class);

        expect((new CredentialChangedNotification('password'))->via($user))->toBe(['mail']);

        $addressless = User::factory()->create();
        $addressless->email = null;

        // A missing address must not throw inside the (queued) listener.
        (new SendPasswordChangedMail)->handle(new PasswordUpdated($addressless));

        Notification::assertSentToTimes($user, CredentialChangedNotification::class, 1);
    });

    test('YB7RG-FR-AUTH-043: in-app listener records the password_changed notice', function (): void {
        $user = User::factory()->create();

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

        (new InvalidateSessionOnPasswordChange($sent))->handle(new PasswordUpdated($user));

        expect($sent->calls)->toHaveCount(1)
            ->and($sent->calls[0]->userId)->toBe($user->id)
            ->and($sent->calls[0]->type)->toBe('password_changed')
            ->and($sent->calls[0]->title)->toBe(__('notifications.password_changed.title'))
            ->and($sent->calls[0]->message)->toBe(__('notifications.password_changed.message'))
            ->and($sent->calls[0]->link)->toBe(route('profile'));
    });

    test('YB7RG-FR-AUTH-044: notice carries subject, named greeting, change line and support address', function (): void {
        $user = User::factory()->create(['name' => 'Budi Santoso']);

        $fallback = (new CredentialChangedNotification('password'))->toMail($user);

        expect($fallback->subject)->toBe(__('auth.notifications.credential_changed_subject'))
            ->and($fallback->greeting)->toContain('Budi Santoso')
            ->and(implode("\n", $fallback->introLines))->toContain(__('auth.notifications.password_changed_line'))
            ->and(implode("\n", $fallback->introLines))->toContain(__('auth.notifications.credential_changed_warning'));

        Setting::updateOrCreate(['key' => 'support_email'], ['value' => 'help@school.test']);
        Cache::forget(config('cache-keys.settings_all'));

        try {
            $withSupport = (new CredentialChangedNotification('password'))->toMail($user->fresh());

            expect(implode("\n", $withSupport->introLines))->toContain('help@school.test')
                ->and(implode("\n", $withSupport->introLines))
                ->toContain(__('auth.notifications.credential_changed_warning_with_email', ['support_email' => 'help@school.test']));
        } finally {
            Setting::where('key', 'support_email')->delete();
            Cache::forget(config('cache-keys.settings_all'));
        }
    });

    test('YB7RG-UC-AUTH-007: one password change reaches both mail and in-app channels', function (): void {
        Notification::fake();

        $inApp = new class implements SendsNotifications
        {
            /** @var list<NotificationData> */
            public array $calls = [];

            public function execute(NotificationData $data): mixed
            {
                $this->calls[] = $data;

                return null;
            }
        };

        app()->instance(SendsNotifications::class, $inApp);

        $user = User::factory()->create();

        event(new PasswordUpdated($user));

        Notification::assertSentTo($user, CredentialChangedNotification::class);

        expect($inApp->calls)->toHaveCount(1)
            ->and($inApp->calls[0]->type)->toBe('password_changed')
            ->and($inApp->calls[0]->userId)->toBe($user->id);
    });
});
