<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('install', self::class);

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
            $created = File::copy(base_path('.env.example'), base_path('.env'));
            
            if ($created) {
                \Illuminate\Support\Facades\Log::info(__('setup::install.audit_logs.env_created'));
            }

            return $created;
        }

        return false;
    }

    /**
     * Generates the application key if not set.
     */
    public function generateAppKey(): bool
    {
        // [S1 - Secure] Only generate key if it doesn't already exist to prevent
        // breaking existing encrypted data in the database.
        if (! empty(config('app.key'))) {
            \Illuminate\Support\Facades\Log::info(__('setup::install.audit_logs.key_exists_skipping'));
            return true;
        }

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
     * Performs a fresh migration if existing migrations are detected to ensure a clean state.
     */
    public function runMigrations(bool $force = false): bool
    {
        try {
            $hasMigrations = $this->hasExistingMigrations();
            
            // [S1 - Secure] Consistent Clean State Mandate
            // The installation process implies a full system reset. 
            // We use migrate:fresh if any migrations exist to wipe old data.
            $command = $hasMigrations ? 'migrate:fresh' : 'migrate';

            $result = Artisan::call($command, ['--force' => true]) === 0;

            if ($result) {
                \Illuminate\Support\Facades\Log::info(__('setup::install.audit_logs.migrations_executed', ['command' => $command]), [
                    'command' => $command,
                    'is_fresh' => $hasMigrations,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Migration failure during installation: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
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
                \Illuminate\Support\Facades\DB::table('migrations')->exists();
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
            return \Illuminate\Support\Facades\DB::transaction(function () {
                $seeded = Artisan::call('db:seed', ['--force' => true]) === 0;

                if ($seeded) {
                    $token = Str::random(32);
                    $this->settingService->setValue('setup_token', $token);

                    \Illuminate\Support\Facades\Log::info(__('setup::install.audit_logs.seeding_completed'));
                }

                return $seeded;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Seeding failure during installation: ' . $e->getMessage());
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
