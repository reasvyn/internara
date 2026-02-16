<?php

declare(strict_types=1);

namespace Modules\Permission\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Permission\Services\Contracts\PermissionManager as PermissionManagerContract;
use Modules\Permission\Services\Contracts\PermissionService as PermissionServiceContract;
use Modules\Permission\Services\Contracts\RoleService as RoleServiceContract;
use Modules\Permission\Services\PermissionManager;
use Modules\Permission\Services\PermissionService;
use Modules\Permission\Services\RoleService;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Modules\User\Models\User;

class PermissionServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;

    protected string $name = 'Permission';

    protected string $nameLower = 'permission';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->overrideSpatieConfig();
        $this->bootModule();
        $this->registerPolicies();
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
     * Override Spatie Permission configuration at runtime.
     */
    protected function overrideSpatieConfig(): void
    {
        $this->app['config']->set('permission.models.role', \Modules\Permission\Models\Role::class);
        $this->app['config']->set(
            'permission.models.permission',
            \Modules\Permission\Models\Permission::class,
        );
    }

    /**
     * Register the module policies.
     */
    protected function registerPolicies(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [
            PermissionManagerContract::class => PermissionManager::class,
            RoleServiceContract::class => RoleService::class,
            PermissionServiceContract::class => PermissionService::class,
        ];
    }
}
