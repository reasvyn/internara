<?php

declare(strict_types=1);

namespace App\Domain\Registration\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum AccountApplicationStatus: string implements LabelEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('registration.status.pending'),
            self::APPROVED => __('registration.status.approved'),
            self::REJECTED => __('registration.status.rejected'),
        };
    }
}
