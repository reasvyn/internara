<?php

declare(strict_types=1);

namespace Modules\Core\Academic\Support;

/**
 * Class AcademicYear
 *
 * Provides dynamic generation of academic year strings based on current date.
 */
final class AcademicYear
{
    /**
     * Get the current academic year string (YYYY/YYYY).
     *
     * Example: Feb 2026 -> "2025/2026" | Aug 2026 -> "2026/2027"
     */
    public static function current(): string
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        // Academic years typically transition in July
        if ($month < 7) {
            return $year - 1 . '/' . $year;
        }

        return $year . '/' . ($year + 1);
    }
}
