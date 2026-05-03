<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Console\Commands;

use Illuminate\Console\Command;
use Modules\Support\Testing\Support\AppTestOrchestrator;

/**
 * Thin command for orchestrating modular tests.
 *
 * S1 (Secure): No business logic here - delegated to orchestrator.
 * S2 (Sustain): Clear separation of concerns.
 * S3 (Scalable): Supports 29+ modules via orchestrator.
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
    public function handle(AppTestOrchestrator $orchestrator): int
    {
        // Handle clear-sessions early (doesn't need orchestrator)
        if ($this->option('clear-sessions')) {
            $orchestrator->clearSessions();
            $this->components->info('Persistent testing sessions cleared.');

            return self::SUCCESS;
        }

        // Display banner
        $this->displayBanner();

        // Build options array from command input
        $options = $this->buildOptions();

        // Handle --report mode
        if ($options['report']) {
            $passRate = $orchestrator->report();

            if (isset($options['fail-on-stability'])) {
                return $orchestrator->evaluateStability(
                    $passRate,
                    (float) $options['fail-on-stability'],
                );
            }

            return self::SUCCESS;
        }

        // Handle --list mode
        if ($options['list']) {
            return $this->handleListMode($orchestrator, $options);
        }

        // Execute tests
        $result = $orchestrator->execute($options);

        // Display results (delegated to orchestrator's dependencies)
        // The orchestrator's reporter handles display during execution

        // Handle exports
        if (! empty($options['log-junit'])) {
            // Export handled by orchestrator
        }

        if (! empty($options['log-json'])) {
            // Export handled by orchestrator
        }

        // Check for missing modules
        $missing = $orchestrator->getMissingModules();
        if (! empty($missing)) {
            $this->newLine();
            foreach ($missing as $module) {
                $this->components->error(
                    "Target module [{$module}] was not found or is currently disabled.",
                );
            }

            return self::FAILURE;
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Build options array from command line inputs.
     */
    protected function buildOptions(): array
    {
        $modules = $this->argument('modules');

        return [
            'modules' => array_map('strtolower', $modules),
            'parallel' => (bool) $this->option('parallel'),
            'stop-on-failure' => (bool) $this->option('stop-on-failure'),
            'dirty' => (bool) $this->option('dirty'),
            'no-arch' => (bool) $this->option('no-arch'),
            'no-unit' => (bool) $this->option('no-unit'),
            'no-feature' => (bool) $this->option('no-feature'),
            'no-browser' => (bool) $this->option('no-browser'),
            'with-browser' => (bool) $this->option('with-browser'),
            'arch-only' => (bool) $this->option('arch-only'),
            'unit-only' => (bool) $this->option('unit-only'),
            'feature-only' => (bool) $this->option('feature-only'),
            'browser-only' => (bool) $this->option('browser-only'),
            'filter' => $this->option('filter'),
            'coverage' => (bool) $this->option('coverage'),
            'session' => $this->option('session'),
            'continue' => (bool) $this->option('continue'),
            'report' => (bool) $this->option('report'),
            'log-junit' => $this->option('log-junit'),
            'log-json' => $this->option('log-json'),
            'fail-on-stability' => $this->option('fail-on-stability'),
            'list' => (bool) $this->option('list'),
            'clear-sessions' => (bool) $this->option('clear-sessions'),
            'timeout' => 1200, // Default timeout
        ];
    }

    /**
     * Handle --list mode.
     */
    protected function handleListMode(AppTestOrchestrator $orchestrator, array $options): int
    {
        $segments = $orchestrator->listSegments($options);

        if (empty($segments)) {
            $this->components->warn(
                'No testable targets identified for the current configuration.',
            );

            return self::SUCCESS;
        }

        $this->components->info('Identified Test Targets:');
        foreach ($segments as $segment) {
            $this->line(" - {$segment['label']} (<fg=gray>{$segment['path']}</>)");
        }

        return self::SUCCESS;
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
}
