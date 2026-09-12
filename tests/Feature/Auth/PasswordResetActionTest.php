<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Password\Actions\ResetPasswordAction;
use App\Modules\Auth\Domain\Password\Actions\SendPasswordResetLinkAction;
use App\Modules\Auth\Domain\Password\Data\ResetPasswordData;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

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
});
