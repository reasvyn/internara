<?php

declare(strict_types=1);

use App\Auth\Account\Livewire\ActivateAccount;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('activate account form renders', function () {
    Livewire::test(ActivateAccount::class)
        ->assertSuccessful();
});

test('activate validates required fields', function () {
    Livewire::test(ActivateAccount::class)
        ->set('email', '')
        ->set('code', '')
        ->set('password', '')
        ->call('activate')
        ->assertHasErrors([
            'email' => 'required',
            'code' => 'required',
            'password' => 'required',
        ]);
});

test('activate validates email format', function () {
    Livewire::test(ActivateAccount::class)
        ->set('email', 'not-an-email')
        ->call('activate')
        ->assertHasErrors(['email' => 'email']);
});

test('activate validates password confirmation', function () {
    Livewire::test(ActivateAccount::class)
        ->set('password', 'newpassword')
        ->set('password_confirmation', 'different')
        ->call('activate')
        ->assertHasErrors(['password' => 'confirmed']);
});
