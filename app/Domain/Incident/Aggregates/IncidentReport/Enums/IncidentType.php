<?php

declare(strict_types=1);

namespace App\Domain\Incident\Aggregates\IncidentReport\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum IncidentType: string implements LabelEnum
{
    case ACCIDENT = 'accident';
    case SAFETY_VIOLATION = 'safety_violation';
    case HARASSMENT = 'harassment';
    case DISCIPLINARY = 'disciplinary';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ACCIDENT => __('Accident'),
            self::SAFETY_VIOLATION => __('Safety Violation'),
            self::HARASSMENT => __('Harassment'),
            self::DISCIPLINARY => __('Disciplinary'),
            self::OTHER => __('Other'),
        };
    }
}
