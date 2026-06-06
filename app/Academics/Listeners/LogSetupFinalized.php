<?php

declare(strict_types=1);

namespace App\Academics\Listeners;

use App\Academics\Events\SetupFinalized;
use App\Core\Support\SmartLogger;

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
