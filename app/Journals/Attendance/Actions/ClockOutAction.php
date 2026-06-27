<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\Attendance\Events\AttendanceClockOut;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Carbon\Carbon;

final class ClockOutAction extends BaseCommandAction
{
    public function execute(User $user, array $data, ?string $requestIp = null): Attendance
    {
        return $this->transaction(function () use ($user, $data, $requestIp) {
            $now = Carbon::now();

            $log = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if (! $log) {
                throw new RejectedException('You must clock in first.');
            }

            if ($log->clock_out) {
                throw new RejectedException('Already clocked out for today.');
            }

            $log->update([
                'clock_out' => $now->toTimeString(),
                'clock_out_ip' => $requestIp ?? null,
                'clock_out_latitude' => $data['latitude'] ?? null,
                'clock_out_longitude' => $data['longitude'] ?? null,
            ]);

            $this->log('clock_out', $log, ['time' => $log->clock_out]);

            event(new AttendanceClockOut($log));

            return $log;
        });
    }
}
