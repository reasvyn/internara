<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\Setup\Services\Contracts\InstallationAuditor;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Setup\Services\SystemInstaller;

/**
 * [S1 - Secure] Test gate authorization, encrypted tokens
 * [S2 - Sustain] Test clear error handling
 * [S3 - Scalable] Test independent operations
 */
describe('SystemInstaller', function () {
    beforeEach(function () {
        // Mock dependencies
        $this->setupService = Mockery::mock(SetupService::class);
        $this->auditor = Mockery::mock(InstallationAuditor::class);

        $this->installer = new SystemInstaller(
            $this->setupService,
            $this->auditor
        );
    });

    describe('install', function () {
        it('fails when env file cannot be created', function () {
            File::shouldReceive('exists')
                ->with(base_path('.env'))
                ->andReturn(false);
            File::shouldReceive('exists')
                ->with(base_path('.env.example'))
                ->andReturn(false);

            expect($this->installer->install())->toBeFalse();
        });

        it('fails when auditor does not pass', function () {
            File::shouldReceive('exists')->andReturn(true);
            $this->auditor->shouldReceive('passes')->andReturn(false);

            expect($this->installer->install())->toBeFalse();
        });

        it('fails when app key generation fails', function () {
            File::shouldReceive('exists')->andReturn(true);
            $this->auditor->shouldReceive('passes')->andReturn(true);
            // Simulate key already set
            config(['app.key' => '']);
            Artisan::shouldReceive('call')
                ->with('key:generate', ['--force' => true])
                ->andReturn(1); // Failure

            expect($this->installer->install())->toBeFalse();
        });
    });

    describe('ensureEnvFileExists', function () {
        it('returns true if .env already exists', function () {
            File::shouldReceive('exists')
                ->with(base_path('.env'))
                ->andReturn(true);

            expect($this->installer->ensureEnvFileExists())->toBeTrue();
        });

        it('creates .env from .env.example', function () {
            File::shouldReceive('exists')
                ->with(base_path('.env'))
                ->andReturn(false);
            File::shouldReceive('exists')
                ->with(base_path('.env.example'))
                ->andReturn(true);
            File::shouldReceive('copy')
                ->andReturn(true);

            Log::shouldReceive('info');

            expect($this->installer->ensureEnvFileExists())->toBeTrue();
        });
    });

    describe('generateAppKey', function () {
        it('skips if key already set', function () {
            config(['app.key' => 'existing-key']);
            Log::shouldReceive('info');

            expect($this->installer->generateAppKey())->toBeTrue();
        });

        it('generates key if not set', function () {
            config(['app.key' => '']);
            Artisan::shouldReceive('call')
                ->with('key:generate', ['--force' => true])
                ->andReturn(0);

            expect($this->installer->generateAppKey())->toBeTrue();
        });
    });

    describe('runMigrations', function () {
        it('runs migrate:fresh if migrations exist', function () {
            DB::shouldReceive('connection')->andReturnSelf();
            DB::shouldReceive('getPdo')->andReturnSelf();
            DB::shouldReceive('table')->andReturnSelf();
            DB::shouldReceive('exists')->andReturn(true);

            Artisan::shouldReceive('call')
                ->with('migrate:fresh', ['--force' => true])
                ->andReturn(0);

            expect($this->installer->runMigrations())->toBeTrue();
        });

        it('runs migrate if no migrations exist', function () {
            DB::shouldReceive('connection')->andReturnSelf();
            DB::shouldReceive('getPdo')->andReturnSelf();
            DB::shouldReceive('table')->andThrow(new \Exception);

            Artisan::shouldReceive('call')
                ->with('migrate', ['--force' => true])
                ->andReturn(0);

            expect($this->installer->runMigrations())->toBeTrue();
        });
    });
});
