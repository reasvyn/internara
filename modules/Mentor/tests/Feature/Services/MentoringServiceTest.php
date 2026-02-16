<?php

declare(strict_types=1);

use Modules\Internship\Models\InternshipRegistration;
use Modules\Mentor\Services\Contracts\MentoringService;
use Modules\User\Models\User;


test('it can record a mentoring visit', function () {
    $registration = InternshipRegistration::factory()->create();
    $teacher = User::factory()->create();

    $service = app(MentoringService::class);

    $visit = $service->recordVisit([
        'registration_id' => $registration->id,
        'teacher_id' => $teacher->id,
        'visit_date' => now()->toDateString(),
        'notes' => 'Observing student performance.',
    ]);

    expect($visit)
        ->toBeInstanceOf(\Modules\Mentor\Models\MentoringVisit::class)
        ->and($visit->registration_id)
        ->toBe($registration->id);

    $this->assertDatabaseHas('mentoring_visits', [
        'id' => $visit->id,
        'notes' => 'Observing student performance.',
    ]);
});

test('it can record a mentoring log', function () {
    $registration = InternshipRegistration::factory()->create();
    $causer = User::factory()->create();

    $service = app(MentoringService::class);

    $log = $service->recordLog([
        'registration_id' => $registration->id,
        'causer_id' => $causer->id,
        'type' => 'feedback',
        'subject' => 'Weekly Review',
        'content' => 'Good progress so far.',
    ]);

    expect($log)->toBeInstanceOf(\Modules\Mentor\Models\MentoringLog::class);

    $this->assertDatabaseHas('mentoring_logs', [
        'id' => $log->id,
        'subject' => 'Weekly Review',
    ]);
});

test('it can retrieve mentoring stats', function () {
    $registration = InternshipRegistration::factory()->create();
    $service = app(MentoringService::class);

    // Create some data
    $service->recordVisit([
        'registration_id' => $registration->id,
        'teacher_id' => User::factory()->create()->id,
        'visit_date' => now()->toDateString(),
    ]);

    $service->recordLog([
        'registration_id' => $registration->id,
        'causer_id' => User::factory()->create()->id,
        'type' => 'feedback',
        'subject' => 'Subject 1',
        'content' => 'Content 1',
    ]);

    $stats = $service->getMentoringStats($registration->id);

    expect($stats['visits_count'])->toBe(1)->and($stats['logs_count'])->toBe(1);
});
