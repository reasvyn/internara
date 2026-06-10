<?php

declare(strict_types=1);

use App\Assessment\Entities\AssessmentResult;
use Carbon\Carbon;

test('assessment result detects finalized state', function () {
    $finalized = new AssessmentResult(new Carbon, [], 85);
    expect($finalized->isFinalized())->toBeTrue();

    $notFinalized = new AssessmentResult(null, [], 85);
    expect($notFinalized->isFinalized())->toBeFalse();
});

test('assessment result calculate total score from competencies', function () {
    $scoresData = [
        'competencies' => [
            ['indicators' => [80, 90]],
            ['indicators' => [70]],
        ],
    ];
    $result = new AssessmentResult(null, $scoresData, 0);
    expect($result->calculateTotalScore())->toBe(240.0);
});

test('assessment result returns score directly when not array', function () {
    $result = new AssessmentResult(null, 85.5, 85.5);
    expect($result->calculateTotalScore())->toBe(85.5);
});

test('assessment result returns zero for empty competencies', function () {
    $result = new AssessmentResult(null, ['competencies' => []], 0);
    expect($result->calculateTotalScore())->toBe(0.0);
});
