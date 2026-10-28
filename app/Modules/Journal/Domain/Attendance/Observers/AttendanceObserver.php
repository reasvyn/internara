<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\Attendance\Observers;

use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use Illuminate\Support\Facades\Cache;

final class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        $this->invalidate($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        $this->invalidate($attendance);
    }

    public function deleted(Attendance $attendance): void
    {
        $this->invalidate($attendance);
    }

    private function invalidate(Attendance $attendance): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));

        $studentId = $attendance->registration?->student_id;
        if ($studentId) {
            Cache::forget(config('cache-keys.dashboard_student').$studentId);
        }
    }
}
