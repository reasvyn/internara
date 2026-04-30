<?php

declare(strict_types=1);

namespace Modules\Support\Contracts\Testing;

use Illuminate\Console\OutputStyle;

/**
 * Interface for reporting test results in various formats.
 *
 * S1 (Secure): Sensitive output must be masked in reports.
 * S2 (Sustain): Clear, readable output that explains WHY things failed.
 * S3 (Scalable): Support for CI/CD integration formats (JUnit, JSON).
 */
interface ResultReporterInterface
{
    /**
     * Set the output style for console reporting.
     */
    public function setOutput(OutputStyle $output): self;

    /**
     * Display the modular verification matrix.
     *
     * @param array<int, array{module: string, Arch?: string, Unit?: string, Feature?: string, Browser?: string, total: float}> $results
     */
    public function displayMatrix(array $results): void;

    /**
     * Display performance metrics.
     */
    public function displayPerformance(int $totalSegments, int $passedSegments, float $duration): void;

    /**
     * Display failure details.
     *
     * @param array<int, array{label: string, output: string, error: string}> $failures
     */
    public function displayFailures(array $failures): void;

    /**
     * Display session metrics.
     *
     * @param array<int, array{module: string, type: string, success: bool, timestamp: string}> $sessionResults
     * @return float Global pass rate (0-100)
     */
    public function displaySessionMetrics(string $sessionId, array $sessionResults, int $totalPossibleSegments): float;

    /**
     * Export results to JUnit XML format.
     */
    public function exportToJUnit(string $filePath, array $results, string $sessionId): void;

    /**
     * Export results to JSON format.
     */
    public function exportToJSON(string $filePath, array $results, string $sessionId): void;

    /**
     * Display code coverage summary from Pest output.
     */
    public function displayCoverageSummary(string $output): void;
}
