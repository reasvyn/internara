<?php

declare(strict_types=1);

namespace App\Domain\Incident\Enums;

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
            self::ACCIDENT => 'Accident',
            self::SAFETY_VIOLATION => 'Safety Violation',
            self::HARASSMENT => 'Harassment',
            self::DISCIPLINARY => 'Disciplinary',
            self::OTHER => 'Other',
        };
    }
}
