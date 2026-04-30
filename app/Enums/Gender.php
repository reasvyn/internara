<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Gender values for user profiles.
 */
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
        };
    }
}
