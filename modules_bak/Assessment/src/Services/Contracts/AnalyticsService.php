<?php

declare(strict_types=1);

namespace Modules\Assessment\Services\Contracts;

/**
 * Interface AnalyticsService
 *
 * Provides aggregated visual data for competency achievements and participation trends.
 */
interface AnalyticsService
{
    /**
     * Get aggregated competency achievement stats for a registration.
     *
     * @param string $registrationId The registration UUID.
     *
     * @return array<string, mixed> Data structured for visualization.
     */
    public function getCompetencyStats(string $registrationId): array;

    /**
     * Get participation trends (Attendance vs Journals) for a registration.
     *
     * @param string $registrationId The registration UUID.
     *
     * @return array<string, mixed> Data structured for trend analysis.
     */
    public function getParticipationTrends(string $registrationId): array;
}
