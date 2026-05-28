<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Actions;

use App\Domain\Assignment\Models\Submission;
use App\Domain\Core\Actions\BaseAction;

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
