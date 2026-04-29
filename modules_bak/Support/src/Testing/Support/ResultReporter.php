<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Modules\Support\Contracts\Testing\ResultReporterInterface;

/**
 * Handles formatting and displaying test results in various formats.
 *
 * S1 (Secure): Sensitive output must be masked in reports.
 * S2 (Sustain): Clear, readable output that explains WHY things failed.
 * S3 (Scalable): Support for CI/CD integration formats (JUnit, JSON).
 */
class ResultReporter implements ResultReporterInterface
{
    protected OutputStyle $output;

    protected const STABILITY_STABLE = 100.0;
    protected const STABILITY_REFINING = 80.0;
    protected const STABILITY_UNSTABLE = 50.0;

    public function __construct(OutputStyle $output)
    {
        $this->output = $output;
    }

    /**
     * Set the output style for console reporting.
     */
    public function setOutput(OutputStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Display the modular verification matrix.
     *
     * @param array<int, array{module: string, Arch?: string, Unit?: string, Feature?: string, Browser?: string, total: float}> $results
     */
    public function displayMatrix(array $results): void
    {
        $this->output->info('Section 1: Modular Verification Matrix');

        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                $row['module'] ?? 'Unknown',
                $row['Arch'] ?? '-',
                $row['Unit'] ?? '-',
                $row['Feature'] ?? '-',
                $row['Browser'] ?? '-',
                number_format($row['total'] ?? 0, 2) . 's',
            ];
        }

        $this->renderTable(['Module', 'Arch', 'Unit', 'Feature', 'Browser', 'Total'], $rows);
    }

    /**
     * Display performance metrics.
     */
    public function displayPerformance(int $totalSegments, int $passedSegments, float $duration): void
    {
        $failedSegments = $totalSegments - $passedSegments;
        $peakMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';

        $this->output->info('Section 2: High-Fidelity Performance Metrics');
        $this->renderTable(
            ['Metric', 'Value'],
            [
                ['Total Segments Processed', $totalSegments],
                ['Successful (Green)', "<fg=green>{$passedSegments}</>"],
                ['Failed (Red)', $failedSegments > 0 ? "<fg=red>{$failedSegments}</>" : '<fg=green>0</>'],
                ['Total Execution Time', number_format($duration, 2) . ' s'],
                ['Orchestrator Peak Memory', $peakMemory],
            ],
        );
    }

    /**
     * Display failure details.
     *
     * @param array<int, array{label: string, output: string, error: string}> $failures
     */
    public function displayFailures(array $failures): void
    {
        if (empty($failures)) {
            return;
        }

        $this->output->warn('Section 3: Failure Traceability (Forensic View)');
        foreach ($failures as $failure) {
            $this->output->block(
                "FAIL: {$failure['label']}",
                'FAIL',
                'fg=white;bg=red',
                ' ',
                true,
            );

            if (!empty($failure['error'])) {
                $this->output->error($failure['error']);
            }
            if (!empty($failure['output'])) {
                $this->output->text("<fg=gray>{$failure['output']}</>");
            }
        }
    }

    /**
     * Display session metrics.
     *
     * @param array<int, array{module: string, type: string, success: bool, timestamp: string}> $sessionResults
     * @return float Global pass rate (0-100)
     */
    public function displaySessionMetrics(string $sessionId, array $sessionResults, int $totalPossibleSegments): float
    {
        $this->output->info('Module-Aware Testing Session Report');
        $this->output->text("Session ID: <fg=yellow>{$sessionId}</>");

        $grouped = [];
        $passedSegments = 0;
        $latestTimestamp = null;

        foreach ($sessionResults as $result) {
            $module = $result['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'Arch' => '-',
                    'Unit' => '-',
                    'Feature' => '-',
                    'Browser' => '-',
                ];
            }

            $status = $result['success'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            $grouped[$module][$result['type']] = $status;

            if ($result['success']) {
                $passedSegments++;
            }

            if (!$latestTimestamp || $result['timestamp'] > $latestTimestamp) {
                $latestTimestamp = $result['timestamp'];
            }
        }

        $rows = [];
        foreach ($grouped as $module => $types) {
            $rows[] = [
                $module,
                $types['Arch'],
                $types['Unit'],
                $types['Feature'],
                $types['Browser'],
            ];
        }

        $this->renderTable(['Module', 'Arch', 'Unit', 'Feature', 'Browser'], $rows);

        // Actual Global Pass Rate Calculation
        $passRate = $totalPossibleSegments > 0
            ? ($passedSegments / $totalPossibleSegments) * 100
            : 0;

        $stability = match (true) {
            $passRate >= self::STABILITY_STABLE => '<fg=green;options=bold>STABLE</>',
            $passRate >= self::STABILITY_REFINING => '<fg=blue>REFINING</>',
            $passRate >= self::STABILITY_UNSTABLE => '<fg=yellow>UNSTABLE</>',
            default => '<fg=red;options=bold>CRITICAL</>',
        };

        $this->output->info('System Stability Metrics');
        $this->output->definitionList(
            ['Total System Segments', (string) $totalPossibleSegments],
            ['Segments Verified', "<fg=green>{$passedSegments}</>"],
            ['Last Execution Date', $latestTimestamp ? Carbon::parse($latestTimestamp)->diffForHumans() : 'Unknown'],
            ['Global Pass Rate', number_format($passRate, 2) . '%'],
            ['Stability Index', $stability],
        );

        return (float) $passRate;
    }

    /**
     * Export results to JUnit XML format.
     */
    public function exportToJUnit(string $filePath, array $results, string $sessionId): void
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $testsuites = $dom->createElement('testsuites');
        $testsuites->setAttribute('name', 'Internara Modular Verification');
        $testsuites->setAttribute('id', $sessionId);
        $dom->appendChild($testsuites);

        $grouped = [];
        foreach ($results as $result) {
            $grouped[$result['module']][] = $result;
        }

        foreach ($grouped as $module => $segments) {
            $testsuite = $dom->createElement('testsuite');
            $testsuite->setAttribute('name', $module);

            $tests = 0;
            $failures = 0;

            foreach ($segments as $segment) {
                $tests++;
                $testcase = $dom->createElement('testcase');
                $testcase->setAttribute('name', "{$module}: {$segment['type']}");
                $testcase->setAttribute('classname', "Modules\\{$module}\\{$segment['type']}");

                if (!($segment['success'] ?? false)) {
                    $failures++;
                    $failure = $dom->createElement('failure');
                    $failure->setAttribute('message', "Test segment {$segment['type']} failed.");
                    $failure->nodeValue = htmlspecialchars(
                        $segment['error'] ?? $segment['output'] ?? 'No output',
                    );
                    $testcase->appendChild($failure);
                }

                $testsuite->appendChild($testcase);
            }

            $testsuite->setAttribute('tests', (string) $tests);
            $testsuite->setAttribute('failures', (string) $failures);
            $testsuites->appendChild($testsuite);
        }

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $dom->saveXML());

        $this->output->info("JUnit XML report exported to: {$filePath}");
    }

    /**
     * Export results to JSON format.
     */
    public function exportToJSON(string $filePath, array $results, string $sessionId): void
    {
        $data = [
            'session_id' => $sessionId,
            'exported_at' => now()->toIso8601String(),
            'summary' => [
                'total_segments' => count($results),
                'passed' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
                'failed' => count(array_filter($results, fn($r) => !($r['success'] ?? false))),
            ],
            'results' => $results,
        ];

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->output->info("JSON report exported to: {$filePath}");
    }

    /**
     * Display code coverage summary from Pest output.
     */
    public function displayCoverageSummary(string $output): void
    {
        if (!str_contains($output, 'Lines:')) {
            return;
        }

        $this->output->info('Section 4: Code Coverage Insights');

        preg_match('/Lines:\s+(\d+\.\d+)%/', $output, $lines);
        preg_match('/Methods:\s+(\d+\.\d+)%/', $output, $methods);

        if (isset($lines[1])) {
            $rate = (float) $lines[1];
            $color = $rate >= 80 ? 'green' : ($rate >= 50 ? 'yellow' : 'red');
            $this->output->text("Line Coverage: <fg={$color}>{$lines[1]}%</>");
        }

        if (isset($methods[1])) {
            $this->output->text("Method Coverage: {$methods[1]}%");
        }
    }

    /**
     * Render a table using Symfony Table helper.
     */
    protected function renderTable(array $headers, array $rows): void
    {
        $table = new Table(new ConsoleOutput());
        $table->setHeaders($headers)->setRows($rows)->render();
    }
}
