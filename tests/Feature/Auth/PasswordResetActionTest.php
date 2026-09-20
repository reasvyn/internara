<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Password\Actions\ResetPasswordAction;
use App\Modules\Auth\Domain\Password\Actions\SendPasswordResetLinkAction;
use App\Modules\Auth\Domain\Password\Data\ResetPasswordData;
use App\Modules\Auth\Domain\Password\Livewire\ForgotPassword;
use App\Modules\Auth\Domain\Password\Livewire\ResetPassword;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('D9TKW: password reset', function (): void {
    test('D9TKW-FR-PWRST-003: link request returns RESET_LINK_SENT and stores a broker token', function (): void {
        $user = User::factory()->create();

        $response = app(SendPasswordResetLinkAction::class)->execute($user->email);

        expect($response)->toBeInstanceOf(ActionResponse::class)
            ->and($response->success)->toBeTrue()
            ->and($response->data)->toBe(Password::RESET_LINK_SENT);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    });

    test('D9TKW-FR-PWRST-002: the action-level throttle hides behind RESET_LINK_SENT', function (): void {
        $user = User::factory()->create();
        $action = app(SendPasswordResetLinkAction::class);

        $responses = [];
        for ($i = 0; $i < 4; $i++) {
            $responses[] = $action->execute($user->email);
        }

        foreach ($responses as $response) {
            expect($response->success)->toBeTrue();
        }

        expect($responses[0]->data)->toBe(Password::RESET_LINK_SENT)
            ->and($responses[3]->data)->toBe(Password::RESET_LINK_SENT);
    });

    test('D9TKW-FR-PWRST-002: unknown email still returns RESET_LINK_SENT', function (): void {
        $response = app(SendPasswordResetLinkAction::class)->execute('ghost-'.uniqid().'@example.com');

        expect($response->success)->toBeTrue()
            ->and($response->data)->toBe(Password::RESET_LINK_SENT);
    });

    test('D9TKW-FR-PWRST-002: broker throttle still returns RESET_LINK_SENT', function (): void {
        $user = User::factory()->create();
        $action = app(SendPasswordResetLinkAction::class);

        $first = $action->execute($user->email);
        $second = $action->execute($user->email);

        expect($first->success)->toBeTrue()
            ->and($first->data)->toBe(Password::RESET_LINK_SENT)
            ->and($second->success)->toBeTrue()
            ->and($second->data)->toBe(Password::RESET_LINK_SENT);
    });

    test('D9TKW-FR-PWRST-009: valid token resets the password and returns ActionResponse::ok', function (): void {
        $user = User::factory()->withPassword('old-secret')->create();
        $token = Password::createToken($user);

        $response = app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'new-secret-456',
            passwordConfirmation: 'new-secret-456',
        ));

        expect($response)->toBeInstanceOf(ActionResponse::class)
            ->and($response->success)->toBeTrue()
            ->and(Hash::check('new-secret-456', $user->fresh()->password))->toBeTrue();
    });

    test('D9TKW-FR-PWRST-007: confirmation mismatch throws RejectedException without touching the password', function (): void {
        $user = User::factory()->withPassword('old-secret')->create();
        $token = Password::createToken($user);

        expect(fn () => app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'new-secret-456',
            passwordConfirmation: 'different-confirmation',
        )))->toThrow(RejectedException::class);

        expect(Hash::check('old-secret', $user->fresh()->password))->toBeTrue();
    });

    test('D9TKW-FR-PWRST-011: invalid token throws RejectedException', function (): void {
        $user = User::factory()->withPassword('old-secret')->create();

        expect(fn () => app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: 'bogus-token-value',
            password: 'new-secret-456',
            passwordConfirmation: 'new-secret-456',
        )))->toThrow(RejectedException::class);

        expect(Hash::check('old-secret', $user->fresh()->password))->toBeTrue();
    });

    test('D9TKW-FR-PWRST-015: consumed token cannot be reused', function (): void {
        $user = User::factory()->withPassword('old-secret')->create();
        $token = Password::createToken($user);
        $action = app(ResetPasswordAction::class);

        $action->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'new-secret-456',
            passwordConfirmation: 'new-secret-456',
        ));

        expect(fn () => $action->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'another-secret-789',
            passwordConfirmation: 'another-secret-789',
        )))->toThrow(RejectedException::class);

        expect(Hash::check('new-secret-456', $user->fresh()->password))->toBeTrue();
    });

    test('D9TKW-FR-PWRST-001: rate limiter enforces hourly budget of max 3 attempts per email and IP', function (): void {
        RateLimiter::clear('forgot-password:test-budget@example.com|127.0.0.1');
        $action = app(SendPasswordResetLinkAction::class);

        for ($i = 0; $i < 3; $i++) {
            $action->execute('test-budget@example.com');
        }

        $throttleKey = 'forgot-password:test-budget@example.com|127.0.0.1';
        expect(RateLimiter::tooManyAttempts($throttleKey, 3))->toBeTrue();
    });

    test('D9TKW-FR-PWRST-004: SendPasswordResetLinkAction logs link-requested and link-throttled events via SmartLogger', function (): void {
        RateLimiter::clear('forgot-password:logger-test@example.com|127.0.0.1');
        $captured = captureLogs();
        $user = User::factory()->create(['email' => 'logger-test@example.com']);
        $action = app(SendPasswordResetLinkAction::class);

        for ($i = 0; $i < 4; $i++) {
            $action->execute($user->email);
        }

        expect($captured->firstWhere('message', 'password_reset_link_requested'))->not->toBeNull()
            ->and($captured->firstWhere('message', 'password_reset_link_throttled'))->not->toBeNull();
    });

    test('D9TKW-FR-PWRST-005: ResetPasswordAction throttles by email and IP with max 5 attempts per 300 seconds', function (): void {
        RateLimiter::clear('reset-password:throttle-target@example.com|127.0.0.1');
        $action = app(ResetPasswordAction::class);

        for ($i = 0; $i < 5; $i++) {
            try {
                $action->execute(new ResetPasswordData(
                    email: 'throttle-target@example.com',
                    token: 'dummy',
                    password: 'password1',
                    passwordConfirmation: 'password1',
                ));
            } catch (RejectedException) {
            }
        }

        $throttleKey = 'reset-password:throttle-target@example.com|127.0.0.1';
        expect(RateLimiter::tooManyAttempts($throttleKey, 5))->toBeTrue();
    });

    test('D9TKW-FR-PWRST-006: throttled redemption throws RejectedException carrying the throttle message', function (): void {
        $throttleKey = 'reset-password:blocked-user@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($throttleKey, 300);
        }

        $action = app(ResetPasswordAction::class);

        expect(fn () => $action->execute(new ResetPasswordData(
            email: 'blocked-user@example.com',
            token: 'dummy',
            password: 'pwd',
            passwordConfirmation: 'pwd',
        )))->toThrow(RejectedException::class);
    });

    test('D9TKW-FR-PWRST-008: confirmation mismatch logs the mismatch event via SmartLogger and throws RejectedException', function (): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->actingAs($user);

        expect(fn () => app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'valid-secret-123',
            passwordConfirmation: 'mismatched-secret-123',
        )))->toThrow(RejectedException::class);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'password_reset_confirmation_mismatch',
        ]);
    });

    test('D9TKW-FR-PWRST-010: successful reset logs password_reset_success and returns ActionResponse::ok', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $res = app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'new-great-password',
            passwordConfirmation: 'new-great-password',
        ));

        expect($res->success)->toBeTrue();
        expect($captured->firstWhere('message', 'password_reset_success'))->not->toBeNull();
    });

    test('D9TKW-FR-PWRST-012: ForgotPassword Livewire component shows email input and link-sent state', function (): void {
        Livewire::test(ForgotPassword::class)
            ->assertSeeHtml('email')
            ->set('form.email', 'test-livewire@example.com')
            ->call('sendResetLink')
            ->assertSet('linkSent', true);
    });

    test('D9TKW-FR-PWRST-013: ResetPassword Livewire component binds URL token and sets form values', function (): void {
        Livewire::test(ResetPassword::class, ['token' => 'sample-test-token'])
            ->assertSet('form.token', 'sample-test-token');
    });

    test('D9TKW-FR-PWRST-014: reset tokens expire after 60 minutes', function (): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->travel(61)->minutes();

        expect(fn () => app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'new-password-after-expiry',
            passwordConfirmation: 'new-password-after-expiry',
        )))->toThrow(RejectedException::class);
    });

    test('D9TKW-UC-PWRST-001: user requests a reset link and receives an existence-neutral confirmation', function (): void {
        $user = User::factory()->create();
        $resKnown = app(SendPasswordResetLinkAction::class)->execute($user->email);
        $resUnknown = app(SendPasswordResetLinkAction::class)->execute('nonexistent-ghost@example.com');

        expect($resKnown->data)->toBe($resUnknown->data)
            ->and($resKnown->success)->toBeTrue()
            ->and($resUnknown->success)->toBeTrue();
    });

    test('D9TKW-UC-PWRST-002: user redeems a valid token and sets a new password end-to-end', function (): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $res = app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'brand-new-secret-xyz',
            passwordConfirmation: 'brand-new-secret-xyz',
        ));

        expect($res->success)->toBeTrue();
        expect(Hash::check('brand-new-secret-xyz', $user->fresh()->password))->toBeTrue();
    });

    test('D9TKW-NFR-PWRST-001: all reset events are logged via SmartLogger under Auth module', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $token = Password::createToken($user);

        app(SendPasswordResetLinkAction::class)->execute($user->email);
        app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'valid-password-audit',
            passwordConfirmation: 'valid-password-audit',
        ));

        expect($captured->firstWhere('message', 'password_reset_success'))->not->toBeNull()
            ->and($captured->firstWhere('message', 'password_reset_link_requested'))->not->toBeNull();
    });

    test('D9TKW-NFR-PWRST-002: email addresses are masked in logs via withPiiMasking', function (): void {
        $user = User::factory()->create(['email' => 'pii-secret-test@example.com']);
        $token = Password::createToken($user);

        expect(fn () => app(ResetPasswordAction::class)->execute(new ResetPasswordData(
            email: $user->email,
            token: $token,
            password: 'pass',
            passwordConfirmation: 'mismatch',
        )))->toThrow(RejectedException::class);

        $log = DB::table('activity_log')
            ->where('event', 'password_reset_confirmation_mismatch')
            ->latest('id')
            ->first();

        expect($log)->not->toBeNull();
        expect($log->properties)->not->toContain('pii-secret-test@example.com');
    });

    test('D9TKW-NFR-PWRST-003: throttle and unknown address replies reveal nothing about existence', function (): void {
        $user = User::factory()->create();
        $action = app(SendPasswordResetLinkAction::class);

        $res1 = $action->execute($user->email);
        $res2 = $action->execute('nobody@domain.test');

        expect($res1->success)->toBe($res2->success)
            ->and($res1->data)->toBe($res2->data);
    });

    test('D9TKW-NFR-PWRST-004: reset tokens persist hashed never plain', function (): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $row = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        expect($row)->not->toBeNull();
        expect($row->token)->not->toBe($token);
        expect(Hash::check($token, $row->token))->toBeTrue();
    });

    test('D9TKW-NFR-PWRST-005: all password reset classes declare strict_types', function (): void {
        $files = [
            app_path('Modules/Auth/Domain/Password/Actions/SendPasswordResetLinkAction.php'),
            app_path('Modules/Auth/Domain/Password/Actions/ResetPasswordAction.php'),
            app_path('Modules/Auth/Domain/Password/Data/ResetPasswordData.php'),
            app_path('Modules/Auth/Domain/Password/Livewire/ForgotPassword.php'),
            app_path('Modules/Auth/Domain/Password/Livewire/ResetPassword.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)->toContain('declare(strict_types=1);');
        }
    });

    test('D9TKW-DD-PWRST-001: throttled dispatch answers success to starve enumeration', function (): void {
        $action = app(SendPasswordResetLinkAction::class);
        $throttleKey = 'forgot-password:dd-test@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        for ($i = 0; $i < 5; $i++) {
            $res = $action->execute('dd-test@example.com');
            expect($res->success)->toBeTrue();
            expect($res->data)->toBe(Password::RESET_LINK_SENT);
        }
    });

    test('D9TKW-DD-PWRST-002: token lifecycle rides the framework broker not a bespoke store', function (): void {
        $broker = Password::broker();
        expect($broker)->toBeInstanceOf(PasswordBroker::class);
    });
});
