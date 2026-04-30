<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle states of an assignment.
 */
enum AssignmentStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CLOSED = 'closed';

    public function isActive(): bool
    {
        return $this === self::PUBLISHED;
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::CLOSED => 'Closed',
        };
    }
}
