<?php

declare(strict_types=1);

use App\User\UserManagement\Livewire\StudentManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders student manager', function () {
    Livewire::test(StudentManager::class)
        ->assertSuccessful();
});

test('opens create student modal', function () {
    Livewire::test(StudentManager::class)
        ->call('create')
        ->assertSet('userModal', true);
});
