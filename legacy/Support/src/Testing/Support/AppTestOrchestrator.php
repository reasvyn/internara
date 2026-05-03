<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Illuminate\Support\Facades\File;
use Modules\Support\Contracts\Testing\OrchestratorInterface;
use Modules\Support\Contracts\Testing\ProcessExecutorInterface;
use Modules\Support\Contracts\Testing\ResultReporterInterface;
use Modules\Support\Contracts\Testing\SessionManagerInterface;
use Modules\Support\Contracts\Testing\TargetDiscoveryInterface;

/**
 * Orchestrates modular test execution with proper dependency injection.
 *
 * S1 (Secure): Each segment runs in isolated process with no cross-contamination.
 * S2 (Sustain): Clear progress reporting and resumable sessions.
 * S3 (Scalable): Support for 29+ modules with parallel execution.
 */
class AppTestOrchestrator implements OrchestratorInterface
{
    protected TargetDiscoveryInterface $discovery;

    protected ProcessExecutorInterface $executor;

    protected SessionManagerInterface $sessionManager;

    protected ResultReporterInterface $reporter;

    protected array $missingModules = [];

    public function __construct(
        TargetDiscoveryInterface $discovery,
        ProcessExecutorInterface $executor,
        SessionManagerInterface $sessionManager,
        ResultReporterInterface $reporter,
    ) {
        $this->discovery = $discovery;
        $this->executor = $executor;
        $this->sessionManager = $sessionManager;
        $this->reporter = $reporter;
    }

    /**
     * Execute all test segments with the given options.
     *
     * @param array<string, mixed> $options
     *
     * @return array{success: bool, results: array, failures: array, duration: float}
     */
    public function execute(array $options = []): array
    {
        $startTime = microtime(true);

        // Get all possible targets for baseline
        $allTargets = $this->discovery->discover([], false);
        $totalPossibleSegments = $this->calculateTotalSegments($allTargets);

        // Get actual targets
        $targets = $this->discovery->discover(
            $this->getRequestedModules($options),
            $options['dirty'] ?? false,
            $this->missingModules,
        );

        if (empty($targets)) {
            return [
                'success' => true,
                'results' => [],
                'failures' => [],
                'duration' => 0,
            ];
        }

        $results = [];
        $failures = [];
        $overallSuccess = true;
        $totalSegments = $this->calculateTotalSegments($targets);
        $currentSegment = 0;
        $allOutput = '';

        foreach ($targets as $target) {
            $subsegments = $target['segments'] ?? ['Arch', 'Unit', 'Feature', 'Browser'];

            foreach ($subsegments as $sub) {
                $testPath = $target['path'].DIRECTORY_SEPARATOR.$sub;

                if (! File::isDirectory($testPath)) {
                    continue;
                }

                if ($this->shouldSkipSegment($sub, $options)) {
                    $results[] = [
                        'module' => $target['label'],
                        'type' => $sub,
                        'status' => 'skipped',
                        'duration' => 0,
                    ];

                    continue;
                }

                if (
                    ($options['continue'] ?? false) &&
                    $this->sessionManager->isPassed($target['label'], $sub)
                ) {
                    $results[] = [
                        'module' => $target['label'],
                        'type' => $sub,
                        'status' => 'passed_cached',
                        'duration' => 0,
                    ];
                    $currentSegment++;

                    continue;
                }

                $currentSegment++;
                $segmentStart = microtime(true);

                // Build command
                $command = $this->buildCommand($testPath, $options);

                // Execute in isolated process
                $executionResult = $this->executor
                    ->setTimeout($options['timeout'] ?? 1200)
                    ->setParallel($options['parallel'] ?? false)
                    ->execute($command, $this->getProcessEnv($options));

                $success = $executionResult['exitCode'] === 0;
                $duration = microtime(true) - $segmentStart;

                // Record result
                $this->sessionManager->record($target['label'], $sub, $success, $executionResult);

                $allOutput .= $executionResult['output'].$executionResult['errorOutput'];

                $results[] = [
                    'module' => $target['label'],
                    'type' => $sub,
                    'status' => $success ? 'passed' : 'failed',
                    'duration' => $duration,
                    'peakMemory' => $executionResult['peakMemory'] ?? 0,
                ];

                if (! $success) {
                    $overallSuccess = false;
                    $failures[] = [
                        'label' => "{$target['label']} > {$sub}",
                        'output' => $executionResult['output'],
                        'error' => $executionResult['errorOutput'],
                    ];

                    if ($options['stop-on-failure'] ?? false) {
                        break 2;
                    }
                }

                // Force garbage collection after each segment (S2)
                $this->executor->collectGarbage();
            }
        }

        $totalDuration = microtime(true) - $startTime;

        return [
            'success' => $overallSuccess,
            'results' => $results,
            'failures' => $failures,
            'duration' => $totalDuration,
            'totalSegments' => $totalSegments,
            'passedSegments' => $totalSegments - count($failures),
            'allOutput' => $allOutput,
        ];
    }

    /**
     * List all identified test segments without executing them.
     *
     * @param array<string, mixed> $options
     *
     * @return array<int, array{label: string, path: string}>
     */
    public function listSegments(array $options = []): array
    {
        $targets = $this->discovery->discover(
            $this->getRequestedModules($options),
            $options['dirty'] ?? false,
            $this->missingModules,
        );

        $segments = [];
        foreach ($targets as $target) {
            $segments[] = [
                'label' => $target['label'],
                'path' => $target['path'],
            ];
        }

        return $segments;
    }

    /**
     * Display report from the current or latest session.
     *
     * @return float Pass rate percentage
     */
    public function report(): float
    {
        $sessionResults = $this->sessionManager->getResults();

        // Get all possible targets for baseline
        $allTargets = $this->discovery->discover([], false);
        $totalPossibleSegments = $this->calculateTotalSegments($allTargets);

        return $this->reporter->displaySessionMetrics(
            $this->sessionManager->getSessionId(),
            $sessionResults,
            $totalPossibleSegments,
        );
    }

    /**
     * Clear all persistent session data.
     */
    public function clearSessions(): void
    {
        $this->sessionManager->clearAll();
    }

    /**
     * Evaluate if the stability meets the required threshold.
     *
     * @return int Exit code (0 = success, 1 = failure)
     */
    public function evaluateStability(float $passRate, float $threshold): int
    {
        if ($passRate < $threshold) {
            return 1; // Failure
        }

        return 0; // Success
    }

    /**
     * Get missing modules from the last discovery.
     *
     * @return array<string>
     */
    public function getMissingModules(): array
    {
        return $this->missingModules;
    }

    /**
     * Build the command array for Pest.
     */
    protected function buildCommand(string $testPath, array $options): array
    {
        $command = [PHP_BINARY];

        // Handle coverage mode (PCOV + JIT disable)
        if ($options['coverage'] ?? false) {
            $command[] = '-d';
            $command[] = 'extension=pcov.so';
            $command[] = '-d';
            $command[] = 'pcov.enabled=1';
            $command[] = '-d';
            $command[] = 'opcache.jit=0';
        }

        $command[] = base_path('vendor/bin/pest');
        $command[] = $testPath;

        if ($options['parallel'] ?? false) {
            $command[] = '--parallel';
        }

        if ($options['stop-on-failure'] ?? false) {
            $command[] = '--stop-on-failure';
        }

        if (! empty($options['filter'])) {
            $command[] = '--filter';
            $command[] = $options['filter'];
        }

        if ($options['coverage'] ?? false) {
            $command[] = '--coverage';
        }

        return $command;
    }

    /**
     * Get environment variables for the process.
     */
    protected function getProcessEnv(array $options): array
    {
        $env = ['APP_ENV' => 'testing'];

        if ($options['coverage'] ?? false) {
            $env['PCOV_ENABLED'] = '1';
        }

        return $env;
    }

    /**
     * Determine if a segment should be skipped.
     */
    protected function shouldSkipSegment(string $sub, array $options): bool
    {
        $subLower = strtolower($sub);
        $noFlag = "no-{$subLower}";

        // Check --no-* flags
        if ($options[$noFlag] ?? false) {
            return true;
        }

        // Check --with-browser for Browser tests
        if (
            $sub === 'Browser' &&
            ! ($options['with-browser'] ?? false) &&
            ! ($options['browser-only'] ?? false)
        ) {
            return true;
        }

        // Check --*-only flags
        $onlyFlags = [
            $options['arch-only'] ?? false,
            $options['unit-only'] ?? false,
            $options['feature-only'] ?? false,
            $options['browser-only'] ?? false,
        ];

        if (in_array(true, $onlyFlags, true) && ! ($options["{$subLower}-only"] ?? false)) {
            return true;
        }

        return false;
    }

    /**
     * Get requested modules from options.
     *
     * @return array<string>
     */
    protected function getRequestedModules(array $options): array
    {
        $modules = $options['modules'] ?? [];

        return array_map('strtolower', $modules);
    }

    /**
     * Calculate total segments for given targets.
     */
    protected function calculateTotalSegments(array $targets): int
    {
        $count = 0;
        foreach ($targets as $target) {
            foreach ($target['segments'] ?? ['Arch', 'Unit', 'Feature', 'Browser'] as $sub) {
                $testPath = $target['path'].DIRECTORY_SEPARATOR.$sub;
                if (File::isDirectory($testPath)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
