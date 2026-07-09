<?php

declare(strict_types=1);

use App\User\UserManagement\Livewire\UserManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders user manager', function () {
    Livewire::test(UserManager::class)
        ->assertSuccessful();
});

test('opens create user modal', function () {
    Livewire::test(UserManager::class)
        ->call('createUser')
        ->assertSet('userModal', true);
});

test('validates name is required', function () {
    Livewire::test(UserManager::class)
        ->set('form.name', '')
        ->set('form.email', '')
        ->call('saveUser')
        ->assertHasErrors(['form.name', 'form.email']);
});
