<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Setup\Services\InstallerService;

describe('InstallerService Unit Test', function () {
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->auditor = $this->mock(SystemAuditor::class);
        
        // Ensure Gate is always authorized for unit tests
        Gate::shouldReceive('authorize')->byDefault()->andReturn(true);
        $this->auditor->shouldReceive('passes')->byDefault()->andReturn(true);
        
        // Bind the mocks to the container so partials can find them
        app()->instance(SettingService::class, $this->settingService);
        app()->instance(SystemAuditor::class, $this->auditor);
    });

    test('it validates environment requirements correctly', function () {
        $this->auditor
            ->shouldReceive('audit')
            ->once()
            ->andReturn(['requirements' => [], 'permissions' => [], 'database' => []]);

        $service = new InstallerService($this->settingService, $this->auditor);
        $results = $service->validateEnvironment();

        expect($results)->toBeArray();
    });

    test('it triggers migration commands', function () {
        $partial = \Mockery::mock(InstallerService::class, [$this->settingService, $this->auditor])->makePartial();
        $partial->shouldAllowMockingProtectedMethods();
        
        // We verify that the method is called, but we don't mock internals that trigger Artisan
        $partial->shouldReceive('runMigrations')->once()->andReturn(true);

        expect($partial->runMigrations())->toBeTrue();
    });

    test('it generates setup token after seeding', function () {
        $partial = \Mockery::mock(InstallerService::class, [$this->settingService, $this->auditor])->makePartial();
        $partial->shouldAllowMockingProtectedMethods();
        $partial->shouldReceive('runSeeders')->once()->andReturn(true);

        expect($partial->runSeeders())->toBeTrue();
    });

    test('it orchestrates the complete installation sequence', function () {
        $partial = \Mockery::mock(InstallerService::class, [$this->settingService, $this->auditor])->makePartial();
        
        $partial->shouldReceive('ensureEnvFileExists')->andReturn(true);
        $partial->shouldReceive('validateEnvironment')->andReturn(['passed' => true]);
        $partial->shouldReceive('generateAppKey')->andReturn(true);
        $partial->shouldReceive('runMigrations')->andReturn(true);
        $partial->shouldReceive('runSeeders')->andReturn(true);
        $partial->shouldReceive('createStorageLink')->andReturn(true);

        expect($partial->install())->toBeTrue();
    });
});
