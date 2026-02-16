<?php

declare(strict_types=1);

namespace Modules\Profile\Enums;

/**
 * Enum Gender
 *
 * Represents the biological sex of a person.
 */
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    /**
     * Get the human-readable label for the gender.
     */
    public function label(): string
    {
        return match ($this) {
            self::MALE => __('profile::enums.gender.male'),
            self::FEMALE => __('profile::enums.gender.female'),
        };
    }
}
