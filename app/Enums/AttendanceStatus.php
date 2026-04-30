<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Attendance status for a given day.
 */
enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case EARLY_OUT = 'early_out';
    case ABSENT = 'absent';
    case PERMISSION = 'permission';
    case SICK = 'sick';

    public function isOnTime(): bool
    {
        return $this === self::PRESENT;
    }

    public function isExcused(): bool
    {
        return in_array($this, [self::PERMISSION, self::SICK], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::LATE => 'Late',
            self::EARLY_OUT => 'Early Out',
            self::ABSENT => 'Absent',
            self::PERMISSION => 'Permission',
            self::SICK => 'Sick',
        };
    }
}
