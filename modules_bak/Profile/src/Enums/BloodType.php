<?php

declare(strict_types=1);

namespace Modules\Profile\Enums;

/**
 * Enum BloodType
 *
 * Represents the human blood group types.
 */
enum BloodType: string
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
