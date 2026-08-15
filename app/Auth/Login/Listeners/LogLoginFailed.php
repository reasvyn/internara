<?php

declare(strict_types=1);

namespace App\Auth\Login\Listeners;

use App\Auth\Login\Events\LoginFailed;
use App\Core\Services\SmartLogger;
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
