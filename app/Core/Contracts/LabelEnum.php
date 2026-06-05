<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Contract for enums that provide human-readable labels.
 *
 * Implemented by all status, type, and category enums across modules.
 */
interface LabelEnum
{
    public function label(): string;
}
