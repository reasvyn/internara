<?php

declare(strict_types=1);

use App\Domain\Assignment\Actions\CreateAssignmentAction;
use App\Domain\Assignment\Actions\DeleteAssignmentAction;
use App\Domain\Assignment\Actions\GradeSubmissionAction;
use App\Domain\Assignment\Actions\PublishAssignmentAction;
use App\Domain\Assignment\Actions\SubmitAssignmentAction;
use App\Domain\Assignment\Actions\UpdateAssignmentAction;
use App\Domain\Assignment\Actions\VerifySubmissionAction;
use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\AssignmentType;
use App\Domain\Assignment\Models\Submission;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Internship\Models\Internship;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('CreateAssignmentAction', function () {
    it('creates an assignment', function () {
        $type = AssignmentType::factory()->create();
        $internship = Internship::factory()->create();

        $assignment = app(CreateAssignmentAction::class)->execute(
            assignmentTypeId: $type->id,
            internshipId: $internship->id,
            title: 'Test Assignment',
            description: 'Test description',
            isMandatory: true,
        );

        expect($assignment)->toBeInstanceOf(Assignment::class)
            ->and($assignment->title)->toBe('Test Assignment')
            ->and($assignment->status->value)->toBe('draft');
    });
});

describe('UpdateAssignmentAction', function () {
    it('updates an assignment', function () {
        $assignment = Assignment::factory()->create();

        $updated = app(UpdateAssignmentAction::class)->execute(
            $assignment,
            title: 'Updated Title',
            isMandatory: true,
        );

        expect($updated->title)->toBe('Updated Title')
            ->and($updated->is_mandatory)->toBeTrue();
    });
});

describe('DeleteAssignmentAction', function () {
    it('deletes an assignment', function () {
        $assignment = Assignment::factory()->create();

        app(DeleteAssignmentAction::class)->execute($assignment);

        expect(Assignment::find($assignment->id))->toBeNull();
    });
});

describe('PublishAssignmentAction', function () {
    it('publishes a draft assignment', function () {
        $assignment = Assignment::factory()->create(['status' => 'draft']);

        $published = app(PublishAssignmentAction::class)->execute($assignment);

        expect($published->status->value)->toBe('published');
    });

    it('throws when publishing non-draft assignment', function () {
        $assignment = Assignment::factory()->published()->create();

        app(PublishAssignmentAction::class)->execute($assignment);
    })->throws(RejectedException::class);
});

describe('SubmitAssignmentAction', function () {
    it('throws for unpublished assignment', function () {
        $student = User::factory()->create();
        $assignment = Assignment::factory()->create(['status' => 'draft']);

        app(SubmitAssignmentAction::class)->execute($student, $assignment, ['content' => 'test']);
    })->throws(RejectedException::class);
});

describe('VerifySubmissionAction', function () {
    it('verifies a submission', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $assignment = Assignment::factory()->create();
        $submissionId = (string) Str::uuid();
        DB::table('submissions')->insert([
            'id' => $submissionId,
            'assignment_id' => $assignment->id,
            'registration_id' => Registration::factory()->create()->id,
            'student_id' => User::factory()->create()->id,
            'content' => 'Test submission',
            'status' => 'submitted',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $submission = Submission::find($submissionId);

        $verified = app(VerifySubmissionAction::class)->execute($submission);

        expect($verified->status->value)->toBe('verified')
            ->and($verified->verified_by)->toBe($admin->id);
    });
});

describe('GradeSubmissionAction', function () {
    it('grades a submission', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $assignment = Assignment::factory()->create();
        $submissionId = (string) Str::uuid();
        DB::table('submissions')->insert([
            'id' => $submissionId,
            'assignment_id' => $assignment->id,
            'registration_id' => Registration::factory()->create()->id,
            'student_id' => User::factory()->create()->id,
            'content' => 'Test submission',
            'status' => 'submitted',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $submission = Submission::find($submissionId);

        $graded = app(GradeSubmissionAction::class)->execute($submission, 85, 'Good work!');

        expect($graded->score)->toBe(85)
            ->and($graded->feedback)->toBe('Good work!')
            ->and($graded->status->value)->toBe('graded');
    });

    it('throws for invalid score', function () {
        $assignment = Assignment::factory()->create();
        $submissionId = (string) Str::uuid();
        DB::table('submissions')->insert([
            'id' => $submissionId,
            'assignment_id' => $assignment->id,
            'registration_id' => Registration::factory()->create()->id,
            'student_id' => User::factory()->create()->id,
            'content' => 'Test',
            'status' => 'submitted',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $submission = Submission::find($submissionId);

        app(GradeSubmissionAction::class)->execute($submission, 150);
    })->throws(RejectedException::class);
});
