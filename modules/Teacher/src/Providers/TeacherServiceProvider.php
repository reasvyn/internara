<?php

declare(strict_types=1);

namespace Modules\Teacher\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class TeacherServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Teacher';

    protected string $nameLower = 'teacher';

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
                'ui::menu-item' => [
                    'title' => __('Dashboard'),
                    'icon' => 'tabler.layout-dashboard',
                    'link' => '/teacher',
                    'order' => 10,
                ],
                'ui::menu-item#reports' => [
                    'title' => __('report::ui.title'),
                    'icon' => 'tabler.file-analytics',
                    'link' => '/teacher/reports',
                    'order' => 80,
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
            \Modules\Teacher\Services\Contracts\TeacherService::class => \Modules\Teacher\Services\TeacherService::class,
        ];
    }
}
