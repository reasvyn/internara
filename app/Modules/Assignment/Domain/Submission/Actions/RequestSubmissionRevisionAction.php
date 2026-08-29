<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Actions;

use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Events\SubmissionRevisionRequested;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;

final class RequestSubmissionRevisionAction extends BaseCommandAction
{
    public function execute(Submission $submission, string $feedback): Submission
    {
        if ($submission->status !== SubmissionStatus::SUBMITTED) {
            throw new RejectedException(__('submission.only_submitted_can_revise'));
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
