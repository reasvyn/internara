<?php

declare(strict_types=1);

namespace Modules\Support\Contracts\Testing;

/**
 * Interface for orchestrating modular test execution.
 *
 * S1 (Secure): Each segment runs in isolated process with no cross-contamination.
 * S2 (Sustain): Clear progress reporting and resumable sessions.
 * S3 (Scalable): Support for 29+ modules with parallel execution.
 */
interface OrchestratorInterface
{
    /**
     * Execute all test segments with the given options.
     *
     * @param array<string, mixed> $options
     *
     * @return array{success: bool, results: array, failures: array, duration: float}
     */
    public function execute(array $options = []): array;

    /**
     * List all identified test segments without executing them.
     *
     * @return array<int, array{label: string, path: string}>
     */
    public function listSegments(array $options = []): array;

    /**
     * Display report from the current or latest session.
     *
     * @return float Pass rate percentage
     */
    public function report(): float;

    /**
     * Clear all persistent session data.
     */
    public function clearSessions(): void;

    /**
     * Evaluate if the stability meets the required threshold.
     *
     * @return int Exit code (0 = success, 1 = failure)
     */
    public function evaluateStability(float $passRate, float $threshold): int;
}
