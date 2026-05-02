<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionFeedbackNotification;
use InvalidArgumentException;

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
        User $verifier,
        SubmissionStatus|string $status,
        ?string $feedback = null,
    ): Submission {
        $statusValue = is_string($status) ? $status : $status->value;
        $validStatuses = [SubmissionStatus::VERIFIED->value, SubmissionStatus::REVISION_REQUIRED->value];

        if (! in_array($statusValue, $validStatuses)) {
            throw new InvalidArgumentException('Invalid verification status.');
        }

        if (! $verifier->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            throw new InvalidArgumentException('Not authorized to verify submissions.');
        }

        $submission->update([
            'status' => $statusValue,
            'metadata' => array_merge($submission->metadata ?? [], [
                'feedback' => $feedback,
                'verified_at' => now()->toIso8601String(),
                'verified_by' => $verifier->name,
            ]),
        ]);

        // Notify Student
        $submission->student->notify(new SubmissionFeedbackNotification(
            $submission->assignment->title,
            $statusValue,
            $feedback
        ));

        return $submission->fresh();
    }
}
