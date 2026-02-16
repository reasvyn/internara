<?php

declare(strict_types=1);

namespace Modules\Notification\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class NotificationServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Notification';

    protected string $nameLower = 'notification';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerModule();
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [
            \Modules\Notification\Services\Contracts\Notifier::class =>
                \Modules\Notification\Services\Notifier::class,
            \Modules\Notification\Services\Contracts\NotificationService::class =>
                \Modules\Notification\Services\NotificationService::class,
        ];
    }
}
