<?php

declare(strict_types=1);

namespace Modules\Assessment\Services\Contracts;

/**
 * Interface ComplianceService
 *
 * Orchestrates participation-driven scoring based on Attendance and Journal evidence.
 */
interface ComplianceService
{
    /**
     * Calculate compliance metrics and final score for a specific registration.
     *
     * @return array{
     *     attendance_score: float,
     *     journal_score: float,
     *     final_score: float,
     *     total_days: int,
     *     attended_days: int,
     *     approved_journals: int
     * }
     */
    public function calculateScore(string $registrationId): array;
}
