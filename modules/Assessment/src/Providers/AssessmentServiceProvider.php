<?php

declare(strict_types=1);

namespace Modules\Assessment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AssessmentServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Assessment';

    protected string $nameLower = 'assessment';

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
            \Modules\Assessment\Services\Contracts\AssessmentService::class => \Modules\Assessment\Services\AssessmentService::class,
            \Modules\Assessment\Services\Contracts\CertificateService::class => \Modules\Assessment\Services\CertificateService::class,
            \Modules\Assessment\Services\Contracts\ComplianceService::class => \Modules\Assessment\Services\ComplianceService::class,
        ];
    }
}
