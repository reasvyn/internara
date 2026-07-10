<?php

declare(strict_types=1);

use App\Enrollment\Placement\Livewire\DirectPlacementManager;
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

test('renders the direct placement manager component', function () {
    Livewire::test(DirectPlacementManager::class)
        ->assertSuccessful();
});

test('validates student_id is required', function () {
    Livewire::test(DirectPlacementManager::class)
        ->set('form.student_id', '')
        ->set('form.placement_id', 'non-existent')
        ->set('form.academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['form.student_id']);
});

test('validates placement_id is required', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::test(DirectPlacementManager::class)
        ->set('form.student_id', $student->id)
        ->set('form.placement_id', '')
        ->set('form.academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['form.placement_id']);
});

test('validates academic_year is required', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $company = Company::factory()->create();
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create([
        'company_id' => $company->id,
        'internship_id' => $internship->id,
    ]);

    Livewire::test(DirectPlacementManager::class)
        ->set('form.student_id', $student->id)
        ->set('form.placement_id', $placement->id)
        ->set('form.academic_year', '')
        ->call('submit')
        ->assertHasErrors(['form.academic_year']);
});
