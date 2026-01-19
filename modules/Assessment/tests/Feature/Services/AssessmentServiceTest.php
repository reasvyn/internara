<?php

namespace Modules\Assessment\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('submitEvaluation creates assessment and calculates score', function () {
    // Arrange
    $user = User::factory()->create();
    $registration = app(InternshipRegistrationService::class)->factory()->create();
    $service = app(AssessmentService::class);

    $data = [
        'criteria_a' => 80,
        'criteria_b' => 90,
    ];

    // Act
    $assessment = $service->submitEvaluation(
        $registration->id,
        $user->id,
        'teacher',
        $data,
        'Great job!'
    );

    // Assert
    expect($assessment)->toBeObject();
    expect($assessment->registration_id)->toBe($registration->id);
    expect($assessment->evaluator_id)->toBe($user->id);
    expect($assessment->type)->toBe('teacher');
    expect((float) $assessment->score)->toBe(85.0); // Average of 80 and 90
    expect($assessment->feedback)->toBe('Great job!');
    expect($assessment->isFinalized())->toBeTrue();
});

test('getScoreCard returns both assessments', function () {
    // Arrange
    $service = app(AssessmentService::class);
    $registration = app(InternshipRegistrationService::class)->factory()->create();
    $teacher = User::factory()->create();
    $mentor = User::factory()->create();

    $service->submitEvaluation($registration->id, $teacher->id, 'teacher', ['a' => 100], 'Teacher note');
    $service->submitEvaluation($registration->id, $mentor->id, 'mentor', ['b' => 80], 'Mentor note');

    // Act
    $scoreCard = $service->getScoreCard($registration->id);

    // Assert
    expect($scoreCard['teacher'])->not->toBeNull();
    expect((float) $scoreCard['teacher']->score)->toBe(100.0);
    expect($scoreCard['mentor'])->not->toBeNull();
    expect((float) $scoreCard['mentor']->score)->toBe(80.0);
    expect((float) $scoreCard['final_grade'])->toBe(90.0); // (100 + 80) / 2
});