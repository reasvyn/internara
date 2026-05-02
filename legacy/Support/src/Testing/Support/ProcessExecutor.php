<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Modules\Support\Contracts\Testing\ProcessExecutorInterface;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/**
 * Executes test segments in isolated processes with memory leak prevention.
 *
 * S1 (Secure): Processes are isolated - no cross-contamination of state.
 * S2 (Sustain): Proper cleanup prevents resource exhaustion.
 * S3 (Scalable): Supports parallel execution with resource controls.
 */
class ProcessExecutor implements ProcessExecutorInterface
{
    /**
     * Default timeout in seconds for each test segment.
     */
    protected const DEFAULT_TIMEOUT = 1200;

    /**
     * Default memory limit for child processes (512MB).
     */
    protected const DEFAULT_MEMORY_LIMIT = 536870912;

    /**
     * Maximum retry attempts for transient failures.
     */
    protected const MAX_RETRIES = 2;

    protected int $timeout;

    protected int $memoryLimit;

    protected bool $parallel;

    protected ?Process $currentProcess = null;

    /**
     * Track all spawned processes for cleanup.
     *
     * @var array<int, Process>
     */
    protected array $activeProcesses = [];

    public function __construct()
    {
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->memoryLimit = self::DEFAULT_MEMORY_LIMIT;
        $this->parallel = false;
    }

    /**
     * Execute a test segment in an isolated process.
     *
     * @param array<string> $command The command to execute
     * @param array<string, string> $env Environment variables
     *
     * @return array{output: string, errorOutput: string, exitCode: int, peakMemory: int}
     */
    public function execute(array $command, array $env = []): array
    {
        $attempt = 0;
        $lastError = '';

        while ($attempt <= self::MAX_RETRIES) {
            $result = $this->doExecute($command, $env);

            // Success or non-transient failure
            if ($result['exitCode'] === 0 || ! $this->isTransientFailure($result)) {
                return $result;
            }

            $lastError = $result['errorOutput'];
            $attempt++;

            if ($attempt <= self::MAX_RETRIES) {
                usleep(500000); // Wait 500ms before retry
                $this->collectGarbage();
            }
        }

        // Return the last failure
        return [
            'output' => '',
            'errorOutput' => "Max retries exceeded. Last error: {$lastError}",
            'exitCode' => 1,
            'peakMemory' => 0,
        ];
    }

    /**
     * Internal execution with proper cleanup.
     */
    protected function doExecute(array $command, array $env = []): array
    {
        $processEnv = array_merge(
            ['APP_ENV' => 'testing'],
            $env,
            ['PHP_MEMORY_LIMIT' => (string) $this->memoryLimit],
        );

        $process = new Process($command, base_path(), $processEnv);
        $process->setTimeout($this->timeout);

        // Track the process for cleanup
        $this->currentProcess = $process;
        $this->activeProcesses[] = $process;

        $peakMemory = 0;

        try {
            $process->run();

            $peakMemory = $this->getProcessPeakMemory($process);

            $result = [
                'output' => $process->getOutput(),
                'errorOutput' => $process->getErrorOutput(),
                'exitCode' => $process->getExitCode() ?? 1,
                'peakMemory' => $peakMemory,
            ];

            return $result;
        } catch (ProcessSignaledException $e) {
            return [
                'output' => $process->getOutput(),
                'errorOutput' => "Process terminated by signal: {$e->getSignal()}",
                'exitCode' => 1,
                'peakMemory' => $peakMemory,
            ];
        } finally {
            $this->cleanupProcess($process);
        }
    }

    /**
     * Check if the failure is transient (can be retried).
     */
    protected function isTransientFailure(array $result): bool
    {
        $errorOutput = strtolower($result['errorOutput']);
        $output = strtolower($result['output']);

        $transientPatterns = [
            'timeout',
            'memory exhausted',
            'allowed memory size',
            'connection refused',
            'too many open files',
        ];

        foreach ($transientPatterns as $pattern) {
            if (str_contains($errorOutput, $pattern) || str_contains($output, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get peak memory usage of a process.
     */
    protected function getProcessPeakMemory(Process $process): int
    {
        $status = $process->getStatus();

        return isset($status['memory_max']) ? (int) $status['memory_max'] : 0;
    }

    /**
     * Clean up a process and free resources.
     */
    protected function cleanupProcess(Process $process): void
    {
        try {
            if ($process->isRunning()) {
                $process->stop(10, SIGTERM); // Graceful stop
                usleep(100000); // Wait 100ms

                if ($process->isRunning()) {
                    $process->stop(5, SIGKILL); // Force kill
                }
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        } finally {
            // Remove from active processes
            $this->activeProcesses = array_filter(
                $this->activeProcesses,
                fn (Process $p) => $p !== $process
            );

            // Clear references
            if ($this->currentProcess === $process) {
                $this->currentProcess = null;
            }
        }
    }

    /**
     * Set the timeout for process execution in seconds.
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set the memory limit for child processes in bytes.
     */
    public function setMemoryLimit(int $bytes): self
    {
        $this->memoryLimit = $bytes;

        return $this;
    }

    /**
     * Enable or disable parallel execution.
     */
    public function setParallel(bool $parallel): self
    {
        $this->parallel = $parallel;

        return $this;
    }

    /**
     * Force garbage collection after process execution.
     */
    public function collectGarbage(): void
    {
        // Clean up all tracked processes
        foreach ($this->activeProcesses as $process) {
            $this->cleanupProcess($process);
        }

        $this->activeProcesses = [];

        // Force PHP garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        // Clear stat caches
        clearstatcache();
    }

    /**
     * Destructor to ensure cleanup on object destruction.
     */
    public function __destruct()
    {
        $this->collectGarbage();
    }

    /**
     * Get current timeout setting.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get current memory limit setting.
     */
    public function getMemoryLimit(): int
    {
        return $this->memoryLimit;
    }

    /**
     * Check if parallel execution is enabled.
     */
    public function isParallel(): bool
    {
        return $this->parallel;
    }
}
