<?php

declare(strict_types=1);

namespace Modules\Assessment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\Competency;
use Modules\Assessment\Models\DepartmentCompetency;
use Modules\Assessment\Models\StudentCompetencyLog;
use Modules\Assessment\Policies\AssessmentPolicy;
use Modules\Assessment\Policies\CompetencyPolicy;
use Modules\Assessment\Services\AnalyticsService;
use Modules\Assessment\Services\AssessmentService;
use Modules\Assessment\Services\CertificateService;
use Modules\Assessment\Services\CompetencyService;
use Modules\Assessment\Services\ComplianceService;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AssessmentServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Assessment';

    protected string $nameLower = 'assessment';

    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Assessment::class => AssessmentPolicy::class,
        Competency::class => CompetencyPolicy::class,
        DepartmentCompetency::class => CompetencyPolicy::class,
        StudentCompetencyLog::class => AssessmentPolicy::class,
    ];

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
            \Modules\Assessment\Services\Contracts\AssessmentService::class => AssessmentService::class,
            \Modules\Assessment\Services\Contracts\AnalyticsService::class => AnalyticsService::class,
            \Modules\Assessment\Services\Contracts\CertificateService::class => CertificateService::class,
            \Modules\Assessment\Services\Contracts\ComplianceService::class => ComplianceService::class,
            \Modules\Assessment\Services\Contracts\CompetencyService::class => CompetencyService::class,
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
