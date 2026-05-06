<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Shared\Contracts\LabelEnum;

/**
 * Blood type values for user profiles.
 */
enum BloodType: string implements LabelEnum
{
    case A = 'A';
    case B = 'B';
    case AB = 'AB';
    case O = 'O';

    /**
     * Get the human-readable label for the blood type.
     */
    public function label(): string
    {
        return $this->value;
    }
}
