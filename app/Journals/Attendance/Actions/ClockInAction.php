<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\Attendance\Events\AttendanceClockIn;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Carbon\Carbon;

final class ClockInAction extends BaseCommandAction
{
    public function execute(User $user, array $data, ?string $requestIp = null): Attendance
    {
        return $this->transaction(function () use ($user, $data, $requestIp) {
            $now = Carbon::now();

            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RejectedException(__('journals.no_active_registration'));
            }

            $existingLog = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($existingLog) {
                throw new RejectedException(__('journals.attendance.already_clocked_in'));
            }

            $log = Attendance::create([
                'user_id' => $user->id,
                'registration_id' => $registration->id,
                'date' => $now->toDateString(),
                'clock_in' => $now->toTimeString(),
                'clock_in_ip' => $requestIp ?? null,
                'clock_in_latitude' => $data['latitude'] ?? null,
                'clock_in_longitude' => $data['longitude'] ?? null,
                'status' => 'present',
            ]);

            $this->log('clock_in', $log, ['time' => $log->clock_in]);

            event(new AttendanceClockIn($log));

            return $log;
        });
    }
}
