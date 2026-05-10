<?php

declare(strict_types=1);

use App\Entities\Assessment\AssessmentResult;
use Carbon\Carbon;

it('detects finalized assessment', function () {
    $entity = new AssessmentResult(Carbon::now(), [], 0.0);

    expect($entity->isFinalized())->toBeTrue();
});

it('detects not finalized assessment', function () {
    $entity = new AssessmentResult(null, [], 0.0);

    expect($entity->isFinalized())->toBeFalse();
});

it('calculates total score from competencies content', function () {
    $entity = new AssessmentResult(null, [
        'competencies' => [
            'comp-1' => [
                'indicators' => [
                    'ind-1' => 80,
                    'ind-2' => 90,
                ],
            ],
            'comp-2' => [
                'indicators' => [
                    'ind-3' => 70,
                ],
            ],
        ],
    ], 0.0);

    expect($entity->calculateTotalScore())->toBe(240.0);
});

it('falls back to score when content is not array', function () {
    $entity = new AssessmentResult(null, 85.5, 0.0);

    expect($entity->calculateTotalScore())->toBe(0.0);
});

it('returns zero when content has no competencies', function () {
    $entity = new AssessmentResult(null, [], 75.0);

    expect($entity->calculateTotalScore())->toBe(0.0);
});
