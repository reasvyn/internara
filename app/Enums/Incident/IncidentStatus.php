<?php

declare(strict_types=1);

namespace App\Enums\Incident;

use App\Contracts\Shared\LabelEnum;

enum IncidentStatus: string implements LabelEnum
{
    case REPORTED = 'reported';
    case INVESTIGATING = 'investigating';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::REPORTED => 'Reported',
            self::INVESTIGATING => 'Investigating',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::CLOSED;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::REPORTED => in_array($target, [self::INVESTIGATING, self::RESOLVED], true),
            self::INVESTIGATING => in_array($target, [self::RESOLVED, self::CLOSED], true),
            self::RESOLVED => $target === self::CLOSED,
            self::CLOSED => false,
        };
    }
}
