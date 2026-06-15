<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Carbon\Carbon;

final class VerifySupervisionLogAction extends BaseCommandAction
{
    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        if ($log->status === SupervisionLogStatus::VERIFIED) {
            throw new RejectedException('This supervision log has already been verified.');
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
