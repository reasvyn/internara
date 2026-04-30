<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Audit\LogAuditAction;
use App\Enums\SupervisionLogStatus;
use App\Models\SupervisionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VerifySupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(SupervisionLog $log, string|User $verifier): SupervisionLog
    {
        return DB::transaction(function () use ($log, $verifier) {
            $log->update([
                'is_verified' => true,
                'verified_at' => Carbon::now(),
                'status' => SupervisionLogStatus::VERIFIED->value,
            ]);

            $verifierName = is_string($verifier) ? $verifier : $verifier->name;

            $this->logAudit->execute(
                action: 'supervision_log_verified',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['verifier' => $verifierName],
                module: 'Supervision'
            );

            return $log;
        });
    }
}
