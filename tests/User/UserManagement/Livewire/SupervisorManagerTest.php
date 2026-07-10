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

test('renders supervisor manager', function () {
    Livewire::test(SupervisorManager::class)
        ->assertSuccessful();
});

test('opens create supervisor modal', function () {
    Livewire::test(SupervisorManager::class)
        ->call('create')
        ->assertSet('userModal', true);
});

test('lists supervisor users', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    Livewire::test(SupervisorManager::class)
        ->assertSee($supervisor->name);
});
