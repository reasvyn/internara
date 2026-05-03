<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Enums;

/**
 * Types of supervision activities.
 */
enum SupervisionType: string
{
    case GUIDANCE = 'guidance';
    case SUPERVISORING = 'mentoring';
    case MONITORING = 'monitoring';

    public function label(): string
    {
        return match ($this) {
            self::GUIDANCE => 'Bimbingan',
            self::SUPERVISORING => 'Mentoring',
            self::MONITORING => 'Monitoring',
        };
    }
}
