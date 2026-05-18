<?php

declare(strict_types=1);

namespace App\Actions\Attendance;

use App\Actions\Core\LogAuditAction;
use App\Models\Attendance;
use App\Models\Briefing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClockInAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data, ?string $requestIp = null): Attendance
    {
        return DB::transaction(function () use ($user, $data, $requestIp) {
            $now = Carbon::now();

            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RuntimeException('No active internship registration found.');
            }

            if (! Briefing::hasStudentCompletedMandatoryBriefing($user->id, $registration->internship_id)) {
                throw new RuntimeException('You must attend the mandatory briefing before clocking in.');
            }

            // Check if already clocked in today
            $existingLog = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($existingLog) {
                throw new RuntimeException('Already clocked in for today.');
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

            $this->logAudit->execute(
                action: 'clock_in',
                subjectType: Attendance::class,
                subjectId: $log->id,
                payload: ['time' => $log->clock_in],
                module: 'Attendance',
            );

            return $log;
        });
    }
}
