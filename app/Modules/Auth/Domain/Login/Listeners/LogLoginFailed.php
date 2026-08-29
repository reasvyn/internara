<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Login\Listeners;

use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Core\Services\SmartLogger;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogLoginFailed implements ShouldQueue
{
    public function handle(LoginFailed $event): void
    {
        SmartLogger::warning('login_failed')
            ->event('login_failed')
            ->module('Auth')
            ->withPayload([
                'identifier' => $event->identifier,
                'reason' => $event->reason,
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}
