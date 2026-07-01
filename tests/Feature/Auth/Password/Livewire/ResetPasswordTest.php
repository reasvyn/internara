<?php

declare(strict_types=1);

use App\Auth\Password\Livewire\ResetPassword;
use Livewire\Livewire;

test('reset password component renders with token', function () {
    Livewire::test(ResetPassword::class, ['token' => 'test-token'])
        ->assertSuccessful();
});

test('reset password validates required fields', function () {
    Livewire::test(ResetPassword::class, ['token' => 'test-token'])
        ->set('form.email', '')
        ->set('form.password', '')
        ->call('resetPassword')
        ->assertHasErrors([
            'form.email' => 'required',
            'form.password' => 'required',
        ]);
});

test('reset password validates email format', function () {
    Livewire::test(ResetPassword::class, ['token' => 'test-token'])
        ->set('form.email', 'not-an-email')
        ->set('form.password', 'newpassword')
        ->set('form.password_confirmation', 'newpassword')
        ->call('resetPassword')
        ->assertHasErrors(['form.email' => 'email']);
});

test('reset password validates password confirmation', function () {
    Livewire::test(ResetPassword::class, ['token' => 'test-token'])
        ->set('form.email', 'user@example.com')
        ->set('form.password', 'newpassword')
        ->set('form.password_confirmation', 'different')
        ->call('resetPassword')
        ->assertHasErrors(['form.password' => 'confirmed']);
});
