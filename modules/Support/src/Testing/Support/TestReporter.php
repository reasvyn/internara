<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Carbon\Carbon;
use Illuminate\Console\View\Components\Factory;

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
                number_format($row['total'] ?? 0, 2) . 's'
            ];
        }

        $this->table(['Module', 'Arch', 'Unit', 'Feature', 'Browser', 'Total'], $rows);
    }

    /**
     * Display comprehensive session metrics and stability index.
     */
    public function displaySessionMetrics(string $sessionId, array $sessionResults, int $totalPossibleSegments): void
    {
        $this->components->info('Module-Aware Testing Session Report');
        $this->components->twoColumnDetail('Session ID', $sessionId);

        $grouped = [];
        $passedSegments = 0;
        $latestTimestamp = null;

        foreach ($sessionResults as $result) {
            $module = $result['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = ['Arch' => '-', 'Unit' => '-', 'Feature' => '-', 'Browser' => '-'];
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
            $rows[] = [$module, $types['Arch'], $types['Unit'], $types['Feature'], $types['Browser']];
        }

        $this->table(['Module', 'Arch', 'Unit', 'Feature', 'Browser'], $rows);

        // Actual Global Pass Rate Calculation: compared to 100% system baseline
        $passRate = $totalPossibleSegments > 0 ? ($passedSegments / $totalPossibleSegments) * 100 : 0;
        
        $stability = match(true) {
            $passRate === 100.0 => '<fg=green;options=bold>STABLE</>',
            $passRate >= 80.0 => '<fg=blue>Refining</>',
            $passRate >= 50.0 => '<fg=yellow>UNSTABLE</>',
            default => '<fg=red;options=bold>CRITICAL</>',
        };

        $this->components->info('System Stability Metrics');
        $this->components->twoColumnDetail('Total System Segments', (string) $totalPossibleSegments);
        $this->components->twoColumnDetail('Segments Verified', "<fg=green>{$passedSegments}</>");
        $this->components->twoColumnDetail('Last Execution Date', $latestTimestamp ? Carbon::parse($latestTimestamp)->diffForHumans() : 'Unknown');
        $this->components->twoColumnDetail('Global Pass Rate', number_format($passRate, 2) . '%');
        $this->components->twoColumnDetail('Stability Index', $stability);
    }

    /**
     * Display performance metrics for the current run.
     */
    public function displayPerformance(int $total, int $passed, float $duration): void
    {
        $failed = $total - $passed;
        $peakMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB';

        $this->components->info('Section 2: High-Fidelity Performance Metrics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Segments Processed', $total],
                ['Successful (Green)', "<fg=green>{$passed}</>"],
                ['Failed (Red)', $failed > 0 ? "<fg=red>{$failed}</>" : '<fg=green>0</>'],
                ['Total Execution Time', number_format($duration, 2).' s'],
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
            if (! empty($failure['error'])) {
                $this->error($failure['error']);
            }
            if (! empty($failure['output'])) {
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
        $table = new \Symfony\Component\Console\Helper\Table(new \Symfony\Component\Console\Output\ConsoleOutput());
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
