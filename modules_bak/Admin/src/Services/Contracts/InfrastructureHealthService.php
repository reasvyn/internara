<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

/**
 * Interface InfrastructureHealthService
 *
 * Provides a standardized contract for monitoring the health and performance
 * of the underlying system infrastructure.
 */
interface InfrastructureHealthService
{
    /**
     * Get the current status of background worker queues.
     */
    public function getQueueStatus(): array;

    /**
     * Get the current database storage utilization.
     */
    public function getDatabaseSize(): string;

    /**
     * Get the count of active user sessions within the last 15 minutes.
     */
    public function getActiveSessionCount(): int;

    /**
     * Get the timestamp of the last successful system backup.
     */
    public function getLastBackupTimestamp(): ?string;
}
