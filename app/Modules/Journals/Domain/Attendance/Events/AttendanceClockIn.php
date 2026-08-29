<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;

final class AttendanceClockIn extends BaseEvent
{
    public function __construct(public Attendance $attendance) {}

    public function eventName(): string
    {
        return 'attendance.clock_in';
    }
}
