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
            // 'SomeContract::class' => 'SomeConcrete::class'
        ];
    }

    /**
     * Define the view slots for the module.
     */
    protected function viewSlots(): array
    {
        return [
            'sidebar.menu' => [
                'ui::components.menu-item#activities' => [
                    'title' => __('log::ui.activity_feed'),
                    'icon' => 'tabler.history',
                    'link' => '/admin/activities',
                ],
            ],
        ];
    }
}
