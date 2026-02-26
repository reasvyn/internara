<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Setup\Services\InstallerService;

beforeEach(function () {
    $this->settingService = Mockery::mock(SettingService::class);
    $this->auditor = Mockery::mock(SystemAuditor::class);
    $this->service = new InstallerService($this->settingService, $this->auditor);
});

test('it validates environment requirements correctly', function () {
    $this->auditor
        ->shouldReceive('audit')
        ->once()
        ->andReturn(['requirements' => [], 'permissions' => [], 'database' => []]);

    $results = $this->service->validateEnvironment();

    expect($results)
        ->toBeArray()
        ->toHaveKeys(['requirements', 'permissions', 'database']);
});

test('it runs migrations with force flag for fresh installation', function () {
    \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')
        ->with('migrations')
        ->andReturn(false);

    Artisan::shouldReceive('call')
        ->with('migrate', ['--force' => true])
        ->once()
        ->andReturn(0);

    $result = $this->service->runMigrations();

    expect($result)->toBeTrue();
});

test('it runs migrate:fresh with force flag if migrations already exist', function () {
    \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')
        ->with('migrations')
        ->andReturn(true);
    \Illuminate\Support\Facades\DB::shouldReceive('table')
        ->with('migrations')
        ->andReturn(Mockery::mock(['count' => 5]));

    Artisan::shouldReceive('call')
        ->with('migrate:fresh', ['--force' => true])
        ->once()
        ->andReturn(0);

    $result = $this->service->runMigrations();

    expect($result)->toBeTrue();
});

test('it returns false if migrations fail', function () {
    \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')->andReturn(false);
    Artisan::shouldReceive('call')->andReturn(1);

    $result = $this->service->runMigrations();

    expect($result)->toBeFalse();
});

test('it runs seeders with force flag and generates token', function () {
    Artisan::shouldReceive('call')
        ->with('db:seed', ['--force' => true])
        ->once()
        ->andReturn(0);

    $this->settingService
        ->shouldReceive('setValue')
        ->with('setup_token', Mockery::type('string'))
        ->once();

    $result = $this->service->runSeeders();

    expect($result)->toBeTrue();
});

test('it creates storage symlink if it does not exist', function () {
    File::shouldReceive('exists')->with(public_path('storage'))->andReturn(false);

    Artisan::shouldReceive('call')->with('storage:link')->once()->andReturn(0);

    $result = $this->service->createStorageSymlink();

    expect($result)->toBeTrue();
});

test('it skip storage symlink creation if it already exists', function () {
    File::shouldReceive('exists')->with(public_path('storage'))->andReturn(true);

    Artisan::shouldReceive('call')->never();

    $result = $this->service->createStorageSymlink();

    expect($result)->toBeTrue();
});

test('it ensures env file exists', function () {
    File::shouldReceive('exists')->with(base_path('.env'))->andReturn(false);

    File::shouldReceive('exists')->with(base_path('.env.example'))->andReturn(true);

    File::shouldReceive('copy')->once()->andReturn(true);

    $result = $this->service->ensureEnvFileExists();

    expect($result)->toBeTrue();
});

test('it generates app key', function () {
    Artisan::shouldReceive('call')
        ->with('key:generate', ['--force' => true])
        ->once()
        ->andReturn(0);

    $result = $this->service->generateAppKey();

    expect($result)->toBeTrue();
});

test('it orchestrates the complete installation process', function () {
    // 1. Mock Env Existence
    File::shouldReceive('exists')->andReturn(true);

    // 2. Mock Environment Validation
    $this->auditor->shouldReceive('passes')->once()->andReturn(true);

    // 3. Mock Key Generate
    Artisan::shouldReceive('call')
        ->with('key:generate', ['--force' => true])
        ->andReturn(0);

    // 4. Mock Migrations
    \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')
        ->with('migrations')
        ->andReturn(false);
    Artisan::shouldReceive('call')
        ->with('migrate', ['--force' => true])
        ->andReturn(0);

    // 5. Mock Seeders & Token
    Artisan::shouldReceive('call')
        ->with('db:seed', ['--force' => true])
        ->andReturn(0);
    $this->settingService
        ->shouldReceive('setValue')
        ->with('setup_token', Mockery::type('string'))
        ->once();

    // 6. Mock Symlink
    Artisan::shouldReceive('call')->with('storage:link')->andReturn(0);

    expect($result)->toBeTrue();
});

test('it is idempotent and can be run multiple times safely', function () {
    // 1. Mock Environment Validation
    $this->auditor->shouldReceive('passes')->twice()->andReturn(true);
    
    // 2. Mock File Existence
    File::shouldReceive('exists')->andReturn(true);

    // 3. Mock Key Generate
    Artisan::shouldReceive('call')->with('key:generate', ['--force' => true])->twice()->andReturn(0);

    // 4. Mock Migrations (migrations table exists on second run)
    \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')
        ->with('migrations')
        ->andReturn(false, true); // First false, then true
    
    \Illuminate\Support\Facades\DB::shouldReceive('table')
        ->with('migrations')
        ->andReturn(Mockery::mock(['count' => 5]));

    Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('migrate:fresh', ['--force' => true])->once()->andReturn(0);

    // 5. Mock Seeders
    Artisan::shouldReceive('call')->with('db:seed', ['--force' => true])->twice()->andReturn(0);
    
    $this->settingService->shouldReceive('setValue')->twice();

    // 6. Mock Symlink
    Artisan::shouldReceive('call')->with('storage:link')->twice()->andReturn(0);

    // First run
    expect($this->service->install())->toBeTrue();
    
    // Second run
    expect($this->service->install())->toBeTrue();
});
