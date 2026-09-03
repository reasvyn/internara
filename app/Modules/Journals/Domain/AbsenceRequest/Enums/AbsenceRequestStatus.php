<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Enums;

use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\StatusEnum;

enum AbsenceRequestStatus: string implements LabelEnum, StatusEnum
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
            self::PENDING => __('common.enums.pending'),
            self::APPROVED => __('common.enums.approved'),
            self::REJECTED => __('common.enums.rejected'),
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED => [],
            self::REJECTED => [],
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
