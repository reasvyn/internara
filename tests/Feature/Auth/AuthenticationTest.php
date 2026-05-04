<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles if they don't exist
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    }
});

describe('Login', function () {
    it('can render the login page', function () {
        $this->get('/login')->assertOk();
    });

    it('can authenticate a user with valid email and password', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('can authenticate a user with valid username and password', function () {
        $user = User::factory()->create([
            'username' => 'u12345678',
            'password' => Hash::make('password123'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        Livewire::test(Login::class)
            ->set('identifier', 'u12345678')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('fails authentication with invalid password', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors();

        $this->assertGuest();
    });

    it('prevents login if account is suspended', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->setStatus(AccountStatus::SUSPENDED->value);

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors();

        $this->assertGuest();
    });
});

describe('Password Reset', function () {
    it('can render the forgot password page', function () {
        $this->get('/forgot-password')->assertOk();
    });

    it('can send a password reset link', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors();
    });

    it('can render the reset password page with token', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'test@example.com']))->assertOk();
    });

    it('can reset the password with valid token', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => 'test@example.com'])
            ->set('email', 'test@example.com')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    });
});
