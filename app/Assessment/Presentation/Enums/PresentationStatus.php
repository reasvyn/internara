<?php

declare(strict_types=1);

namespace App\Assessment\Presentation\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum PresentationStatus: string implements LabelEnum, StatusEnum
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => __('Scheduled'),
            self::COMPLETED => __('Completed'),
            self::CANCELLED => __('Cancelled'),
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::SCHEDULED => [self::COMPLETED, self::CANCELLED],
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
