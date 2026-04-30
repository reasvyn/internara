<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Audit\LogAuditAction;
use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClockOutAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): AttendanceLog
    {
        return DB::transaction(function () use ($user, $data) {
            $now = Carbon::now();
            
            $log = AttendanceLog::where('user_id', $user->id)
                ->where('date', $now->toDateString())
                ->first();

            if (!$log) {
                throw new \Exception('You must clock in first.');
            }

            if ($log->clock_out) {
                throw new \Exception('Already clocked out for today.');
            }

            $log->update([
                'clock_out' => $now->toTimeString(),
                'clock_out_ip' => $data['ip'] ?? null,
                'clock_out_latitude' => $data['latitude'] ?? null,
                'clock_out_longitude' => $data['longitude'] ?? null,
            ]);

            $this->logAudit->execute(
                action: 'clock_out',
                subjectType: AttendanceLog::class,
                subjectId: $log->id,
                payload: ['time' => $log->clock_out],
                module: 'Attendance'
            );

            return $log;
        });
    }
}
