<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Aggregates\Evaluation\Entities;

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Evaluation\Aggregates\Evaluation\Enums\EvaluationCategory;
use Illuminate\Database\Eloquent\Model;

final readonly class EvaluationResult extends BaseEntity
{
    public function __construct(
        private EvaluationCategory $category,
        private float $overallScore,
        private array $criteriaScores,
        private ?string $feedback,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            category: $model->evaluation_type instanceof EvaluationCategory
                ? $model->evaluation_type
                : EvaluationCategory::from($model->evaluation_type ?? 'mentor'),
            overallScore: (float) ($model->overall_score ?? 0),
            criteriaScores: $model->criteria_scores ?? [],
            feedback: $model->feedback,
        );
    }

    public function category(): EvaluationCategory
    {
        return $this->category;
    }

    public function overallScore(): float
    {
        return $this->overallScore;
    }

    public function criteriaScores(): array
    {
        return $this->criteriaScores;
    }

    public function feedback(): ?string
    {
        return $this->feedback;
    }

    public function averageCriterionScore(): float
    {
        $scores = array_filter($this->criteriaScores, fn ($v) => is_numeric($v));

        if ($scores === []) {
            return 0.0;
        }

        return array_sum($scores) / count($scores);
    }

    public function isValid(): bool
    {
        return $this->overallScore >= 0 && $this->overallScore <= 100;
    }

    public function scoreBand(): string
    {
        return match (true) {
            $this->overallScore >= 85 => 'excellent',
            $this->overallScore >= 70 => 'good',
            $this->overallScore >= 55 => 'satisfactory',
            $this->overallScore >= 40 => 'needs_improvement',
            default => 'poor',
        };
    }
}
