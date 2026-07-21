<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;

final class ReviewLogAction extends BaseCommandAction
{
    public function execute(SupervisionLog $log, User $supervisor, string $feedback): SupervisionLog
    {
        if ($log->status !== SupervisionLogStatus::SUBMITTED) {
            throw new RejectedException(__('journals.log_not_submitted'));
        }

        return $this->transaction(function () use ($log, $supervisor, $feedback) {
            $log->update([
                'status' => SupervisionLogStatus::REVIEWED->value,
                'supervisor_feedback' => $feedback,
                'reviewed_by' => $supervisor->id,
                'reviewed_at' => now(),
            ]);

            $this->log('supervision_log_reviewed', $log, [
                'reviewed_by' => $supervisor->id,
            ]);

            return $log->fresh();
        });
    }
}
