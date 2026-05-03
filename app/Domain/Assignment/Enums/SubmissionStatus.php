<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Enums;

/**
 * Lifecycle states of a student's assignment submission.
 */
enum SubmissionStatus: string
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
