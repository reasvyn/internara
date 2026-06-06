<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Core\Actions\BaseAction;

final class UpdateAssessmentScoresAction extends BaseAction
{
    /**
     * Update the score for a specific indicator within an assessment.
     */
    public function execute(Assessment $assessment, string $competencyId, string $indicatorId, ?float $score): Assessment
    {
        $content = $assessment->content ?? [];
        $content['competencies'][$competencyId]['evaluator_id'] = auth()->id();
        $content['competencies'][$competencyId]['evaluated_at'] = now()->toIso8601String();

        if ($score === null || $score < 0) {
            unset($content['competencies'][$competencyId]['indicators'][$indicatorId]);
        } else {
            $content['competencies'][$competencyId]['indicators'][$indicatorId] = $score;
        }

        $assessment->update(['content' => $content]);

        return $assessment->fresh();
    }
}
