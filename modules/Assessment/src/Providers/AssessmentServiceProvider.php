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
        $this->bootModule();
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
            \Modules\Assessment\Services\Contracts\CompetencyService::class => \Modules\Assessment\Services\CompetencyService::class,
        ];
    }

    /**
     * Define view slots for UI injection.
     */
    protected function viewSlots(): array
    {
        return [
            'student.dashboard.active-content' => 'livewire:assessment::skill-progress',
        ];
    }
}
