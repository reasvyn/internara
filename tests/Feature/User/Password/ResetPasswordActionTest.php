<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Password\Actions\ResetPasswordAction;
use App\Domain\User\Aggregates\Password\Actions\SendPasswordResetLinkAction;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

// ─── SendPasswordResetLinkAction ───

test('send reset link returns sent status for existing user', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'alice@example.com']);

    $status = app(SendPasswordResetLinkAction::class)->execute('alice@example.com');

    expect($status)->toBe(Password::RESET_LINK_SENT);
    Notification::assertSentTo($user, ResetPassword::class);
});

test('send reset link returns user status for non-existent user', function () {
    Notification::fake();

    $status = app(SendPasswordResetLinkAction::class)->execute('nobody@example.com');

    expect($status)->toBe(Password::INVALID_USER);
});

// ─── ResetPasswordAction ───

test('reset password succeeds with valid token and email', function () {
    $user = User::factory()->create([
        'email' => 'bob@example.com',
        'password' => Hash::make('OldPass123'),
    ]);

    $token = Password::createToken($user);

    $result = app(ResetPasswordAction::class)->execute(
        email: 'bob@example.com',
        token: $token,
        password: 'NewSecurePass1',
        passwordConfirmation: 'NewSecurePass1',
    );

    expect($result)->toBeTrue();
    expect(Hash::check('NewSecurePass1', $user->fresh()->password))->toBeTrue();
});

test('reset password fails with invalid token', function () {
    $user = User::factory()->create([
        'email' => 'carol@example.com',
        'password' => Hash::make('OldPass123'),
    ]);

    expect(fn () => app(ResetPasswordAction::class)->execute(
        email: 'carol@example.com',
        token: 'invalid-token',
        password: 'NewSecurePass1',
        passwordConfirmation: 'NewSecurePass1',
    ))->toThrow(RuntimeException::class, __('passwords.token'));

    expect(Hash::check('OldPass123', $user->fresh()->password))->toBeTrue();
});

test('reset password fails with wrong email', function () {
    $user = User::factory()->create([
        'email' => 'dave@example.com',
    ]);

    $token = Password::createToken($user);

    expect(fn () => app(ResetPasswordAction::class)->execute(
        email: 'wrong@example.com',
        token: $token,
        password: 'NewSecurePass1',
        passwordConfirmation: 'NewSecurePass1',
    ))->toThrow(RuntimeException::class);

    expect(Hash::check('OldPass123', $user->fresh()->password))->toBeFalse();
});

test('reset password fails with non-matching confirmation', function () {
    $user = User::factory()->create([
        'email' => 'eve@example.com',
        'password' => Hash::make('OldPass123'),
    ]);

    $token = Password::createToken($user);

    expect(fn () => app(ResetPasswordAction::class)->execute(
        email: 'eve@example.com',
        token: $token,
        password: 'NewSecurePass1',
        passwordConfirmation: 'DifferentPass1',
    ))->toThrow(RuntimeException::class, __('validation.custom.password.confirmed'));

    expect(Hash::check('OldPass123', $user->fresh()->password))->toBeTrue();
});

test('reset password succeeds with confirmed flag creates new session', function () {
    $user = User::factory()->create([
        'email' => 'frank@example.com',
        'password' => Hash::make('OldPass123'),
    ]);

    $token = Password::createToken($user);

    app(ResetPasswordAction::class)->execute(
        email: 'frank@example.com',
        token: $token,
        password: 'NewSecurePass1',
        passwordConfirmation: 'NewSecurePass1',
    );

    expect(Hash::check('NewSecurePass1', $user->fresh()->password))->toBeTrue();
});
