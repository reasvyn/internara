<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Setting\SetSettingAction;
use App\Support\AppInfo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Stateless Action to orchestrate the technical installation of the system.
 *
 * S1 - Secure: Atomic installation via transactions.
 * S2 - Sustain: Clean orchestration of CLI tasks.
 */
class InstallSystemAction
{
    public function __construct(
        protected readonly SetSettingAction $setSetting,
        protected readonly LogAuditAction $logAudit
    ) {}

    /**
     * Ensure .env file exists, copy from .env.example if missing.
     */
    public function ensureEnvFileExists(): void
    {
        if (File::exists(base_path('.env'))) {
            return;
        }

        if (File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
        }
    }

    /**
     * Ensure application key exists, generate if missing.
     */
    public function ensureAppKeyExists(): void
    {
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }
    }

    /**
     * Run database migrations.
     */
    public function runMigrations(bool $fresh = false): void
    {
        $command = $fresh ? 'migrate:fresh' : 'migrate';
        Artisan::call($command, ['--force' => true]);
    }

    /**
     * Run database seeders.
     */
    public function runSeeders(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
    }

    /**
     * Configure initial system settings and log audit.
     */
    public function configureInitialSettings(): void
    {
        DB::transaction(function () {
            $this->setSetting->execute('app_installed', 'true', 'boolean', 'system');
            $this->setSetting->execute('app_version', AppInfo::version(), 'string', 'system');
            $this->setSetting->execute('installed_at', now()->toIso8601String(), 'datetime', 'system');

            $this->logAudit->execute(
                action: 'system_installed',
                payload: ['version' => AppInfo::version()],
                module: 'System'
            );
        });
    }

    /**
     * Link storage directories.
     */
    public function linkStorage(): void
    {
        Artisan::call('storage:link', ['--force' => true]);
    }

    /**
     * Optimize application and clear cache.
     */
    public function optimize(): void
    {
        Artisan::call('optimize:clear');
    }

    /**
     * Execute the complete system installation (Legacy/Convenience).
     */
    public function execute(bool $force = false): void
    {
        $this->ensureEnvFileExists();
        $this->ensureAppKeyExists();
        $this->runMigrations($force);
        $this->runSeeders();
        $this->configureInitialSettings();
        $this->linkStorage();
        $this->optimize();
    }
}
