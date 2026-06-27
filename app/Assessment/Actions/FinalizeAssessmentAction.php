<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Events\AssessmentFinalized;
use App\Assessment\Models\Assessment;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;

final class FinalizeAssessmentAction extends BaseCommandAction
{
    public function execute(Assessment $assessment, User $finalizer): Assessment
    {
        return $this->transaction(function () use ($assessment, $finalizer) {
            if ($assessment->finalized_at !== null) {
                throw new RejectedException('Assessment is already finalized.');
            }

            $rubric = $assessment->rubric;

            if ($rubric === null) {
                throw new RejectedException('Assessment must have a rubric to finalize.');
            }

            $structure = $rubric->structure ?? ['competencies' => []];
            $competencies = $structure['competencies'] ?? [];
            $content = $assessment->scores_data ?? [];
            $competencyScores = $content['competencies'] ?? [];

            $scoredCompetencies = [];

            foreach ($competencies as $competency) {
                $compId = $competency['id'] ?? '';
                $indicatorsData = $competencyScores[$compId]['indicators'] ?? [];
                $hasAnyScore = false;

                foreach ($competency['indicators'] ?? [] as $indicator) {
                    if (($indicatorsData[$indicator['id']] ?? null) !== null) {
                        $hasAnyScore = true;
                        break;
                    }
                }

                if (! $hasAnyScore && ($competency['evaluator_role'] ?? 'teacher') === 'supervisor') {
                    continue;
                }

                $scoredCompetencies[] = $competency;
            }

            if (empty($scoredCompetencies)) {
                throw new RejectedException('No competencies have been scored.');
            }

            $originalTotalWeight = (int) collect($competencies)->sum('weight');
            $scoredTotalWeight = (int) collect($scoredCompetencies)->sum('weight');

            if ($scoredTotalWeight === 0) {
                throw new RejectedException('No competencies have been scored.');
            }

            $totalWeightedScore = 0.0;

            foreach ($scoredCompetencies as $competency) {
                $effectiveWeight =
                    $originalTotalWeight > 0
                        ? ($competency['weight'] / $scoredTotalWeight) * $originalTotalWeight
                        : ($competency['weight'] ?? 0);

                $compId = $competency['id'] ?? '';
                $indicatorsData = $competencyScores[$compId]['indicators'] ?? [];
                $competencyScore = 0.0;
                $totalIndicatorWeight = 0;

                foreach ($competency['indicators'] ?? [] as $indicator) {
                    $score = $indicatorsData[$indicator['id']] ?? null;
                    if ($score !== null) {
                        $maxScore = $indicator['max_score'] ?? 100;
                        $normalized = ($score / $maxScore) * 100;
                        $competencyScore += $normalized * (($indicator['weight'] ?? 0) / 100);
                        $totalIndicatorWeight += $indicator['weight'] ?? 0;
                    }
                }

                if ($totalIndicatorWeight > 0) {
                    $totalWeightedScore += $competencyScore * ($effectiveWeight / 100);
                }
            }

            $finalScore = round($totalWeightedScore, 1);

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
}
