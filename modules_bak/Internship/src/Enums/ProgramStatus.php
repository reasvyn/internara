<?php

declare(strict_types=1);

namespace Modules\Internship\Enums;

/**
 * Enum ProgramStatus
 *
 * Defines the lifecycle of an internship program.
 */
enum ProgramStatus: string
{
    case DRAFT = 'draft'; // Initial creation, not visible to students
    case PUBLISHED = 'published'; // Visible, but registration not yet open
    case OPEN = 'open'; // Registration active
    case ONGOING = 'ongoing'; // Program in progress, registration closed
    case COMPLETED = 'completed'; // Program finished
    case CLOSED = 'closed'; // Manually closed/cancelled
    case ARCHIVED = 'archived'; // Historical data

    /**
     * Get the visual color/variant for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'metadata',
            self::PUBLISHED => 'info',
            self::OPEN => 'success',
            self::ONGOING => 'primary',
            self::COMPLETED => 'secondary',
            self::CLOSED => 'warning',
            self::ARCHIVED => 'metadata',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return __('internship::status.program.' . $this->value);
    }
}
