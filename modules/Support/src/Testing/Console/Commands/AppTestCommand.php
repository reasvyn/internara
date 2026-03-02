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
     * Execute the console command.
     */
    public function handle(
        TargetDiscovery $discovery,
        TestExecutor $executor,
    ): int {
        if ($this->option('clear-sessions')) {
            TestSessionManager::clearAll();
            $this->components->info('Persistent testing sessions cleared.');
            return self::SUCCESS;
        }

        $session = new TestSessionManager($this->option('session'));
        $reporter = new TestReporter($this->components);
        
        if ($this->option('report')) {
            // Get all possible targets to establish the 100% baseline
            $allTargets = $discovery->identify([], false);
            $totalPossible = $this->calculateTotalSegments($allTargets);
            
            $reporter->displaySessionMetrics($session->getSessionId(), $session->getResults(), $totalPossible);
            
            return self::SUCCESS;
        }

        $startTime = microtime(true);
        $missing = [];
        $targets = $discovery->identify($this->argument('modules'), (bool) $this->option('dirty'), $missing);

        if (! empty($missing)) {
            $this->newLine();
            foreach ($missing as $module) {
                $this->components->error("Target module [{$module}] was not found or is currently disabled.");
            }
            return self::FAILURE;
        }

        if (empty($targets)) {
            $this->components->warn('No testable targets identified for the current configuration.');
            return self::SUCCESS;
        }

        if ($this->option('list')) {
            $this->components->info('Identified Test Targets:');
            foreach ($targets as $target) {
                $this->line(" - {$target['label']} (<fg=gray>{$target['path']}</>)");
            }
            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info(config('app.name', 'Internara').' Advanced Verification Engine');
        $this->components->twoColumnDetail('Session ID', $session->getSessionId());

        $results = [];
        $failures = [];
        $overallSuccess = true;
        $totalSegments = $this->calculateTotalSegments($targets);
        $currentSegment = 0;

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

                    $success = $this->components->task("Segment ({$currentSegment}/{$totalSegments}): {$segmentLabel}", function () use (
                        $executor, $testPath, &$segmentOutput, &$segmentError
                    ) {
                        return $executor->execute(
                            $testPath,
                            (bool) $this->option('parallel'),
                            !$this->option('continue-on-failure'),
                            $this->option('filter'),
                            $segmentOutput,
                            $segmentError
                        );
                    });

                    $session->record($target['label'], $sub, $success, $segmentOutput, $segmentError);

                    $duration = microtime(true) - $segmentStart;
                    $row[$sub] = $success ? '<fg=green>PASS</> (' . number_format($duration, 2) . 's)' : '<fg=red>FAIL</>';
                    $row['total'] += $duration;

                    if (! $success) {
                        $overallSuccess = false;
                        $failures[] = ['label' => $segmentLabel, 'output' => $segmentOutput, 'error' => $segmentError];
                        if (! $this->option('continue-on-failure')) {
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
        $reporter->displayPerformance($totalSegments, $totalSegments - count($failures), $totalDuration);
        $reporter->displayFailures($failures);

        return $overallSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Determines if a specific test subsegment should be skipped.
     */
    protected function shouldSkipSegment(string $sub): bool
    {
        $subLower = strtolower($sub);
        $onlyFlags = $this->option('arch-only') || $this->option('unit-only') || $this->option('feature-only') || $this->option('browser-only');

        if ($this->option("no-{$subLower}")) return true;
        if ($sub === 'Browser' && !$this->option('with-browser') && !$this->option('browser-only')) return true;
        if ($onlyFlags && !$this->option("{$subLower}-only")) return true;

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
                if (File::isDirectory($target['path'].DIRECTORY_SEPARATOR.$sub) && ! $this->shouldSkipSegment($sub)) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
