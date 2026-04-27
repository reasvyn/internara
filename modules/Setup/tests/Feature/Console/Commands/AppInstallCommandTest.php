<?php

declare(strict_types=1);

use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemInstaller;

test('it asks for confirmation before installation', function () {
    $this->artisan('app:install')
        ->expectsConfirmation(__('setup::install.confirmation'), 'no')
        ->assertFailed();
});

test('it executes the installation steps correctly', function () {
    $installerMock = Mockery::mock(SystemInstaller::class);

    $installerMock->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);

    $installerMock
        ->shouldReceive('validateEnvironment')
        ->once()
        ->andReturn([
            'requirements' => ['php_version' => true],
            'permissions' => ['writable_storage' => true],
            'database' => ['connection' => true, 'message' => 'Connected'],
            'functions' => ['proc_open' => true],
        ]);

    $installerMock->shouldReceive('generateAppKey')->once()->andReturn(true);

    $installerMock->shouldReceive('runMigrations')->once()->andReturn(true);

    $installerMock->shouldReceive('runSeeders')->once()->andReturn(true);

    $installerMock->shouldReceive('createStorageSymlink')->once()->andReturn(true);

    $this->app->instance(SystemInstaller::class, $installerMock);

    $settingServiceMock = Mockery::mock(SettingService::class);
    $settingServiceMock
        ->shouldReceive('getValue')
        ->with('setup_token')
        ->andReturn('test-token-123');
    $settingServiceMock->shouldReceive('getValue')->with('setup_token_expires_at')->andReturn(null);
    $settingServiceMock
        ->shouldReceive('setValue')
        ->with('setup_token_expires_at', Mockery::any())
        ->andReturn(true);

    $this->app->instance(SettingService::class, $settingServiceMock);

    $this->artisan('app:install')
        ->expectsConfirmation(__('setup::install.confirmation'), 'yes')
        ->expectsOutputToContain(__('setup::install.banner.engine'))
        ->expectsOutputToContain(__('setup::install.success'))
        ->expectsOutputToContain('token=test-token-123')
        ->assertSuccessful();
});

test('it fails if environment validation fails', function () {
    $installerMock = Mockery::mock(SystemInstaller::class);

    $installerMock->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);

    $installerMock
        ->shouldReceive('validateEnvironment')
        ->once()
        ->andReturn([
            'requirements' => ['php_version' => false],
            'permissions' => ['writable_storage' => true],
            'database' => ['connection' => true, 'message' => 'Connected'],
            'functions' => ['proc_open' => true],
        ]);

    $this->app->instance(SystemInstaller::class, $installerMock);

    $this->artisan('app:install')
        ->expectsConfirmation(__('setup::install.confirmation'), 'yes')
        ->assertFailed();
});

test('it forces installation if flag is provided', function () {
    $installerMock = Mockery::mock(SystemInstaller::class);
    $installerMock->shouldReceive('ensureEnvFileExists')->andReturn(true);
    $installerMock->shouldReceive('validateEnvironment')->andReturn([
        'requirements' => ['php_version' => true],
        'permissions' => ['writable_storage' => true],
        'database' => ['connection' => true, 'message' => 'Connected'],
    ]);
    $installerMock->shouldReceive('generateAppKey')->andReturn(true);
    $installerMock->shouldReceive('runMigrations')->andReturn(true);
    $installerMock->shouldReceive('runSeeders')->andReturn(true);
    $installerMock->shouldReceive('createStorageSymlink')->andReturn(true);

    $this->app->instance(SystemInstaller::class, $installerMock);

    $settingServiceMock = Mockery::mock(SettingService::class);
    $settingServiceMock
        ->shouldReceive('getValue')
        ->with('setup_token')
        ->andReturn('test-token-123');
    $settingServiceMock
        ->shouldReceive('getValue')
        ->with('setup_token_expires_at')
        ->andReturn(now()->toIso8601String());

    $this->app->instance(SettingService::class, $settingServiceMock);

    // No expectsConfirmation needed because of --force
    $this->artisan('app:install', ['--force' => true])->assertSuccessful();
});
