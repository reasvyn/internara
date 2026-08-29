<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\User\Models\User;
use Carbon\Carbon;

final class VerifySupervisionLogAction extends BaseCommandAction
{
    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        if ($log->status === SupervisionLogStatus::VERIFIED) {
            throw new RejectedException(__('journals.log_already_verified'));
        }

        return $this->transaction(function () use ($log, $verifier) {
            $log->update([
                'is_verified' => true,
                'verified_at' => Carbon::now(),
                'status' => SupervisionLogStatus::VERIFIED->value,
                'verified_by' => $verifier->id,
            ]);

            $this->log('supervision_log_verified', $log, ['verifier' => $verifier->name]);

            return $log;
        });
    }
}
