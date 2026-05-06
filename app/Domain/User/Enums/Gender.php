<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Shared\Contracts\LabelEnum;

/**
 * Gender values for user profiles.
 */
enum Gender: string implements LabelEnum
{
    case MALE = 'male';
    case FEMALE = 'female';

    /**
     * Get the human-readable label for the gender.
     */
    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
        };
    }
}
