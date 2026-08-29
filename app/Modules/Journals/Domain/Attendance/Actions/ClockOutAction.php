<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journals\Domain\Attendance\Data\ClockOutData;
use App\Modules\Journals\Domain\Attendance\Events\AttendanceClockOut;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use Carbon\Carbon;

final class ClockOutAction extends BaseCommandAction
{
    public function execute(ClockOutData $data): Attendance
    {
        return $this->transaction(function () use ($data) {
            $now = Carbon::now();

            $log = Attendance::where('user_id', $data->userId)
                ->whereDate('date', $now->toDateString())
                ->first();

            if (! $log) {
                throw new RejectedException(__('journals.attendance.must_clock_in_first'));
            }

            if ($log->clock_out) {
                throw new RejectedException(__('journals.attendance.already_clocked_out'));
            }

            $log->update([
                'clock_out' => $now->toTimeString(),
                'clock_out_ip' => $data->requestIp ?? null,
                'clock_out_latitude' => $data->data['latitude'] ?? null,
                'clock_out_longitude' => $data->data['longitude'] ?? null,
            ]);

            $this->log('clock_out', $log, ['time' => $log->clock_out]);

            event(new AttendanceClockOut($log));

            return $log;
        });
    }
}
