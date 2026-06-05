<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Actions;

use App\Core\Actions\BaseAction;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Carbon\Carbon;
use RuntimeException;

final class VerifySupervisionLogAction extends BaseAction
{
    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        if ($log->status === SupervisionLogStatus::VERIFIED) {
            throw new RuntimeException('This supervision log has already been verified.');
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
