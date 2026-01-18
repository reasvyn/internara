<?php

declare(strict_types=1);

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class DashboardServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Dashboard';

    protected string $nameLower = 'dashboard';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
    }

    protected function viewSlots(): array
    {
        return [
            'navbar.items' => 'dashboard::components.nav-link',
        ];
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerBindings();
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
            // 'SomeContract::class' => 'SomeConcrete::class'
        ];
    }
}
