<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Livewire\AccountRecovery;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    User::factory()->create([
        'status' => AccountStatus::ACTIVATED,
        'username' => 'testuser',
    ]);
    test()->actingAs(User::first());
});

test('account recovery form renders', function () {
    Livewire::test(AccountRecovery::class)
        ->assertSuccessful();
});

test('account recovery form validates required fields', function () {
    Livewire::test(AccountRecovery::class)
        ->set('form.username', '')
        ->set('form.recoveryCode', '')
        ->set('form.password', '')
        ->call('redeem')
        ->assertHasErrors([
            'form.username' => 'required',
            'form.recoveryCode' => 'required',
            'form.password' => 'required',
        ]);
});

test('account recovery form validates recovery code size', function () {
    Livewire::test(AccountRecovery::class)
        ->set('form.username', 'testuser')
        ->set('form.recoveryCode', 'short')
        ->set('form.password', 'newpassword')
        ->set('form.password_confirmation', 'newpassword')
        ->call('redeem')
        ->assertHasErrors(['form.recoveryCode' => 'size']);
});

test('account recovery form validates password confirmation', function () {
    Livewire::test(AccountRecovery::class)
        ->set('form.username', 'testuser')
        ->set('form.recoveryCode', 'ABCD1234XYZ')
        ->set('form.password', 'newpassword')
        ->set('form.password_confirmation', 'different')
        ->call('redeem')
        ->assertHasErrors(['form.password' => 'confirmed']);
});
