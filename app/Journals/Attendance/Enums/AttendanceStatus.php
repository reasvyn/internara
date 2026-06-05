<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum AttendanceStatus: string implements LabelEnum, StatusEnum
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
            self::PRESENT => __('Present'),
            self::LATE => __('Late'),
            self::EARLY_OUT => __('Early Out'),
            self::ABSENT => __('Absent'),
            self::PERMISSION => __('Permission'),
            self::SICK => __('Sick'),
        };
    }

    public function isTerminal(): bool
    {
        return true;
    }

    public function validTransitions(): array
    {
        return [];
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! $target instanceof self) {
            return false;
        }

        return false;
    }
}
