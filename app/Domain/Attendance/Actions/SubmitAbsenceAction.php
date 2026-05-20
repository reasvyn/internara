<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Attendance\Models\AbsenceRequest;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;

class SubmitAbsenceAction extends BaseAction
{
    public function execute(User $user, array $data): AbsenceRequest
    {
        return $this->transaction(function () use ($user, $data) {
            $request = AbsenceRequest::create([
                'user_id' => $user->id,
                'registration_id' => $data['registration_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? $data['start_date'],
                'reason_type' => $data['reason_type'],
                'reason_description' => $data['reason_description'] ?? null,
                'status' => AbsenceRequestStatus::PENDING,
            ]);

            $this->log('absence_request_submitted', $request, ['reason_type' => $request->reason_type?->value]);

            return $request;
        });
    }
}
