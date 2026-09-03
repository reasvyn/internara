<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Enums;

use App\Modules\Core\Contracts\LabelEnum;

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
            self::ACCIDENT => __('common.enums.accident'),
            self::SAFETY_VIOLATION => __('common.enums.safety_violation'),
            self::HARASSMENT => __('common.enums.harassment'),
            self::DISCIPLINARY => __('common.enums.disciplinary'),
            self::OTHER => __('common.enums.other'),
        };
    }
}
