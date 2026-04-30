<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Setting\SetSettingAction;
use App\Support\AppInfo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
     * Execute the system installation.
     */
    public function execute(): void
    {
        DB::transaction(function () {
            // 1. Run Migrations
            Artisan::call('migrate:fresh', ['--force' => true]);

            // 2. Run Seeders
            Artisan::call('db:seed', ['--force' => true]);

            // 3. Set System Settings
            $this->setSetting->execute('app_installed', 'true', 'boolean', 'system');
            $this->setSetting->execute('app_version', AppInfo::version(), 'string', 'system');
            $this->setSetting->execute('installed_at', now()->toIso8601String(), 'datetime', 'system');

            // 4. Log Audit Event
            $this->logAudit->execute(
                action: 'system_installed',
                payload: ['version' => AppInfo::version()],
                module: 'System'
            );
        });

        // 5. Link Storage
        Artisan::call('storage:link', ['--force' => true]);
        
        // 6. Clear Cache
        Artisan::call('optimize:clear');
    }
}
