<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Data\UpdateAssessmentScoresData;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;

final class UpdateAssessmentScoresAction extends BaseCommandAction
{
    /**
     * Update the score for a specific indicator within an assessment.
     */
    public function execute(
        Assessment $assessment,
        UpdateAssessmentScoresData $data,
    ): ActionResponse {
        $scoresData = $assessment->scores_data ?? [];
        $scoresData['competencies'] ??= [];
        $scoresData['competencies'][$data->competencyId]['evaluator_id'] = auth()->id();
        $scoresData['competencies'][$data->competencyId]['evaluated_at'] = now()->toIso8601String();

        if ($data->score === null || $data->score < 0) {
            unset($scoresData['competencies'][$data->competencyId]['indicators'][$data->indicatorId]);
        } else {
            $scoresData['competencies'][$data->competencyId]['indicators'][$data->indicatorId] = $data->score;
        }

        $assessment->update(['scores_data' => $scoresData]);

        $this->log('assessment_scores_updated', $assessment, [
            'competency_id' => $data->competencyId,
            'indicator_id' => $data->indicatorId,
        ]);

        return ActionResponse::updated($assessment->fresh());
    }
}
