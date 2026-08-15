<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Support;

use DateTimeInterface;

/**
 * Computes the academic-year period containing a given date.
 *
 * The Indonesian school year runs July–June: a date in January–June belongs to
 * the `Y-1/Y` period, a date in July–December to the `Y/Y+1` period. This is
 * the single source of truth shared by the seeders and the settings UI.
 */
final class AcademicYearPeriod
{
    /**
     * Returns the school-year name containing the given date (e.g. "2025/2026").
     */
    public static function nameFor(DateTimeInterface $date): string
    {
        [$startYear, $endYear] = self::yearsFor($date);

        return "{$startYear}/{$endYear}";
    }

    /**
     * Returns the start date of the school year containing the given date (July 1).
     */
    public static function startDateFor(DateTimeInterface $date): string
    {
        [$startYear] = self::yearsFor($date);

        return "{$startYear}-07-01";
    }

    /**
     * Returns the end date of the school year containing the given date (June 30).
     */
    public static function endDateFor(DateTimeInterface $date): string
    {
        [, $endYear] = self::yearsFor($date);

        return "{$endYear}-06-30";
    }

    /**
     * Returns the [startYear, endYear] pair of the school year containing the given date.
     *
     * @return array{0: int, 1: int}
     */
    public static function yearsFor(DateTimeInterface $date): array
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        return $month <= 6
            ? [$year - 1, $year]
            : [$year, $year + 1];
    }
}
