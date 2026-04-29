<?php

declare(strict_types=1);

namespace Modules\Core\Academic\Support;

use Illuminate\Support\Facades\Config;

/**
 * Class AcademicYear
 *
 * Provides dynamic generation of academic year strings based on current date.
 * Transition month is configurable via Core config (default: July/7).
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

        // Get configurable transition month (default: July = 7)
        $transitionMonth = (int) Config::get('core.academic_year_transition_month', 7);

        if ($month < $transitionMonth) {
            return $year - 1 . '/' . $year;
        }

        return $year . '/' . ($year + 1);
    }
}
