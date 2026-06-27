<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Actions;

use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Events\SubmissionRevisionRequested;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;

final class RequestSubmissionRevisionAction extends BaseCommandAction
{
    public function execute(Submission $submission, string $feedback): Submission
    {
        if ($submission->status !== SubmissionStatus::SUBMITTED) {
            throw new RejectedException('Only submitted submissions can be revised.');
        }

        return $this->transaction(function () use ($submission, $feedback) {
            $submission->update([
                'status' => SubmissionStatus::REVISION_REQUIRED->value,
                'feedback' => $feedback,
            ]);

            $this->log('submission_revision_requested', $submission, [
                'assignment_title' => $submission->assignment?->title,
                'student_id' => $submission->student_id,
            ]);

            event(new SubmissionRevisionRequested($submission));

            return $submission->fresh();
        });
    }
}
