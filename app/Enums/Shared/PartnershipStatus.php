<?php

declare(strict_types=1);

namespace App\Enums\Shared;

use App\Contracts\Shared\LabelEnum;

enum PartnershipStatus: string implements LabelEnum
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
}
