<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Processing states of an absence request.
 */
enum AbsenceRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function isProcessed(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}
