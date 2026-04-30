<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Carbon\Carbon;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Handles formatting and displaying test results and metrics in the console.
 */
class TestReporter
{
    public function __construct(protected Factory $components) {}

    /**
     * Display the modular verification matrix table.
     */
    public function displayMatrix(array $results): void
    {
        $this->components->info('Section 1: Modular Verification Matrix');

        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                $row['module'],
                $row['Arch'] ?? '-',
                $row['Unit'] ?? '-',
                $row['Feature'] ?? '-',
                $row['Browser'] ?? '-',
                number_format($row['total'] ?? 0, 2) . 's',
            ];
        }

        $this->table(['Module', 'Arch', 'Unit', 'Feature', 'Browser', 'Total'], $rows);
    }

    /**
     * Display comprehensive session metrics and stability index.
     *
     * @return float The global pass rate (0-100)
     */
    public function displaySessionMetrics(
        string $sessionId,
        array $sessionResults,
        int $totalPossibleSegments,
    ): float {
        $this->components->info('Module-Aware Testing Session Report');
        $this->components->twoColumnDetail('Session ID', $sessionId);

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

        $this->table(['Module', 'Arch', 'Unit', 'Feature', 'Browser'], $rows);

        // Actual Global Pass Rate Calculation: compared to 100% system baseline
        $passRate =
            $totalPossibleSegments > 0 ? ($passedSegments / $totalPossibleSegments) * 100 : 0;

        $stability = match (true) {
            $passRate === 100.0 => '<fg=green;options=bold>STABLE</>',
            $passRate >= 80.0 => '<fg=blue>Refining</>',
            $passRate >= 50.0 => '<fg=yellow>UNSTABLE</>',
            default => '<fg=red;options=bold>CRITICAL</>',
        };

        $this->components->info('System Stability Metrics');
        $this->components->twoColumnDetail(
            'Total System Segments',
            (string) $totalPossibleSegments,
        );
        $this->components->twoColumnDetail('Segments Verified', "<fg=green>{$passedSegments}</>");
        $this->components->twoColumnDetail(
            'Last Execution Date',
            $latestTimestamp ? Carbon::parse($latestTimestamp)->diffForHumans() : 'Unknown',
        );
        $this->components->twoColumnDetail('Global Pass Rate', number_format($passRate, 2) . '%');
        $this->components->twoColumnDetail('Stability Index', $stability);

        return (float) $passRate;
    }

    /**
     * Export session results to a JUnit XML file.
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
                $testcase->setAttribute('classname', "Modules.{$module}.{$segment['type']}");

                if (!($segment['success'] ?? false)) {
                    $failures++;
                    $failure = $dom->createElement('failure');
                    $failure->setAttribute('message', "Test segment {$segment['type']} failed.");
                    $failure->nodeValue = htmlspecialchars(
                        $segment['error'] ?: $segment['output'] ?: 'No output',
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

        $this->components->info("JUnit XML report exported to: {$filePath}");
    }

    /**
     * Export session results to a JSON file.
     */
    public function exportToJSON(string $filePath, array $results, string $sessionId): void
    {
        $data = [
            'session_id' => $sessionId,
            'exported_at' => now()->toIso8601String(),
            'summary' => [
                'total_segments' => count($results),
                'passed' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success'])),
            ],
            'results' => $results,
        ];

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT));

        $this->components->info("JSON report exported to: {$filePath}");
    }

    /**
     * Display a summary of code coverage if available.
     */
    public function displayCoverageSummary(string $output): void
    {
        if (!str_contains($output, 'Lines:')) {
            return;
        }

        $this->components->info('Section 4: Code Coverage Insights');

        // Extract basic coverage stats from Pest output
        preg_match('/Lines:\s+(\d+\.\d+)%/', $output, $lines);
        preg_match('/Methods:\s+(\d+\.\d+)%/', $output, $methods);

        if (isset($lines[1])) {
            $rate = (float) $lines[1];
            $color = $rate >= 80 ? 'green' : ($rate >= 50 ? 'yellow' : 'red');
            $this->components->twoColumnDetail('Line Coverage', "<fg={$color}>{$lines[1]}%</>");
        }

        if (isset($methods[1])) {
            $this->components->twoColumnDetail('Method Coverage', "{$methods[1]}%");
        }
    }

    /**
     * Display performance metrics for the current run.
     */
    public function displayPerformance(int $total, int $passed, float $duration): void
    {
        $failed = $total - $passed;
        $peakMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';

        $this->components->info('Section 2: High-Fidelity Performance Metrics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Segments Processed', $total],
                ['Successful (Green)', "<fg=green>{$passed}</>"],
                ['Failed (Red)', $failed > 0 ? "<fg=red>{$failed}</>" : '<fg=green>0</>'],
                ['Total Execution Time', number_format($duration, 2) . ' s'],
                ['Orchestrator Peak Memory', $peakMemory],
            ],
        );
    }

    /**
     * Display failure forensic details.
     */
    public function displayFailures(array $failures): void
    {
        if (empty($failures)) {
            return;
        }

        $this->components->warn('Section 3: Failure Traceability (Forensic View)');
        foreach ($failures as $failure) {
            $this->components->twoColumnDetail("<fg=red>FAIL</> {$failure['label']}");
            if (!empty($failure['error'])) {
                $this->error($failure['error']);
            }
            if (!empty($failure['output'])) {
                $this->line("<fg=gray>{$failure['output']}</>");
            }
        }
    }

    /**
     * Wrapper for console table.
     */
    protected function table(array $headers, array $rows): void
    {
        // Use standard table rendering instead of missing component
        $table = new Table(new ConsoleOutput());
        $table->setHeaders($headers)->setRows($rows)->render();
    }

    /**
     * Wrapper for console error.
     */
    protected function error(string $message): void
    {
        $this->components->error($message);
    }

    /**
     * Wrapper for console line.
     */
    protected function line(string $message): void
    {
        // Use standard output for gray text
        echo $message . PHP_EOL;
    }
}
