<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Enums\Attendance\AbsenceRequestStatus;
use App\Models\AbsenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessAbsenceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(AbsenceRequest $absence, User $processor, AbsenceRequestStatus $status, ?string $notes = null): AbsenceRequest
    {
        if ($absence->status->isProcessed()) {
            throw new RuntimeException('This absence request has already been processed.');
        }

        return DB::transaction(function () use ($absence, $processor, $status, $notes) {
            $absence->update([
                'status' => $status,
                'processed_by' => $processor->id,
                'processed_at' => now(),
                'admin_notes' => $notes,
            ]);

            $this->logAudit->execute(
                action: 'absence_request_'.$status->value,
                subjectType: AbsenceRequest::class,
                subjectId: $absence->id,
                module: 'Attendance',
            );

            return $absence;
        });
    }
}
