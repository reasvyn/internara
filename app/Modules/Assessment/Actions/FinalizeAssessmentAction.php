<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Events\AssessmentFinalized;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;

final class FinalizeAssessmentAction extends BaseCommandAction
{
    public function execute(Assessment $assessment, User $finalizer): Assessment
    {
        return $this->transaction(function () use ($assessment, $finalizer) {
            $this->ensureCanFinalize($assessment);
            $competencies = $this->getCompetencies($assessment);
            $competencyScores = ($assessment->scores_data ?? [])['competencies'] ?? [];
            $scoredCompetencies = $this->getScoredCompetencies($competencies, $competencyScores);
            $finalScore = $this->calculateFinalScore($competencies, $scoredCompetencies, $competencyScores);

            $assessment->update([
                'score' => $finalScore,
                'finalized_at' => now(),
                'evaluator_id' => $finalizer->id,
            ]);

            $this->log('assessment_finalized', $assessment, ['final_score' => $finalScore]);

            event(new AssessmentFinalized($assessment));

            return $assessment->fresh();
        });
    }

    private function ensureCanFinalize(Assessment $assessment): void
    {
        if ($assessment->finalized_at !== null) {
            throw new RejectedException(__('assessment.already_finalized'));
        }

        if ($assessment->rubric === null) {
            throw new RejectedException(__('assessment.rubric_required'));
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCompetencies(Assessment $assessment): array
    {
        return $assessment->rubric->structure['competencies'] ?? [];
    }

    /**
     * @param list<array<string, mixed>> $competencies
     * @param array<string, mixed> $competencyScores
     *
     * @return list<array<string, mixed>>
     */
    private function getScoredCompetencies(array $competencies, array $competencyScores): array
    {
        $scored = [];

        foreach ($competencies as $competency) {
            $scores = $competencyScores[$competency['id'] ?? '']['indicators'] ?? [];
            $hasScore = collect($competency['indicators'] ?? [])
                ->contains(fn (array $indicator): bool => ($scores[$indicator['id']] ?? null) !== null);

            if (! $hasScore) {
                continue;
            }

            $scored[] = $competency;
        }

        if ($scored === []) {
            throw new RejectedException(__('assessment.no_competencies_scored'));
        }

        return $scored;
    }

    /**
     * @param list<array<string, mixed>> $competencies
     * @param list<array<string, mixed>> $scoredCompetencies
     * @param array<string, mixed> $competencyScores
     */
    private function calculateFinalScore(
        array $competencies,
        array $scoredCompetencies,
        array $competencyScores,
    ): float {
        $originalTotalWeight = (int) collect($competencies)->sum('weight');
        $scoredTotalWeight = (int) collect($scoredCompetencies)->sum('weight');

        if ($scoredTotalWeight === 0) {
            throw new RejectedException(__('assessment.no_competencies_scored'));
        }

        $totalWeightedScore = 0.0;

        foreach ($scoredCompetencies as $competency) {
            $effectiveWeight = $originalTotalWeight > 0
                ? ($competency['weight'] / $scoredTotalWeight) * $originalTotalWeight
                : ($competency['weight'] ?? 0);
            $scores = $competencyScores[$competency['id'] ?? '']['indicators'] ?? [];
            $totalWeightedScore += $this->calculateCompetencyScore($competency, $scores, $effectiveWeight);
        }

        return round($totalWeightedScore, 1);
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
