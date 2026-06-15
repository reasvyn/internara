<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;

final class CreateAttendanceAction extends BaseCommandAction
{
    public function execute(User $user, array $data): Attendance
    {
        return $this->transaction(function () use ($user, $data) {
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

            $this->log('attendance_created', $log, [
                'user_id' => $user->id,
                'date' => $log->date->toDateString(),
                'status' => $log->status->value,
            ]);

            return $log;
        });
    }
}
