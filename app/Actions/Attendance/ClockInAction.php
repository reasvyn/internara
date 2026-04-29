<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Audit\LogAuditAction;
use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClockInAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): AttendanceLog
    {
        return DB::transaction(function () use ($user, $data) {
            $now = Carbon::now();
            
            // Check if already clocked in today
            $log = AttendanceLog::where('user_id', $user->id)
                ->where('date', $now->toDateString())
                ->first();

            if ($log) {
                throw new \Exception('Already clocked in for today.');
            }

            // Find active registration
            $registration = $user->registrations()
                ->where('status', 'active')
                ->first();

            if (!$registration) {
                throw new \Exception('No active internship registration found.');
            }

            $log = AttendanceLog::create([
                'user_id' => $user->id,
                'registration_id' => $registration->id,
                'date' => $now->toDateString(),
                'clock_in' => $now->toTimeString(),
                'clock_in_ip' => $data['ip'] ?? null,
                'clock_in_latitude' => $data['latitude'] ?? null,
                'clock_in_longitude' => $data['longitude'] ?? null,
                'status' => $data['status'] ?? 'present',
            ]);

            $this->logAudit->execute(
                action: 'clock_in',
                subjectType: AttendanceLog::class,
                subjectId: $log->id,
                payload: ['time' => $log->clock_in],
                module: 'Attendance'
            );

            return $log;
        });
    }
}
