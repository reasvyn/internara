<?php

declare(strict_types=1);

namespace Modules\Mentor\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class MentorServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Mentor';

    protected string $nameLower = 'mentor';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
    }

    /**
     * Define view slots for UI injection.
     */
    protected function viewSlots(): array
    {
        return [
            'sidebar.menu' => [
                'ui::components.menu-item' => [
                    'title' => __('Dashboard'),
                    'icon' => 'tabler.layout-dashboard',
                    'link' => '/mentor',
                ],
            ],
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
            // \Modules\Example\Services\Contract\YourContractService::class => \Modules\Example\Services\YourService::class
        ];
    }
}
