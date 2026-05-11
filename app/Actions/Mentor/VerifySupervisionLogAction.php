<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Core\LogAuditAction;
use App\Enums\Mentor\SupervisionLogStatus;
use App\Models\SupervisionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerifySupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        if ($log->is_verified) {
            throw new RuntimeException('This supervision log has already been verified.');
        }

        return DB::transaction(function () use ($log, $verifier) {
            $log->update([
                'is_verified' => true,
                'verified_at' => Carbon::now(),
                'status' => SupervisionLogStatus::VERIFIED->value,
            ]);

            $this->logAudit->execute(
                action: 'supervision_log_verified',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['verifier' => $verifier->name],
                module: 'Supervision',
            );

            return $log;
        });
    }
}
