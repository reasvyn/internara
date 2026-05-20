<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Enums;

use App\Domain\Core\Contracts\StatusEnum;

enum SupervisionLogStatus: string implements StatusEnum
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS, self::SUBMITTED], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::VERIFIED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::SUBMITTED => 'Submitted',
            self::VERIFIED => 'Verified',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::SUBMITTED, self::CANCELLED],
            self::SUBMITTED => [self::VERIFIED, self::COMPLETED, self::CANCELLED],
            self::VERIFIED => [self::COMPLETED],
            self::COMPLETED => [],
            self::CANCELLED => [],
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
