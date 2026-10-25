<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\SupervisionLog\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journal\Domain\SupervisionLog\Data\ReviewLogData;
use App\Modules\Journal\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\User\Models\User;

final class ReviewLogAction extends BaseCommandAction
{
    public function execute(ReviewLogData $data): SupervisionLog
    {
        $log = SupervisionLog::findOrFail($data->logId);

        if ($log->status !== SupervisionLogStatus::SUBMITTED) {
            throw new RejectedException(__('journal.log_not_submitted'));
        }

        return $this->transaction(function () use ($data, $log) {
            $log->update([
                'status' => SupervisionLogStatus::REVIEWED->value,
                'supervisor_feedback' => $data->feedback,
                'reviewed_by' => $data->supervisorId,
                'reviewed_at' => now(),
            ]);

            $reviewer = User::find($data->supervisorId) ?? auth()->user();
            $mentorEntity = $log->registration?->asMentorEntity();
            $proxyMeta = ($reviewer !== null && $mentorEntity !== null)
                ? $mentorEntity->resolveProxyMetadata($reviewer, 'supervisor')
                : [];

            $this->log('supervision_log_reviewed', $log, array_merge([
                'reviewed_by' => $data->supervisorId,
            ], $proxyMeta), $reviewer);

            return $log->fresh();
        });
    }
}
