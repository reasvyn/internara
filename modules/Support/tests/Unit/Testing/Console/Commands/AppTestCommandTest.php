<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Console\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mockery;
use Modules\Support\Testing\Support\TargetDiscovery;
use Modules\Support\Testing\Support\TestExecutor;
use Modules\Support\Testing\Support\TestSessionManager;

describe('AppTestCommand', function () {
    beforeEach(function () {
        $this->discovery = Mockery::mock(TargetDiscovery::class);
        $this->executor = Mockery::mock(TestExecutor::class);

        $this->app->instance(TargetDiscovery::class, $this->discovery);
        $this->app->instance(TestExecutor::class, $this->executor);

        Config::set('app.env', 'testing');
        Config::set('app.version', '0.14.0');

        // Clean up session data
        TestSessionManager::clearAll();
    });

    it('displays the modular verification banner', function () {
        $this->discovery->shouldReceive('identify')->andReturn([]);

        $this->artisan('app:test')
            ->expectsOutputToContain('INTERNARA')
            ->expectsOutputToContain('MODULAR VERIFICATION ENGINE')
            ->assertExitCode(0);
    });

    it('successfully exports JUnit and JSON reports', function () {
        $junitPath = storage_path('test-results/junit.xml');
        $jsonPath = storage_path('test-results/report.json');

        $this->discovery
            ->shouldReceive('identify')
            ->twice()
            ->andReturn([
                [
                    'label' => 'Core',
                    'path' => base_path('modules/Core/tests'),
                    'segments' => ['Unit'],
                ],
            ]);

        // Mock directory check
        File::shouldReceive('isDirectory')->andReturn(true);

        $this->executor->shouldReceive('execute')->once()->andReturn(true);

        $this->artisan('app:test --log-junit=' . $junitPath . ' --log-json=' . $jsonPath)
            ->expectsOutputToContain('JUnit XML report exported to')
            ->expectsOutputToContain('JSON report exported to')
            ->assertExitCode(0);

        expect(File::exists($junitPath))->toBeTrue();
        expect(File::exists($jsonPath))->toBeTrue();

        File::delete([$junitPath, $jsonPath]);
    });

    it('enforces stability threshold for CI/CD', function () {
        $this->discovery
            ->shouldReceive('identify')
            ->twice()
            ->andReturn([
                [
                    'label' => 'Core',
                    'path' => base_path('modules/Core/tests'),
                    'segments' => ['Unit', 'Feature'],
                ],
            ]);

        File::shouldReceive('isDirectory')->andReturn(true);

        // One pass, one fail = 50% stability
        $this->executor->shouldReceive('execute')->twice()->andReturn(true, false);

        // Fail when threshold is 80%
        $this->artisan('app:test --fail-on-stability=80')
            ->expectsOutputToContain(
                'Stability failure: Global pass rate (50.00%) is below required threshold (80.00%)',
            )
            ->assertExitCode(1);

        // Pass when threshold is 40%
        $this->executor->shouldReceive('execute')->twice()->andReturn(true, false);
        $this->artisan('app:test --fail-on-stability=40')->assertExitCode(0);
    });

    it('resumes previous session with --continue', function () {
        $session = new TestSessionManager('test-session');
        $session->record('Core', 'Unit', true, 'Output', '');

        $this->discovery
            ->shouldReceive('identify')
            ->twice()
            ->andReturn([
                [
                    'label' => 'Core',
                    'path' => base_path('modules/Core/tests'),
                    'segments' => ['Unit', 'Feature'],
                ],
            ]);

        File::shouldReceive('isDirectory')->andReturn(true);

        // Should only execute 'Feature' since 'Unit' is already passed in session
        $this->executor
            ->shouldReceive('execute')
            ->once()
            ->with(
                Mockery::on(fn($path) => str_contains($path, 'Feature')),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            )
            ->andReturn(true);

        $this->artisan('app:test --session=test-session --continue')
            ->expectsOutputToContain('PASS (Saved)')
            ->assertExitCode(0);
    });
});
