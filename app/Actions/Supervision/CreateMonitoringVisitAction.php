<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Audit\LogAuditAction;
use App\Models\MonitoringVisit;
use Illuminate\Support\Facades\DB;

class CreateMonitoringVisitAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): MonitoringVisit
    {
        return DB::transaction(function () use ($data) {
            $visit = MonitoringVisit::create($data);

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
