<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Assessment;

class UpdateAssessmentScoresAction
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
