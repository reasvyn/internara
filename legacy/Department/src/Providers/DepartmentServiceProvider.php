<?php

declare(strict_types=1);

namespace Modules\Department\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Department\Models\Department;
use Modules\Department\Policies\DepartmentPolicy;
use Modules\Department\Services\DepartmentService;
use Modules\Department\Setup\DepartmentSetupRequirement;
use Modules\Setup\Services\SetupRequirementRegistry;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class DepartmentServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Department';

    protected string $nameLower = 'department';

    /**
     * The policy mappings for the module.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Department::class => DepartmentPolicy::class,
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
                ->register($this->app->make(DepartmentSetupRequirement::class));
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
            \Modules\Department\Services\Contracts\DepartmentService::class => DepartmentService::class,
        ];
    }

    protected function viewSlots(): array
    {
        return [
            'department-manager' => 'livewire:department::department-manager',
        ];
    }
}
