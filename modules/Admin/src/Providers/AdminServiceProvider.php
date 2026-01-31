<?php

declare(strict_types=1);

namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
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
                    'link' => '/admin',
                    'order' => 10,
                ],
                'ui::components.menu-item#students' => [
                    'title' => __('Students'),
                    'icon' => 'tabler.users',
                    'link' => '/admin/students',
                    'order' => 50,
                ],
                'ui::components.menu-item#teachers' => [
                    'title' => __('Teachers'),
                    'icon' => 'tabler.school',
                    'link' => '/admin/teachers',
                    'order' => 51,
                ],
                'ui::components.menu-item#mentors' => [
                    'title' => __('Industry Mentors'),
                    'icon' => 'tabler.briefcase',
                    'link' => '/admin/mentors',
                    'order' => 52,
                ],
                'ui::components.menu-item#administrators' => [
                    'title' => __('Administrators'),
                    'icon' => 'tabler.shield-lock',
                    'link' => '/admin/administrators',
                    'permission' => 'super-admin', // Custom check in some implementations or handled by route
                    'order' => 90,
                ],
                'ui::components.menu-item#job-monitor' => [
                    'title' => __('Job Monitor'),
                    'icon' => 'tabler.activity',
                    'link' => '/admin/jobs',
                    'order' => 91,
                ],
                'ui::components.menu-item#reports' => [
                    'title' => __('report::ui.title'),
                    'icon' => 'tabler.file-analytics',
                    'link' => '/admin/reports',
                    'order' => 80,
                ],
                'ui::components.menu-item#readiness' => [
                    'title' => __('Graduation Readiness'),
                    'icon' => 'tabler.user-check',
                    'link' => '/admin/readiness',
                    'order' => 81,
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
