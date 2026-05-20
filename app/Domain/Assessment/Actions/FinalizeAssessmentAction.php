<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Models\User;

class FinalizeAssessmentAction extends BaseAction
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

            $competencies = $rubric->competencies()->with('indicators')->get();
            $content = $assessment->content ?? [];
            $competencyScores = $content['competencies'] ?? [];

            $scoredCompetencies = [];

            foreach ($competencies as $competency) {
                $indicatorsData = $competencyScores[$competency->id]['indicators'] ?? [];
                $hasAnyScore = false;

                foreach ($competency->indicators as $indicator) {
                    if (($indicatorsData[$indicator->id] ?? null) !== null) {
                        $hasAnyScore = true;
                        break;
                    }
                }

                if (! $hasAnyScore && $competency->evaluator_role?->value === 'supervisor') {
                    continue;
                }

                $scoredCompetencies[] = $competency;
            }

            if (empty($scoredCompetencies)) {
                throw new RejectedException('No competencies have been scored.');
            }

            $originalTotalWeight = (int) $competencies->sum('weight');
            $scoredTotalWeight = (int) collect($scoredCompetencies)->sum('weight');

            if ($scoredTotalWeight === 0) {
                throw new RejectedException('No competencies have been scored.');
            }

            $totalWeightedScore = 0.0;

            foreach ($scoredCompetencies as $competency) {
                $effectiveWeight = $originalTotalWeight > 0
                    ? ($competency->weight / $scoredTotalWeight) * $originalTotalWeight
                    : $competency->weight;

                $indicatorsData = $competencyScores[$competency->id]['indicators'] ?? [];
                $competencyScore = 0.0;
                $totalIndicatorWeight = 0;

                foreach ($competency->indicators as $indicator) {
                    $score = $indicatorsData[$indicator->id] ?? null;
                    if ($score !== null) {
                        $normalized = ($score / $indicator->max_score) * 100;
                        $competencyScore += $normalized * ($indicator->weight / 100);
                        $totalIndicatorWeight += $indicator->weight;
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
                'content' => $content,
            ]);

            $this->log('assessment_finalized', $assessment, ['final_score' => $finalScore]);

            return $assessment->fresh();
        });
    }
}
