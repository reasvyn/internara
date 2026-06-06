<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Password\Actions\ResetPasswordAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(ResetPasswordAction::class);
    $this->token = Password::broker()->createToken($this->user);
});

test('resets password with valid token', function () {
    $result = $this->action->execute(
        $this->user->email,
        $this->token,
        'NewSecurePass123!',
        'NewSecurePass123!',
    );

    expect($result)->toBeTrue();
    expect(Hash::check('NewSecurePass123!', $this->user->fresh()->password))->toBeTrue();
});

test('fails with mismatched password confirmation', function () {
    expect(fn () => $this->action->execute(
        $this->user->email,
        $this->token,
        'NewPass123!',
        'DifferentPass456!',
    ))->toThrow(RuntimeException::class, trans('validation.custom.password.confirmed'));
});

test('fails with invalid token', function () {
    expect(fn () => $this->action->execute(
        $this->user->email,
        'invalid-token',
        'NewSecurePass123!',
        'NewSecurePass123!',
    ))->toThrow(RuntimeException::class, trans('passwords.token'));
});

test('fails with non-existent email', function () {
    expect(fn () => $this->action->execute(
        'nonexistent@test.com',
        $this->token,
        'NewSecurePass123!',
        'NewSecurePass123!',
    ))->toThrow(RuntimeException::class, trans('passwords.user'));
});
