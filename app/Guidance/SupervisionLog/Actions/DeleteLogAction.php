<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Models\SupervisionLog;

final class DeleteLogAction extends BaseCommandAction
{
    public function execute(SupervisionLog $log): void
    {
        if ($log->status !== SupervisionLogStatus::DRAFT) {
            throw new RejectedException(__('guidance.only_draft_can_be_deleted'));
        }

        $this->transaction(function () use ($log) {
            $this->log('supervision_log_deleted', $log, [
                'topic' => $log->topic,
            ]);

            $log->delete();
        });
    }
}
