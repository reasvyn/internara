<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Evaluation\Enums\EvaluationCategory;

describe('EvaluationCategory enum', function () {
    it('implements LabelEnum', function () {
        expect(EvaluationCategory::class)->toImplement(LabelEnum::class);
    });

    it('has labels', function () {
        expect(EvaluationCategory::MENTOR->label())->toBe('Mentor Evaluation')
            ->and(EvaluationCategory::PROGRAM->label())->toBe('Program Evaluation')
            ->and(EvaluationCategory::COMPANY->label())->toBe('Company Evaluation')
            ->and(EvaluationCategory::FACILITY->label())->toBe('Facility Evaluation')
            ->and(EvaluationCategory::OVERALL->label())->toBe('Overall Satisfaction');
    });

    it('has default criteria for mentor type', function () {
        $criteria = EvaluationCategory::MENTOR->defaultCriteria();

        expect($criteria)->toHaveKeys(['communication', 'responsiveness', 'guidance_quality']);
    });

    it('has default criteria for program type', function () {
        $criteria = EvaluationCategory::PROGRAM->defaultCriteria();

        expect($criteria)->toHaveKeys(['curriculum_relevance', 'administration', 'facility_support']);
    });

    it('has default criteria for company type', function () {
        $criteria = EvaluationCategory::COMPANY->defaultCriteria();

        expect($criteria)->toHaveKeys(['workplace_safety', 'task_relevance', 'mentoring_quality']);
    });

    it('has default criteria for facility type', function () {
        $criteria = EvaluationCategory::FACILITY->defaultCriteria();

        expect($criteria)->toHaveKeys(['equipment_quality', 'workspace_comfort', 'infrastructure']);
    });

    it('has default criteria for overall type', function () {
        $criteria = EvaluationCategory::OVERALL->defaultCriteria();

        expect($criteria)->toHaveKeys(['overall_satisfaction', 'recommendation_score', 'experience_rating']);
    });

    it('has five cases', function () {
        expect(EvaluationCategory::cases())->toHaveCount(5);
    });
});
