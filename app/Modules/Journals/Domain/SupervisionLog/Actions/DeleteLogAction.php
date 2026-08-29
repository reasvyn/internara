<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;

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
