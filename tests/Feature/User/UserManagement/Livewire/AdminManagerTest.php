<?php

declare(strict_types=1);

use App\User\UserManagement\Livewire\AdminManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders', function () {
    Livewire::test(AdminManager::class)
        ->assertSuccessful();
});

test('opens create modal', function () {
    Livewire::test(AdminManager::class)
        ->call('create')
        ->assertSet('userModal', true);
});

test('validates name is required', function () {
    Livewire::test(AdminManager::class)
        ->set('form.name', '')
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

test('validates email is required', function () {
    Livewire::test(AdminManager::class)
        ->set('form.name', 'Test Admin')
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors(['form.email']);
});
