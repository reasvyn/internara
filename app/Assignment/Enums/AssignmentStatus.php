<?php

declare(strict_types=1);

namespace App\Assignment\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum AssignmentStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CLOSED = 'closed';

    public function isActive(): bool
    {
        return $this === self::PUBLISHED;
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::PUBLISHED => __('Published'),
            self::CLOSED => __('Closed'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::CLOSED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::CLOSED],
            self::PUBLISHED => [self::CLOSED],
            self::CLOSED => [],
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
