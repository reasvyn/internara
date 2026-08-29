<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Journals\Domain\AbsenceRequest\Data\SubmitAbsenceData;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;

final class SubmitAbsenceAction extends BaseCommandAction
{
    public function execute(SubmitAbsenceData $data): Attendance
    {
        return $this->transaction(function () use ($data) {
            $attendance = Attendance::create([
                'user_id' => $data->userId,
                'registration_id' => $data->registrationId,
                'date' => $data->data['start_date'] ?? now()->toDateString(),
                'status' => 'absent',
                'absence_type' => $data->data['reason_type'],
                'absence_reason' => $data->data['reason_description'] ?? null,
                'absence_attachment' => $data->data['attachment_path'] ?? null,
                'absence_status' => AbsenceRequestStatus::PENDING->value,
            ]);

            $this->log('absence_submitted', $attendance, [
                'user_id' => $data->userId,
                'absence_type' => $data->data['reason_type'],
            ]);

            return $attendance;
        });
    }
}
