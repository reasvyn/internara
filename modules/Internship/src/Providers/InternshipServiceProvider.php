<?php

declare(strict_types=1);

namespace Modules\Internship\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Policies\InternshipPolicy;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class InternshipServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Internship';

    protected string $nameLower = 'internship';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Internship::class => InternshipPolicy::class,
        InternshipPlacement::class => InternshipPolicy::class,
        \Modules\Internship\Models\Company::class => \Modules\Internship\Policies\CompanyPolicy::class,
        InternshipRegistration::class => \Modules\Internship\Policies\InternshipRegistrationPolicy::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // Register Report Providers
        if (class_exists(\Modules\Report\Services\ReportService::class)) {
            $reportService = app(\Modules\Report\Services\ReportService::class);
            $reportService->registerProvider(
                new \Modules\Internship\Reports\InternshipClassReportProvider,
            );
            $reportService->registerProvider(
                new \Modules\Internship\Reports\PartnerEngagementReportProvider,
            );
            $reportService->registerProvider(
                new \Modules\Internship\Reports\CompetencyAchievementReportProvider,
            );
        }
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
            \Modules\Internship\Services\Contracts\InternshipService::class => \Modules\Internship\Services\InternshipService::class,
            \Modules\Internship\Services\Contracts\CompanyService::class => \Modules\Internship\Services\CompanyService::class,
            \Modules\Internship\Services\Contracts\InternshipPlacementService::class => \Modules\Internship\Services\InternshipPlacementService::class,
            \Modules\Internship\Services\Contracts\RegistrationService::class => \Modules\Internship\Services\RegistrationService::class,
            \Modules\Internship\Services\Contracts\SupervisorService::class => \Modules\Internship\Services\SupervisorService::class,
            \Modules\Internship\Services\Contracts\PlacementService::class => \Modules\Internship\Services\PlacementService::class,
            \Modules\Internship\Services\Contracts\InternshipRequirementService::class => \Modules\Internship\Services\InternshipRequirementService::class,
            \Modules\Internship\Services\Contracts\PlacementLogger::class => \Modules\Internship\Services\PlacementLoggerService::class,
        ];
    }

    /**
     * Define the view slots for the module.
     *
     * @return array<string, array>
     */
    protected function viewSlots(): array
    {
        return [
            'internship-manager' => 'livewire:internship::internship-manager',
            'student.dashboard.requirements' => 'livewire:internship::requirement-submission-manager',
            'sidebar.menu' => [
                'ui::menu-item' => [
                    'title' => __('internship::ui.requirement_title'),
                    'icon' => 'tabler.checklist',
                    'link' => '/internships/requirements',
                    'role' => 'student',
                    'order' => 25,
                ],
            ],
        ];
    }
}
