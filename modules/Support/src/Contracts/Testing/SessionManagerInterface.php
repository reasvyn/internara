<?php

declare(strict_types=1);

namespace Modules\Support\Contracts\Testing;

/**
 * Interface for managing persistent test sessions.
 *
 * S1 (Secure): Session data must be stored with integrity validation.
 * S2 (Sustain): Cleanup policies must prevent disk space exhaustion.
 * S3 (Scalable): Support for multiple concurrent sessions.
 */
interface SessionManagerInterface
{
    /**
     * Record the result of a test segment.
     *
     * @param array{output: string, errorOutput: string} $executionResult
     */
    public function record(string $module, string $type, bool $success, array $executionResult): void;

    /**
     * Check if a segment has passed and is still valid.
     */
    public function isPassed(string $module, string $type): bool;

    /**
     * Get all recorded results for the current session.
     *
     * @return array<int, array{module: string, type: string, success: bool, timestamp: string}>
     */
    public function getResults(): array;

    /**
     * Get the current session ID.
     */
    public function getSessionId(): string;

    /**
     * Clear the current session data.
     */
    public function clear(): void;

    /**
     * Clear all sessions older than the specified days.
     */
    public function cleanup(int $olderThanDays = 7): int;

    /**
     * Get session metadata including disk usage.
     *
     * @return array{sessionId: string, segmentCount: int, diskUsageBytes: int}
     */
    public function getMetadata(): array;
}
