<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\Attendance\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;

final class AttendanceClockOut extends BaseEvent
{
    public function __construct(public Attendance $attendance) {}

    public function eventName(): string
    {
        return 'attendance.clock_out';
    }
}
