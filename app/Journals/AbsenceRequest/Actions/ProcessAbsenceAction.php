<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;

final class ProcessAbsenceAction extends BaseCommandAction
{
    public function execute(
        Attendance $absence,
        User $processor,
        AbsenceRequestStatus $status,
        ?string $notes = null,
    ): Attendance {
        $currentStatus = AbsenceRequestStatus::tryFrom($absence->absence_status);
        if ($currentStatus && $currentStatus->isProcessed()) {
            throw new RejectedException('This absence request has already been processed.');
        }

        return $this->transaction(function () use ($absence, $processor, $status, $notes) {
            $absence->update([
                'absence_status' => $status,
                'absence_processed_by' => $processor->id,
                'absence_processed_at' => now(),
                'absence_admin_notes' => $notes,
            ]);

            $this->log('absence_request_'.$status->value, $absence, [
                'status' => $status->value,
            ]);

            return $absence;
        });
    }
}
