<?php

declare(strict_types=1);

namespace Tests\Feature\Internship;

use App\Enums\InternshipStatus;
use App\Models\Internship;
use App\Models\InternshipPlacement;
use App\Models\InternshipRegistration;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'student']);
});

test('internship index page is accessible for authenticated admin user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Internship::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('admin.internships'))
        ->assertOk()
        ->assertSee(__('internship.title'));
});

test('admin can create a new internship batch', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->set('name', 'PKL Semester Ganjil 2026/2027')
        ->set('start_date', '2026-07-01')
        ->set('end_date', '2026-12-31')
        ->set('status', InternshipStatus::DRAFT->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('internships', [
        'name' => 'PKL Semester Ganjil 2026/2027',
        'status' => InternshipStatus::DRAFT->value,
    ]);
});

test('admin can edit an existing internship batch', function () {
    $internship = Internship::factory()->create([
        'name' => 'Old Batch',
        'status' => InternshipStatus::DRAFT->value,
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('edit', $internship)
        ->assertSet('name', 'Old Batch')
        ->assertSet('showModal', true)
        ->set('name', 'New Batch')
        ->call('save')
        ->assertHasNoErrors();

    $internship->refresh();
    expect($internship->name)->toBe('New Batch');
});

test('admin cannot delete internship with placements', function () {
    $internship = Internship::factory()->create();
    InternshipPlacement::factory()->create(['internship_id' => $internship->id]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('delete', $internship);

    $this->assertDatabaseHas('internships', ['id' => $internship->id]);
});

test('admin cannot delete internship with registrations', function () {
    $internship = Internship::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('student');

    InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('delete', $internship);

    $this->assertDatabaseHas('internships', ['id' => $internship->id]);
});

test('admin can delete internship without placements or registrations', function () {
    $internship = Internship::factory()->create();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('delete', $internship);

    $this->assertDatabaseMissing('internships', ['id' => $internship->id]);
});

test('internship name must be unique', function () {
    Internship::factory()->create(['name' => 'Existing Batch']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('create')
        ->set('name', 'Existing Batch')
        ->set('start_date', '2026-07-01')
        ->set('end_date', '2026-12-31')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

test('end date must be after start date', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->call('create')
        ->set('name', 'Test Batch')
        ->set('start_date', '2026-12-31')
        ->set('end_date', '2026-07-01')
        ->call('save')
        ->assertHasErrors(['end_date' => 'after']);
});

test('internship index shows stats', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Internship::factory()->count(2)->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->assertViewHas('internships');
});

test('internship search filters by name', function () {
    Internship::factory()->create(['name' => 'PKL Ganjil']);
    Internship::factory()->create(['name' => 'PKL Genap']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->set('search', 'Ganjil')
        ->assertSee('PKL Ganjil')
        ->assertDontSee('PKL Genap');
});

test('unauthenticated user cannot access internship index', function () {
    $this->get(route('admin.internships'))
        ->assertRedirect(route('login'));
});

test('internship status options are available', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\InternshipIndex::class)
        ->assertSet('status', InternshipStatus::DRAFT->value);
});
