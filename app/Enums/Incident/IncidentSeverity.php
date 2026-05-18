<?php

declare(strict_types=1);

namespace App\Enums\Incident;

use App\Contracts\Shared\LabelEnum;

enum IncidentSeverity: string implements LabelEnum
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }
}
