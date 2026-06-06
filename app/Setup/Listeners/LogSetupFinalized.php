<?php

declare(strict_types=1);

namespace App\Setup\Listeners;

use App\Core\Support\SmartLogger;
use App\Setup\Events\SetupFinalized;

class LogSetupFinalized
{
    public function handle(SetupFinalized $event): void
    {
        SmartLogger::info('setup_finalized')
            ->event('setup_finalized')
            ->module('SysAdmin')
            ->withPayload([
                'department_id' => $event->departmentId,
                'installed_at' => $event->installedAt->format('Y-m-d H:i:s'),
            ])
            ->systemOnly()
            ->save();
    }
}
