<?php

declare(strict_types=1);

namespace App\Domain\Setup\Listeners;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\Setup\Events\SetupFinalized;

class LogSetupFinalized
{
    public function handle(SetupFinalized $event): void
    {
        SmartLogger::info('System setup finalized')
            ->withPayload([
                'school_id' => $event->schoolId,
                'installed_at' => $event->installedAt->format('Y-m-d H:i:s'),
            ])
            ->systemOnly()
            ->save();
    }
}
