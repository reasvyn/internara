<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Enums\Attendance\AbsenceRequestStatus;
use App\Models\AbsenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitAbsenceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): AbsenceRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $request = AbsenceRequest::create([
                'user_id' => $user->id,
                'registration_id' => $data['registration_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? $data['start_date'],
                'reason_type' => $data['reason_type'],
                'reason_description' => $data['reason_description'] ?? null,
                'status' => AbsenceRequestStatus::PENDING,
            ]);

            $this->logAudit->execute(
                action: 'absence_request_submitted',
                subjectType: AbsenceRequest::class,
                subjectId: $request->id,
                payload: ['reason_type' => $request->reason_type?->value],
                module: 'Attendance',
            );

            return $request;
        });
    }
}
