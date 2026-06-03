<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\AbsenceRequest\Enums;

use App\Domain\Core\Contracts\LabelEnum;

/**
 * Types of absence reasons.
 */
enum AbsenceReasonType: string implements LabelEnum
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
            self::SICK => __('Sick'),
            self::PERMISSION => __('Permission'),
            self::EMERGENCY => __('Emergency'),
            self::OTHER => __('Other'),
        };
    }
}
