<?php

declare(strict_types=1);

namespace Modules\Internship\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Internship\Models\Company;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Models\InternshipRequirement;
use Modules\Internship\Models\RequirementSubmission;
use Modules\Internship\Policies\CompanyPolicy;
use Modules\Internship\Policies\InternshipPolicy;
use Modules\Internship\Policies\InternshipRegistrationPolicy;
use Modules\Internship\Reports\CompetencyAchievementReportProvider;
use Modules\Internship\Reports\InternshipClassReportProvider;
use Modules\Internship\Reports\PartnerEngagementReportProvider;
use Modules\Internship\Services\CompanyService;
use Modules\Internship\Services\Contracts\PlacementLogger;
use Modules\Internship\Services\InternshipPlacementService;
use Modules\Internship\Services\InternshipRequirementService;
use Modules\Internship\Services\InternshipService;
use Modules\Internship\Services\PlacementLoggerService;
use Modules\Internship\Services\PlacementService;
use Modules\Internship\Services\RegistrationService;
use Modules\Internship\Services\SupervisorService;
use Modules\Internship\Setup\InternshipSetupRequirement;
use Modules\Report\Services\ReportService;
use Modules\Setup\Services\SetupRequirementRegistry;
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
        Company::class => CompanyPolicy::class,
        InternshipRegistration::class => InternshipRegistrationPolicy::class,
        InternshipRequirement::class => InternshipPolicy::class,
        RequirementSubmission::class => InternshipRegistrationPolicy::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // [S3 - Scalable] Register Setup Hook
        if ($this->app->bound(SetupRequirementRegistry::class)) {
            $this->app
                ->make(SetupRequirementRegistry::class)
                ->register($this->app->make(InternshipSetupRequirement::class));
        }

        // Register Report Providers
        if (class_exists(ReportService::class)) {
            $reportService = app(ReportService::class);
            $reportService->registerProvider(new InternshipClassReportProvider());
            $reportService->registerProvider(new PartnerEngagementReportProvider());
            $reportService->registerProvider(new CompetencyAchievementReportProvider());
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
            \Modules\Internship\Services\Contracts\InternshipService::class =>
                InternshipService::class,
            \Modules\Internship\Services\Contracts\CompanyService::class => CompanyService::class,
            \Modules\Internship\Services\Contracts\InternshipPlacementService::class =>
                InternshipPlacementService::class,
            \Modules\Internship\Services\Contracts\RegistrationService::class =>
                RegistrationService::class,
            \Modules\Internship\Services\Contracts\SupervisorService::class =>
                SupervisorService::class,
            \Modules\Internship\Services\Contracts\PlacementService::class =>
                PlacementService::class,
            \Modules\Internship\Services\Contracts\InternshipRequirementService::class =>
                InternshipRequirementService::class,
            PlacementLogger::class => PlacementLoggerService::class,
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
            'student.dashboard.requirements' =>
                'livewire:internship::requirement-submission-manager',
            // Menu items now managed in UI/config/sidebar.php for centralized control
            'sidebar.menu' => [
                'ui::menu-item#company-manager' => [
                    'title' => 'internship::ui.company_title',
                    'icon' => 'tabler.building-community',
                    'link' => '/internships/companies',
                    'permission' => 'internship.manage',
                    'order' => 61,
                ],
            ],
        ];
    }
}
