<?php

declare(strict_types=1);

use App\Assignment\Actions\CreateAssignmentAction;
use App\Assignment\Models\Assignment;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates assignment with valid data', function () {
    $internship = Internship::factory()->create();

    $assignment = app(CreateAssignmentAction::class)->execute(
        assignmentType: 'report',
        internshipId: $internship->id,
        title: 'Laporan Magang',
    );

    expect($assignment)->toBeInstanceOf(Assignment::class);
    expect($assignment->title)->toBe('Laporan Magang');
    expect($assignment->status->value)->toBe('draft');
});

test('creates assignment with all optional fields', function () {
    $internship = Internship::factory()->create();

    $assignment = app(CreateAssignmentAction::class)->execute(
        assignmentType: 'report',
        internshipId: $internship->id,
        title: 'Jurnal Harian',
        description: 'Daily journal entry',
        isMandatory: true,
        dueDate: now()->addMonth()->toDateString(),
    );

    expect($assignment->is_mandatory)->toBeTrue();
});
