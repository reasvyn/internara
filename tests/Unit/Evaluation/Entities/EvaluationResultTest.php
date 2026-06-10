<?php

declare(strict_types=1);

use App\Evaluation\Entities\EvaluationResult;
use App\Evaluation\Enums\EvaluationCategory;

test('evaluation result can be created with valid data', function () {
    $result = new EvaluationResult(
        category: EvaluationCategory::MENTOR,
        overallScore: 85.5,
        criteriaScores: ['communication' => 90, 'responsiveness' => 80],
        feedback: 'Great mentor',
    );

    expect($result->category())->toBe(EvaluationCategory::MENTOR);
    expect($result->overallScore())->toBe(85.5);
    expect($result->criteriaScores())->toBe(['communication' => 90, 'responsiveness' => 80]);
    expect($result->feedback())->toBe('Great mentor');
});

test('evaluation result is valid when score between 0 and 100', function () {
    $valid = new EvaluationResult(EvaluationCategory::MENTOR, 75, [], null);
    expect($valid->isValid())->toBeTrue();

    $boundaryHigh = new EvaluationResult(EvaluationCategory::MENTOR, 100, [], null);
    expect($boundaryHigh->isValid())->toBeTrue();

    $boundaryLow = new EvaluationResult(EvaluationCategory::MENTOR, 0, [], null);
    expect($boundaryLow->isValid())->toBeTrue();
});

test('evaluation result is invalid when score out of range', function () {
    $negative = new EvaluationResult(EvaluationCategory::MENTOR, -1, [], null);
    expect($negative->isValid())->toBeFalse();

    $over = new EvaluationResult(EvaluationCategory::MENTOR, 101, [], null);
    expect($over->isValid())->toBeFalse();
});

test('score band returns correct band', function () {
    $excellent = new EvaluationResult(EvaluationCategory::MENTOR, 95, [], null);
    expect($excellent->scoreBand())->toBe('excellent');

    $good = new EvaluationResult(EvaluationCategory::MENTOR, 75, [], null);
    expect($good->scoreBand())->toBe('good');

    $satisfactory = new EvaluationResult(EvaluationCategory::MENTOR, 60, [], null);
    expect($satisfactory->scoreBand())->toBe('satisfactory');

    $needsImprovement = new EvaluationResult(EvaluationCategory::MENTOR, 45, [], null);
    expect($needsImprovement->scoreBand())->toBe('needs_improvement');

    $poor = new EvaluationResult(EvaluationCategory::MENTOR, 20, [], null);
    expect($poor->scoreBand())->toBe('poor');
});

test('average criterion score computes correctly', function () {
    $result = new EvaluationResult(
        EvaluationCategory::MENTOR, 80,
        ['communication' => 90, 'responsiveness' => 80, 'guidance_quality' => 70],
        null,
    );

    expect($result->averageCriterionScore())->toBe(80.0);
});

test('average criterion score returns zero for empty criteria', function () {
    $result = new EvaluationResult(EvaluationCategory::MENTOR, 80, [], null);

    expect($result->averageCriterionScore())->toBe(0.0);
});

test('average criterion score ignores non-numeric values', function () {
    $result = new EvaluationResult(
        EvaluationCategory::MENTOR, 80,
        ['communication' => 90, 'notes' => 'N/A'],
        null,
    );

    expect($result->averageCriterionScore())->toBe(90.0);
});

test('evaluation result is immutable', function () {
    $result = new EvaluationResult(EvaluationCategory::MENTOR, 85, [], null);

    $reflection = new ReflectionClass($result);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});

test('evaluation result can be created with null feedback', function () {
    $result = new EvaluationResult(EvaluationCategory::MENTOR, 50, [], null);

    expect($result->feedback())->toBeNull();
});
