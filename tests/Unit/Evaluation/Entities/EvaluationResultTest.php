<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Evaluation\Entities\EvaluationResult;
use App\Domain\Evaluation\Enums\EvaluationCategory;

describe('EvaluationResult entity', function () {
    it('extends BaseEntity', function () {
        expect(EvaluationResult::class)->toExtend(BaseEntity::class);
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(EvaluationResult::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('returns the category', function () {
        $result = new EvaluationResult(
            category: EvaluationCategory::MENTOR,
            overallScore: 85.0,
            criteriaScores: ['communication' => 80],
            feedback: 'Great mentor',
        );

        expect($result->category())->toBe(EvaluationCategory::MENTOR);
    });

    it('returns the overall score', function () {
        $result = new EvaluationResult(
            category: EvaluationCategory::PROGRAM,
            overallScore: 72.5,
            criteriaScores: [],
            feedback: null,
        );

        expect($result->overallScore())->toBe(72.5);
    });

    it('returns the criteria scores', function () {
        $scores = ['communication' => 90, 'responsiveness' => 85];

        $result = new EvaluationResult(
            category: EvaluationCategory::MENTOR,
            overallScore: 88.0,
            criteriaScores: $scores,
            feedback: null,
        );

        expect($result->criteriaScores())->toBe($scores);
    });

    it('returns feedback', function () {
        $result = new EvaluationResult(
            category: EvaluationCategory::OVERALL,
            overallScore: 95.0,
            criteriaScores: [],
            feedback: 'Excellent experience',
        );

        expect($result->feedback())->toBe('Excellent experience');
    });

    it('computes average criterion score', function () {
        $result = new EvaluationResult(
            category: EvaluationCategory::MENTOR,
            overallScore: 85.0,
            criteriaScores: ['a' => 80, 'b' => 90, 'c' => 70],
            feedback: null,
        );

        expect($result->averageCriterionScore())->toBe(80.0);
    });

    it('returns zero average when no criteria scores', function () {
        $result = new EvaluationResult(
            category: EvaluationCategory::PROGRAM,
            overallScore: 75.0,
            criteriaScores: [],
            feedback: null,
        );

        expect($result->averageCriterionScore())->toBe(0.0);
    });

    it('validates score range', function () {
        $valid = new EvaluationResult(EvaluationCategory::MENTOR, 85.0, [], null);
        $invalid = new EvaluationResult(EvaluationCategory::MENTOR, -1.0, [], null);

        expect($valid->isValid())->toBeTrue()
            ->and($invalid->isValid())->toBeFalse();
    });

    it('classifies score bands', function () {
        expect((new EvaluationResult(EvaluationCategory::MENTOR, 90, [], null))->scoreBand())->toBe('excellent')
            ->and((new EvaluationResult(EvaluationCategory::MENTOR, 75, [], null))->scoreBand())->toBe('good')
            ->and((new EvaluationResult(EvaluationCategory::MENTOR, 60, [], null))->scoreBand())->toBe('satisfactory')
            ->and((new EvaluationResult(EvaluationCategory::MENTOR, 45, [], null))->scoreBand())->toBe('needs_improvement')
            ->and((new EvaluationResult(EvaluationCategory::MENTOR, 20, [], null))->scoreBand())->toBe('poor');
    });
});
