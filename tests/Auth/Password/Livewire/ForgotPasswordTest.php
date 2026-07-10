<?php

declare(strict_types=1);

use App\Auth\Password\Livewire\ForgotPassword;
use Livewire\Livewire;

test('forgot password component renders', function () {
    Livewire::test(ForgotPassword::class)
        ->assertSuccessful();
});

test('forgot password validates required fields', function () {
    Livewire::test(ForgotPassword::class)
        ->set('form.email', '')
        ->call('sendResetLink')
        ->assertHasErrors(['form.email' => 'required']);
});

test('forgot password validates email format', function () {
    Livewire::test(ForgotPassword::class)
        ->set('form.email', 'not-an-email')
        ->call('sendResetLink')
        ->assertHasErrors(['form.email' => 'email']);
});
