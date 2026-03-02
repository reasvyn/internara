<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/**
 * Executes test segments in isolated processes to prevent memory accumulation.
 */
class TestExecutor
{
    /**
     * Timeout for each test segment in seconds.
     */
    protected const TIMEOUT = 1200;

    /**
     * Run a specific test path using Pest.
     */
    public function execute(
        string $path,
        bool $parallel = false,
        bool $stopOnFailure = true,
        ?string $filter = null,
        ?string &$output = '',
        ?string &$errorOutput = ''
    ): bool {
        $command = [base_path('vendor/bin/pest'), $path];

        if ($parallel) {
            $command[] = '--parallel';
        }
        
        if ($stopOnFailure) {
            $command[] = '--stop-on-failure';
        }
        
        if ($filter) {
            $command[] = '--filter';
            $command[] = $filter;
        }

        $process = new Process($command, base_path(), ['APP_ENV' => 'testing']);
        $process->setTimeout(self::TIMEOUT);

        try {
            $process->run();
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            
            return $process->isSuccessful();
        } catch (ProcessSignaledException $e) {
            $errorOutput = "Process terminated by signal: {$e->getSignal()}";
            return false;
        }
    }
}
