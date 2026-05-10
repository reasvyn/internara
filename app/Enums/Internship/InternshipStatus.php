<?php

declare(strict_types=1);

namespace App\Enums\Internship;

use App\Contracts\Shared\LabelEnum;

/**
 * Lifecycle states of an internship program.
 */
enum InternshipStatus: string implements LabelEnum
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

    /**
     * @return list<self>
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::CANCELLED],
            self::PUBLISHED => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->validTransitions(), true);
    }
}
