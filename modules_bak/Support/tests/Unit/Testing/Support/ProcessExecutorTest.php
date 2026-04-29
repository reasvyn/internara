<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Support;

use Modules\Support\Contracts\Testing\ProcessExecutorInterface;
use Modules\Support\Testing\Support\ProcessExecutor;
use Symfony\Component\Process\Process;

describe('ProcessExecutor', function () {
    beforeEach(function () {
        $this->executor = new ProcessExecutor();
    });

    it('implements ProcessExecutorInterface', function () {
        expect($this->executor)->toBeInstanceOf(ProcessExecutorInterface::class);
    });

    it('can set and get timeout', function () {
        $this->executor->setTimeout(600);
        expect($this->executor->getTimeout())->toBe(600);
    });

    it('can set and get memory limit', function () {
        $this->executor->setMemoryLimit(268435456); // 256MB
        expect($this->executor->getMemoryLimit())->toBe(268435456);
    });

    it('can enable and disable parallel', function () {
        $this->executor->setParallel(true);
        expect($this->executor->isParallel())->toBeTrue();

        $this->executor->setParallel(false);
        expect($this->executor->isParallel())->toBeFalse();
    });

    it('executes a simple command successfully', function () {
        $result = $this->executor->execute([PHP_BINARY, '-r', 'echo "test";']);

        expect($result['exitCode'])->toBe(0);
        expect($result['output'])->toContain('test');
        expect($result)->toHaveKeys(['output', 'errorOutput', 'exitCode', 'peakMemory']);
    });

    it('handles command failure gracefully', function () {
        $result = $this->executor->execute([PHP_BINARY, '-r', 'exit(1);']);

        expect($result['exitCode'])->not->toBe(0);
    });

    it('detects transient failures', function () {
        // This is a protected method, but we can test via reflection
        $reflection = new \ReflectionClass(ProcessExecutor::class);
        $method = $reflection->getMethod('isTransientFailure');
        $method->setAccessible(true);

        $transientResult = ['output' => '', 'errorOutput' => 'allowed memory size exhausted'];
        expect($method->invoke($this->executor, $transientResult))->toBeTrue();

        $nonTransientResult = ['output' => '', 'errorOutput' => 'syntax error'];
        expect($method->invoke($this->executor, $nonTransientResult))->toBeFalse();
    });

    it('collects garbage after execution', function () {
        // Just ensure it doesn't throw exceptions
        $this->executor->collectGarbage();
        expect(true)->toBeTrue();
    });

    it('cleans up processes on destruct', function () {
        $executor = new ProcessExecutor();
        // Execute something
        $executor->execute([PHP_BINARY, '-r', 'echo "test";']);
        
        // Destructor should run without errors
        unset($executor);
        expect(true)->toBeTrue();
    });
});
