<?php

declare(strict_types=1);

use App\Modules\Assignment\Actions\CreateAssignmentAction;
use App\Modules\Assignment\Actions\DeleteAssignmentAction;
use App\Modules\Assignment\Actions\PublishAssignmentAction;
use App\Modules\Assignment\Actions\UpdateAssignmentAction;
use App\Modules\Assignment\Data\CreateAssignmentData;
use App\Modules\Assignment\Data\UpdateAssignmentData;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Assignment\Events\AssignmentPublished;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Assignment\Notifications\AssignmentNotification;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('T657Z-FR-ASG-001: creates a draft through the command action', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $internship = Internship::factory()->create();
    $this->actingAs($teacher);

    $response = app(CreateAssignmentAction::class)->execute(new CreateAssignmentData(
        assignmentType: 'project',
        internshipId: $internship->id,
        title: 'Build a deployment checklist',
        description: 'Describe the release checks.',
        isMandatory: true,
    ));

    expect($response->success)->toBeTrue()
        ->and($response->data)->toBeInstanceOf(Assignment::class)
        ->and($response->data->status)->toBe(AssignmentStatus::DRAFT);
});

test('T657Z-FR-ASG-002: preserves nullable due dates and mandatory values', function (): void {
    $assignment = Assignment::factory()->create(['due_date' => null, 'is_mandatory' => false]);

    expect($assignment->fresh()->due_date)->toBeNull()
        ->and($assignment->is_mandatory)->toBeFalse();
});

test('T657Z-FR-ASG-003: assignment uses a UUID primary key and fillable contract', function (): void {
    $assignment = Assignment::factory()->create();

    expect($assignment->getKey())->toMatch('/^[0-9a-f-]{36}$/i')
        ->and($assignment->getFillable())->toContain('title', 'due_date', 'created_by');
});

test('T657Z-FR-ASG-004: partial updates ignore null fields', function (): void {
    $assignment = Assignment::factory()->create([
        'title' => 'Original title',
        'description' => 'Original description',
    ]);

    $response = app(UpdateAssignmentAction::class)->execute($assignment, new UpdateAssignmentData(
        title: 'Updated title',
    ));

    expect($response->data->title)->toBe('Updated title')
        ->and($response->data->description)->toBe('Original description');
});

test('T657Z-FR-ASG-005: delete removes childless assignments', function (): void {
    $assignment = Assignment::factory()->create();

    app(DeleteAssignmentAction::class)->execute($assignment);

    expect(Assignment::find($assignment->id))->toBeNull();
});

test('T657Z-FR-ASG-005: delete cascades dependent submissions at the database boundary', function (): void {
    $assignment = Assignment::factory()->create();
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id]);

    app(DeleteAssignmentAction::class)->execute($assignment);

    expect(Assignment::find($assignment->id))->toBeNull()
        ->and(Submission::find($submission->id))->toBeNull();
});

test('T657Z-FR-ASG-006: assignment status exposes the forward-only lifecycle', function (): void {
    expect(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeTrue()
        ->and(AssignmentStatus::PUBLISHED->canTransitionTo(AssignmentStatus::CLOSED))->toBeTrue()
        ->and(AssignmentStatus::CLOSED->validTransitions())->toBe([]);
});

test('T657Z-FR-ASG-007: publish changes only draft assignments', function (): void {
    $assignment = Assignment::factory()->create();

    $published = app(PublishAssignmentAction::class)->execute($assignment);

    expect($published->status)->toBe(AssignmentStatus::PUBLISHED);
    expect(fn () => app(PublishAssignmentAction::class)->execute($published))
        ->toThrow(RejectedException::class);
});

test('T657Z-UC-ASG-001, T657Z-FR-ASG-008: publish emits its event and notifies enrolled students', function (): void {
    Event::fake();
    Notification::fake();
    $assignment = Assignment::factory()->create();
    $student = User::factory()->create();
    Registration::factory()->active()->create([
        'student_id' => $student->id,
        'internship_id' => $assignment->internship_id,
    ]);

    app(PublishAssignmentAction::class)->execute($assignment);

    Event::assertDispatched(AssignmentPublished::class);
    Notification::assertSentTo($student, AssignmentNotification::class);
});

test('T657Z-UC-ASG-002, T657Z-FR-ASG-010: a closed assignment remains readable with its submissions', function (): void {
    $assignment = Assignment::factory()->closed()->create();
    $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'score' => 88]);

    expect($assignment->fresh()->status)->toBe(AssignmentStatus::CLOSED)
        ->and($assignment->submissions()->find($submission->id)->score)->toBe(88.0);
});

test('T657Z-FR-ASG-011: publish action rechecks state independently of policy', function (): void {
    $assignment = Assignment::factory()->published()->create();

    expect(fn () => app(PublishAssignmentAction::class)->execute($assignment))
        ->toThrow(RejectedException::class);
});

test('T657Z-FR-ASG-014: deleting an assignment does not silently bypass the database boundary', function (): void {
    $assignment = Assignment::factory()->create();
    $assignment->delete();

    expect($assignment->fresh())->toBeNull();
});
