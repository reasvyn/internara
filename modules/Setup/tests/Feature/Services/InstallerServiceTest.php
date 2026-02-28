<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Setup\Services\InstallerService;

describe('InstallerService Feature Test', function () {
    
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->auditor = $this->mock(SystemAuditor::class);
        
        $this->installer = new InstallerService(
            $this->settingService,
            $this->auditor
        );
    });

    describe('Environmental Preparation', function () {
        test('it ensures .env file exists and copies from .env.example if missing', function () {
            $envPath = base_path('.env');
            $examplePath = base_path('.env.example');
            
            // Backup existing .env
            $originalEnv = File::exists($envPath) ? File::get($envPath) : null;
            if ($originalEnv) File::delete($envPath);
            
            // Setup example
            File::put($examplePath, 'APP_KEY=');
            
            expect($this->installer->ensureEnvFileExists())->toBeTrue();
            expect(File::exists($envPath))->toBeTrue();
            
            // Cleanup and Restore
            File::delete($envPath);
            if ($originalEnv) File::put($envPath, $originalEnv);
        });

        test('it audits system environment and prevents install if auditor fails', function () {
            $this->auditor->shouldReceive('passes')
                ->once()
                ->andReturn(false);
                
            expect($this->installer->install())->toBeFalse();
        });
    });

    describe('System Bootstrapping', function () {
        test('it can generate a secure application key', function () {
            Artisan::shouldReceive('call')
                ->with('key:generate', ['--force' => true])
                ->once()
                ->andReturn(0);
                
            expect($this->installer->generateAppKey())->toBeTrue();
        });

        test('it performs fresh migration if database is already initialized', function () {
            // Mock Schema check to simulate existing table
            Schema::shouldReceive('hasTable')
                ->once()
                ->with('migrations')
                ->andReturn(true);
            
            // Mock DB count check
            \Illuminate\Support\Facades\DB::shouldReceive('table')
                ->once()
                ->with('migrations')
                ->andReturn(new class {
                    public function count() { return 1; }
                });

            Artisan::shouldReceive('call')
                ->with('migrate:fresh', ['--force' => true])
                ->once()
                ->andReturn(0);
                
            expect($this->installer->runMigrations())->toBeTrue();
        });

        test('it performs standard migration for fresh installations', function () {
            Schema::shouldReceive('hasTable')
                ->once()
                ->with('migrations')
                ->andReturn(false);
            
            Artisan::shouldReceive('call')
                ->with('migrate', ['--force' => true])
                ->once()
                ->andReturn(0);
                
            expect($this->installer->runMigrations())->toBeTrue();
        });

        test('it runs system seeders and generates a secure setup token', function () {
            Artisan::shouldReceive('call')
                ->with('db:seed', ['--force' => true])
                ->once()
                ->andReturn(0);
                
            $this->settingService->shouldReceive('setValue')
                ->with('setup_token', \Mockery::type('string'))
                ->once();
                
            expect($this->installer->runSeeders())->toBeTrue();
        });

        test('it establishes the public storage link', function () {
            // Mock File to say storage link doesn't exist
            File::shouldReceive('exists')
                ->with(public_path('storage'))
                ->once()
                ->andReturn(false);

            Artisan::shouldReceive('call')
                ->with('storage:link')
                ->once()
                ->andReturn(0);
                
            expect($this->installer->createStorageSymlink())->toBeTrue();
        });
    });

    describe('Installation Idempotency (V&V BP-SYS-01)', function () {
        test('it can safely run the install process multiple times without corruption', function () {
            // Mock successful environment and audit
            $this->auditor->shouldReceive('passes')->twice()->andReturn(true);
            
            // Mock env file check (once per install)
            File::shouldReceive('exists')->with(base_path('.env'))->twice()->andReturn(true);

            // Mock key generation
            Artisan::shouldReceive('call')->with('key:generate', ['--force' => true])->twice()->andReturn(0);
            
            // Mock migrations
            Schema::shouldReceive('hasTable')->with('migrations')->twice()->andReturn(false);
            Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->twice()->andReturn(0);
            
            // Mock seeders
            Artisan::shouldReceive('call')->with('db:seed', ['--force' => true])->twice()->andReturn(0);
            $this->settingService->shouldReceive('setValue')->with('setup_token', \Mockery::any())->twice();
            
            // Mock storage link (once per install)
            File::shouldReceive('exists')->with(public_path('storage'))->twice()->andReturn(false);
            Artisan::shouldReceive('call')->with('storage:link')->twice()->andReturn(0);
            
            // Run 1
            expect($this->installer->install())->toBeTrue();
            
            // Run 2
            expect($this->installer->install())->toBeTrue();
        });
    });
});
