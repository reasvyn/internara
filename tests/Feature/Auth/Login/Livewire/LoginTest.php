<?php

declare(strict_types=1);

use App\Auth\Login\Livewire\Login;
use Livewire\Livewire;

test('login component renders', function () {
    Livewire::test(Login::class)
        ->assertSuccessful();
});

test('login validates required fields', function () {
    Livewire::test(Login::class)
        ->set('form.identifier', '')
        ->set('form.password', '')
        ->call('login')
        ->assertHasErrors([
            'form.identifier' => 'required',
            'form.password' => 'required',
        ]);
});
