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

it('calculates total score from content', function () {
    $entity = new AssessmentResult(null, [
        ['score' => 80],
        ['score' => 90],
        ['score' => 70],
    ], 0.0);

    expect($entity->calculateTotalScore())->toBe(240.0);
});

it('falls back to score when content is not array', function () {
    $entity = new AssessmentResult(null, 85.5, 0.0);

    expect($entity->calculateTotalScore())->toBe(0.0);
});

it('uses score field when content is empty', function () {
    $entity = new AssessmentResult(null, [], 75.0);

    expect($entity->calculateTotalScore())->toBe(0.0);
});
