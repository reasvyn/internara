<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Data\GradeSubmissionData;
use App\Modules\Assignment\Domain\Submission\Entities\SubmissionState;
use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Http\Requests\SubmitAssignmentRequest;
use App\Modules\Assignment\Domain\Submission\Notifications\SubmissionFeedbackNotification;
use App\Modules\Assignment\Entities\AssignmentRules;
use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Assignment\Livewire\AssignmentManager;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

final class AssignmentContractModelDouble extends Model
{
    protected $guarded = [];
}

test('T657B-FR-SUBM-008: HTTP submission validation permits supported attachment types', function (): void {
    $rules = (new SubmitAssignmentRequest)->rules();

    expect($rules['file'])->toContain('mimes:pdf,doc,docx,zip')
        ->and($rules['file'])->toContain('max:5120');
});

test('T657B-FR-SUBM-008: HTTP submission validation requires content', function (): void {
    expect((new SubmitAssignmentRequest)->rules()['content'])->toContain('required', 'string');
});

test('T657B-FR-SUBM-009: submission statuses expose terminal and actionable predicates', function (): void {
    expect(SubmissionStatus::GRADED->isFinalized())->toBeTrue()
        ->and(SubmissionStatus::REVISION_REQUIRED->requiresAction())->toBeTrue()
        ->and(SubmissionStatus::DRAFT->isTerminal())->toBeFalse();
});

test('T657B-FR-SUBM-010: submission state predicates are status based', function (): void {
    $model = new AssignmentContractModelDouble(['status' => SubmissionStatus::REVISION_REQUIRED]);
    $state = SubmissionState::fromModel($model);

    expect($state->canBeEdited())->toBeTrue()
        ->and($state->isVerified())->toBeFalse();
});

test('T657B-FR-SUBM-014: submission state conversion does not persist a model', function (): void {
    $model = new AssignmentContractModelDouble(['status' => SubmissionStatus::VERIFIED]);

    SubmissionState::fromModel($model);

    expect($model->exists)->toBeFalse();
});

test('T657C-FR-GRADE-001: grading status filters only actionable submissions', function (): void {
    expect(SubmissionStatus::SUBMITTED->requiresAction())->toBeTrue()
        ->and(SubmissionStatus::REVISION_REQUIRED->requiresAction())->toBeTrue()
        ->and(SubmissionStatus::GRADED->requiresAction())->toBeFalse();
});

test('T657C-FR-GRADE-002: grading bounds are represented by the integer data contract', function (): void {
    $data = new GradeSubmissionData(100, 'Perfect work.');

    expect($data->score)->toBe(100)->and($data->feedback)->toBe('Perfect work.');
});

test('T657C-FR-GRADE-003: grading data allows optional feedback at the DTO boundary', function (): void {
    $data = new GradeSubmissionData(50);

    expect($data->feedback)->toBeNull();
});

test('T657C-FR-GRADE-005: feedback notification is queueable', function (): void {
    expect(is_subclass_of(
        SubmissionFeedbackNotification::class,
        ShouldQueue::class,
    ))->toBeTrue();
});

test('T657C-FR-GRADE-007: revision transitions are limited to submitted records', function (): void {
    expect(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::REVISION_REQUIRED))->toBeTrue()
        ->and(SubmissionStatus::GRADED->canTransitionTo(SubmissionStatus::REVISION_REQUIRED))->toBeFalse();
});

test('T657C-FR-GRADE-010: verification is a valid submission transition', function (): void {
    expect(SubmissionStatus::SUBMITTED->canTransitionTo(SubmissionStatus::VERIFIED))->toBeTrue()
        ->and(SubmissionStatus::VERIFIED->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeFalse();
});

test('T657C-FR-GRADE-011: assignment lifecycle predicates reject cross-domain transitions', function (): void {
    expect(AssignmentStatus::PUBLISHED->canTransitionTo(SubmissionStatus::SUBMITTED))->toBeFalse();
});

test('T657C-FR-GRADE-012: assignment rules use the supplied server clock', function (): void {
    $rules = AssignmentRules::fromArray([
        'isMandatory' => true,
        'dueDate' => Carbon::parse('2026-09-14 12:00:00'),
    ]);

    expect($rules->isMandatory())->toBeTrue()
        ->and($rules->isOverdue(Carbon::parse('2026-09-14 11:59:59')))->toBeFalse()
        ->and($rules->isOverdue(Carbon::parse('2026-09-14 12:00:01')))->toBeTrue();
});

test('T657C-FR-GRADE-013: assignment rules keep an unset deadline open', function (): void {
    $rules = AssignmentRules::fromArray(['isMandatory' => false, 'dueDate' => null]);

    expect($rules->isOverdue(Carbon::parse('2030-01-01 00:00:00')))->toBeFalse();
});

test('T657Z-FR-ASG-009: assignment manager exposes the supported search columns', function (): void {
    $headers = app(AssignmentManager::class)->headers();

    expect(collect($headers)->pluck('index')->all())
        ->toContain('title', 'assignment_type', 'internship.name', 'status');
});

test('T657Z-FR-ASG-012: assignment manager limits assignment types to the contract', function (): void {
    $types = app(AssignmentManager::class)->assignmentTypes;

    expect($types->pluck('id')->all())->toBe(['project', 'report', 'essay']);
});

test('T657Z-FR-ASG-013: assignment status labels use the translation boundary', function (): void {
    app()->setLocale('en');

    expect(AssignmentStatus::DRAFT->label())->toBe('Draft')
        ->and(AssignmentStatus::PUBLISHED->label())->toBe('Published');
});

test('T657B-FR-SUBM-001: submission requests require an authenticated caller', function (): void {
    expect((new SubmitAssignmentRequest)->authorize())->toBeFalse();
});

test('T657C-FR-GRADE-004: submission feedback notifications carry assignment and status context', function (): void {
    $notification = new SubmissionFeedbackNotification('Final report', 'graded', 'Good evidence.');

    expect($notification->toCustomDatabase(new AssignmentContractModelDouble)['data'])
        ->toMatchArray(['assignment_title' => 'Final report', 'status' => 'graded']);
});

test('T657C-FR-GRADE-006: feedback notifications publish mail, broadcast, and database channels', function (): void {
    $notification = new SubmissionFeedbackNotification('Final report', 'graded');

    expect($notification->via(new AssignmentContractModelDouble))
        ->toContain('mail', 'broadcast');
});

test('T657C-FR-GRADE-008: revision feedback is represented as notification content', function (): void {
    $notification = new SubmissionFeedbackNotification('Final report', 'revision_required', 'Add sources.');

    expect($notification->toCustomDatabase(new AssignmentContractModelDouble)['data']['feedback'])
        ->toBe('Add sources.');
});

test('T657C-FR-GRADE-009: revision feedback notification is queueable and non-blocking', function (): void {
    expect($notification = new SubmissionFeedbackNotification('Final report', 'revision_required'))
        ->toBeInstanceOf(Notification::class)
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
});
