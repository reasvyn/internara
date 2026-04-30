<?php

declare(strict_types=1);

namespace Tests\Feature\Placement;

use App\Models\Internship;
use App\Models\InternshipCompany;
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

test('placement index page is accessible for authenticated admin user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    InternshipPlacement::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('admin.internships.placements'))
        ->assertOk()
        ->assertSee(__('placement.title'));
});

test('admin can create a new placement', function () {
    $company = InternshipCompany::factory()->create();
    $internship = Internship::factory()->create(['status' => 'active']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->set('company_id', $company->id)
        ->set('internship_id', $internship->id)
        ->set('name', 'Frontend Developer Intern')
        ->set('quota', 10)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('internship_placements', [
        'name' => 'Frontend Developer Intern',
        'quota' => 10,
    ]);
});

test('admin can edit an existing placement', function () {
    $placement = InternshipPlacement::factory()->create(['name' => 'Old Position']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->call('edit', $placement)
        ->assertSet('name', 'Old Position')
        ->assertSet('showModal', true)
        ->set('name', 'New Position')
        ->call('save')
        ->assertHasNoErrors();

    $placement->refresh();
    expect($placement->name)->toBe('New Position');
});

test('admin cannot delete placement with registered students', function () {
    $placement = InternshipPlacement::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('student');

    InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'placement_id' => $placement->id,
        'internship_id' => $placement->internship_id,
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->call('delete', $placement);

    $this->assertDatabaseHas('internship_placements', ['id' => $placement->id]);
});

test('admin can delete placement without registrations', function () {
    $placement = InternshipPlacement::factory()->create();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->call('delete', $placement);

    $this->assertDatabaseMissing('internship_placements', ['id' => $placement->id]);
});

test('placement requires company and internship', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->call('create')
        ->set('company_id', '')
        ->set('internship_id', '')
        ->set('name', 'Test Position')
        ->set('quota', 5)
        ->call('save')
        ->assertHasErrors(['company_id' => 'required', 'internship_id' => 'required']);
});

test('placement quota must be positive integer', function () {
    $company = InternshipCompany::factory()->create();
    $internship = Internship::factory()->create(['status' => 'active']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->call('create')
        ->set('company_id', $company->id)
        ->set('internship_id', $internship->id)
        ->set('name', 'Test Position')
        ->set('quota', 0)
        ->call('save')
        ->assertHasErrors(['quota' => 'min']);
});

test('placement index shows stats', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    InternshipPlacement::factory()->count(2)->create(['quota' => 10]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->assertViewHas('placements');
});

test('placement search filters by name and company', function () {
    $company = InternshipCompany::factory()->create(['name' => 'Tech Corp']);
    InternshipPlacement::factory()->create(['name' => 'Developer Intern', 'company_id' => $company->id]);
    InternshipPlacement::factory()->create(['name' => 'Accountant Intern', 'company_id' => $company->id]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->set('search', 'Developer')
        ->assertSee('Developer Intern')
        ->assertDontSee('Accountant Intern');
});

test('unauthenticated user cannot access placement index', function () {
    $this->get(route('admin.internships.placements'))
        ->assertRedirect(route('login'));
});

test('placement companies computed returns ordered list', function () {
    InternshipCompany::factory()->create(['name' => 'Zebra Corp']);
    InternshipCompany::factory()->create(['name' => 'Alpha Corp']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Internship\PlacementIndex::class)
        ->assertViewHas('placements');
});
