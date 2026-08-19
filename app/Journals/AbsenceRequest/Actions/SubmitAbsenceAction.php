<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\AbsenceRequest\Data\SubmitAbsenceData;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;

final class SubmitAbsenceAction extends BaseCommandAction
{
    public function execute(SubmitAbsenceData $data): Attendance
    {
        return $this->transaction(function () use ($data) {
            $attendance = Attendance::create([
                'user_id' => $data->user->id,
                'registration_id' => $data->registrationId,
                'date' => $data->data['start_date'] ?? now()->toDateString(),
                'status' => 'absent',
                'absence_type' => $data->data['reason_type'],
                'absence_reason' => $data->data['reason_description'] ?? null,
                'absence_attachment' => $data->data['attachment_path'] ?? null,
                'absence_status' => AbsenceRequestStatus::PENDING->value,
            ]);

            $this->log('absence_submitted', $attendance, [
                'user_id' => $data->user->id,
                'absence_type' => $data->data['reason_type'],
            ]);

            return $attendance;
        });
    }
}
