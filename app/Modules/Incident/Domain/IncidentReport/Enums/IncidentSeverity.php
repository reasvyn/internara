<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Enums;

use App\Modules\Core\Contracts\LabelEnum;

enum IncidentSeverity: string implements LabelEnum
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('common.enums.low'),
            self::MEDIUM => __('common.enums.medium'),
            self::HIGH => __('common.enums.high'),
            self::CRITICAL => __('common.enums.critical'),
        };
    }
}
