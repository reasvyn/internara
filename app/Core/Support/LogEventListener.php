<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Events\BaseEvent;

/**
 * Generic event listener that logs all event dispatches.
 *
 * Used as a placeholder for events that need a listener registered
 * but don't yet have specific side effect logic.
 * Replace with a dedicated listener when real logic is implemented.
 */
class LogEventListener
{
    public function handle(BaseEvent $event): void
    {
        SmartLogger::info('Event dispatched: '.$event->eventName())
            ->withPayload($event->toPayload())
            ->systemOnly()
            ->save();
    }
}
