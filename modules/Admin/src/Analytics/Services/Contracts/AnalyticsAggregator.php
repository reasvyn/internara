<?php

declare(strict_types=1);

namespace Modules\Admin\Analytics\Services\Contracts;

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
     * @return array<array{student_name: string, reason: string, risk_level: string}>
     */
    public function getAtRiskStudents(int $limit = 5): array;

    /**
     * Get a summary of recent security-related events.
     *
     * @return array{failed_logins: int, throttled_attempts: int, suspicious_activities: array}
     */
    public function getSecuritySummary(): array;

    /**
     * Get the current status of the application infrastructure.
     *
     * @return array{queue_pending: int, queue_failed: int, db_size: string, last_backup: ?string}
     */
    public function getInfrastructureStatus(): array;

    /**
     * Get user distribution statistics by role and activity.
     *
     * @return array{by_role: array<string, int>, active_sessions: int}
     */
    public function getUserDistribution(): array;
}
