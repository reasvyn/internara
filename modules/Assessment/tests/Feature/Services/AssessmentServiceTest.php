<?php

declare(strict_types=1);

namespace Modules\Assessment\Tests\Feature\Services;

use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\User\Models\User;

test('submitEvaluation creates assessment and calculates score', function () {
    // Arrange
    $user = User::factory()->create();
    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'teacher_id' => $user->id,
        ]);
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
        'Great job!',
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

test('it throws exception for unauthorized evaluator', function () {
    // Arrange
    $user = User::factory()->create();
    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'teacher_id' => User::factory()->create()->id, // Different teacher
        ]);
    $service = app(AssessmentService::class);

    // Act & Assert
    expect(
        fn () => $service->submitEvaluation(
            $registration->id,
            $user->id,
            'teacher',
            ['a' => 100],
            '',
        ),
    )->toThrow(\Modules\Exception\AppException::class);
});

test('getScoreCard returns both assessments', function () {
    // Arrange
    $service = app(AssessmentService::class);
    $teacher = User::factory()->create();
    $mentor = User::factory()->create();
    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'teacher_id' => $teacher->id,
            'mentor_id' => $mentor->id,
        ]);

    $service->submitEvaluation(
        $registration->id,
        $teacher->id,
        'teacher',
        ['a' => 100],
        'Teacher note',
    );
    $service->submitEvaluation(
        $registration->id,
        $mentor->id,
        'mentor',
        ['b' => 80],
        'Mentor note',
    );

    // Act
    $scoreCard = $service->getScoreCard($registration->id);

    // Assert
    expect($scoreCard['teacher'])->not->toBeNull();
    expect((float) $scoreCard['teacher']->score)->toBe(100.0);
    expect($scoreCard['mentor'])->not->toBeNull();
    expect((float) $scoreCard['mentor']->score)->toBe(80.0);
    expect((float) $scoreCard['final_grade'])->toBe(72.0); // (100*0.4) + (80*0.4) + (0*0.2)
});
