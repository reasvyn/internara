<?php

declare(strict_types=1);

namespace App\Enums\Assignment;

use App\Contracts\Shared\LabelEnum;

/**
 * Lifecycle states of a student's assignment submission.
 */
enum SubmissionStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case GRADED = 'graded';
    case REVISION_REQUIRED = 'revision_required';

    public function isFinalized(): bool
    {
        return in_array($this, [self::VERIFIED, self::GRADED], true);
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
            self::GRADED => 'Graded',
            self::REVISION_REQUIRED => 'Revision Required',
        };
    }
}
