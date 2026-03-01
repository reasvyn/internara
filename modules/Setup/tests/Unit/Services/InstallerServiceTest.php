<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Setup\Services\InstallerService;

describe('InstallerService Unit Test', function () {
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->auditor = $this->mock(SystemAuditor::class);
        $this->service = new InstallerService($this->settingService, $this->auditor);

        // Standard DB Mocking for Unit Tests using standard Mockery to avoid Pest mock class collision
        $dbConnection = \Mockery::mock(\Illuminate\Database\ConnectionInterface::class);
        $dbConnection->shouldReceive('getSchemaBuilder')->andReturn(\Mockery::mock(\Illuminate\Database\Schema\Builder::class));
        $dbConnection->shouldReceive('getPdo')->andReturn(true);

        DB::shouldReceive('connection')->andReturn($dbConnection);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());
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
        Schema::shouldReceive('hasTable')
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
        Schema::shouldReceive('hasTable')
            ->with('migrations')
            ->andReturn(true);
        
        DB::shouldReceive('table')
            ->with('migrations')
            ->andReturn(\Mockery::mock(['count' => 5]));

        Artisan::shouldReceive('call')
            ->with('migrate:fresh', ['--force' => true])
            ->once()
            ->andReturn(0);

        $result = $this->service->runMigrations();

        expect($result)->toBeTrue();
    });

    test('it runs seeders with force flag and generates token', function () {
        Artisan::shouldReceive('call')
            ->with('db:seed', ['--force' => true])
            ->once()
            ->andReturn(0);

        $this->settingService
            ->shouldReceive('setValue')
            ->with('setup_token', \Mockery::type('string'))
            ->once();

        $result = $this->service->runSeeders();

        expect($result)->toBeTrue();
    });

    test('it orchestrates the complete installation process', function () {
        File::shouldReceive('exists')->andReturn(true);
        $this->auditor->shouldReceive('passes')->once()->andReturn(true);
        Artisan::shouldReceive('call')->with('key:generate', ['--force' => true])->andReturn(0);
        Schema::shouldReceive('hasTable')->with('migrations')->andReturn(false);
        Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('call')->with('db:seed', ['--force' => true])->andReturn(0);
        $this->settingService->shouldReceive('setValue')->with('setup_token', \Mockery::type('string'))->once();
        Artisan::shouldReceive('call')->with('storage:link')->andReturn(0);

        $result = $this->service->install();

        expect($result)->toBeTrue();
    });
});
