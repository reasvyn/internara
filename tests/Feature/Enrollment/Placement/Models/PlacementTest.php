<?php

declare(strict_types=1);

use App\Enrollment\Placement\Entities\PlacementCapacity;
use App\Enrollment\Placement\Entities\PlacementState;
use App\Enrollment\Placement\Models\Placement;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('placement has fillable attributes', function () {
    $placement = new Placement;

    expect($placement->getFillable())->toContain('company_id', 'internship_id', 'name', 'address', 'quota', 'filled_quota', 'description');
});

test('placement belongs to company', function () {
    $company = Company::factory()->create();
    $placement = Placement::factory()->create(['company_id' => $company->id]);

    expect($placement->company)->toBeInstanceOf(Company::class);
});

test('placement belongs to internship', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);

    expect($placement->internship)->toBeInstanceOf(Internship::class);
});

test('placement default quota values', function () {
    $placement = Placement::factory()->create();

    expect($placement->quota)->toBeGreaterThan(0);
    expect($placement->filled_quota)->toBe(0);
});

test('placement returns capacity entity', function () {
    $placement = Placement::factory()->create(['quota' => 10, 'filled_quota' => 3]);

    $capacity = $placement->asPlacementCapacity();

    expect($capacity)->toBeInstanceOf(PlacementCapacity::class);
    expect($capacity->isFull())->toBeFalse();
    expect($capacity->availableSlots())->toBe(7);
});

test('placement returns state entity', function () {
    $placement = Placement::factory()->create();

    $state = $placement->asPlacementState();

    expect($state)->toBeInstanceOf(PlacementState::class);
});
