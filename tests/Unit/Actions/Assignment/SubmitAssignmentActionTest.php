<?php

declare(strict_types=1);

use App\Actions\Assignment\SubmitAssignmentAction;
use App\Models\Submission;
use Database\Factories\AssignmentFactory;
use Database\Factories\RegistrationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('submits an assignment successfully', function () {
        $assignment = AssignmentFactory::new()->published()->create();
        $registration = RegistrationFactory::new()->create();
        $student = UserFactory::new()->create();

        $submission = app(SubmitAssignmentAction::class)->execute(
            $assignment,
            registrationId: $registration->id,
            studentId: $student->id,
            content: 'My submission content',
        );

        expect($submission)->toBeInstanceOf(Submission::class)
            ->and($submission->assignment_id)->toBe($assignment->id)
            ->and($submission->student_id)->toBe($student->id)
            ->and($submission->registration_id)->toBe($registration->id)
            ->and($submission->content)->toBe('My submission content')
            ->and($submission->status->value)->toBe('submitted');
    });

    it('throws if assignment is not published', function () {
        $assignment = AssignmentFactory::new()->create(['status' => 'draft']);

        expect(fn () => app(SubmitAssignmentAction::class)->execute(
            $assignment,
            registrationId: 'reg-1',
            studentId: 'student-1',
        ))->toThrow(InvalidArgumentException::class, 'Cannot submit to unpublished assignment');
    });

    it('throws on duplicate submission', function () {
        $assignment = AssignmentFactory::new()->published()->create();
        $registration = RegistrationFactory::new()->create();
        $student = UserFactory::new()->create();

        app(SubmitAssignmentAction::class)->execute(
            $assignment,
            registrationId: $registration->id,
            studentId: $student->id,
        );

        expect(fn () => app(SubmitAssignmentAction::class)->execute(
            $assignment,
            registrationId: $registration->id,
            studentId: $student->id,
        ))->toThrow(RuntimeException::class, 'already submitted');
    });
});
