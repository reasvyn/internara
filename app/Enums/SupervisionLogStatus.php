<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Processing states of a supervision log.
 */
enum SupervisionLogStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
