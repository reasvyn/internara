<?php

declare(strict_types=1);

namespace Modules\Assignment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Assignment\Models\Submission;
use Modules\Assignment\Policies\AssignmentPolicy;
use Modules\Assignment\Policies\SubmissionPolicy;
use Modules\Assignment\Services\AssignmentService;
use Modules\Assignment\Services\AssignmentTypeService;
use Modules\Assignment\Services\SubmissionService;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AssignmentServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Assignment';

    protected string $nameLower = 'assignment';

    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Assignment::class => AssignmentPolicy::class,
        AssignmentType::class => AssignmentPolicy::class,
        Submission::class => SubmissionPolicy::class,
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
            \Modules\Assignment\Services\Contracts\AssignmentService::class => AssignmentService::class,
            \Modules\Assignment\Services\Contracts\AssignmentTypeService::class => AssignmentTypeService::class,
            \Modules\Assignment\Services\Contracts\SubmissionService::class => SubmissionService::class,
        ];
    }

    /**
     * Define the view slots for the module.
     */
    protected function viewSlots(): array
    {
        return [
            'student.dashboard.quick-actions' => [
                'ui::button' => [
                    'label' => 'assignment::ui.menu.final_assignment',
                    'icon' => 'tabler.certificate',
                    'link' => '/assignments',
                    'class' => 'btn-ghost justify-start w-full',
                ],
            ],
        ];
    }
}
