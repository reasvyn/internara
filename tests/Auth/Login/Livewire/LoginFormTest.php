<?php

declare(strict_types=1);

use App\Auth\Login\Livewire\Login;
use Livewire\Livewire;

test('login form has correct default values', function () {
    Livewire::test(Login::class)
        ->assertSet('form.identifier', '')
        ->assertSet('form.password', '')
        ->assertSet('form.remember', false);
});

test('login form validates through login component', function () {
    Livewire::test(Login::class)
        ->set('form.identifier', '')
        ->set('form.password', '')
        ->call('login')
        ->assertHasErrors([
            'form.identifier' => 'required',
            'form.password' => 'required',
        ]);
});
