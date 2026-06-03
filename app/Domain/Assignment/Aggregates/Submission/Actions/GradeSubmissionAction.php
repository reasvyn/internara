<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Aggregates\Submission\Actions;

use App\Domain\Assignment\Aggregates\Submission\Models\Submission;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;

final class GradeSubmissionAction extends BaseAction
{
    public function execute(Submission $submission, int $score, ?string $feedback = null): Submission
    {
        if ($score < 0 || $score > 100) {
            throw new RejectedException('Score must be between 0 and 100.');
        }

        return $this->transaction(function () use ($submission, $score, $feedback) {
            $submission->update([
                'score' => $score,
                'feedback' => $feedback,
                'status' => 'graded',
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            $this->log('submission_graded', $submission, [
                'score' => $score,
                'assignment_title' => $submission->assignment?->title,
            ]);

            return $submission;
        });
    }
}
