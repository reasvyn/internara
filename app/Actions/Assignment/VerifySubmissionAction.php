<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Enums\SubmissionStatus;
use App\Models\Submission;

/**
 * Stateless Action to verify a submission.
 *
 * S1 - Secure: Only authorized evaluators can verify.
 * S2 - Sustain: Status transition with feedback.
 */
class VerifySubmissionAction
{
    public function execute(
        Submission $submission,
        string $status, // 'verified' or 'revision_required'
        ?string $feedback = null,
    ): Submission {
        $validStatuses = [SubmissionStatus::VERIFIED->value, SubmissionStatus::REVISION_REQUIRED->value];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Invalid verification status.');
        }

        $submission->update([
            'status' => $status,
            'metadata' => array_merge($submission->metadata ?? [], [
                'feedback' => $feedback,
                'verified_at' => now()->toIso8601String(),
            ]),
        ]);

        return $submission->fresh();
    }
}
