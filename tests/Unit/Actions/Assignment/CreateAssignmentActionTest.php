<?php

declare(strict_types=1);

use App\Actions\Assignment\CreateAssignmentAction;
use App\Models\Assignment;
use Database\Factories\AssignmentTypeFactory;
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a new assignment in draft status', function () {
        $type = AssignmentTypeFactory::new()->create();
        $internship = InternshipFactory::new()->create();

        $assignment = app(CreateAssignmentAction::class)->execute(
            assignmentTypeId: $type->id,
            internshipId: $internship->id,
            title: 'Test Assignment',
            description: 'A test assignment',
            academicYear: '2025/2026',
            isMandatory: true,
            dueDate: '2026-06-01',
            config: ['allow_file_upload' => true],
        );

        expect($assignment)->toBeInstanceOf(Assignment::class)
            ->and($assignment->title)->toBe('Test Assignment')
            ->and($assignment->status->value)->toBe('draft')
            ->and($assignment->assignment_type_id)->toBe($type->id)
            ->and($assignment->internship_id)->toBe($internship->id)
            ->and($assignment->description)->toBe('A test assignment')
            ->and($assignment->academic_year)->toBe('2025/2026')
            ->and($assignment->is_mandatory)->toBeTrue()
            ->and($assignment->config)->toBe(['allow_file_upload' => true]);
    });

    it('creates assignment with default values for optional fields', function () {
        $type = AssignmentTypeFactory::new()->create();
        $internship = InternshipFactory::new()->create();

        $assignment = app(CreateAssignmentAction::class)->execute(
            assignmentTypeId: $type->id,
            internshipId: $internship->id,
            title: 'Minimal Assignment',
        );

        expect($assignment->title)->toBe('Minimal Assignment')
            ->and($assignment->is_mandatory)->toBeFalse()
            ->and($assignment->description)->toBeNull()
            ->and($assignment->due_date)->toBeNull();
    });

    it('throws if assignment type does not exist', function () {
        $internship = InternshipFactory::new()->create();

        expect(fn () => app(CreateAssignmentAction::class)->execute(
            assignmentTypeId: 'non-existent',
            internshipId: $internship->id,
            title: 'Test',
        ))->toThrow(ModelNotFoundException::class);
    });
});
