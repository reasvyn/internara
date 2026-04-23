<?php

declare(strict_types=1);

namespace Modules\Status\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class StatusServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Status';

    protected string $nameLower = 'status';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        // Override Spatie Model Status configuration at runtime
        config(['model-status.status_model' => config('status.status_model')]);

        \Modules\Status\Models\Status::observe(\Modules\Status\Observers\StatusObserver::class);
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
            \Modules\Status\Services\AccountAuditLogger::class => \Modules\Status\Services\AccountAuditLogger::class,
            \Modules\Status\Services\StatusTransitionService::class => \Modules\Status\Services\StatusTransitionService::class,
            \Modules\Status\Services\RoleBasedStatusTransitionService::class => \Modules\Status\Services\RoleBasedStatusTransitionService::class,
            \Modules\Status\Services\AccountLockoutService::class => \Modules\Status\Services\AccountLockoutService::class,
            \Modules\Status\Services\IdleAccountDetectionService::class => \Modules\Status\Services\IdleAccountDetectionService::class,
            \Modules\Status\Services\VerificationService::class => \Modules\Status\Services\VerificationService::class,
            \Modules\Status\Services\SessionExpirationService::class => \Modules\Status\Services\SessionExpirationService::class,
            \Modules\Status\Services\ActivationWorkflow::class => \Modules\Status\Services\ActivationWorkflow::class,
            \Modules\Status\Services\PasswordPolicyService::class => \Modules\Status\Services\PasswordPolicyService::class,
            \Modules\Status\Services\SuperAdminGuardRails::class => \Modules\Status\Services\SuperAdminGuardRails::class,
            \Modules\Status\Services\GdprComplianceService::class => \Modules\Status\Services\GdprComplianceService::class,
            \Modules\Status\Services\AccountCloneDetectionService::class => \Modules\Status\Services\AccountCloneDetectionService::class,
            \Modules\Status\Policies\StatusChangePolicy::class => \Modules\Status\Policies\StatusChangePolicy::class,
            \Modules\Status\Policies\RoleBasedAccessPolicy::class => \Modules\Status\Policies\RoleBasedAccessPolicy::class,
        ];
    }
}
