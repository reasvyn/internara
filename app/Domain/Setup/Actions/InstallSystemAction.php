<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Setup\Exceptions\SetupException;
use App\Domain\Setup\Services\EnvAuditor;
use App\Domain\Setup\Services\SetupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * System Installer Action - Technical initialization.
 *
 * [S1 - Secure] Gate authorization, encrypted tokens, audit logging.
 * [S2 - Sustain] Clear error messages, atomic operations.
 * [S3 - Scalable] Independent of business setup, UUID-based.
 */
class InstallSystemAction
{
    public function __construct(
        private SetupService $setupService,
        private EnvAuditor $auditor,
    ) {}

    /**
     * Execute the installation process.
     *
     * @throws SetupException
     */
    public function execute(): void
    {
        Gate::authorize('install', self::class);

        $this->ensureEnvFileExists();

        if (! $this->auditor->audit()['passed']) {
            throw new SetupException('Environment audit failed. Check system requirements.');
        }

        $this->generateAppKey();
        $this->runMigrations();
        $this->runSeeders();
        $this->generateSetupToken();
        $this->createStorageSymlink();
    }

    /**
     * Ensure .env file exists.
     */
    public function ensureEnvFileExists(): void
    {
        if (File::exists(base_path('.env'))) {
            return;
        }

        if (! File::exists(base_path('.env.example'))) {
            throw SetupException::missingEnvExample();
        }

        if (! File::copy(base_path('.env.example'), base_path('.env'))) {
            throw new SetupException('Failed to create .env file from .env.example.');
        }

        Log::info('Environment file created from .env.example');
    }

    /**
     * Generate application key.
     */
    public function generateAppKey(): void
    {
        if (! empty(config('app.key'))) {
            return;
        }

        try {
            $result = Artisan::call('key:generate', ['--force' => true]);

            if ($result !== 0) {
                throw SetupException::keyGenerationFailed();
            }

            Log::info('Application key generated successfully');
        } catch (\Exception $e) {
            throw SetupException::keyGenerationFailed();
        }
    }

    /**
     * Run database migrations.
     */
    public function runMigrations(bool $force = false): void
    {
        try {
            $hasMigrations = $this->hasExistingMigrations();
            $command = $hasMigrations && ! $force ? 'migrate:fresh' : 'migrate';

            $result = Artisan::call($command, ['--force' => true]);

            if ($result !== 0) {
                throw SetupException::migrationFailed($command);
            }

            Log::info('Database migrations executed', [
                'command' => $command,
                'is_fresh' => $hasMigrations,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof SetupException) {
                throw $e;
            }
            throw SetupException::migrationFailed('migrate', $e);
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
     * Run database seeders.
     */
    public function runSeeders(): void
    {
        try {
            $seeded = Artisan::call('db:seed', ['--force' => true]) === 0;

            if (! $seeded) {
                throw SetupException::seedingFailed();
            }

            Log::info('Database seeding completed');
        } catch (\Exception $e) {
            if ($e instanceof SetupException) {
                throw $e;
            }
            throw SetupException::seedingFailed($e);
        }
    }

    /**
     * Generate setup token.
     */
    protected function generateSetupToken(): void
    {
        try {
            $token = $this->setupService->generateToken();

            Log::info('Setup token generated', [
                'token_preview' => substr($token, 0, 8).'...',
            ]);
        } catch (\Exception $e) {
            throw new SetupException('Failed to generate setup token: '.$e->getMessage());
        }
    }

    /**
     * Create storage symlink.
     */
    public function createStorageSymlink(): void
    {
        try {
            if (File::exists(public_path('storage'))) {
                return;
            }

            $result = Artisan::call('storage:link');

            if ($result !== 0) {
                throw SetupException::storageLinkFailed();
            }

            Log::info('Storage symlink created');
        } catch (\Exception $e) {
            throw SetupException::storageLinkFailed();
        }
    }

    /**
     * Run final optimizations.
     */
    public function optimize(): void
    {
        try {
            $result = Artisan::call('optimize');
            if ($result !== 0) {
                throw new SetupException('Optimization command returned non-zero exit code.');
            }
            Log::info('System optimized');
        } catch (\Exception $e) {
            Log::warning('Optimization failed: '.$e->getMessage());
            throw new SetupException('Optimization failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
