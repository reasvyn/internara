<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Enums;

use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\StatusEnum;

enum RegistrationStatus: string implements LabelEnum, StatusEnum
{
    case PREPARATION = 'preparation';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PREPARATION => __('registration.status.preparation'),
            self::PENDING => __('registration.status.pending'),
            self::CONFIRMED => __('registration.status.confirmed'),
            self::ACTIVE => __('registration.status.active'),
            self::COMPLETED => __('registration.status.completed'),
            self::REJECTED => __('registration.status.rejected'),
        };
    }

    public function isPreparation(): bool
    {
        return $this === self::PREPARATION;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isTerminal(): bool
    {
        return $this === self::COMPLETED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PREPARATION => [self::PENDING, self::CONFIRMED],
            self::PENDING => [self::CONFIRMED, self::REJECTED],
            self::CONFIRMED => [self::ACTIVE, self::REJECTED],
            self::ACTIVE => [self::COMPLETED],
            self::REJECTED => [self::PREPARATION, self::PENDING],
            self::COMPLETED => [],
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
