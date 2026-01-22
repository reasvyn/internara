<?php

declare(strict_types=1);

namespace Modules\Core\Services\Contracts;

/**
 * Interface AnalyticsAggregator
 *
 * Provides high-level institutional analytics by aggregating data from various modules.
 */
interface AnalyticsAggregator
{
    /**
     * Get institutional summary metrics.
     *
     * @return array{total_interns: int, active_partners: int, placement_rate: float}
     */
    public function getInstitutionalSummary(): array;

    /**
     * Identify students who are "At-Risk" based on engagement metrics.
     *
     * @param int $limit
     * @return array<array{student_name: string, reason: string, risk_level: string}>
     */
    public function getAtRiskStudents(int $limit = 5): array;
}
