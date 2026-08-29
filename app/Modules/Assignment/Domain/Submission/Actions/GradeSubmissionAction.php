<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Actions;

use App\Modules\Assignment\Domain\Submission\Data\GradeSubmissionData;
use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;

final class GradeSubmissionAction extends BaseCommandAction
{
    public function execute(
        Submission $submission,
        GradeSubmissionData $data,
    ): ActionResponse {
        if ($data->score < 0 || $data->score > 100) {
            throw new RejectedException(__('submission.score_range'));
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
