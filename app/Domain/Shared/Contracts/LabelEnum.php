<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Contract for enums that provide human-readable labels.
 *
 * Implemented by all status, type, and category enums across domains.
 */
interface LabelEnum
{
    public function label(): string;
}
