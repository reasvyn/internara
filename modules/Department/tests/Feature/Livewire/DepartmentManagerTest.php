<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Department\Livewire\DepartmentManager;
use Modules\Department\Models\Department;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);
});

test('department management page is forbidden for unauthorized users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('department.index'))->assertForbidden();
});

test('department management page is accessible by authorized users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('department.view');
    $this->actingAs($user);

    $this->get(route('department.index'))->assertOk()->assertSee('Jurusan');
});

test('it can create a new department', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['department.view', 'department.create']);
    $this->actingAs($user);

    Livewire::test(DepartmentManager::class)
        ->call('add')
        ->set('form.name', 'Teknik Informatika')
        ->set('form.description', 'Jurusan IT')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formModal', false);

    $this->assertDatabaseHas('departments', [
        'name' => 'Teknik Informatika',
        'description' => 'Jurusan IT',
    ]);
});

test('it can update an existing department', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['department.view', 'department.update']);
    $this->actingAs($user);

    $department = Department::factory()->create(['name' => 'Old Name']);

    Livewire::test(DepartmentManager::class)
        ->call('edit', $department->id)
        ->set('form.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($department->refresh()->name)->toBe('New Name');
});

test('it can delete a department', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['department.view', 'department.delete']);
    $this->actingAs($user);

    $department = Department::factory()->create();

    Livewire::test(DepartmentManager::class)
        ->call('discard', $department->id)
        ->call('remove', $department->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});
