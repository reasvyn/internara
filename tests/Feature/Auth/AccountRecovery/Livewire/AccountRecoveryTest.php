<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Livewire\AccountRecovery;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create([
        'status' => AccountStatus::ACTIVATED,
        'username' => 'testuser',
    ]);
    test()->actingAs($user);
});

test('renders the account recovery component', function () {
    Livewire::test(AccountRecovery::class)
        ->assertSuccessful();
});

test('validates recovery code input', function () {
    Livewire::test(AccountRecovery::class)
        ->set('form.recoveryCode', '')
        ->call('redeem')
        ->assertHasErrors(['form.recoveryCode']);
});

test('validates password confirmation', function () {
    Livewire::test(AccountRecovery::class)
        ->set('form.recoveryCode', 'ABCD1234XYZ')
        ->set('form.username', 'testuser')
        ->set('form.password', 'newpassword')
        ->set('form.password_confirmation', 'different')
        ->call('redeem')
        ->assertHasErrors(['form.password']);
});
