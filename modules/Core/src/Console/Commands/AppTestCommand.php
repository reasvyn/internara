<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Class AppTestCommand
 *
 * Orchestrates memory-efficient test execution by running tests sequentially per module.
 * This command ensures that the service container is reset between segments,
 * preventing memory exhaustion in resource-constrained environments.
 */
class AppTestCommand extends Command
{
    /**
     * Timeout for each test segment in seconds (20 minutes).
     */
    protected const SEGMENT_TIMEOUT = 1200;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test 
                            {module? : Optional module name to target specifically}
                            {--p|parallel : Run tests within each module in parallel}
                            {--f|stop-on-failure : Stop execution on the first test failure}
                            {--no-unit : Skip unit tests}
                            {--no-feature : Skip feature tests}
                            {--no-browser : Skip browser tests}
                            {--l|list : Display the identified test segments without executing them}';

    /**
     * The console command description.
     */
    protected $description = 'Run system and modular tests sequentially to minimize memory usage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $results = [];

        $this->newLine();
        $this->components->info(config('app.name', 'Internara').' Modular Test Orchestrator');

        // Identify test targets
        $targets = $this->identifyTargets();

        if ($this->option('list')) {
            $this->displayTargets($targets);

            return self::SUCCESS;
        }

        $subsegments = ['Unit', 'Feature', 'Browser'];
        $overallSuccess = true;

        foreach ($targets as $target) {
            $row = [
                'module' => $target['label'],
                'Unit' => '-',
                'Feature' => '-',
                'Browser' => '-',
                'total' => 0.0,
            ];

            $hasTests = false;

            foreach ($subsegments as $sub) {
                $testPath = $target['path'].DIRECTORY_SEPARATOR.$sub;

                if (File::isDirectory($testPath)) {
                    $hasTests = true;

                    if ($this->option('no-'.strtolower($sub))) {
                        $row[$sub] = '<fg=yellow>SKIP</>';

                        continue;
                    }

                    $segmentStart = microtime(true);

                    $success = $this->runTestSegment("{$target['label']} > {$sub}", $testPath);

                    $duration = microtime(true) - $segmentStart;
                    $row[$sub] = number_format($duration, 2).'s';
                    $row['total'] += $duration;

                    if (! $success) {
                        $overallSuccess = false;
                        $row[$sub] = '<fg=red>FAIL</>';
                        if ($this->option('stop-on-failure')) {
                            $results[] = [
                                $row['module'],
                                $row['Unit'],
                                $row['Feature'],
                                $row['Browser'],
                                number_format($row['total'], 2).'s',
                            ];
                            break 2;
                        }
                    }
                }
            }

            if ($hasTests) {
                $results[] = [
                    $row['module'],
                    $row['Unit'],
                    $row['Feature'],
                    $row['Browser'],
                    number_format($row['total'], 2).'s',
                ];
            }
        }

        $totalDuration = microtime(true) - $startTime;
        $this->displaySummary($results, $totalDuration, $overallSuccess);

        return $overallSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Identifies all testable targets based on the current filesystem structure.
     *
     * @return array<int, array{label: string, path: string}>
     */
    protected function identifyTargets(): array
    {
        $targetModule = $this->argument('module');
        $targets = [];

        if (! $targetModule || strtolower($targetModule) === 'root') {
            $targets[] = ['label' => 'Root', 'path' => base_path('tests')];
        }

        $modulesPath = base_path('modules');
        if (File::isDirectory($modulesPath)) {
            $moduleDirs = File::directories($modulesPath);
            foreach ($moduleDirs as $path) {
                $moduleName = basename($path);

                if ($targetModule && strtolower($targetModule) !== strtolower($moduleName)) {
                    continue;
                }

                $targets[] = [
                    'label' => $moduleName,
                    'path' => $path.DIRECTORY_SEPARATOR.'tests',
                ];
            }
        }

        return $targets;
    }

    /**
     * Displays the identified test targets to the console.
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
     * Display the test execution summary.
     */
    protected function displaySummary(array $results, float $totalDuration, bool $success): void
    {
        $this->newLine();
        $this->components->info('Test Execution Summary');

        $this->table(['Module', 'Unit', 'Feature', 'Browser', 'Total'], $results);

        $this->components->twoColumnDetail(
            'Total Execution Time',
            number_format($totalDuration, 2).'s',
        );
        $this->components->twoColumnDetail(
            'Peak Memory Usage',
            number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).'MB',
        );

        $this->newLine();
        if ($success) {
            $this->components->info('Verification Passed: System is stable.');
        } else {
            $this->components->error('Verification Failed: System requires attention.');
        }
    }

    /**
     * Run a specific test segment.
     */
    protected function runTestSegment(string $label, string $path): bool
    {
        $success = true;

        $this->components->task("Testing segment: {$label}", function () use ($path, &$success) {
            $command = [base_path('vendor/bin/pest'), $path];

            if ($this->option('parallel')) {
                $command[] = '--parallel';
            }

            if ($this->option('stop-on-failure')) {
                $command[] = '--stop-on-failure';
            }

            // Ensure memory limit is respected within the sub-process

            // and add Chrome/Chromium flags to prevent SIGILL/crashes in Linux

            $process = new Process($command, base_path(), [
                'PHP_RAM_LIMIT' => '512M',

                'BROWSER_NO_SANDBOX' => '1',

                'PLAYWRIGHT_CHROMIUM_ARGS' => '--no-sandbox --disable-setuid-sandbox --disable-dev-shm-usage --disable-gpu --disable-software-rasterizer --disable-features=VizDisplayCompositor --disable-extensions --no-zygote',
            ]);

            $process->setTimeout(self::SEGMENT_TIMEOUT);

            try {
                $process->run();
            } catch (\Symfony\Component\Process\Exception\ProcessSignaledException $e) {
                $this->newLine();

                $this->components->error(
                    "Process terminated by signal: {$e->getSignal()} (Illegal Instruction usually means CPU/Sandbox incompatibility)",
                );

                $this->line($process->getOutput());

                $this->line($process->getErrorOutput());

                $success = false;

                return false;
            }

            if (! $process->isSuccessful()) {
                $this->newLine();

                $this->line($process->getOutput());

                $this->line($process->getErrorOutput());

                $success = false;

                return false;
            }

            return true;
        });

        return $success;
    }
}
