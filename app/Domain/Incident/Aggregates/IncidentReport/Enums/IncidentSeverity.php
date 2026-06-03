<?php

declare(strict_types=1);

namespace App\Domain\Incident\Aggregates\IncidentReport\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum IncidentSeverity: string implements LabelEnum
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('Low'),
            self::MEDIUM => __('Medium'),
            self::HIGH => __('High'),
            self::CRITICAL => __('Critical'),
        };
    }
}
