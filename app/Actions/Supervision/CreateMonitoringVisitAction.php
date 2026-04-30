<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Audit\LogAuditAction;
use App\Models\MonitoringVisit;
use Illuminate\Support\Facades\DB;

class CreateMonitoringVisitAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User|array $user, array $data = []): MonitoringVisit
    {
        // Support both old and new calling conventions
        if ($user instanceof User) {
            $data['teacher_id'] = $user->id;
            $user = $data;
        }

        // Set default status if not provided
        if (!isset($user['status'])) {
            $user['status'] = 'completed';
        }

        return DB::transaction(function () use ($user) {
            $visit = MonitoringVisit::create($user);

            $this->logAudit->execute(
                action: 'monitoring_visit_created',
                subjectType: MonitoringVisit::class,
                subjectId: $visit->id,
                payload: ['date' => $visit->date],
                module: 'Supervision'
            );

            return $visit;
        });
    }
}
