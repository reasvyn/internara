<?php

declare(strict_types=1);

use App\Enrollment\Placement\Livewire\PlacementIndex;
use App\Enrollment\Placement\Models\Placement;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the placement index component', function () {
    Livewire::test(PlacementIndex::class)
        ->assertSuccessful();
});

test('shows create modal', function () {
    Livewire::test(PlacementIndex::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('opens edit modal with placement data', function () {
    $company = Company::factory()->create();
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create([
        'company_id' => $company->id,
        'internship_id' => $internship->id,
    ]);

    Livewire::test(PlacementIndex::class)
        ->call('edit', $placement->id)
        ->assertSet('showModal', true)
        ->assertSet('form.name', $placement->name);
});

test('ask delete opens confirmation', function () {
    $company = Company::factory()->create();
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create([
        'company_id' => $company->id,
        'internship_id' => $internship->id,
    ]);

    Livewire::test(PlacementIndex::class)
        ->call('askDelete', $placement->id)
        ->assertSet('showConfirm', true);
});

test('validates form fields on save', function () {
    Livewire::test(PlacementIndex::class)
        ->set('form.company_id', '')
        ->set('form.internship_id', '')
        ->set('form.name', '')
        ->set('form.quota', '')
        ->call('save')
        ->assertHasErrors(['form.company_id', 'form.internship_id', 'form.name', 'form.quota']);
});

test('displays stats from computed property', function () {
    $companies = Company::factory()->count(3)->create();
    $internships = Internship::factory()->count(3)->create();
    foreach ($companies as $i => $company) {
        Placement::factory()->create([
            'company_id' => $company->id,
            'internship_id' => $internships[$i]->id,
        ]);
    }

    Livewire::test(PlacementIndex::class)
        ->assertSet('stats.total', 3);
});
