<?php

declare(strict_types=1);

namespace Modules\Internship\Enums;

/**
 * Enum Semester
 *
 * Defines the standard academic semesters supported by the institutional framework.
 */
enum Semester: string
{
    case ODD = 'Ganjil';
    case EVEN = 'Genap';
    case FULL = 'Tahunan';

    /**
     * Get the localized label for the semester.
     */
    public function label(): string
    {
        return match ($this) {
            self::ODD => __('internship::ui.semester_odd'),
            self::EVEN => __('internship::ui.semester_even'),
            self::FULL => __('internship::ui.semester_full'),
        };
    }
}
