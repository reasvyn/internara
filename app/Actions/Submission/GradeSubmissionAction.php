<?php

declare(strict_types=1);

namespace App\Actions\Submission;

use App\Enums\Assignment\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\Assignment\SubmissionFeedbackNotification;
use InvalidArgumentException;

class GradeSubmissionAction
{
    public function execute(
        Submission $submission,
        User $grader,
        float $score,
        SubmissionStatus|string $status,
        ?string $feedback = null,
    ): Submission {
        $statusValue = is_string($status) ? $status : $status->value;

        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException('Score must be between 0 and 100.');
        }

        $validStatuses = [
            SubmissionStatus::GRADED->value,
            SubmissionStatus::VERIFIED->value,
            SubmissionStatus::REVISION_REQUIRED->value,
        ];

        if (! in_array($statusValue, $validStatuses)) {
            throw new InvalidArgumentException('Invalid grading status.');
        }

        if (! $grader->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor'])) {
            throw new InvalidArgumentException('Not authorized to grade submissions.');
        }

        $submission->update([
            'status' => $statusValue,
            'score' => $score,
            'feedback' => $feedback,
            'graded_by' => $grader->id,
            'graded_at' => now(),
        ]);

        $submission->student->notify(
            new SubmissionFeedbackNotification(
                $submission->assignment->title,
                $statusValue,
                $feedback,
            ),
        );

        return $submission->fresh();
    }
}
