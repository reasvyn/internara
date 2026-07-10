<?php

declare(strict_types=1);

use App\Academics\Department\Livewire\DepartmentManager;
use App\Academics\Department\Models\Department;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the department manager component', function () {
    Livewire::test(DepartmentManager::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(DepartmentManager::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('opens edit modal with department data', function () {
    $department = Department::factory()->create();

    Livewire::test(DepartmentManager::class)
        ->call('edit', $department->id)
        ->assertSet('showModal', true)
        ->assertSet('form.id', $department->id)
        ->assertSet('form.name', $department->name);
});

test('ask delete opens confirmation', function () {
    $department = Department::factory()->create();

    Livewire::test(DepartmentManager::class)
        ->call('askDelete', $department->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete');
});
