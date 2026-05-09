<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAttendanceAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): Attendance
    {
        return DB::transaction(function () use ($user, $data) {
            $log = Attendance::create([
                'user_id' => $user->id,
                'registration_id' => $data['registration_id'],
                'date' => $data['date'],
                'clock_in' => $data['clock_in'] ?? null,
                'clock_out' => $data['clock_out'] ?? null,
                'status' => $data['status'] ?? 'present',
                'notes' => $data['notes'] ?? null,
                'is_verified' => $data['is_verified'] ?? false,
                'verified_by' => $data['is_verified'] ?? false ? auth()->id() : null,
                'verified_at' => $data['is_verified'] ?? false ? now() : null,
            ]);

            $this->logAudit->execute(
                action: 'attendance_created',
                subjectType: Attendance::class,
                subjectId: $log->id,
                payload: [
                    'user_id' => $user->id,
                    'date' => $log->date->toDateString(),
                    'status' => $log->status->value,
                ],
                module: 'Attendance',
            );

            return $log;
        });
    }
}
