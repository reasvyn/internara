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
        InternshipRegistration::class => \Modules\Internship\Policies\InternshipRegistrationPolicy::class,
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
            \Modules\Internship\Services\Contracts\InternshipService::class => \Modules\Internship\Services\InternshipService::class,
            \Modules\Internship\Services\Contracts\InternshipPlacementService::class => \Modules\Internship\Services\InternshipPlacementService::class,
            \Modules\Internship\Services\Contracts\InternshipRegistrationService::class => \Modules\Internship\Services\InternshipRegistrationService::class,
            \Modules\Internship\Services\Contracts\SupervisorService::class => \Modules\Internship\Services\SupervisorService::class,
        ];
    }
}
