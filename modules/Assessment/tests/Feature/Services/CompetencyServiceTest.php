<?php

declare(strict_types=1);

use Modules\Assessment\Models\Competency;
use Modules\Assessment\Services\Contracts\CompetencyService;
use Modules\Internship\Models\InternshipRegistration;

test('it can record student competency progress', function () {
    $registration = InternshipRegistration::factory()->create();
    $competency = Competency::create([
        'name' => 'Web Development',
        'slug' => 'web-dev',
        'category' => 'technical',
    ]);

    $service = app(CompetencyService::class);

    $log = $service->recordProgress([
        'registration_id' => $registration->id,
        'competency_id' => $competency->id,
        'score' => 85,
        'notes' => 'Mastered basic CRUD.',
    ]);

    expect($log)
        ->toBeInstanceOf(\Modules\Assessment\Models\StudentCompetencyLog::class)
        ->and($log->score)
        ->toBe(85);

    $this->assertDatabaseHas('student_competency_logs', [
        'registration_id' => $registration->id,
        'score' => 85,
    ]);
});

test('it can get progress stats for radar chart', function () {
    $registration = InternshipRegistration::factory()->create();
    $comp1 = Competency::create(['name' => 'Backend', 'slug' => 'backend']);
    $comp2 = Competency::create(['name' => 'Frontend', 'slug' => 'frontend']);

    $service = app(CompetencyService::class);

    $service->recordProgress([
        'registration_id' => $registration->id,
        'competency_id' => $comp1->id,
        'score' => 70,
    ]);

    $service->recordProgress([
        'registration_id' => $registration->id,
        'competency_id' => $comp2->id,
        'score' => 90,
    ]);

    $stats = $service->getProgressStats($registration->id);

    expect($stats)
        ->toHaveCount(2)
        ->and($stats[0]['name'])
        ->toBe('Backend')
        ->and($stats[0]['score'])
        ->toBe(70)
        ->and($stats[1]['name'])
        ->toBe('Frontend')
        ->and($stats[1]['score'])
        ->toBe(90);
});
