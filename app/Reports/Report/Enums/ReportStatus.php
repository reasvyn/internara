<?php

declare(strict_types=1);

namespace App\Reports\Report\Enums;

use App\Core\Contracts\LabelEnum;
use App\Core\Contracts\StatusEnum;

enum ReportStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REVISION_REQUIRED = 'revision_required';
    case APPROVED = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::SUBMITTED => __('Submitted'),
            self::REVISION_REQUIRED => __('Revision Required'),
            self::APPROVED => __('Approved'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::APPROVED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED],
            self::SUBMITTED => [self::APPROVED, self::REVISION_REQUIRED],
            self::REVISION_REQUIRED => [self::DRAFT],
            self::APPROVED => [],
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
