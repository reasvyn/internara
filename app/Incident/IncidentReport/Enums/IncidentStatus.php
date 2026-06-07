<?php

declare(strict_types=1);

namespace App\Incident\IncidentReport\Enums;

use App\Core\Contracts\StatusEnum;

enum IncidentStatus: string implements StatusEnum
{
    case REPORTED = 'reported';
    case INVESTIGATING = 'investigating';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::REPORTED => __('Reported'),
            self::INVESTIGATING => __('Investigating'),
            self::RESOLVED => __('Resolved'),
            self::CLOSED => __('Closed'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::CLOSED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::REPORTED => [self::INVESTIGATING, self::RESOLVED],
            self::INVESTIGATING => [self::RESOLVED, self::CLOSED],
            self::RESOLVED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! ($target instanceof self)) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }
}
