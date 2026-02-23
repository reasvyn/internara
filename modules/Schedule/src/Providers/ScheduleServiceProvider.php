<?php

declare(strict_types=1);

namespace Modules\Schedule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class ScheduleServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Schedule';

    protected string $nameLower = 'schedule';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        \Modules\Schedule\Models\Schedule::class => \Modules\Schedule\Policies\SchedulePolicy::class,
    ];

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
            'student.dashboard.sidebar' => [
                'schedule::livewire.timeline' => [
                    'order' => 20,
                ],
            ],
            'sidebar.menu' => [
                'ui::menu-item#schedules' => [
                    'title' => 'schedule::ui.manage_title',
                    'icon' => 'tabler.calendar-stats',
                    'link' => '/schedules',
                    'order' => 60,
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
            \Modules\Schedule\Services\Contracts\ScheduleService::class => \Modules\Schedule\Services\ScheduleService::class,
        ];
    }
}
