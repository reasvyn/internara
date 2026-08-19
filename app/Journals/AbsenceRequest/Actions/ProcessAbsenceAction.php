<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\AbsenceRequest\Data\ProcessAbsenceData;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;

final class ProcessAbsenceAction extends BaseCommandAction
{
    public function execute(ProcessAbsenceData $data): Attendance
    {
        $currentStatus = AbsenceRequestStatus::tryFrom($data->absence->absence_status);
        if ($currentStatus && $currentStatus->isProcessed()) {
            throw new RejectedException(__('journals.absence.already_processed'));
        }

        return $this->transaction(function () use ($data) {
            $data->absence->update([
                'absence_status' => $data->status,
                'absence_processed_by' => $data->processor->id,
                'absence_processed_at' => now(),
                'absence_admin_notes' => $data->notes,
            ]);

            $this->log('absence_request_'.$data->status->value, $data->absence, [
                'status' => $data->status->value,
            ]);

            return $data->absence;
        });
    }
}
