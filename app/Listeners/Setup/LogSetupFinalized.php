<?php

declare(strict_types=1);

namespace App\Listeners\Setup;

use App\Events\Setup\SetupFinalized;
use Illuminate\Support\Facades\Log;

class LogSetupFinalized
{
    public function handle(SetupFinalized $event): void
    {
        Log::info('System setup finalized', [
            'school_id' => $event->schoolId,
            'installed_at' => $event->installedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
