<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Support\Testing\Support\TargetDiscovery;
use Modules\Support\Testing\Support\TestExecutor;
use Modules\Support\Testing\Support\TestReporter;
use Modules\Support\Testing\Support\TestSessionManager;

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
     * The name and signature of the console command.
     */
    protected $signature = 'app:test 
                            {modules?* : Optional module name(s) to target specifically}
                            {--p|parallel : Run tests within each module in parallel}
                            {--f|stop-on-failure : Stop execution if a test failure occurs}
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
                            {--coverage : Generate code coverage report (automates PCOV/JIT flags)}
                            {--session= : Specify a custom session ID for the test run}
                            {--continue : Resume the latest test session, skipping successful segments}
                            {--report : Display the comprehensive report from the current or latest session}
                            {--log-junit= : Path to export report in JUnit XML format}
                            {--log-json= : Path to export report in JSON format}
                            {--fail-on-stability= : Exit with failure if stability percentage is below threshold}
                            {--clear-sessions : Remove all persistent testing session data}
                            {--l|list : Display the identified test segments without executing them}';

    /**
     * The console command description.
     */
    protected $description = 'Orchestrate sophisticated modular verification with memory isolation';

    /**
     * Execute the console command.
     */
    public function handle(TargetDiscovery $discovery, TestExecutor $executor): int
    {
        if ($this->option('clear-sessions')) {
            TestSessionManager::clearAll();
            $this->components->info('Persistent testing sessions cleared.');

            return self::SUCCESS;
        }

        $session = new TestSessionManager($this->option('session'));
        $reporter = new TestReporter($this->components);

        // Get all possible targets to establish the 100% baseline for reporting
        $allPossibleTargets = $discovery->identify([], false);
        $totalPossibleSegments = $this->calculateTotalSegments($allPossibleTargets);

        if ($this->option('report')) {
            $this->displayBanner();
            $passRate = $reporter->displaySessionMetrics(
                $session->getSessionId(),
                $session->getResults(),
                $totalPossibleSegments,
            );

            return $this->evaluateStability($passRate);
        }

        $this->displayBanner();
        $startTime = microtime(true);
        $missing = [];
        $requestedModules = array_map('strtolower', $this->argument('modules'));
        $targets = $discovery->identify($requestedModules, (bool) $this->option('dirty'), $missing);

        if (! empty($missing)) {
            $this->newLine();
            foreach ($missing as $module) {
                $this->components->error(
                    "Target module [{$module}] was not found or is currently disabled.",
                );
            }

            return self::FAILURE;
        }

        if (empty($targets)) {
            $this->components->warn(
                'No testable targets identified for the current configuration.',
            );

            return self::SUCCESS;
        }

        if ($this->option('list')) {
            $this->components->info('Identified Test Targets:');
            foreach ($targets as $target) {
                $this->line(" - {$target['label']} (<fg=gray>{$target['path']}</>)");
            }

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Session ID', $session->getSessionId());
        $this->components->twoColumnDetail('Total Targets', (string) count($targets));

        $results = [];
        $failures = [];
        $overallSuccess = true;
        $totalSegments = $this->calculateTotalSegments($targets);
        $currentSegment = 0;
        $allOutput = '';

        foreach ($targets as $target) {
            $row = ['module' => $target['label'], 'total' => 0.0];
            $subsegments = $target['segments'] ?? ['Arch', 'Unit', 'Feature', 'Browser'];

            foreach ($subsegments as $sub) {
                $testPath = $target['path'].DIRECTORY_SEPARATOR.$sub;

                if (File::isDirectory($testPath)) {
                    if ($this->shouldSkipSegment($sub)) {
                        $row[$sub] = '<fg=yellow>SKIP</>';

                        continue;
                    }

                    if ($this->option('continue') && $session->isPassed($target['label'], $sub)) {
                        $row[$sub] = '<fg=green>PASS</> (Saved)';
                        $currentSegment++;

                        continue;
                    }

                    $currentSegment++;
                    $segmentStart = microtime(true);
                    $segmentLabel = "{$target['label']} > {$sub}";
                    $segmentOutput = '';
                    $segmentError = '';

                    $success = false;
                    $this->components->task(
                        "Segment ({$currentSegment}/{$totalSegments}): {$segmentLabel}",
                        function () use (
                            $executor,
                            $testPath,
                            &$segmentOutput,
                            &$segmentError,
                            &$success,
                        ) {
                            $success = $executor->execute(
                                $testPath,
                                (bool) $this->option('parallel'),
                                $this->option('stop-on-failure'),
                                $this->option('filter'),
                                $segmentOutput,
                                $segmentError,
                                (bool) $this->option('coverage'),
                            );

                            return $success;
                        },
                    );

                    $session->record(
                        $target['label'],
                        $sub,
                        $success,
                        $segmentOutput,
                        $segmentError,
                    );
                    $allOutput .= $segmentOutput.$segmentError;

                    $duration = microtime(true) - $segmentStart;
                    $row[$sub] = $success
                        ? '<fg=green>PASS</> ('.number_format($duration, 2).'s)'
                        : '<fg=red>FAIL</>';
                    $row['total'] += $duration;

                    if (! $success) {
                        $overallSuccess = false;
                        $failures[] = [
                            'label' => $segmentLabel,
                            'output' => $segmentOutput,
                            'error' => $segmentError,
                        ];
                        if ($this->option('stop-on-failure')) {
                            $results[] = $row;
                            break 2;
                        }
                    }
                }
            }
            $results[] = $row;
        }

        $totalDuration = microtime(true) - $startTime;
        $reporter->displayMatrix($results);
        $reporter->displayPerformance(
            $totalSegments,
            $totalSegments - count($failures),
            $totalDuration,
        );

        if ($this->option('coverage')) {
            $reporter->displayCoverageSummary($allOutput);
        }

        $reporter->displayFailures($failures);

        // Handle Exports
        if ($this->option('log-junit')) {
            $reporter->exportToJUnit(
                $this->option('log-junit'),
                $session->getResults(),
                $session->getSessionId(),
            );
        }

        if ($this->option('log-json')) {
            $reporter->exportToJSON(
                $this->option('log-json'),
                $session->getResults(),
                $session->getSessionId(),
            );
        }

        // Stability check for CI/CD
        if ($this->option('fail-on-stability')) {
            $passRate = (($totalSegments - count($failures)) / $totalSegments) * 100;

            return $this->evaluateStability((float) $passRate);
        }

        return $overallSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Display a professional banner.
     */
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line(
            ' <fg=white;bg=magenta;options=bold> INTERNARA </> <fg=magenta;options=bold>MODULAR VERIFICATION ENGINE</>',
        );
        $this->line(
            ' <fg=gray>Advanced Infrastructure Testing Tool v'.
                config('app.version', '0.14.0').
                '</>',
        );
        $this->newLine();
    }

    /**
     * Evaluates if the current stability meets the required threshold.
     */
    protected function evaluateStability(float $passRate): int
    {
        $threshold = (float) $this->option('fail-on-stability', 100);

        if ($passRate < $threshold) {
            $this->components->error(
                'Stability failure: Global pass rate ('.
                    number_format($passRate, 2).
                    '%) is below required threshold ('.
                    number_format($threshold, 2).
                    '%).',
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Determines if a specific test subsegment should be skipped.
     */
    protected function shouldSkipSegment(string $sub): bool
    {
        $subLower = strtolower($sub);
        $onlyFlags =
            $this->option('arch-only') ||
            $this->option('unit-only') ||
            $this->option('feature-only') ||
            $this->option('browser-only');

        if ($this->option("no-{$subLower}")) {
            return true;
        }
        if (
            $sub === 'Browser' &&
            ! $this->option('with-browser') &&
            ! $this->option('browser-only')
        ) {
            return true;
        }
        if ($onlyFlags && ! $this->option("{$subLower}-only")) {
            return true;
        }

        return false;
    }

    /**
     * Calculates the total number of segments to be processed.
     */
    protected function calculateTotalSegments(array $targets): int
    {
        $count = 0;
        foreach ($targets as $target) {
            foreach ($target['segments'] ?? ['Arch', 'Unit', 'Feature', 'Browser'] as $sub) {
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
}
