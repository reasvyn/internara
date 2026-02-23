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
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        \Modules\Report\Models\GeneratedReport::class => \Modules\Report\Policies\ReportPolicy::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
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
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
