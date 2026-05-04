<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\User\Models\User;
use InvalidArgumentException;

/**
 * Stateless Action to update an assessment.
 *
 * S1 - Secure: Only evaluator or admin can update before finalization.
 * S2 - Sustain: Single-purpose action.
 */
class UpdateAssessmentAction
{
    public function execute(
        User $user,
        Assessment $assessment,
        ?array $content = null,
        ?float $score = null,
        ?string $feedback = null,
    ): Assessment {
        if ($assessment->isFinalized()) {
            throw new InvalidArgumentException('Cannot update finalized assessment.');
        }

        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            if ($assessment->evaluator_id !== $user->id) {
                throw new InvalidArgumentException('Not authorized to update this assessment.');
            }
        }

        $assessment->update(
            array_filter(
                [
                    'content' => $content,
                    'score' => $score,
                    'feedback' => $feedback,
                ],
                fn ($value) => ! is_null($value),
            ),
        );

        return $assessment->fresh();
    }
}
