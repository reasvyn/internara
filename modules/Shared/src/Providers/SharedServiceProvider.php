<?php

declare(strict_types=1);

namespace Modules\Shared\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class SharedServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Shared';

    protected string $nameLower = 'shared';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->registerRateLimiters();
    }

    /**
     * Register the module's rate limiters.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('setup', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
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
        return [];
    }
}
