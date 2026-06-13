<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Entities\InternshipPeriod;
use App\Program\Internship\Entities\InternshipState;
use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Models\Internship;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('can create internship using factory', function () {
    $internship = Internship::factory()->create();

    expect($internship)->toBeInstanceOf(Internship::class);
    $this->assertDatabaseHas('internships', ['id' => $internship->id]);
});

test('belongs to academic year', function () {
    $year = AcademicYear::factory()->create();
    $internship = Internship::factory()->create(['academic_year_id' => $year->id]);

    expect($internship->academicYear)->toBeInstanceOf(AcademicYear::class);
    expect((string) $internship->academicYear->id)->toBe((string) $year->id);
});

test('has many placements', function () {
    $internship = Internship::factory()->create();
    $placements = Placement::factory()
        ->count(2)
        ->create(['internship_id' => $internship->id]);

    expect($internship->placements)->toHaveCount(2);
});

test('has many registrations', function () {
    $internship = Internship::factory()->create();
    $registrations = Registration::factory()
        ->count(2)
        ->create(['internship_id' => $internship->id]);

    expect($internship->registrations)->toHaveCount(2);
});

test('casts status to InternshipStatus enum', function () {
    $internship = Internship::factory()->create(['status' => 'draft']);

    expect($internship->status)->toBeInstanceOf(InternshipStatus::class);
    expect($internship->status->value)->toBe('draft');
});

test('casts dates to carbon instances', function () {
    $internship = Internship::factory()->create();

    expect($internship->start_date)->toBeInstanceOf(Carbon::class);
    expect($internship->end_date)->toBeInstanceOf(Carbon::class);
});

test('as internship state returns state entity', function () {
    $internship = Internship::factory()->create();

    expect($internship->asInternshipState())
        ->toBeInstanceOf(InternshipState::class);
});

test('as internship period returns period entity', function () {
    $internship = Internship::factory()->create();

    expect($internship->asInternshipPeriod())
        ->toBeInstanceOf(InternshipPeriod::class);
});

test('uses uuid as primary key', function () {
    $internship = Internship::factory()->create();

    expect($internship->id)->toBeUuid();
});
