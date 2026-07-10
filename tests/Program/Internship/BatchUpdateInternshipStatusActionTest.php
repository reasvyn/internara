<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Program\Internship\Actions\BatchUpdateInternshipStatusAction;
use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates status for all internships matching query', function () {
    $year = AcademicYear::factory()->create();
    Internship::factory()->count(3)->create([
        'academic_year_id' => $year->id,
        'status' => InternshipStatus::DRAFT,
    ]);

    $action = app(BatchUpdateInternshipStatusAction::class);
    $query = Internship::where('academic_year_id', $year->id);

    $updated = $action->execute($query, InternshipStatus::PUBLISHED);

    expect($updated)->toBe(3);
    expect(Internship::where('status', InternshipStatus::PUBLISHED)->count())->toBe(3);
});

test('updates status for filtered subset', function () {
    Internship::factory()->count(2)->create(['status' => InternshipStatus::DRAFT]);
    Internship::factory()->create(['status' => InternshipStatus::PUBLISHED]);

    $action = app(BatchUpdateInternshipStatusAction::class);
    $query = Internship::where('status', InternshipStatus::DRAFT);

    $updated = $action->execute($query, InternshipStatus::CANCELLED);

    expect($updated)->toBe(2);
    expect(Internship::where('status', InternshipStatus::CANCELLED)->count())->toBe(2);
});

test('returns zero when no internships match', function () {
    $action = app(BatchUpdateInternshipStatusAction::class);
    $query = Internship::where('id', '00000000-0000-0000-0000-000000000000');

    $updated = $action->execute($query, InternshipStatus::ACTIVE);

    expect($updated)->toBe(0);
});
