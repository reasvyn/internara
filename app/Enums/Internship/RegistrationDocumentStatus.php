<?php

declare(strict_types=1);

namespace App\Enums\Internship;

use App\Contracts\Shared\LabelEnum;

enum RegistrationDocumentStatus: string implements LabelEnum
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }
}
