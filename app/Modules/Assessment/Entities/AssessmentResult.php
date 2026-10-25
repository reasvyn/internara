<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Entities;

use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Core\Exceptions\RejectedException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class AssessmentResult extends BaseEntity
{
    public function __construct(
        private ?Carbon $finalizedAt,
        private array|float $scoresData,
        private float $score,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            finalizedAt: $model->finalized_at,
            scoresData: $model->scores_data ?? [],
            score: (float) $model->score,
        );
    }

    public function isFinalized(): bool
    {
        return $this->finalizedAt !== null;
    }

    public function calculateTotalScore(): float
    {
        if (! is_array($this->scoresData)) {
            return $this->score;
        }

        $total = 0.0;
        $competencies = $this->scoresData['competencies'] ?? [];
        foreach ($competencies as $competency) {
            $indicators = $competency['indicators'] ?? [];
            foreach ($indicators as $score) {
                $total += (float) $score;
            }
        }

        return $total;
    }

    /**
     * Normalize indicators to 0–100, apply indicator weights, then competency
     * weights, redistributing the weight of unscored competencies across the
     * scored ones (FR-ASM-016).
     *
     * @param list<array<string, mixed>> $competencies Rubric structure competencies
     */
    public function calculateFinalScore(array $competencies): float
    {
        $competencyScores = $this->competencyScores();
        $scoredCompetencies = $this->scoredCompetencies($competencies, $competencyScores);

        $originalTotalWeight = (int) array_sum(array_column($competencies, 'weight'));
        $scoredTotalWeight = (int) array_sum(array_column($scoredCompetencies, 'weight'));

        if ($scoredTotalWeight === 0) {
            throw new RejectedException(__('assessment.no_competencies_scored'));
        }

        $totalWeightedScore = 0.0;

        foreach ($scoredCompetencies as $competency) {
            $effectiveWeight = $originalTotalWeight > 0
                ? (($competency['weight'] ?? 0) / $scoredTotalWeight) * $originalTotalWeight
                : ($competency['weight'] ?? 0);
            $scores = $competencyScores[$competency['id'] ?? '']['indicators'] ?? [];
            $totalWeightedScore += $this->calculateCompetencyScore($competency, $scores, $effectiveWeight);
        }

        return round($totalWeightedScore, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function competencyScores(): array
    {
        if (! is_array($this->scoresData)) {
            return [];
        }

        return $this->scoresData['competencies'] ?? [];
    }

    /**
     * @param list<array<string, mixed>> $competencies
     * @param array<string, mixed> $competencyScores
     *
     * @return list<array<string, mixed>>
     */
    private function scoredCompetencies(array $competencies, array $competencyScores): array
    {
        $scored = [];

        foreach ($competencies as $competency) {
            $scores = $competencyScores[$competency['id'] ?? '']['indicators'] ?? [];

            foreach ($competency['indicators'] ?? [] as $indicator) {
                if (($scores[$indicator['id']] ?? null) !== null) {
                    $scored[] = $competency;

                    break;
                }
            }
        }

        if ($scored === []) {
            throw new RejectedException(__('assessment.no_competencies_scored'));
        }

        return $scored;
    }

    /**
     * @param array<string, mixed> $competency
     * @param array<string, mixed> $scores
     */
    private function calculateCompetencyScore(array $competency, array $scores, float $effectiveWeight): float
    {
        $score = 0.0;
        $totalIndicatorWeight = 0;

        foreach ($competency['indicators'] ?? [] as $indicator) {
            $indicatorScore = $scores[$indicator['id']] ?? null;
            if ($indicatorScore === null) {
                continue;
            }

            $maxScore = $indicator['max_score'] ?? 100;
            $score += ($indicatorScore / $maxScore) * 100 * (($indicator['weight'] ?? 0) / 100);
            $totalIndicatorWeight += $indicator['weight'] ?? 0;
        }

        return $totalIndicatorWeight > 0 ? $score * ($effectiveWeight / 100) : 0.0;
    }
}
