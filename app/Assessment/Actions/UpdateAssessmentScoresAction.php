<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Core\Actions\BaseCommandAction;

final class UpdateAssessmentScoresAction extends BaseCommandAction
{
    /**
     * Update the score for a specific indicator within an assessment.
     */
    public function execute(
        Assessment $assessment,
        string $competencyId,
        string $indicatorId,
        ?float $score,
    ): Assessment {
        $scoresData = $assessment->scores_data ?? [];
        $scoresData['competencies'] ??= [];
        $scoresData['competencies'][$competencyId]['evaluator_id'] = auth()->id();
        $scoresData['competencies'][$competencyId]['evaluated_at'] = now()->toIso8601String();

        if ($score === null || $score < 0) {
            unset($scoresData['competencies'][$competencyId]['indicators'][$indicatorId]);
        } else {
            $scoresData['competencies'][$competencyId]['indicators'][$indicatorId] = $score;
        }

        $assessment->update(['scores_data' => $scoresData]);

        $this->log('assessment_scores_updated', $assessment, [
            'competency_id' => $competencyId,
            'indicator_id' => $indicatorId,
        ]);

        return $assessment->fresh();
    }
}
