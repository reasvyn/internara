<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\InstallationAuditor;
use Modules\Setup\Services\Contracts\SystemInstaller as Contract;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for handling technical system installation.
 */
class SystemInstaller extends BaseService implements Contract
{
    /**
     * SystemInstaller constructor.
     */
    public function __construct(
        protected SettingService $settingService,
        protected InstallationAuditor $auditor,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        Gate::authorize('install', self::class);

        if (!$this->ensureEnvFileExists()) {
            return false;
        }

        if (!$this->auditor->passes()) {
            return false;
        }

        if (!$this->generateAppKey()) {
            return false;
        }

        if (!$this->runMigrations()) {
            return false;
        }

        if (!$this->runSeeders()) {
            return false;
        }

        return $this->createStorageSymlink();
    }

    /**
     * {@inheritdoc}
     */
    public function ensureEnvFileExists(): bool
    {
        if (File::exists(base_path('.env'))) {
            return true;
        }

        if (File::exists(base_path('.env.example'))) {
            $created = File::copy(base_path('.env.example'), base_path('.env'));

            if ($created) {
                Log::info(__('setup::install.audit_logs.env_created'));
            }

            return $created;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAppKey(): bool
    {
        if (!empty(config('app.key'))) {
            Log::info(__('setup::install.audit_logs.key_exists_skipping'));

            return true;
        }

        try {
            return Artisan::call('key:generate', ['--force' => true]) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateEnvironment(): array
    {
        return $this->auditor->audit();
    }

    /**
     * {@inheritdoc}
     */
    public function runMigrations(bool $force = false): bool
    {
        try {
            $hasMigrations = $this->hasExistingMigrations();
            $command = $hasMigrations ? 'migrate:fresh' : 'migrate';

            $result = Artisan::call($command, ['--force' => true]) === 0;

            if ($result) {
                Log::info(
                    __('setup::install.audit_logs.migrations_executed', ['command' => $command]),
                    [
                        'command' => $command,
                        'is_fresh' => $hasMigrations,
                    ],
                );
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Migration failure during installation: ' . $e->getMessage());

            return false;
        }
    }

    protected function hasExistingMigrations(): bool
    {
        try {
            return Schema::hasTable('migrations') && DB::table('migrations')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function runSeeders(): bool
    {
        try {
            return DB::transaction(function () {
                $seeded = Artisan::call('db:seed', ['--force' => true]) === 0;

                if ($seeded) {
                    $token = Str::random(32);
                    $this->settingService->setValue('setup_token', $token);

                    Log::info(__('setup::install.audit_logs.seeding_completed'));
                }

                return $seeded;
            });
        } catch (\Exception $e) {
            Log::error('Seeding failure during installation: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * {@inheritdoc}
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
