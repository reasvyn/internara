<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Program\Internship\Actions\CreateInternshipAction;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates internship with valid data', function () {
    $year = AcademicYear::factory()->create();

    $action = app(CreateInternshipAction::class);
    $internship = $action->execute([
        'name' => 'PKL 2025',
        'academic_year_id' => $year->id,
        'start_date' => '2025-07-01',
        'end_date' => '2025-12-31',
    ]);

    expect($internship)->toBeInstanceOf(Internship::class);
    $this->assertDatabaseHas('internships', ['name' => 'PKL 2025']);
    expect($internship->status)->toBeNull();
});

test('creates internship with explicit status', function () {
    $year = AcademicYear::factory()->create();
    $action = app(CreateInternshipAction::class);

    $internship = $action->execute([
        'name' => 'PKL 2026',
        'academic_year_id' => $year->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'status' => 'draft',
    ]);

    expect($internship->status->value)->toBe('draft');
});
