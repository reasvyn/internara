<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum SupervisionLogStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REVIEWED = 'reviewed';
    case ACKNOWLEDGED = 'acknowledged';
    case VERIFIED = 'verified';
    case COMPLETED = 'completed';

    public function isActive(): bool
    {
        return in_array($this, [self::DRAFT, self::SUBMITTED], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::REVIEWED, self::ACKNOWLEDGED, self::COMPLETED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('journals.status_draft'),
            self::SUBMITTED => __('journals.status_submitted'),
            self::REVIEWED => __('journals.status_reviewed'),
            self::ACKNOWLEDGED => __('journals.status_acknowledged'),
            self::VERIFIED => __('journals.status_verified'),
            self::COMPLETED => __('journals.status_completed'),
        };
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED],
            self::SUBMITTED => [self::REVIEWED, self::DRAFT],
            self::REVIEWED => [self::ACKNOWLEDGED, self::VERIFIED],
            self::ACKNOWLEDGED => [],
            self::VERIFIED => [self::COMPLETED],
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
