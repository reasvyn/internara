<?php

declare(strict_types=1);

namespace Modules\Setting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class SettingServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Setting';

    protected string $nameLower = 'setting';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // Include the global helper function
        require_once module_path($this->name, 'src/Functions/setting.php');
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
            \Modules\Setting\Services\Contracts\SettingService::class => \Modules\Setting\Services\SettingService::class,
        ];
    }
}
