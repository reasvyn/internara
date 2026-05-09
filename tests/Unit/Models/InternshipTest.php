<?php

declare(strict_types=1);

use App\Enums\Internship\InternshipStatus;
use App\Models\AcademicYear;
use App\Models\Internship;
use App\Models\Placement;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $internship = Internship::factory()->create();

    expect($internship)->toBeInstanceOf(Internship::class)
        ->and($internship->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $internship = Internship::factory()->create([
        'start_date' => '2025-09-01',
        'end_date' => '2026-06-30',
        'status' => InternshipStatus::PUBLISHED,
    ]);

    expect($internship->start_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($internship->start_date->format('Y-m-d'))->toBe('2025-09-01')
        ->and($internship->end_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($internship->end_date->format('Y-m-d'))->toBe('2026-06-30')
        ->and($internship->status)->toBe(InternshipStatus::PUBLISHED);
});

it('belongs to academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    $internship = Internship::factory()->create(['academic_year_id' => $academicYear->id]);

    expect($internship->academicYear)->toBeInstanceOf(AcademicYear::class)
        ->and($internship->academicYear->id)->toBe($academicYear->id);
});

it('has many placements', function () {
    $internship = Internship::factory()->create();
    Placement::factory()->count(2)->create(['internship_id' => $internship->id]);

    expect($internship->placements)->toHaveCount(2)
        ->and($internship->placements->first())->toBeInstanceOf(Placement::class);
});

it('has many registrations', function () {
    $internship = Internship::factory()->create();
    Registration::factory()->count(2)->create(['internship_id' => $internship->id]);

    expect($internship->registrations)->toHaveCount(2)
        ->and($internship->registrations->first())->toBeInstanceOf(Registration::class);
});

it('delegates isAcceptingRegistrations to entity', function () {
    $internship = Internship::factory()->create(['status' => InternshipStatus::PUBLISHED]);
    expect($internship->asInternshipPeriod()->isAcceptingRegistrations())->toBeTrue();

    $internship->update(['status' => InternshipStatus::DRAFT]);
    expect($internship->asInternshipPeriod()->isAcceptingRegistrations())->toBeFalse();
});
