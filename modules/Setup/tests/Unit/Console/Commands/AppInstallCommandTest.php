<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Mockery;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\InstallerService;

describe('AppInstallCommand', function () {
    beforeEach(function () {
        app()->setLocale('en');
        $this->installerService = Mockery::mock(InstallerService::class);
        $this->settingService = Mockery::mock(SettingService::class);

        $this->app->instance(InstallerService::class, $this->installerService);
        $this->app->instance(SettingService::class, $this->settingService);

        Config::set('app.env', 'local');
        Config::set('app.version', '0.14.0');
    });

    it('aborts installation when user denies confirmation', function () {
        Artisan::command('app:install', function () {
            // Simulated behavior via Artisan::call
        });

        $this->artisan('app:install')
            ->expectsConfirmation(__('setup::install.warnings.destructive_confirmation'), 'no')
            ->expectsOutputToContain('Installation aborted by user.')
            ->assertExitCode(1);
    });

    it('displays critical warning in production environment', function () {
        Config::set('app.env', 'production');

        $this->installerService->shouldReceive('ensureEnvFileExists')->andReturn(true);
        $this->installerService->shouldReceive('validateEnvironment')->andReturn(['requirements' => [], 'permissions' => []]);
        $this->installerService->shouldReceive('generateAppKey')->andReturn(true);
        $this->installerService->shouldReceive('runMigrations')->andReturn(true);
        $this->installerService->shouldReceive('runSeeders')->andReturn(true);
        $this->installerService->shouldReceive('createStorageSymlink')->andReturn(true);

        $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('test-token');

        $this->artisan('app:install')
            ->expectsOutputToContain('CRITICAL WARNING')
            ->expectsOutputToContain('You are running this command in a PRODUCTION environment.')
            ->expectsConfirmation('Are you absolutely certain you want to proceed with this destructive operation?', 'no')
            ->assertExitCode(1);
    });

    it('completes installation successfully with valid environment', function () {
        $this->installerService->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);
        $this->installerService->shouldReceive('validateEnvironment')->once()->andReturn([
            'requirements' => ['php' => true],
            'permissions' => ['storage' => true],
        ]);
        $this->installerService->shouldReceive('generateAppKey')->once()->andReturn(true);
        $this->installerService->shouldReceive('runMigrations')->once()->andReturn(true);
        $this->installerService->shouldReceive('runSeeders')->once()->andReturn(true);
        $this->installerService->shouldReceive('createStorageSymlink')->once()->andReturn(true);

        $this->settingService->shouldReceive('getValue')->with('setup_token')->once()->andReturn('secure-token');

        $this->artisan('app:install')
            ->expectsConfirmation('Prosedur ini akan mereset database dan menginisialisasi sistem. Apakah Anda ingin melanjutkan?', 'yes')
            ->expectsOutputToContain('Core system initialization completed successfully.')
            ->expectsOutputToContain('secure-token')
            ->assertExitCode(0);
    });

    it('fails when a critical task returns false', function () {
        $this->installerService->shouldReceive('ensureEnvFileExists')->once()->andReturn(false);

        $this->artisan('app:install')
            ->expectsConfirmation('Prosedur ini akan mereset database dan menginisialisasi sistem. Apakah Anda ingin melanjutkan?', 'yes')
            ->expectsOutputToContain('Critical system task failure: Infrastructure: Provisioning environment configuration')
            ->assertExitCode(1);
    });

    it('displays environment validation failures correctly', function () {
        $this->installerService->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);
        $this->installerService->shouldReceive('validateEnvironment')->once()->andReturn([
            'requirements' => ['extension_bcmath' => false],
            'permissions' => ['bootstrap_cache' => false],
            'database' => [
                'connection' => false,
                'message' => 'Access denied for user=admin;password=secret123'
            ]
        ]);

        $this->artisan('app:install')
            ->expectsConfirmation('Prosedur ini akan mereset database dan menginisialisasi sistem. Apakah Anda ingin melanjutkan?', 'yes')
            ->expectsOutputToContain('requirements.extension_bcmath')
            ->expectsOutputToContain('permissions.bootstrap_cache')
            ->expectsOutputToContain('database.connection: Access denied for user=****;password=****')
            ->assertExitCode(1);
    });
});
