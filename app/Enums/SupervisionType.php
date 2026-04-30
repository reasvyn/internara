<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types of supervision activities.
 */
enum SupervisionType: string
{
    case GUIDANCE = 'guidance';
    case MENTORING = 'mentoring';
    case MONITORING = 'monitoring';

    public function label(): string
    {
        return match ($this) {
            self::GUIDANCE => 'Bimbingan',
            self::MENTORING => 'Mentoring',
            self::MONITORING => 'Monitoring',
        };
    }
}
