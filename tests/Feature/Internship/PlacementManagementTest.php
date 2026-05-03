<?php

declare(strict_types=1);

namespace Tests\Feature\Internship;

use App\Domain\Internship\Models\Company;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Placement;
use App\Domain\Internship\Models\Registration;
use App\Domain\User\Models\User;
use App\Livewire\Internship\PlacementIndex;
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

    Placement::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('admin.internships.placements'))
        ->assertOk()
        ->assertSee(__('placement.title'));
});

test('admin can create a new placement', function () {
    $company = Company::factory()->create();
    $internship = Internship::factory()->create(['status' => 'active']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(PlacementIndex::class)
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
    $placement = Placement::factory()->create(['name' => 'Old Position']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(PlacementIndex::class)
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
    $placement = Placement::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('student');

    Registration::factory()->create([
        'student_id' => $student->id,
        'placement_id' => $placement->id,
        'internship_id' => $placement->internship_id,
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)->test(PlacementIndex::class)->call('delete', $placement);

    $this->assertDatabaseHas('internship_placements', ['id' => $placement->id]);
});

test('admin can delete placement without registrations', function () {
    $placement = Placement::factory()->create();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)->test(PlacementIndex::class)->call('delete', $placement);

    $this->assertDatabaseMissing('internship_placements', ['id' => $placement->id]);
});

test('placement requires company and internship', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(PlacementIndex::class)
        ->call('create')
        ->set('company_id', '')
        ->set('internship_id', '')
        ->set('name', 'Test Position')
        ->set('quota', 5)
        ->call('save')
        ->assertHasErrors(['company_id' => 'required', 'internship_id' => 'required']);
});

test('placement quota must be positive integer', function () {
    $company = Company::factory()->create();
    $internship = Internship::factory()->create(['status' => 'active']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(PlacementIndex::class)
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

    Placement::factory()
        ->count(2)
        ->create(['quota' => 10]);

    Livewire::actingAs($user)->test(PlacementIndex::class)->assertViewHas('placements');
});

test('placement search filters by name and company', function () {
    $company = Company::factory()->create(['name' => 'Tech Corp']);
    Placement::factory()->create([
        'name' => 'Developer Intern',
        'company_id' => $company->id,
    ]);
    Placement::factory()->create([
        'name' => 'Accountant Intern',
        'company_id' => $company->id,
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(PlacementIndex::class)
        ->set('search', 'Developer')
        ->assertSee('Developer Intern')
        ->assertDontSee('Accountant Intern');
});

test('unauthenticated user cannot access placement index', function () {
    $this->get(route('admin.internships.placements'))->assertRedirect(route('login'));
});

test('placement companies computed returns ordered list', function () {
    Company::factory()->create(['name' => 'Zebra Corp']);
    Company::factory()->create(['name' => 'Alpha Corp']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)->test(PlacementIndex::class)->assertViewHas('placements');
});
