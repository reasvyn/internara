<?php

declare(strict_types=1);

namespace Modules\Attendance\Enums;

/**
 * Enum AttendanceStatus
 *
 * Formalizes the student attendance states.
 */
enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case SICK = 'sick';
    case PERMITTED = 'permitted';
    case UNEXPLAINED = 'unexplained';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRESENT => __('attendance::status.present'),
            self::SICK => __('attendance::status.sick'),
            self::PERMITTED => __('attendance::status.permitted'),
            self::UNEXPLAINED => __('attendance::status.unexplained'),
        };
    }

    /**
     * Get the visual color for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::SICK => 'info',
            self::PERMITTED => 'warning',
            self::UNEXPLAINED => 'error',
        };
    }
}
