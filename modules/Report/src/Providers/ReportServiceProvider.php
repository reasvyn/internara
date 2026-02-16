<?php

declare(strict_types=1);

namespace Modules\Report\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class ReportServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Report';

    protected string $nameLower = 'report';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [
            \Modules\Report\Services\Contracts\ReportGenerator::class => \Modules\Report\Services\ReportService::class,
            \Modules\Report\Services\Contracts\GeneratedReportService::class => \Modules\Report\Services\GeneratedReportService::class,
        ];
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerModule();
        $this->app->singleton(
            \Modules\Report\Services\Contracts\ReportGenerator::class,
            \Modules\Report\Services\ReportService::class,
        );
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
