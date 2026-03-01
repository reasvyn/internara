<?php

declare(strict_types=1);

namespace Modules\Status\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class StatusServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Status';

    protected string $nameLower = 'status';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // Override Spatie Model Status configuration at runtime
        config(['model-status.status_model' => config('status.status_model')]);

        \Modules\Status\Models\Status::observe(\Modules\Status\Observers\StatusObserver::class);
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
        return [];
    }
}
