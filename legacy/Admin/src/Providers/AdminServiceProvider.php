<?php

declare(strict_types=1);

namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Analytics\Services\AnalyticsAggregator;
use Modules\Admin\Services\AdminService;
use Modules\Admin\Services\InfrastructureHealthService;
use Modules\Admin\Services\SuperAdminService;
use Modules\Admin\Setup\AdminSetupRequirement;
use Modules\Setup\Services\SetupRequirementRegistry;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AdminServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Admin';

    protected string $nameLower = 'admin';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // [S3 - Scalable] Register Setup Hook
        if ($this->app->bound(SetupRequirementRegistry::class)) {
            $this->app
                ->make(SetupRequirementRegistry::class)
                ->register($this->app->make(AdminSetupRequirement::class));
        }
    }

    /**
     * Define view slots for UI injection.
     */
    protected function viewSlots(): array
    {
        return [
            'admin.dashboard.side' => [
                'livewire:admin::widgets.app-info-widget' => [
                    'order' => 999,
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
            \Modules\Admin\Services\Contracts\AdminService::class => AdminService::class,
            \Modules\Admin\Services\Contracts\SuperAdminService::class => SuperAdminService::class,
            \Modules\Admin\Analytics\Services\Contracts\AnalyticsAggregator::class => AnalyticsAggregator::class,
            \Modules\Admin\Services\Contracts\InfrastructureHealthService::class => InfrastructureHealthService::class,
        ];
    }
}
