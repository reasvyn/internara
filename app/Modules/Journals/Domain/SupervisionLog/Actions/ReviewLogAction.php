<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journals\Domain\SupervisionLog\Data\ReviewLogData;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;

final class ReviewLogAction extends BaseCommandAction
{
    public function execute(ReviewLogData $data): SupervisionLog
    {
        $log = SupervisionLog::findOrFail($data->logId);

        if ($log->status !== SupervisionLogStatus::SUBMITTED) {
            throw new RejectedException(__('journals.log_not_submitted'));
        }

        return $this->transaction(function () use ($data, $log) {
            $log->update([
                'status' => SupervisionLogStatus::REVIEWED->value,
                'supervisor_feedback' => $data->feedback,
                'reviewed_by' => $data->supervisorId,
                'reviewed_at' => now(),
            ]);

            $this->log('supervision_log_reviewed', $log, [
                'reviewed_by' => $data->supervisorId,
            ]);

            return $log->fresh();
        });
    }
}
