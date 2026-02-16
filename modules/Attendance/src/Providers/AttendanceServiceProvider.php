<?php

declare(strict_types=1);

namespace Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AttendanceServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Attendance';

    protected string $nameLower = 'attendance';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        \Modules\Attendance\Models\AttendanceLog::class => \Modules\Attendance\Policies\AttendancePolicy::class,
    ];

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
            'navbar.items' => 'attendance::nav-link',
            'student.dashboard.sidebar' => 'livewire:attendance::attendance-manager',
            'student.dashboard.quick-actions' => [
                'ui::button' => [
                    'label' => __('Log Presensi'),
                    'icon' => 'tabler.calendar',
                    'link' => '/attendance',
                    'class' => 'btn-ghost justify-start w-full',
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
            \Modules\Attendance\Services\Contracts\AttendanceService::class => \Modules\Attendance\Services\AttendanceService::class,
        ];
    }
}
