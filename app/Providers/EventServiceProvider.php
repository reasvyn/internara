<?php

declare(strict_types=1);

namespace App\Providers;

use App\Settings\Events\SettingUpdated;
use App\Settings\Listeners\InvalidateSettingsCache;
use App\Setup\SetupWizard\Events\SetupFinalized;
use App\Setup\SetupWizard\Listeners\LogSetupFinalized;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SetupFinalized::class => [
            LogSetupFinalized::class,
        ],

        SettingUpdated::class => [
            InvalidateSettingsCache::class,
        ],
    ];

    public static function registerListener(string $event, string $listener): void
    {
        \Illuminate\Support\Facades\Event::listen($event, $listener);
    }
}