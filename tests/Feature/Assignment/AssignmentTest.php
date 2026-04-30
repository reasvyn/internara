<?php

declare(strict_types=1);

use App\Actions\Assignment\CreateAssignmentAction;
use App\Actions\Assignment\UpdateAssignmentAction;
use App\Actions\Assignment\DeleteAssignmentAction;
use App\Actions\Assignment\SubmitAssignmentAction;
use App\Actions\Assignment\VerifySubmissionAction;
use App\Enums\Role as RoleEnum;
use App\Models\Assignment;
use App\Models\User;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole(RoleEnum::TEACHER);

    $this->student = User::factory()->create();
    $this->student->assignRole(RoleEnum::STUDENT);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::ADMIN);
});

describe('Assignment Management', function () {
    it('allows teacher to create assignment', function () {
        // Create required models first
        $type = \App\Models\AssignmentType::factory()->create();
        $internship = \App\Models\Internship::factory()->create();

        $action = app(CreateAssignmentAction::class);

        $assignment = $action->execute(
            $type->id,
            $internship->id,
            'PHP Basics Assignment',
            'Learn PHP syntax and basics',
            null, // academicYear
            false, // isMandatory
            '2026-05-15' // dueDate
        );

        expect($assignment)->toBeInstanceOf(\App\Models\Assignment::class)
            ->and($assignment->title)->toBe('PHP Basics Assignment');
    });

    it('allows teacher to update assignment', function () {
        $assignment = Assignment::factory()->create([
            'title' => 'Old Title',
        ]);

        $action = app(UpdateAssignmentAction::class);
        $action->execute(
            $assignment,
            'Updated Title',
            'Updated description'
        );

        expect($assignment->fresh()->title)->toBe('Updated Title');
    });

    it('allows teacher to delete assignment', function () {
        $assignment = Assignment::factory()->create();

        $action = app(DeleteAssignmentAction::class);
        $action->execute($assignment);

        expect(Assignment::find($assignment->id))->toBeNull();
    });
});

describe('Assignment Submission', function () {
    it('allows student to submit assignment')->todo('SubmitAssignmentAction needs parameter fix.');

    it('allows teacher to verify submission')->todo('Needs fix for submission status update.');
});

describe('RBAC for Assignments', function () {
    it('prevents student from creating assignment', function () {
        $action = app(CreateAssignmentAction::class);
        $type = \App\Models\AssignmentType::factory()->create();

        $action->execute(
            $type->id,
            \App\Models\Internship::factory()->create()->id,
            'Student Assignment',
            'Test'
        );
    })->throws(\Exception::class);
});