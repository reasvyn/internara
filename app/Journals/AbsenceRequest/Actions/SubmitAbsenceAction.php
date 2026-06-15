<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;

final class SubmitAbsenceAction extends BaseCommandAction
{
    public function execute(User $user, string $registrationId, array $data): Attendance
    {
        return $this->transaction(function () use ($user, $registrationId, $data) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'registration_id' => $registrationId,
                'date' => $data['start_date'] ?? now()->toDateString(),
                'status' => 'absent',
                'absence_type' => $data['reason_type'],
                'absence_reason' => $data['reason_description'] ?? null,
                'absence_attachment' => $data['attachment_path'] ?? null,
                'absence_status' => AbsenceRequestStatus::PENDING->value,
            ]);

            $this->log('absence_submitted', $attendance, [
                'user_id' => $user->id,
                'absence_type' => $data['reason_type'],
            ]);

            return $attendance;
        });
    }
}
