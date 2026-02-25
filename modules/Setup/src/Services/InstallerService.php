<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\InstallerService as InstallerServiceContract;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for handling the technical installation process.
 */
class InstallerService extends BaseService implements InstallerServiceContract
{
    /**
     * InstallerService constructor.
     */
    public function __construct(
        protected SettingService $settingService,
        protected SystemAuditor $auditor,
    ) {}

    /**
     * Orchestrates the complete installation process.
     */
    public function install(): bool
    {
        if (! $this->ensureEnvFileExists()) {
            return false;
        }

        if (! $this->auditor->passes()) {
            return false;
        }

        if (! $this->generateAppKey()) {
            return false;
        }

        if (! $this->runMigrations()) {
            return false;
        }

        if (! $this->runSeeders()) {
            return false;
        }

        return $this->createStorageSymlink();
    }

    /**
     * Ensures the .env file exists, creating it from .env.example if necessary.
     */
    public function ensureEnvFileExists(): bool
    {
        if (File::exists(base_path('.env'))) {
            return true;
        }

        if (File::exists(base_path('.env.example'))) {
            return File::copy(base_path('.env.example'), base_path('.env'));
        }

        return false;
    }

    /**
     * Generates the application key if not set.
     */
    public function generateAppKey(): bool
    {
        try {
            return Artisan::call('key:generate', ['--force' => true]) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validates the system environment requirements.
     */
    public function validateEnvironment(): array
    {
        return $this->auditor->audit();
    }

    /**
     * Executes the database migrations.
     * If the database is already initialized, it performs a fresh migration.
     */
    public function runMigrations(): bool
    {
        try {
            $isFresh = $this->hasExistingMigrations();
            $command = $isFresh ? 'migrate:fresh' : 'migrate';

            return Artisan::call($command, ['--force' => true]) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Checks if the migrations table exists and has entries.
     */
    protected function hasExistingMigrations(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('migrations') &&
                \Illuminate\Support\Facades\DB::table('migrations')->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Executes the core and shared database seeders and generates setup token.
     */
    public function runSeeders(): bool
    {
        try {
            $seeded = Artisan::call('db:seed', ['--force' => true]) === 0;

            if ($seeded) {
                $token = Str::random(32);
                $this->settingService->setValue('setup_token', $token);
            }

            return $seeded;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Creates the storage symbolic link.
     */
    public function createStorageSymlink(): bool
    {
        try {
            if (File::exists(public_path('storage'))) {
                return true;
            }

            return Artisan::call('storage:link') === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
