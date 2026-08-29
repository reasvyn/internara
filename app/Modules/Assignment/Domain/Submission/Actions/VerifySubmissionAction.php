<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Actions;

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Actions\BaseCommandAction;

final class VerifySubmissionAction extends BaseCommandAction
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
