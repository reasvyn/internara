<?php

declare(strict_types=1);

namespace App\Enums\Internship;

use App\Contracts\Shared\LabelEnum;

enum PlacementChangeStatus: string implements LabelEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], true);
    }
}
