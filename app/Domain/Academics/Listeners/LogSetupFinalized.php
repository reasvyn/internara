<?php

declare(strict_types=1);

namespace App\Domain\Academics\Listeners;

use App\Domain\Academics\Events\SetupFinalized;
use App\Domain\Core\Support\SmartLogger;

class LogSetupFinalized
{
    public function handle(SetupFinalized $event): void
    {
        SmartLogger::info('setup_finalized')
            ->event('setup_finalized')
            ->module('SysAdmin')
            ->withPayload([
                'school_id' => $event->schoolId,
                'installed_at' => $event->installedAt->format('Y-m-d H:i:s'),
            ])
            ->systemOnly()
            ->save();
    }
}
