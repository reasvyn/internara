<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\SetupWizard\Listeners;

use App\Modules\Core\Services\SmartLogger;
use App\Modules\Setup\Domain\SetupWizard\Events\SetupFinalized;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class LogSetupFinalized implements ShouldQueue
{
    public function handle(SetupFinalized $event): void
    {
        Cache::forget(config('cache-keys.setup_installed'));

        SmartLogger::info('setup_finalized')
            ->event('setup_finalized')
            ->module('SysAdmin')
            ->withPayload([
                'department_id' => $event->departmentId,
                'installed_at' => $event->installedAt->format('Y-m-d H:i:s'),
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}
