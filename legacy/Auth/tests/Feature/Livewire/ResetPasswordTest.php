<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Livewire;

use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Modules\Auth\Livewire\ResetPassword;
use Modules\User\Models\User;

describe('ResetPassword Component', function () {
    beforeEach(function () {
        config(['services.cloudflare.turnstile.secret_key' => null]);
    });

    test('it renders the reset password form correctly', function () {
        Livewire::test(ResetPassword::class, [
            'token' => 'test-token',
            'email' => 'test@example.com',
        ])
            ->assertSee(__('auth::ui.reset_password.title'))
            ->assertSee(__('auth::ui.reset_password.form.password'));
    });

    test('it requires password confirmation match', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => 'test@example.com'])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'different_password')
            ->call('resetPassword')
            ->assertHasErrors(['password']);
    });

    test('it rejects invalid token', function () {
        Livewire::test(ResetPassword::class, [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['email']);
    });
});
