<?php

declare(strict_types=1);

use App\Assignment\Actions\CreateAssignmentAction;
use App\Assignment\Models\Assignment;
use App\Assignment\Models\AssignmentType;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates assignment with valid data', function () {
    $type = AssignmentType::factory()->create();
    $internship = Internship::factory()->create();

    $assignment = app(CreateAssignmentAction::class)->execute(
        assignmentTypeId: $type->id,
        internshipId: $internship->id,
        title: 'Laporan Magang',
    );

    expect($assignment)->toBeInstanceOf(Assignment::class);
    expect($assignment->title)->toBe('Laporan Magang');
    expect($assignment->status->value)->toBe('draft');
});

test('creates assignment with all optional fields', function () {
    $type = AssignmentType::factory()->create();
    $internship = Internship::factory()->create();

    $assignment = app(CreateAssignmentAction::class)->execute(
        assignmentTypeId: $type->id,
        internshipId: $internship->id,
        title: 'Jurnal Harian',
        description: 'Daily journal entry',
        academicYear: '2025/2026',
        isMandatory: true,
        dueDate: now()->addMonth()->toDateString(),
        config: ['max_entries' => 30],
    );

    expect($assignment->is_mandatory)->toBeTrue();
    expect($assignment->config)->toBe(['max_entries' => 30]);
});
