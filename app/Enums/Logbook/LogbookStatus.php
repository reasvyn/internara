<?php

declare(strict_types=1);

namespace App\Enums\Logbook;

use App\Contracts\Shared\LabelEnum;

/**
 * Lifecycle states of a student's journal entry.
 */
enum LogbookStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case REVISION_REQUIRED = 'revision_required';

    public function isFinalized(): bool
    {
        return $this === self::VERIFIED;
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::SUBMITTED, self::REVISION_REQUIRED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::VERIFIED => 'Verified',
            self::REVISION_REQUIRED => 'Revision Required',
        };
    }
}
