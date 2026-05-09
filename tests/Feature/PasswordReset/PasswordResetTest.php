<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => Hash::make('password123'),
    ]);
});

describe('ForgotPassword', function () {

    it('renders the forgot password page', function () {
        Livewire::test(ForgotPassword::class)
            ->assertSuccessful();
    });

    it('validates required email', function () {
        Livewire::test(ForgotPassword::class)
            ->call('sendResetLink')
            ->assertHasErrors(['email' => 'required']);
    });

    it('validates email format', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'not-an-email')
            ->call('sendResetLink')
            ->assertHasErrors(['email' => 'email']);
    });

    it('shows success state for existing email', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('linkSent', true);
    });

    it('shows success state even for non-existent email', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'unknown@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('linkSent', true);
    });

    it('rate limits after multiple requests', function () {
        for ($i = 0; $i < 3; $i++) {
            Livewire::test(ForgotPassword::class)
                ->set('email', 'test@example.com')
                ->call('sendResetLink');
        }

        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    });

});

describe('ResetPassword', function () {

    beforeEach(function () {
        $this->token = Password::broker()->createToken($this->user);
    });

    it('renders the reset password page with token', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->assertSuccessful();
    });

    it('validates required fields', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->call('resetPassword')
            ->assertHasErrors([
                'email' => 'required',
                'password' => 'required',
            ]);
    });

    it('validates email format', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->set('email', 'not-email')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['email' => 'email']);
    });

    it('validates password minimum length', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('resetPassword')
            ->assertHasErrors(['password' => 'min']);
    });

    it('validates password confirmation', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->set('email', 'test@example.com')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'different')
            ->call('resetPassword')
            ->assertHasErrors(['password' => 'confirmed']);
    });

    it('resets password with valid token', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->set('email', 'test@example.com')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
    });

    it('fails with invalid token', function () {
        Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
            ->set('email', 'test@example.com')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors('email');

        expect(Hash::check('password123', $this->user->fresh()->password))->toBeTrue();
    });

    it('fails with wrong email', function () {
        Livewire::test(ResetPassword::class, ['token' => $this->token])
            ->set('email', 'wrong@example.com')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors('email');
    });

});
