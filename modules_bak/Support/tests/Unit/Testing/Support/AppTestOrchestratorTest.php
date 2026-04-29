<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Support;

use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use Modules\Support\Contracts\Testing\OrchestratorInterface;
use Modules\Support\Contracts\Testing\ProcessExecutorInterface;
use Modules\Support\Contracts\Testing\ResultReporterInterface;
use Modules\Support\Contracts\Testing\SessionManagerInterface;
use Modules\Support\Contracts\Testing\TargetDiscoveryInterface;
use Modules\Support\Testing\Support\AppTestOrchestrator;

describe('AppTestOrchestrator', function () {
    beforeEach(function () {
        $this->discovery = Mockery::mock(TargetDiscoveryInterface::class);
        $this->executor = Mockery::mock(ProcessExecutorInterface::class);
        $this->sessionManager = Mockery::mock(SessionManagerInterface::class);
        $this->reporter = Mockery::mock(ResultReporterInterface::class);

        $this->orchestrator = new AppTestOrchestrator(
            $this->discovery,
            $this->executor,
            $this->sessionManager,
            $this->reporter,
        );
    });

    it('implements OrchestratorInterface', function () {
        expect($this->orchestrator)->toBeInstanceOf(OrchestratorInterface::class);
    });

    it('executes test segments successfully', function () {
        $targets = [
            [
                'label' => 'TestModule',
                'path' => '/fake/path',
                'segments' => ['Unit'],
            ],
        ];

        $this->discovery->shouldReceive('discover')
            ->once()
            ->andReturn($targets);

        $this->executor->shouldReceive('setTimeout')
            ->once()
            ->andReturnSelf();

        $this->executor->shouldReceive('setParallel')
            ->once()
            ->andReturnSelf();

        $this->executor->shouldReceive('execute')
            ->once()
            ->andReturn([
                'output' => 'Test output',
                'errorOutput' => '',
                'exitCode' => 0,
                'peakMemory' => 1024,
            ]);

        $this->sessionManager->shouldReceive('record')
            ->once();

        $this->sessionManager->shouldReceive('isPassed')
            ->once()
            ->andReturn(false);

        $result = $this->orchestrator->execute();

        expect($result['success'])->toBeTrue();
        expect($result['results'])->toHaveCount(1);
    });

    it('handles missing modules', function () {
        $missing = [];
        $this->discovery->shouldReceive('discover')
            ->once()
            ->andReturnUsing(function ($modules, $dirty, &$missingRef) {
                $missingRef = ['NonExistentModule'];
                return [];
            });

        $result = $this->orchestrator->execute();

        expect($result['success'])->toBeTrue();
        expect($this->orchestrator->getMissingModules())->toContain('nonexistentmodule');
    });

    it('skips segments based on options', function () {
        $targets = [
            [
                'label' => 'TestModule',
                'path' => '/fake/path',
                'segments' => ['Arch', 'Unit'],
            ],
        ];

        $this->discovery->shouldReceive('discover')
            ->once()
            ->andReturn($targets);

        $options = [
            'no-arch' => true,
        ];

        $this->executor->shouldReceive('execute')
            ->once() // Only Unit should execute
            ->andReturn([
                'output' => 'Test output',
                'errorOutput' => '',
                'exitCode' => 0,
                'peakMemory' => 1024,
            ]);

        $this->sessionManager->shouldReceive('record')
            ->once();

        $this->sessionManager->shouldReceive('isPassed')
            ->once()
            ->andReturn(false);

        $result = $this->orchestrator->execute($options);

        expect($result['results'])->toHaveCount(2); // One skipped, one executed
        expect($result['results'][0]['status'])->toBe('skipped');
    });

    it('lists segments without executing', function () {
        $targets = [
            [
                'label' => 'TestModule',
                'path' => '/fake/path',
            ],
        ];

        $this->discovery->shouldReceive('discover')
            ->once()
            ->andReturn($targets);

        $segments = $this->orchestrator->listSegments();

        expect($segments)->toHaveCount(1);
        expect($segments[0]['label'])->toBe('TestModule');
    });

    it('generates report', function () {
        $this->sessionManager->shouldReceive('getResults')
            ->once()
            ->andReturn([
                ['module' => 'TestModule', 'type' => 'Unit', 'success' => true, 'timestamp' => now()->toIso8601String()],
            ]);

        $this->reporter->shouldReceive('displaySessionMetrics')
            ->once()
            ->andReturn(100.0);

        $passRate = $this->orchestrator->report();

        expect($passRate)->toBe(100.0);
    });

    it('evaluates stability correctly', function () {
        expect($this->orchestrator->evaluateStability(100, 90))->toBe(0);
        expect($this->orchestrator->evaluateStability(80, 90))->toBe(1);
    });

    it('clears sessions', function () {
        $this->sessionManager->shouldReceive('clearAll')
            ->once();

        $this->orchestrator->clearSessions();
    });
});
