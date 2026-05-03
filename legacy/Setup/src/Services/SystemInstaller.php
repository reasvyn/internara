<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Setup\Services\Contracts\InstallationAuditor;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Setup\Services\Contracts\SystemInstaller as Contract;
use Modules\Shared\Services\BaseService;

/**
 * System Installer - Technical initialization
 *
 * [S1 - Secure] Gate authorization, encrypted tokens, audit logging
 * [S2 - Sustain] Clear error messages, atomic operations
 * [S3 - Scalable] Independent of business setup, UUID-based
 */
class SystemInstaller extends BaseService implements Contract
{
    public function __construct(
        private SetupService $setupService,
        private InstallationAuditor $auditor,
    ) {}

    /**
     * {@inheritdoc}
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

        if (! $this->generateSetupToken()) {
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
            Log::info('Environment file already exists');

            return true;
        }

        if (! File::exists(base_path('.env.example'))) {
            Log::error('Cannot create .env: .env.example not found');

            return false;
        }

        $created = File::copy(base_path('.env.example'), base_path('.env'));

        if ($created) {
            Log::info('Environment file created from .env.example');
        } else {
            Log::error('Failed to create .env file');
        }

        return $created;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAppKey(): bool
    {
        if (! empty(config('app.key'))) {
            Log::info('Application key already exists, skipping');

            return true;
        }

        try {
            $result = Artisan::call('key:generate', ['--force' => true]);

            if ($result === 0) {
                Log::info('Application key generated successfully');
            }

            return $result === 0;
        } catch (\Exception $e) {
            Log::error('Failed to generate application key: '.$e->getMessage());

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
            $command = $hasMigrations && ! $force ? 'migrate:fresh' : 'migrate';

            $result = Artisan::call($command, ['--force' => true]);

            if ($result === 0) {
                Log::info('Database migrations executed', [
                    'command' => $command,
                    'is_fresh' => $hasMigrations,
                ]);
            } else {
                Log::error('Migration command failed', ['command' => $command]);
            }

            return $result === 0;
        } catch (\Exception $e) {
            Log::error('Migration failure during installation: '.$e->getMessage());

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
                    Log::info('Database seeding completed');
                } else {
                    Log::error('Database seeding failed');
                }

                return $seeded;
            });
        } catch (\Exception $e) {
            Log::error('Seeding failure during installation: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Generate setup token (encrypted in database)
     */
    protected function generateSetupToken(): bool
    {
        try {
            $token = $this->setupService->generateToken();

            Log::info('Setup token generated', [
                'token_preview' => substr($token, 0, 8).'...',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to generate setup token: '.$e->getMessage());

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

            $result = Artisan::call('storage:link');

            if ($result === 0) {
                Log::info('Storage symlink created');
            }

            return $result === 0;
        } catch (\Exception $e) {
            Log::error('Failed to create storage symlink: '.$e->getMessage());

            return false;
        }
    }
}
