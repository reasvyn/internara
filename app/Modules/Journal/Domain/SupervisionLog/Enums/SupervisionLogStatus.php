<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\SupervisionLog\Enums;

use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\StatusEnum;

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
            self::DRAFT => __('journal.status_draft'),
            self::SUBMITTED => __('journal.status_submitted'),
            self::REVIEWED => __('journal.status_reviewed'),
            self::ACKNOWLEDGED => __('journal.status_acknowledged'),
            self::VERIFIED => __('journal.status_verified'),
            self::COMPLETED => __('journal.status_completed'),
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
