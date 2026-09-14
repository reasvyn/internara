<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\AbsenceRequest\Enums;

use App\Modules\Core\Contracts\LabelEnum;

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
            self::SICK => __('journal.absence.reason_types.sick'),
            self::PERMISSION => __('journal.absence.reason_types.permission'),
            self::EMERGENCY => __('journal.absence.reason_types.emergency'),
            self::OTHER => __('journal.absence.reason_types.other'),
        };
    }
}
