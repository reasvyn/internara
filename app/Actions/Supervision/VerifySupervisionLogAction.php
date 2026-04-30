<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Audit\LogAuditAction;
use App\Models\SupervisionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VerifySupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        return DB::transaction(function () use ($log, $verifier) {
            $log->update([
                'is_verified' => true,
                'verified_at' => Carbon::now(),
                'status' => 'verified',
            ]);

            $this->logAudit->execute(
                action: 'supervision_log_verified',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['verifier' => $verifier->name],
                module: 'Supervision'
            );

            return $log;
        });
    }
}
