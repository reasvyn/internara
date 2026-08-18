<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Actions;

use App\Assignment\Submission\Data\GradeSubmissionData;
use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Exceptions\RejectedException;

final class GradeSubmissionAction extends BaseCommandAction
{
    public function execute(
        Submission $submission,
        GradeSubmissionData $data,
    ): ActionResponse {
        if ($data->score < 0 || $data->score > 100) {
            throw new RejectedException('Score must be between 0 and 100.');
        }

        return $this->transaction(function () use ($submission, $data) {
            $submission->update([
                'score' => $data->score,
                'feedback' => $data->feedback,
                'status' => SubmissionStatus::GRADED->value,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            $this->log('submission_graded', $submission, [
                'score' => $data->score,
            ]);

            return ActionResponse::updated($submission);
        });
    }
}
