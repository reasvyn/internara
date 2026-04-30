<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Audit\LogAuditAction;
use App\Models\SupervisionLog;
use Illuminate\Support\Facades\DB;

class CreateSupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array|User $user, array $data = []): SupervisionLog
    {
        // Support both old and new calling conventions
        if ($user instanceof User) {
            $data['teacher_id'] = $user->id;
            $user = $data;
        }

        return DB::transaction(function () use ($user) {
            $log = SupervisionLog::create($user);

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
