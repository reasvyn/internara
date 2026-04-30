<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types of absence reasons.
 */
enum AbsenceReasonType: string
{
    case SICK = 'sick';
    case PERMISSION = 'permission';
    case EMERGENCY = 'emergency';
    case OTHER = 'other';

    public function requiresAttachment(): bool
    {
        return in_array($this, [self::SICK, self::EMERGENCY], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SICK => 'Sakit',
            self::PERMISSION => 'Izin',
            self::EMERGENCY => 'Darurat',
            self::OTHER => 'Lainnya',
        };
    }
}
