<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Models\User;
use Carbon\Carbon;

class ClockOutAction extends BaseAction
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

            return $log;
        });
    }
}
