<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Config;
use Mockery;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemInstaller;

describe('AppInstallCommand', function () {
    beforeEach(function () {
        app()->setLocale('en');
        $this->installerService = Mockery::mock(SystemInstaller::class);
        $this->settingService = Mockery::mock(SettingService::class);

        $this->app->instance(SystemInstaller::class, $this->installerService);
        $this->app->instance(SettingService::class, $this->settingService);

        Config::set('app.env', 'local');
        Config::set('app.version', '0.14.0');
    });

    it('aborts installation when user denies confirmation', function () {
        $this->artisan('app:install')
            ->expectsConfirmation(__('setup::install.confirmation'), 'no')
            ->expectsOutputToContain(__('setup::install.warnings.aborted'))
            ->assertExitCode(1);
    });

    it('displays critical warning in production environment', function () {
        Config::set('app.env', 'production');

        $this->artisan('app:install')
            ->expectsOutputToContain('CRITICAL WARNING')
            ->expectsOutputToContain('You are running this command in a PRODUCTION environment.')
            ->expectsConfirmation(__('setup::install.warnings.production_confirm'), 'no')
            ->assertExitCode(1);
    });

    it('completes installation successfully with valid environment', function () {
        $this->installerService->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);
        $this->installerService
            ->shouldReceive('validateEnvironment')
            ->once()
            ->andReturn([
                'requirements' => ['php' => true],
                'permissions' => ['storage' => true],
                'database' => ['connection' => true],
                'functions' => ['proc_open' => true],
            ]);
        $this->installerService->shouldReceive('generateAppKey')->once()->andReturn(true);
        $this->installerService->shouldReceive('runMigrations')->once()->andReturn(true);
        $this->installerService->shouldReceive('runSeeders')->once()->andReturn(true);
        $this->installerService->shouldReceive('createStorageSymlink')->once()->andReturn(true);

        $this->settingService
            ->shouldReceive('getValue')
            ->with('setup_token')
            ->once()
            ->andReturn('secure-token');
        $this->settingService
            ->shouldReceive('getValue')
            ->with('setup_token_expires_at')
            ->andReturn(now()->toIso8601String());
        $this->settingService
            ->shouldReceive('setValue')
            ->with('setup_token_expires_at', Mockery::any())
            ->andReturn(true);

        $this->artisan('app:install')
            ->expectsConfirmation(__('setup::install.confirmation'), 'yes')
            ->expectsOutputToContain(__('setup::install.success'))
            ->expectsOutputToContain('secure-token')
            ->assertExitCode(0);
    });

    it('fails when a critical task returns false', function () {
        $this->installerService->shouldReceive('ensureEnvFileExists')->once()->andReturn(false);

        $this->artisan('app:install')
            ->expectsConfirmation(__('setup::install.confirmation'), 'yes')
            ->expectsOutputToContain(
                'Critical system task failure: ' . __('setup::install.tasks.env'),
            )
            ->assertExitCode(1);
    });

    it('displays environment validation failures correctly', function () {
        $this->installerService->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);
        $this->installerService
            ->shouldReceive('validateEnvironment')
            ->once()
            ->andReturn([
                'requirements' => ['extension_bcmath' => false],
                'permissions' => ['bootstrap_cache' => false],
                'database' => [
                    'connection' => false,
                    'message' => 'Access denied for user=admin;password=secret123',
                ],
            ]);
        $this->artisan('app:install')
            ->expectsConfirmation(__('setup::install.confirmation'), 'yes')
            ->expectsOutputToContain('requirements.extension_bcmath')
            ->expectsOutputToContain('permissions.bootstrap_cache')
            ->expectsOutputToContain(
                'database.connection: Access denied for user=****;password=****',
            )
            ->assertExitCode(1);
    });
});
