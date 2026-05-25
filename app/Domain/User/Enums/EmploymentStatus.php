<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum EmploymentStatus: string implements LabelEnum
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case TEMPORARY = 'temporary';
    case VOLUNTEER = 'volunteer';

    public function label(): string
    {
        return match ($this) {
            self::FULL_TIME => __('Full-time'),
            self::PART_TIME => __('Part-time'),
            self::CONTRACT => __('Contract'),
            self::TEMPORARY => __('Temporary'),
            self::VOLUNTEER => __('Volunteer'),
        };
    }
}
