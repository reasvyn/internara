<?php

declare(strict_types=1);

use App\Auth\Password\Livewire\ForgotPassword;
use Livewire\Livewire;

test('forgot password form has correct default values', function () {
    Livewire::test(ForgotPassword::class)
        ->assertSet('form.email', '');
});

test('forgot password form validates through component', function () {
    Livewire::test(ForgotPassword::class)
        ->set('form.email', '')
        ->call('sendResetLink')
        ->assertHasErrors(['form.email' => 'required']);
});
