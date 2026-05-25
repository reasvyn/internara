<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Models\User;
use Carbon\Carbon;

class ClockInAction extends BaseAction
{
    public function execute(User $user, array $data, ?string $requestIp = null): Attendance
    {
        return $this->transaction(function () use ($user, $data, $requestIp) {
            $now = Carbon::now();

            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RejectedException('No active internship registration found.');
            }

            $existingLog = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($existingLog) {
                throw new RejectedException('Already clocked in for today.');
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

            return $log;
        });
    }
}
