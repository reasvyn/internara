<?php

declare(strict_types=1);

namespace Modules\Log\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class LogServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Log';

    protected string $nameLower = 'log';

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
            \Modules\Log\Services\Contracts\ActivityService::class => \Modules\Log\Services\ActivityService::class,
        ];
    }

    /**
     * Define the view slots for the module.
     */
    protected function viewSlots(): array
    {
        return [
            'admin.dashboard.side' => 'livewire:log::activity-feed',
        ];
    }
}
