<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Internship;
use App\Models\Placement;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $placement = Placement::factory()->create();

    expect($placement)->toBeInstanceOf(Placement::class)
        ->and($placement->id)->toBeUuid();
});

it('belongs to company', function () {
    $company = Company::factory()->create();
    $placement = Placement::factory()->create(['company_id' => $company->id]);

    expect($placement->company)->toBeInstanceOf(Company::class)
        ->and($placement->company->id)->toBe($company->id);
});

it('belongs to internship', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);

    expect($placement->internship)->toBeInstanceOf(Internship::class)
        ->and($placement->internship->id)->toBe($internship->id);
});

it('has many registrations', function () {
    $placement = Placement::factory()->create();
    Registration::factory()->count(2)->create(['placement_id' => $placement->id]);

    expect($placement->registrations)->toHaveCount(2)
        ->and($placement->registrations->first())->toBeInstanceOf(Registration::class);
});

it('delegates capacity checks to entity', function () {
    $placement = Placement::factory()->create([
        'quota' => 10,
        'filled_quota' => 5,
    ]);

    expect($placement->asPlacementCapacity()->isFull())->toBeFalse()
        ->and($placement->asPlacementCapacity()->availableSlots())->toBe(5)
        ->and($placement->asPlacementCapacity()->hasAvailableSlots())->toBeTrue();

    $placement->update(['filled_quota' => 10]);
    expect($placement->asPlacementCapacity()->isFull())->toBeTrue()
        ->and($placement->asPlacementCapacity()->availableSlots())->toBe(0)
        ->and($placement->asPlacementCapacity()->hasAvailableSlots())->toBeFalse();
});
