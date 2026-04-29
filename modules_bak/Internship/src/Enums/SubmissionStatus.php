<?php

declare(strict_types=1);

namespace Modules\Internship\Enums;

/**
 * Enum SubmissionStatus
 *
 * Defines the possible states of a requirement submission.
 */
enum SubmissionStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    /**
     * Get the human-readable label for the submission status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('internship::requirement.status.draft'),
            self::PENDING => __('internship::requirement.status.pending'),
            self::VERIFIED => __('internship::requirement.status.verified'),
            self::REJECTED => __('internship::requirement.status.rejected'),
        };
    }

    /**
     * Get the color associated with the status (for UI).
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'warning',
            self::VERIFIED => 'success',
            self::REJECTED => 'error',
        };
    }
}
