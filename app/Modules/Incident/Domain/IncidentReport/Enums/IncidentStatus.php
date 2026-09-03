<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Enums;

use App\Modules\Core\Contracts\StatusEnum;

enum IncidentStatus: string implements StatusEnum
{
    case REPORTED = 'reported';
    case INVESTIGATING = 'investigating';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::REPORTED => __('common.enums.reported'),
            self::INVESTIGATING => __('common.enums.investigating'),
            self::RESOLVED => __('common.enums.resolved'),
            self::CLOSED => __('common.enums.closed'),
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
