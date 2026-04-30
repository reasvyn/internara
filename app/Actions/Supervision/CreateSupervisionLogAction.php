<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Audit\LogAuditAction;
use App\Models\SupervisionLog;
use Illuminate\Support\Facades\DB;

class CreateSupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): SupervisionLog
    {
        return DB::transaction(function () use ($data) {
            $log = SupervisionLog::create($data);

            $this->logAudit->execute(
                action: 'supervision_log_created',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['type' => $log->type, 'topic' => $log->topic],
                module: 'Supervision'
            );

            return $log;
        });
    }
}
