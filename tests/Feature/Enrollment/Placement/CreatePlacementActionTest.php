<?php

declare(strict_types=1);

use App\Enrollment\Placement\Actions\CreatePlacementAction;
use App\Enrollment\Placement\Models\Placement;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates placement with valid data', function () {
    $company = Company::factory()->create();
    $internship = Internship::factory()->create();

    $placement = app(CreatePlacementAction::class)->execute([
        'company_id' => $company->id,
        'internship_id' => $internship->id,
        'name' => 'Backend Developer Intern',
        'address' => '123 Tech St',
        'quota' => 10,
        'description' => 'Great opportunity',
    ]);

    expect($placement)->toBeInstanceOf(Placement::class);
    $this->assertDatabaseHas('placements', ['id' => $placement->id]);
    expect($placement->filled_quota)->toBe(0);
    expect($placement->name)->toBe('Backend Developer Intern');
});

test('creates placement with default values', function () {
    $company = Company::factory()->create();
    $internship = Internship::factory()->create();

    $placement = app(CreatePlacementAction::class)->execute([
        'company_id' => $company->id,
        'internship_id' => $internship->id,
        'name' => 'Frontend Intern',
    ]);

    expect($placement->quota)->toBe(10);
    expect($placement->filled_quota)->toBe(0);
    expect($placement->address)->toBeNull();
});
