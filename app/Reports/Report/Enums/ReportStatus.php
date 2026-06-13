<?php

declare(strict_types=1);

namespace App\Reports\Report\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum ReportStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REVISION_REQUIRED = 'revision_required';
    case FINALIZED = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::SUBMITTED => __('Submitted'),
            self::APPROVED => __('Approved'),
            self::REVISION_REQUIRED => __('Revision Required'),
            self::FINALIZED => __('Finalized'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::FINALIZED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED, self::FINALIZED],
            self::SUBMITTED => [self::APPROVED, self::REVISION_REQUIRED],
            self::APPROVED => [self::FINALIZED],
            self::REVISION_REQUIRED => [self::DRAFT],
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
