<?php

declare(strict_types=1);

namespace Modules\Support\Contracts\Testing;

/**
 * Interface for executing test processes with memory leak prevention.
 *
 * S1 (Secure): Processes must be properly isolated and cleaned up.
 * S2 (Sustain): Implementation must be swappable for testing.
 * S3 (Scalable): Support for parallel execution with resource controls.
 */
interface ProcessExecutorInterface
{
    /**
     * Execute a test segment in an isolated process.
     *
     * @param array<string> $command The command to execute
     * @param array<string, string> $env Environment variables
     * @return array{output: string, errorOutput: string, exitCode: int, peakMemory: int}
     */
    public function execute(array $command, array $env = []): array;

    /**
     * Set the timeout for process execution in seconds.
     */
    public function setTimeout(int $timeout): self;

    /**
     * Set the memory limit for child processes in bytes.
     */
    public function setMemoryLimit(int $bytes): self;

    /**
     * Enable or disable parallel execution.
     */
    public function setParallel(bool $parallel): self;

    /**
     * Force garbage collection after process execution.
     */
    public function collectGarbage(): void;
}
