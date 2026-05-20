<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Enums;

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

enum PartnershipStatus: string implements LabelEnum, StatusEnum
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::TERMINATED => 'Terminated',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::EXPIRED, self::TERMINATED], true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::ACTIVE => [self::EXPIRED, self::TERMINATED],
            self::EXPIRED => [],
            self::TERMINATED => [],
        };
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! $target instanceof self) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }
}
