<?php

declare(strict_types=1);

namespace Modules\Assignment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AssignmentServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Assignment';

    protected string $nameLower = 'assignment';

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
            \Modules\Assignment\Services\Contracts\AssignmentService::class =>
                \Modules\Assignment\Services\AssignmentService::class,
            \Modules\Assignment\Services\Contracts\AssignmentTypeService::class =>
                \Modules\Assignment\Services\AssignmentTypeService::class,
            \Modules\Assignment\Services\Contracts\SubmissionService::class =>
                \Modules\Assignment\Services\SubmissionService::class,
        ];
    }

    /**
     * Define the view slots for the module.
     */
    protected function viewSlots(): array
    {
        return [
            'sidebar.menu' => [
                'ui::menu-item#assignments' => [
                    'title' => __('Assignments'),
                    'icon' => 'tabler.checklist',
                    'link' => '/assignments',
                    'role' => 'student',
                    'order' => 40,
                ],
                'ui::menu-item#admin-assignments' => [
                    'title' => __('Manage Assignments'),
                    'icon' => 'tabler.settings-automation',
                    'link' => '/admin/assignments',
                    'role' => 'admin|super-admin',
                    'order' => 41,
                ],
                'ui::menu-item#admin-assignment-types' => [
                    'title' => __('Assignment Types'),
                    'icon' => 'tabler.category',
                    'link' => '/admin/assignments/types',
                    'role' => 'admin|super-admin',
                    'order' => 42,
                ],
            ],
            'student.dashboard.quick-actions' => [
                'ui::button' => [
                    'label' => __('Tugas Akhir'),
                    'icon' => 'tabler.certificate',
                    'link' => '/assignments',
                    'class' => 'btn-ghost justify-start w-full',
                ],
            ],
        ];
    }
}
