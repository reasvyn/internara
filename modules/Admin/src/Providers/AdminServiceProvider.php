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
            'admin.dashboard.side' => [
                'livewire:admin::widgets.app-info-widget' => [
                    'order' => 999,
                ],
            ],
            'sidebar.menu' => [
                // --- CORE ---
                'ui::menu-separator#core' => [
                    'title' => 'admin::ui.menu.group_core',
                    'role' => 'admin|super-admin',
                    'order' => 5,
                ],
                'ui::menu-item' => [
                    'title' => 'admin::ui.menu.dashboard',
                    'icon' => 'tabler.layout-dashboard',
                    'link' => '/admin',
                    'role' => 'admin|super-admin',
                    'order' => 10,
                ],

                // --- DATA MANAGEMENT ---
                'ui::menu-separator#management' => [
                    'title' => 'admin::ui.menu.group_management',
                    'role' => 'admin|super-admin',
                    'order' => 45,
                ],
                'ui::menu-item#students' => [
                    'title' => 'admin::ui.menu.students',
                    'icon' => 'tabler.users',
                    'link' => '/admin/students',
                    'role' => 'admin|super-admin',
                    'order' => 50,
                ],
                'ui::menu-item#teachers' => [
                    'title' => 'admin::ui.menu.teachers',
                    'icon' => 'tabler.school',
                    'link' => '/admin/teachers',
                    'role' => 'admin|super-admin',
                    'order' => 51,
                ],
                'ui::menu-item#mentors' => [
                    'title' => 'admin::ui.menu.mentors',
                    'icon' => 'tabler.briefcase',
                    'link' => '/admin/mentors',
                    'role' => 'admin|super-admin',
                    'order' => 52,
                ],

                // --- INTELLIGENCE ---
                'ui::menu-separator#intelligence' => [
                    'title' => 'admin::ui.menu.group_intelligence',
                    'role' => 'admin|super-admin',
                    'order' => 75,
                ],
                'ui::menu-item#reports' => [
                    'title' => 'report::ui.title',
                    'icon' => 'tabler.file-analytics',
                    'link' => '/admin/reports',
                    'role' => 'admin|super-admin',
                    'order' => 80,
                ],
                'ui::menu-item#readiness' => [
                    'title' => 'admin::ui.menu.readiness',
                    'icon' => 'tabler.user-check',
                    'link' => '/admin/readiness',
                    'role' => 'admin|super-admin',
                    'order' => 81,
                ],

                // --- SYSTEM ---
                'ui::menu-separator#system' => [
                    'title' => 'admin::ui.menu.group_system',
                    'role' => 'admin|super-admin',
                    'order' => 85,
                ],
                'ui::menu-item#administrators' => [
                    'title' => 'admin::ui.menu.administrators',
                    'icon' => 'tabler.shield-lock',
                    'link' => '/admin/administrators',
                    'role' => 'super-admin',
                    'permission' => 'super-admin',
                    'order' => 90,
                ],
                'ui::menu-item#job-monitor' => [
                    'title' => 'admin::ui.menu.job_monitor',
                    'icon' => 'tabler.activity',
                    'link' => '/admin/jobs',
                    'role' => 'admin|super-admin',
                    'order' => 91,
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
