<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Blood type values for user profiles.
 */
enum BloodType: string
{
    case A = 'A';
    case B = 'B';
    case AB = 'AB';
    case O = 'O';

    public function label(): string
    {
        return $this->value;
    }
}
