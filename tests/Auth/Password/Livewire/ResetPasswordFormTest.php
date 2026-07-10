<?php

declare(strict_types=1);

use App\Auth\Password\Livewire\ResetPassword;
use Livewire\Livewire;

test('reset password form has correct default values', function () {
    Livewire::test(ResetPassword::class, ['token' => 'test-token'])
        ->assertSet('form.token', 'test-token')
        ->assertSet('form.email', '')
        ->assertSet('form.password', '')
        ->assertSet('form.password_confirmation', '');
});

test('reset password form validates through component', function () {
    Livewire::test(ResetPassword::class, ['token' => 'test-token'])
        ->set('form.email', '')
        ->set('form.password', '')
        ->call('resetPassword')
        ->assertHasErrors([
            'form.email' => 'required',
            'form.password' => 'required',
        ]);
});
