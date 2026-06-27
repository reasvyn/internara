<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Events;

use App\Core\Events\BaseEvent;
use App\Journals\Attendance\Models\Attendance;

final class AttendanceClockIn extends BaseEvent
{
    public function __construct(public Attendance $attendance) {}

    public function eventName(): string
    {
        return 'attendance.clock_in';
    }
}
