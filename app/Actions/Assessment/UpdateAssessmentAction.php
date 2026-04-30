<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Assessment;

/**
 * Stateless Action to update an assessment.
 *
 * S1 - Secure: Only evaluator can update before finalization.
 * S2 - Sustain: Single-purpose action.
 */
class UpdateAssessmentAction
{
    public function execute(
        Assessment $assessment,
        ?array $content = null,
        ?float $score = null,
        ?string $feedback = null,
    ): Assessment {
        if ($assessment->isFinalized()) {
            throw new \InvalidArgumentException('Cannot update finalized assessment.');
        }

        $assessment->update(array_filter([
            'content' => $content,
            'score' => $score,
            'feedback' => $feedback,
        ], fn ($value) => ! is_null($value)));

        return $assessment->fresh();
    }
}
