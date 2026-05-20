<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentor\Enums\SupervisionLogStatus;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use RuntimeException;

class VerifySupervisionLogAction extends BaseAction
{
    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        if ($log->is_verified) {
            throw new RuntimeException('This supervision log has already been verified.');
        }

        return $this->transaction(function () use ($log, $verifier) {
            $log->update([
                'is_verified' => true,
                'verified_at' => Carbon::now(),
                'status' => SupervisionLogStatus::VERIFIED->value,
            ]);

            $this->log('supervision_log_verified', $log, ['verifier' => $verifier->name]);

            return $log;
        });
    }
}
