<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle states of an internship program.
 */
enum InternshipStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function isAcceptingRegistrations(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ACTIVE], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
