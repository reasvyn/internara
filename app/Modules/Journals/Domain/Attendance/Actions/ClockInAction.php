<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journals\Domain\Attendance\Data\ClockInData;
use App\Modules\Journals\Domain\Attendance\Events\AttendanceClockIn;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\User\Models\User;
use Carbon\Carbon;

final class ClockInAction extends BaseCommandAction
{
    public function execute(ClockInData $data): Attendance
    {
        return $this->transaction(function () use ($data) {
            $user = User::findOrFail($data->userId);
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
                'clock_in_ip' => $data->requestIp ?? null,
                'clock_in_latitude' => $data->data['latitude'] ?? null,
                'clock_in_longitude' => $data->data['longitude'] ?? null,
                'status' => 'present',
            ]);

            $this->log('clock_in', $log, ['time' => $log->clock_in]);

            event(new AttendanceClockIn($log));

            return $log;
        });
    }
}
