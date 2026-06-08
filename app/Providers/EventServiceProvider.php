<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $listeners = config('event.listen', []);

        foreach ($listeners as $event => $listenersArray) {
            foreach ($listenersArray as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public static function registerListener(string $event, string $listener): void
    {
        Event::listen($event, $listener);
    }
}