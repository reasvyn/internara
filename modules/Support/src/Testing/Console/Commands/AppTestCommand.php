<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Support\Testing\Support\TestSessionManager;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/**
 * Advanced Orchestrator for modular testing.
 *
 * This command provides high-fidelity verification by running test segments
 * in isolated processes to prevent memory accumulation and ensuring
 * alignment with Internara's 3S engineering doctrine.
 */
class AppTestCommand extends Command
{
    /**
     * Timeout for each test segment in seconds (20 minutes).
     */
    protected const SEGMENT_TIMEOUT = 1200;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:test 
                            {modules?* : Optional module name(s) to target specifically}
                            {--p|parallel : Run tests within each module in parallel}
                            {--c|continue-on-failure : Continue execution even if a test failure occurs}
                            {--dirty : Only run tests for modules with uncommitted changes (Git)}
                            {--no-arch : Skip architectural tests}
                            {--no-unit : Skip unit tests}
                            {--no-feature : Skip feature tests}
                            {--no-browser : Skip browser tests}
                            {--with-browser : Run browser tests (skipped by default)}
                            {--arch-only : Run only architectural tests}
                            {--unit-only : Run only unit tests}
                            {--feature-only : Run only feature tests}
                            {--browser-only : Run only browser tests}
                            {--filter= : Filter tests by name (Pest filter)}
                            {--session= : Specify a custom session ID for the test run}
                            {--continue : Resume the latest test session, skipping successful segments}
                            {--report : Display the comprehensive report from the current or latest session}
                            {--clear-sessions : Remove all persistent testing session data}
                            {--l|list : Display the identified test segments without executing them}';

    /**
     * The console command description.
     */
    protected $description = 'Orchestrate sophisticated modular verification with memory isolation';

    /**
     * The test session manager instance.
     */
    protected TestSessionManager $session;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('clear-sessions')) {
            TestSessionManager::clearAll();
            $this->components->info('Persistent testing sessions cleared.');
            return self::SUCCESS;
        }

        $this->session = new TestSessionManager($this->option('session'));
        
        if ($this->option('report')) {
            return $this->displaySessionReport();
        }

        $startTime = microtime(true);
        $requestedModules = array_map('strtolower', $this->argument('modules'));

        // 1. Target Identification
        $targets = $this->identifyTargets($requestedModules, $missing);

        if (! empty($missing)) {
            $this->newLine();
            foreach ($missing as $module) {
                $this->components->error(
                    "Target module [{$module}] was not found or is currently disabled.",
                );
            }
            $this->newLine();

            return self::FAILURE;
        }

        if (empty($targets)) {
            $this->components->warn(
                'No testable targets identified for the current configuration.',
            );

            return self::SUCCESS;
        }

        $results = [];
        $failures = [];
        $overallSuccess = true;

        $this->newLine();
        $this->components->info(config('app.name', 'Internara').' Advanced Verification Engine');
        $this->components->detail('Session ID', $this->session->getSessionId());

        if ($this->option('list')) {
            $this->displayTargets($targets);

            return self::SUCCESS;
        }

        // 2. Execution Preparation
        $totalSegments = $this->calculateTotalSegments($targets);
        $currentSegment = 0;

        foreach ($targets as $target) {
            $row = [
                'module' => $target['label'],
                'Arch' => '-',
                'Unit' => '-',
                'Feature' => '-',
                'Browser' => '-',
                'total' => 0.0,
            ];

            $hasTests = false;
            $subsegments = $target['segments'] ?? ['Arch', 'Unit', 'Feature', 'Browser'];

            foreach ($subsegments as $sub) {
                $testPath = $target['path'].DIRECTORY_SEPARATOR.$sub;

                if (File::isDirectory($testPath)) {
                    if ($this->shouldSkipSegment($sub)) {
                        $row[$sub] = '<fg=yellow>SKIP</>';
                        continue;
                    }

                    // Handle Session Persistence (Skip passed segments if --continue)
                    if ($this->option('continue') && $this->session->isPassed($target['label'], $sub)) {
                        $row[$sub] = '<fg=green>PASS</> (Saved)';
                        $currentSegment++;
                        continue;
                    }

                    $hasTests = true;
                    $currentSegment++;
                    $segmentStart = microtime(true);

                    $segmentLabel = "{$target['label']} > {$sub}";
                    $segmentOutput = '';
                    $segmentError = '';

                    // Isolated Process Execution (Minimizes Memory Leak)
                    $success = $this->runTestSegment(
                        $segmentLabel,
                        $testPath,
                        $currentSegment,
                        $totalSegments,
                        $segmentOutput,
                        $segmentError,
                    );

                    // Persist Result to Session
                    $this->session->record($target['label'], $sub, $success, $segmentOutput, $segmentError);

                    $duration = microtime(true) - $segmentStart;
                    $row[$sub] = number_format($duration, 2).'s';
                    $row['total'] += $duration;

                    if (! $success) {
                        $overallSuccess = false;
                        $row[$sub] = '<fg=red>FAIL</>';
                        $failures[] = [
                            'label' => $segmentLabel,
                            'output' => $segmentOutput,
                            'error' => $segmentError,
                        ];

                        if (! $this->option('continue-on-failure')) {
                            $this->recordResult($results, $row);
                            break 2;
                        }
                    } else {
                        $row[$sub] = '<fg=green>PASS</> (' . number_format($duration, 2) . 's)';
                    }
                }
            }

            $this->recordResult($results, $row);
        }

        $totalDuration = microtime(true) - $startTime;
        $this->displaySummary($results, $failures, $totalDuration, $overallSuccess);

        return $overallSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Display a comprehensive report from the current test session.
     */
    protected function displaySessionReport(): int
    {
        $sessionResults = $this->session->getResults();
        
        if (empty($sessionResults)) {
            $this->components->warn('No results found for session: ' . $this->session->getSessionId());
            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Module-Aware Testing Session Report');
        $this->components->detail('Session ID', $this->session->getSessionId());

        $grouped = [];
        $totalSuccess = true;

        foreach ($sessionResults as $result) {
            $module = $result['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = ['Arch' => '-', 'Unit' => '-', 'Feature' => '-', 'Browser' => '-'];
            }
            
            $status = $result['success'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            $grouped[$module][$result['type']] = $status;
            
            if (!$result['success']) {
                $totalSuccess = false;
            }
        }

        $rows = [];
        foreach ($grouped as $module => $types) {
            $rows[] = [$module, $types['Arch'], $types['Unit'], $types['Feature'], $types['Browser']];
        }

        $this->table(['Module', 'Arch', 'Unit', 'Feature', 'Browser'], $rows);

        return $totalSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Determines if a specific test subsegment should be skipped based on input options.
     */
    protected function shouldSkipSegment(string $sub): bool
    {
        $subLower = strtolower($sub);

        $onlyFlagsActive =
            $this->option('arch-only') ||
            $this->option('unit-only') ||
            $this->option('feature-only') ||
            $this->option('browser-only');

        if ($this->option("no-{$subLower}")) {
            return true;
        }

        // Browser tests are skipped by default unless explicitly requested
        if (
            $sub === 'Browser' &&
            ! $this->option('with-browser') &&
            ! $this->option('browser-only')
        ) {
            return true;
        }

        if ($onlyFlagsActive && ! $this->option("{$subLower}-only")) {
            return true;
        }

        return false;
    }

    /**
     * Identifies all testable targets, supporting git diff for "dirty" detection.
     */
    protected function identifyTargets(array $requestedModules, ?array &$missing = []): array
    {
        $targets = [];
        $foundRequested = [];
        $missing = [];

        $dirtyModules = $this->option('dirty') ? $this->getDirtyModules() : null;

        // 1. System Level Targets
        if (File::isDirectory(base_path('tests/Arch'))) {
            if ($this->shouldIncludeTarget('system', $requestedModules, $dirtyModules)) {
                $targets[] = [
                    'label' => 'System',
                    'path' => base_path('tests'),
                    'segments' => ['Arch'],
                ];
                $foundRequested[] = 'system';
            }
        }

        if ($this->shouldIncludeTarget('root', $requestedModules, $dirtyModules)) {
            $targets[] = [
                'label' => 'Root',
                'path' => base_path('tests'),
                'segments' => ['Unit', 'Feature', 'Browser'],
            ];
            $foundRequested[] = 'root';
        }

        // 2. Modular Targets
        $statusPath = base_path('modules_statuses.json');
        if (File::exists($statusPath)) {
            $statuses = json_decode(File::get($statusPath), true) ?: [];
            foreach ($statuses as $moduleName => $isActive) {
                if ($isActive !== true) {
                    continue;
                }

                $lowerName = strtolower($moduleName);
                if ($this->shouldIncludeTarget($lowerName, $requestedModules, $dirtyModules)) {
                    $foundRequested[] = $lowerName;
                    $testPath = base_path("modules/{$moduleName}/tests");
                    if (File::isDirectory($testPath)) {
                        $targets[] = [
                            'label' => $moduleName,
                            'path' => $testPath,
                            'segments' => ['Arch', 'Unit', 'Feature', 'Browser'],
                        ];
                    }
                }
            }
        }

        if (! empty($requestedModules)) {
            $missing = array_diff($requestedModules, $foundRequested);
        }

        return $targets;
    }

    /**
     * Logic to determine if a target should be included in the test run.
     */
    protected function shouldIncludeTarget(string $label, array $requested, ?array $dirty): bool
    {
        $label = strtolower($label);

        // If specific modules requested, only include those
        if (! empty($requested)) {
            return in_array($label, $requested);
        }

        // If dirty flag is active, only include if changed
        if ($dirty !== null) {
            return in_array($label, $dirty);
        }

        return true;
    }

    /**
     * Detects changed modules using git.
     *
     * @return array<string>
     */
    protected function getDirtyModules(): array
    {
        $process = new Process(['git', 'status', '--porcelain'], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $output = $process->getOutput();
        $changedModules = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $file = substr($line, 3);
            if (str_starts_with($file, 'modules/')) {
                $parts = explode('/', $file);
                if (isset($parts[1])) {
                    $changedModules[] = strtolower($parts[1]);
                }
            } else {
                $changedModules[] = 'root';
                $changedModules[] = 'system';
            }
        }

        return array_unique($changedModules);
    }

    /**
     * Calculates the total number of segments to be processed.
     */
    protected function calculateTotalSegments(array $targets): int
    {
        $count = 0;
        foreach ($targets as $target) {
            $subsegments = $target['segments'] ?? ['Arch', 'Unit', 'Feature', 'Browser'];
            foreach ($subsegments as $sub) {
                if (
                    File::isDirectory($target['path'].DIRECTORY_SEPARATOR.$sub) &&
                    ! $this->shouldSkipSegment($sub)
                ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Records a module's results into the matrix.
     */
    protected function recordResult(array &$results, array $row): void
    {
        $results[] = [
            $row['module'],
            $row['Arch'],
            $row['Unit'],
            $row['Feature'],
            $row['Browser'],
            number_format($row['total'] ?? 0, 2).'s',
        ];
    }

    /**
     * Displays the identified test targets.
     */
    protected function displayTargets(array $targets): void
    {
        $this->components->info('Identified Test Targets:');
        foreach ($targets as $target) {
            $this->line(" - {$target['label']} (<fg=gray>{$target['path']}</>)");
        }
        $this->newLine();
    }

    /**
     * Run a specific test segment in an isolated process.
     */
    protected function runTestSegment(
        string $label,
        string $path,
        int $current,
        int $total,
        string &$output,
        string &$errorOutput,
    ): bool {
        $isSuccessful = false;

        $this->components->task("Segment ({$current}/{$total}): {$label}", function () use (
            $path,
            &$output,
            &$errorOutput,
            &$isSuccessful,
        ) {
            $command = [base_path('vendor/bin/pest'), $path];

            if ($this->option('parallel')) {
                $command[] = '--parallel';
            }
            if (! $this->option('continue-on-failure')) {
                $command[] = '--stop-on-failure';
            }
            if ($filter = $this->option('filter')) {
                $command[] = '--filter';
                $command[] = $filter;
            }

            $process = new Process($command, base_path(), ['APP_ENV' => 'testing']);
            $process->setTimeout(self::SEGMENT_TIMEOUT);

            try {
                $process->run();
                $output = $process->getOutput();
                $errorOutput = $process->getErrorOutput();
                $isSuccessful = $process->isSuccessful();

                return $isSuccessful;
            } catch (ProcessSignaledException $e) {
                $errorOutput = "Process terminated by signal: {$e->getSignal()}";
                $isSuccessful = false;

                return false;
            }
        });

        return $isSuccessful;
    }

    /**
     * Display the comprehensive verification summary.
     */
    protected function displaySummary(
        array $results,
        array $failures,
        float $totalDuration,
        bool $success,
    ): void {
        $this->newLine();
        $this->components->info('Section 1: Modular Verification Matrix');
        $this->table(['Module', 'Arch', 'Unit', 'Feature', 'Browser', 'Total'], $results);

        $totalSegments = 0;
        $failedSegments = count($failures);
        foreach ($results as $row) {
            for ($i = 1; $i <= 4; $i++) {
                if (! in_array($row[$i], ['-', '<fg=yellow>SKIP</>'])) {
                    $totalSegments++;
                }
            }
        }

        $passedSegments = $totalSegments - $failedSegments;
        $peakMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB';

        $this->newLine();
        $this->components->info('Section 2: High-Fidelity Performance Metrics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Segments Processed', $totalSegments],
                ['Successful (Green)', "<fg=green>{$passedSegments}</>"],
                [
                    'Failed (Red)',
                    $failedSegments > 0 ? "<fg=red>{$failedSegments}</>" : '<fg=green>0</>',
                ],
                ['Total Execution Time', number_format($totalDuration, 2).' s'],
                ['Orchestrator Peak Memory', $peakMemory],
            ],
        );

        if (! empty($failures)) {
            $this->newLine();
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

        if ($success) {
            $this->newLine();
            $this->components->info('All test segments passed successfully.');
        } else {
            $this->newLine();
            $this->components->error('Some test segments failed. Use --continue to resume after fixing.');
        }
    }
}
