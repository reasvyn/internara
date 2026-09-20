<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Actions\GradeSubmissionAction;
use App\Modules\Assignment\Domain\Submission\Actions\RequestSubmissionRevisionAction;
use App\Modules\Assignment\Domain\Submission\Actions\SubmitAssignmentAction;
use App\Modules\Assignment\Domain\Submission\Actions\VerifySubmissionAction;
use App\Modules\Assignment\Domain\Submission\Data\GradeSubmissionData;
use App\Modules\Assignment\Domain\Submission\Data\SubmitAssignmentData;
use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Events\SubmissionRevisionRequested;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function submissionFixture(array $assignment = [], array $registration = []): array
{
    $student = User::factory()->create();
    $internship = Internship::factory()->create();
    $assignmentModel = Assignment::factory()->published()->create($assignment + ['internship_id' => $internship->id]);
    $registrationModel = Registration::factory()->active()->create($registration + [
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);

    return [$student, $assignmentModel, $registrationModel];
}

test('T657B-FR-SUBM-002: draft assignments cannot receive submissions', function (): void {
    [$student, $assignment] = submissionFixture();
    $assignment->update(['status' => 'draft']);

    expect(fn () => app(SubmitAssignmentAction::class)->execute($student, $assignment, new SubmitAssignmentData('A sufficiently long report.')))
        ->toThrow(RejectedException::class);
});

test('T657B-FR-SUBM-003: overdue assignments reject new submissions', function (): void {
    [$student, $assignment] = submissionFixture(['due_date' => now()->subMinute()]);

    expect(fn () => app(SubmitAssignmentAction::class)->execute($student, $assignment, new SubmitAssignmentData('A sufficiently long report.')))
        ->toThrow(RejectedException::class);
    expect(Submission::count())->toBe(0);
});

test('T657B-FR-SUBM-003: null deadlines remain open until closure', function (): void {
    [$student, $assignment] = submissionFixture(['due_date' => null]);

    $submission = app(SubmitAssignmentAction::class)->execute($student, $assignment, new SubmitAssignmentData('A sufficiently long report.'));

    expect($submission->status)->toBe(SubmissionStatus::SUBMITTED);
});

test('T657B-FR-SUBM-004: only active registrations can submit', function (): void {
    [$student, $assignment] = submissionFixture(['due_date' => null], ['status' => 'pending']);

    expect(fn () => app(SubmitAssignmentAction::class)->execute($student, $assignment, new SubmitAssignmentData('A sufficiently long report.')))
        ->toThrow(RejectedException::class);
});

test('T657B-FR-SUBM-005: a second submission is rejected', function (): void {
    [$student, $assignment] = submissionFixture();
    $action = app(SubmitAssignmentAction::class);
    $data = new SubmitAssignmentData('A sufficiently long report.');
    $action->execute($student, $assignment, $data);

    expect(fn () => $action->execute($student, $assignment, $data))
        ->toThrow(RejectedException::class);
});

test('T657B-UC-SUBM-001, T657B-FR-SUBM-006: submission stores content and a server timestamp', function (): void {
    [$student, $assignment, $registration] = submissionFixture();
    $before = now();

    $submission = app(SubmitAssignmentAction::class)->execute($student, $assignment, new SubmitAssignmentData('A sufficiently long report.'));

    expect($submission->registration_id)->toBe($registration->id)
        ->and($submission->content)->toBe('A sufficiently long report.')
        ->and($submission->submitted_at->greaterThanOrEqualTo($before->copy()->subSecond()))->toBeTrue();
});

test('T657B-UC-SUBM-002, T657B-FR-SUBM-007: revision required submissions reuse their row', function (): void {
    [$student, $assignment, $registration] = submissionFixture();
    $submission = Submission::factory()->create([
        'student_id' => $student->id,
        'assignment_id' => $assignment->id,
        'registration_id' => $registration->id,
        'status' => SubmissionStatus::REVISION_REQUIRED,
        'feedback' => 'Please expand the conclusion.',
    ]);

    $updated = app(SubmitAssignmentAction::class)->execute($student, $assignment, new SubmitAssignmentData('The revised report content.'));

    expect($updated->id)->toBe($submission->id)
        ->and($updated->status)->toBe(SubmissionStatus::SUBMITTED)
        ->and($updated->feedback)->toBeNull()
        ->and(Submission::count())->toBe(1);
});

test('T657C-FR-GRADE-003: grading writes the authenticated actor and timestamp', function (): void {
    $grader = User::factory()->create();
    $this->actingAs($grader);
    $submission = Submission::factory()->create(['status' => SubmissionStatus::SUBMITTED]);

    $response = app(GradeSubmissionAction::class)->execute($submission, new GradeSubmissionData(75, 'Good analysis.'));
    $submission->refresh();

    expect($response->success)->toBeTrue()
        ->and($submission->status)->toBe(SubmissionStatus::GRADED)
        ->and($submission->score)->toBe(75.0)
        ->and($submission->graded_by)->toBe($grader->id)
        ->and($submission->graded_at)->not->toBeNull();
});

test('T657C-FR-GRADE-002: scores outside the contract are rejected without mutation', function (): void {
    $submission = Submission::factory()->create(['score' => null, 'status' => SubmissionStatus::SUBMITTED]);

    expect(fn () => app(GradeSubmissionAction::class)->execute($submission, new GradeSubmissionData(101, 'Invalid score.')))
        ->toThrow(RejectedException::class);
    expect($submission->fresh()->score)->toBeNull();
});

test('T657C-FR-GRADE-002: zero and one hundred are valid scores', function (int $score): void {
    $grader = User::factory()->create();
    $this->actingAs($grader);
    $submission = Submission::factory()->create(['status' => SubmissionStatus::SUBMITTED]);

    app(GradeSubmissionAction::class)->execute($submission, new GradeSubmissionData($score, 'Recorded feedback.'));

    expect($submission->fresh()->score)->toBe((float) $score);
})->with([0, 100]);

test('T657C-FR-GRADE-007: revision requests update submitted records and emit an event', function (): void {
    Event::fake();
    $submission = Submission::factory()->create(['status' => SubmissionStatus::SUBMITTED]);

    $updated = app(RequestSubmissionRevisionAction::class)->execute($submission, 'Please add evidence and references.');

    expect($updated->status)->toBe(SubmissionStatus::REVISION_REQUIRED)
        ->and($updated->feedback)->toBe('Please add evidence and references.');
    Event::assertDispatched(SubmissionRevisionRequested::class);
});

test('T657C-FR-GRADE-007: revision requests reject non-submitted records', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::GRADED]);

    expect(fn () => app(RequestSubmissionRevisionAction::class)->execute($submission, 'Please revise this work.'))
        ->toThrow(RejectedException::class);
});

test('T657C-FR-GRADE-010: verification records the authenticated verifier', function (): void {
    $verifier = User::factory()->create();
    $this->actingAs($verifier);
    $submission = Submission::factory()->graded()->create();

    $verified = app(VerifySubmissionAction::class)->execute($submission);

    expect($verified->status)->toBe(SubmissionStatus::VERIFIED)
        ->and($verified->verified_by)->toBe($verifier->id)
        ->and($verified->verified_at)->not->toBeNull();
});
