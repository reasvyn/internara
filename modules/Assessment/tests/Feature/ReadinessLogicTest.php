<?php

declare(strict_types=1);

use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Internship\Models\InternshipRegistration;
use Modules\User\Models\User;

test('it calculates readiness correctly including mandatory assignments', function () {
    $student = User::factory()->create();
    $teacher = User::factory()->create();
    $mentor = User::factory()->create();

    // 1. Not ready: period not ended
    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'mentor_id' => $mentor->id,
        'end_date' => now()->addDay(),
    ]);

    $service = app(AssessmentService::class);
    $status = $service->getReadinessStatus($registration->id);
    expect($status['is_ready'])->toBeFalse();
    expect($status['missing'])->toContain(__('assessment::messages.period_not_ended'));

    // 2. Not ready: missing evaluations & assignments
    $registration->update(['end_date' => now()->subDay()]);

    // Mock AssignmentService to return incomplete fulfillment
    $assignmentMock = Mockery::mock(AssignmentService::class);
    $assignmentMock
        ->shouldReceive('isFulfillmentComplete')
        ->with($registration->id)
        ->andReturn(false);
    app()->instance(AssignmentService::class, $assignmentMock);

    $status = $service->getReadinessStatus($registration->id);
    expect($status['missing'])->toContain(__('assessment::messages.missing_teacher_eval'));
    expect($status['missing'])->toContain(__('assessment::messages.missing_mentor_eval'));
    expect($status['missing'])->toContain(__('assessment::messages.pending_assignments'));

    // 3. Ready: all clear
    Assessment::create([
        'registration_id' => $registration->id,
        'evaluator_id' => $teacher->id,
        'type' => 'teacher',
        'score' => 80,
    ]);
    Assessment::create([
        'registration_id' => $registration->id,
        'evaluator_id' => $mentor->id,
        'type' => 'mentor',
        'score' => 85,
    ]);

    $assignmentMock
        ->shouldReceive('isFulfillmentComplete')
        ->with($registration->id)
        ->andReturn(true);

    $status = $service->getReadinessStatus($registration->id);
    expect($status['is_ready'])->toBeTrue();
    expect($status['missing'])->toBeEmpty();
});
