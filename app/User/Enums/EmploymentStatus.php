<?php

declare(strict_types=1);

namespace App\User\Enums;

use App\Core\Contracts\LabelEnum;

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
            self::FULL_TIME => __('user.employment.full_time'),
            self::PART_TIME => __('user.employment.part_time'),
            self::CONTRACT => __('user.employment.contract'),
            self::TEMPORARY => __('user.employment.temporary'),
            self::VOLUNTEER => __('user.employment.volunteer'),
        };
    }

    /** @return list<array{id: string, name: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'id' => $case->value,
                'name' => $case->label(),
            ],
            self::cases(),
        );
    }
}
