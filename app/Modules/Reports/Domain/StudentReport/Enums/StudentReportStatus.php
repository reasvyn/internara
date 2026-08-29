<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Enums;

use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Core\Contracts\StatusEnum;

enum StudentReportStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('report.status_draft'),
            self::FINALIZED => __('report.status_finalized'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::FINALIZED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::FINALIZED],
            self::FINALIZED => [],
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
