<?php

declare(strict_types=1);

use App\User\UserManagement\Livewire\SupervisorManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders within supervisor manager', function () {
    Livewire::test(SupervisorManager::class)
        ->assertSuccessful();
});

test('validates name is required via form', function () {
    Livewire::test(SupervisorManager::class)
        ->call('create')
        ->set('form.name', '')
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

test('validates email is required via form', function () {
    Livewire::test(SupervisorManager::class)
        ->call('create')
        ->set('form.name', 'Test Supervisor')
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors(['form.email']);
});
