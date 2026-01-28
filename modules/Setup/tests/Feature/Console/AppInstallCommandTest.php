<?php

declare(strict_types=1);

use Modules\Setup\Services\Contracts\InstallerService;

test('it asks for confirmation before installation', function () {
    $this->artisan('app:install')
        ->expectsConfirmation(
            'This will reset your database and initialize the system. Do you want to proceed?',
            'no',
        )
        ->assertFailed();
});

test('it executes the installation steps correctly', function () {
    $installerMock = Mockery::mock(InstallerService::class);

    $installerMock->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);

    $installerMock
        ->shouldReceive('validateEnvironment')
        ->once()
        ->andReturn([
            'requirements' => ['php_version' => true],
            'permissions' => ['writable_storage' => true],
            'database' => ['connection' => true, 'message' => 'Connected'],
        ]);

    $installerMock->shouldReceive('generateAppKey')->once()->andReturn(true);

    $installerMock->shouldReceive('runMigrations')->once()->andReturn(true);

    $installerMock->shouldReceive('runSeeders')->once()->andReturn(true);

    $installerMock->shouldReceive('createStorageSymlink')->once()->andReturn(true);

    $this->app->instance(InstallerService::class, $installerMock);

    $settingServiceMock = Mockery::mock(\Modules\Setting\Services\Contracts\SettingService::class);
    $settingServiceMock
        ->shouldReceive('getValue')
        ->with('setup_token')
        ->andReturn('test-token-123');
    $this->app->instance(
        \Modules\Setting\Services\Contracts\SettingService::class,
        $settingServiceMock,
    );

    $this->artisan('app:install')
        ->expectsConfirmation(
            'This will reset your database and initialize the system. Do you want to proceed?',
            'yes',
        )
        ->expectsOutputToContain('Internara System Installation')
        ->expectsOutputToContain('Technical installation completed successfully!')
        ->expectsOutputToContain('token=test-token-123')
        ->assertSuccessful();
});

test('it fails if environment validation fails', function () {
    $installerMock = Mockery::mock(InstallerService::class);

    $installerMock->shouldReceive('ensureEnvFileExists')->once()->andReturn(true);

    $installerMock
        ->shouldReceive('validateEnvironment')
        ->once()
        ->andReturn([
            'requirements' => ['php_version' => false],
            'permissions' => ['writable_storage' => true],
            'database' => ['connection' => true, 'message' => 'Connected'],
        ]);

    $this->app->instance(InstallerService::class, $installerMock);

    $this->artisan('app:install')
        ->expectsConfirmation(
            'This will reset your database and initialize the system. Do you want to proceed?',
            'yes',
        )
        ->assertFailed();
});

test('it forces installation if flag is provided', function () {
    $installerMock = Mockery::mock(InstallerService::class);
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

    $this->app->instance(InstallerService::class, $installerMock);

    $settingServiceMock = Mockery::mock(\Modules\Setting\Services\Contracts\SettingService::class);
    $settingServiceMock
        ->shouldReceive('getValue')
        ->with('setup_token')
        ->andReturn('test-token-123');
    $this->app->instance(
        \Modules\Setting\Services\Contracts\SettingService::class,
        $settingServiceMock,
    );

    // No expectsConfirmation needed because of --force
    $this->artisan('app:install', ['--force' => true])->assertSuccessful();
});
