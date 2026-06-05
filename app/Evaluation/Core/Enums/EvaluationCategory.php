<?php

declare(strict_types=1);

namespace App\Evaluation\Core\Enums;

use App\Core\Contracts\LabelEnum;

enum EvaluationCategory: string implements LabelEnum
{
    case MENTOR = 'mentor';
    case PROGRAM = 'program';
    case COMPANY = 'company';
    case FACILITY = 'facility';
    case OVERALL = 'overall';

    public function label(): string
    {
        return match ($this) {
            self::MENTOR => __('Mentor Evaluation'),
            self::PROGRAM => __('Program Evaluation'),
            self::COMPANY => __('Company Evaluation'),
            self::FACILITY => __('Facility Evaluation'),
            self::OVERALL => __('Overall Satisfaction'),
        };
    }

    public function defaultCriteria(): array
    {
        return match ($this) {
            self::MENTOR => [
                'communication' => __('evaluation.criteria.communication'),
                'responsiveness' => __('evaluation.criteria.responsiveness'),
                'guidance_quality' => __('evaluation.criteria.guidance_quality'),
            ],
            self::PROGRAM => [
                'curriculum_relevance' => __('evaluation.criteria.curriculum_relevance'),
                'administration' => __('evaluation.criteria.administration'),
                'facility_support' => __('evaluation.criteria.facility_support'),
            ],
            self::COMPANY => [
                'workplace_safety' => __('evaluation.criteria.workplace_safety'),
                'task_relevance' => __('evaluation.criteria.task_relevance'),
                'mentoring_quality' => __('evaluation.criteria.mentoring_quality'),
            ],
            self::FACILITY => [
                'equipment_quality' => __('evaluation.criteria.equipment_quality'),
                'workspace_comfort' => __('evaluation.criteria.workspace_comfort'),
                'infrastructure' => __('evaluation.criteria.infrastructure'),
            ],
            self::OVERALL => [
                'overall_satisfaction' => __('evaluation.criteria.overall_satisfaction'),
                'recommendation_score' => __('evaluation.criteria.recommendation_score'),
                'experience_rating' => __('evaluation.criteria.experience_rating'),
            ],
        };
    }
}
