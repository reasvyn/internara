<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum PartnershipStatus: string implements LabelEnum, StatusEnum
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('Active'),
            self::EXPIRED => __('Expired'),
            self::TERMINATED => __('Terminated'),
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
        if (! ($target instanceof self)) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }
}
