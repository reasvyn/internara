<?php

declare(strict_types=1);

use App\Actions\Auth\ResetPasswordAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('throws RuntimeException with invalid token', function () {
        UserFactory::new()->create(['email' => 'test@example.com']);

        expect(fn () => app(ResetPasswordAction::class)->execute(
            email: 'test@example.com',
            token: 'invalid-token',
            password: 'new-password',
            passwordConfirmation: 'new-password',
        ))->toThrow(RuntimeException::class);
    });

    it('throws RuntimeException for non-existent user', function () {
        expect(fn () => app(ResetPasswordAction::class)->execute(
            email: 'nonexistent@example.com',
            token: 'some-token',
            password: 'new-password',
            passwordConfirmation: 'new-password',
        ))->toThrow(RuntimeException::class);
    });
});
