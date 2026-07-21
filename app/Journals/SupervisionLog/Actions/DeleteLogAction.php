<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;
use App\Journals\SupervisionLog\Models\SupervisionLog;

final class DeleteLogAction extends BaseCommandAction
{
    public function execute(SupervisionLog $log): void
    {
        if ($log->status !== SupervisionLogStatus::DRAFT) {
            throw new RejectedException(__('journals.only_draft_can_be_deleted'));
        }

        $this->transaction(function () use ($log) {
            $this->log('supervision_log_deleted', $log, [
                'topic' => $log->topic,
            ]);

            $log->delete();
        });
    }
}
