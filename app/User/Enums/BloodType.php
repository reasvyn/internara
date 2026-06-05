<?php

declare(strict_types=1);

namespace App\User\Enums;

use App\Core\Contracts\LabelEnum;

/**
 * Blood type values for user profiles.
 */
enum BloodType: string implements LabelEnum
{
    case A = 'a';
    case B = 'b';
    case AB = 'ab';
    case O = 'o';

    /**
     * Get the human-readable label for the blood type.
     */
    public function label(): string
    {
        return $this->value;
    }
}
