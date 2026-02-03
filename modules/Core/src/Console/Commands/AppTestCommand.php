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
                            {--p|parallel : Run tests within each module in parallel}
                            {--f|stop-on-failure : Stop execution on the first test failure}';

    /**
     * The console command description.
     *
     * @var string
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

        // Identify segments
        $segments = [['label' => 'Root Application', 'path' => base_path('tests')]];

        $modulesPath = base_path('modules');
        $moduleDirs = File::directories($modulesPath);
        foreach ($moduleDirs as $path) {
            $testPath = $path.DIRECTORY_SEPARATOR.'tests';
            if (File::isDirectory($testPath)) {
                $segments[] = [
                    'label' => basename($path).' Module',
                    'path' => $testPath,
                ];
            }
        }

        $totalSegments = count($segments);
        $overallSuccess = true;

        foreach ($segments as $index => $segment) {
            $currentIndex = $index + 1;
            $segmentStart = microtime(true);

            $success = $this->runTestSegment(
                "{$segment['label']} ({$currentIndex}/{$totalSegments})",
                $segment['path']
            );

            $duration = microtime(true) - $segmentStart;
            $results[] = [
                'segment' => $segment['label'],
                'status' => $success ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
                'duration' => number_format($duration, 2).'s',
            ];

            if (! $success) {
                $overallSuccess = false;
                if ($this->option('stop-on-failure')) {
                    break;
                }
            }
        }

        $totalDuration = microtime(true) - $startTime;
        $this->displaySummary($results, $totalDuration, $overallSuccess);

        return $overallSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Display the test execution summary.
     */
    protected function displaySummary(array $results, float $totalDuration, bool $success): void
    {
        $this->newLine();
        $this->components->info('Test Execution Summary');

        $this->table(
            ['Segment', 'Status', 'Duration'],
            $results
        );

        $this->components->twoColumnDetail('Total Duration', number_format($totalDuration, 2).'s');
        $this->components->twoColumnDetail('Peak Memory Usage', number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).'MB');

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
            $process = new Process($command, base_path(), [
                'PHP_RAM_LIMIT' => '512M',
            ]);

            $process->setTimeout(self::SEGMENT_TIMEOUT);
            $process->run();

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
