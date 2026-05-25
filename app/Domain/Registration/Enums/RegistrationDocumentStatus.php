<?php

declare(strict_types=1);

namespace App\Domain\Registration\Enums;

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

enum RegistrationDocumentStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::VERIFIED => __('Verified'),
            self::REJECTED => __('Rejected'),
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

    public function isTerminal(): bool
    {
        return in_array($this, [self::VERIFIED, self::REJECTED], true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::VERIFIED, self::REJECTED],
            self::VERIFIED => [],
            self::REJECTED => [],
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
