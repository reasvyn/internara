<?php

declare(strict_types=1);

namespace Tests\Feature\Department;

use App\Livewire\Admin\Department\DepartmentIndex;
use App\Models\Department;
use App\Models\Profile;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->school = School::factory()->create();
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'admin']);
});

test('department index page is accessible for authenticated admin user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Department::factory()->count(3)->create(['school_id' => $this->school->id]);

    $this->actingAs($user)
        ->get(route('admin.departments'))
        ->assertOk()
        ->assertSee(__('department.title'));
});

test('admin can create a new department', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->set('formData.name', 'Teknik Informatika')
        ->set('formData.description', 'Computer engineering program')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('departments', [
        'name' => 'Teknik Informatika',
        'description' => 'Computer engineering program',
        'school_id' => $this->school->id,
    ]);
});

test('admin can edit an existing department', function () {
    $department = Department::factory()->create([
        'school_id' => $this->school->id,
        'name' => 'Old Name',
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('edit', $department)
        ->assertSet('formData.name', 'Old Name')
        ->assertSet('showModal', true)
        ->set('formData.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $department->refresh();
    expect($department->name)->toBe('New Name');
});

test('admin cannot delete department with student profiles', function () {
    $department = Department::factory()->create(['school_id' => $this->school->id]);
    Profile::factory()->create(['department_id' => $department->id]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('delete', $department);

    $this->assertDatabaseHas('departments', ['id' => $department->id]);
});

test('admin can delete department without student profiles', function () {
    $department = Department::factory()->create(['school_id' => $this->school->id]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('delete', $department);

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});

test('department name must be unique', function () {
    Department::factory()->create([
        'school_id' => $this->school->id,
        'name' => 'Existing Department',
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('create')
        ->set('formData.name', 'Existing Department')
        ->call('save')
        ->assertHasErrors(['formData.name' => 'unique']);
});

test('department index shows stats', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Department::factory()->count(2)->create(['school_id' => $this->school->id]);

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->assertOk();
});

test('department search filters by name', function () {
    Department::factory()->create(['school_id' => $this->school->id, 'name' => 'Teknik Informatika']);
    Department::factory()->create(['school_id' => $this->school->id, 'name' => 'Akuntansi']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->set('search', 'Teknik')
        ->assertSee('Teknik Informatika')
        ->assertDontSee('Akuntansi');
});

test('unauthenticated user cannot access department index', function () {
    $this->get(route('admin.departments'))
        ->assertRedirect(route('login'));
});
