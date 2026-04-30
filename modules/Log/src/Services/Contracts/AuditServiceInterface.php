<?php

declare(strict_types=1);

namespace Modules\Log\Services\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for comprehensive audit services across all 29+ modules.
 *
 * S1 (Secure): All PII must be masked before persistence.
 * S2 (Sustain): Clear audit trails with forensic capabilities.
 * S3 (Scalable): Support for 29+ modules with event-driven architecture.
 */
interface AuditServiceInterface
{
    /**
     * Log an audit event with automatic PII masking.
     *
     * @param array<string, mixed> $payload Data to log (will be masked)
     */
    public function log(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?array $payload = null,
    ): void;

    /**
     * Log a critical system event (security-related).
     */
    public function logSecurity(string $action, array $payload = []): void;

    /**
     * Log a data modification with before/after comparison.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    public function logDataChange(
        string $subjectType,
        string $subjectId,
        array $oldValues,
        array $newValues,
        string $action = 'updated',
    ): void;

    /**
     * Query audit logs with filters.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, array>
     */
    public function query(array $filters = []): Collection;

    /**
     * Get audit statistics for a specific module.
     */
    public function getModuleStats(string $moduleName, ?int $days = 30): array;

    /**
     * Export audit logs for compliance reporting.
     *
     * @param array<string, mixed> $filters
     */
    public function exportForCompliance(array $filters = []): string;

    /**
     * Purge old audit logs based on retention policy.
     */
    public function purgeOldLogs(?int $retentionDays = null): int;

    /**
     * Verify audit trail integrity for forensic analysis.
     */
    public function verifyIntegrity(): array;

    /**
     * Get all auditable modules with their event counts.
     *
     * @return array<string, array{events: int, last_activity: ?string}>
     */
    public function getAuditableModules(): array;
}
