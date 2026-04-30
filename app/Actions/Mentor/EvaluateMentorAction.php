<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Models\User;

/**
 * Evaluates a mentor's performance.
 *
 * S2 - Sustain: Provides structured mentor evaluation.
 */
class EvaluateMentorAction
{
    public function execute(User $evaluator, User $mentor, array $data): array
    {
        // TODO: Implement mentor evaluation logic
        return [
            'evaluator_id' => $evaluator->id,
            'mentor_id' => $mentor->id,
            'status' => 'pending_implementation',
        ];
    }
}
