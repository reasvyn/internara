<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Actions;

use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseAction;

final class VerifySubmissionAction extends BaseAction
{
    public function execute(Submission $submission): Submission
    {
        return $this->transaction(function () use ($submission) {
            $submission->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $this->log('submission_verified', $submission, [
                'assignment_title' => $submission->assignment?->title,
            ]);

            return $submission;
        });
    }
}
