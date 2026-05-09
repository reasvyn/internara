<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $academicYear = AcademicYear::factory()->create();

    expect($academicYear)->toBeInstanceOf(AcademicYear::class)
        ->and($academicYear->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $academicYear = AcademicYear::factory()->create([
        'start_date' => '2025-09-01',
        'end_date' => '2026-06-30',
        'is_active' => true,
    ]);

    expect($academicYear->start_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($academicYear->start_date->format('Y-m-d'))->toBe('2025-09-01')
        ->and($academicYear->end_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($academicYear->end_date->format('Y-m-d'))->toBe('2026-06-30')
        ->and($academicYear->is_active)->toBeTrue();
});

it('delegates isActive to entity', function () {
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    expect($academicYear->asAcademicYearState()->isActive())->toBeTrue();

    $academicYear->update(['is_active' => false]);
    expect($academicYear->asAcademicYearState()->isActive())->toBeFalse();
});
