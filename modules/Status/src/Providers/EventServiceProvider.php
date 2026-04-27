<?php

declare(strict_types=1);

namespace Modules\Status\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [
            \Modules\Status\Listeners\LogSuccessfulLogin::class,
        ],
        \Illuminate\Auth\Events\Failed::class => [
            \Modules\Status\Listeners\LogFailedLogin::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
