<?php

declare(strict_types=1);

namespace App\Enums\Report;

use App\Contracts\Shared\LabelEnum;

enum ReportStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REVISION_REQUIRED = 'revision_required';
    case APPROVED = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::REVISION_REQUIRED => 'Revision Required',
            self::APPROVED => 'Approved',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::APPROVED;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => $target === self::SUBMITTED,
            self::SUBMITTED => in_array($target, [self::APPROVED, self::REVISION_REQUIRED], true),
            self::REVISION_REQUIRED => $target === self::DRAFT,
            self::APPROVED => false,
        };
    }
}
