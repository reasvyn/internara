<?php

declare(strict_types=1);

namespace Modules\Status\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Modules\Status\Models\Status;
use Modules\Status\Observers\StatusObserver;
use Modules\Status\Policies\RoleBasedAccessPolicy;
use Modules\Status\Policies\StatusChangePolicy;
use Modules\Status\Services\AccountAuditLogger;
use Modules\Status\Services\AccountCloneDetectionService;
use Modules\Status\Services\AccountLockoutService;
use Modules\Status\Services\ActivationWorkflow;
use Modules\Status\Services\GdprComplianceService;
use Modules\Status\Services\IdleAccountDetectionService;
use Modules\Status\Services\RoleBasedStatusTransitionService;
use Modules\Status\Services\SessionExpirationService;
use Modules\Status\Services\StatusTransitionService;
use Modules\Status\Services\SuperAdminGuardRails;
use Modules\Status\Services\VerificationService;
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

        Status::observe(StatusObserver::class);
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
            AccountAuditLogger::class => AccountAuditLogger::class,
            StatusTransitionService::class => StatusTransitionService::class,
            RoleBasedStatusTransitionService::class => RoleBasedStatusTransitionService::class,
            AccountLockoutService::class => AccountLockoutService::class,
            IdleAccountDetectionService::class => IdleAccountDetectionService::class,
            VerificationService::class => VerificationService::class,
            SessionExpirationService::class => SessionExpirationService::class,
            ActivationWorkflow::class => ActivationWorkflow::class,
            SuperAdminGuardRails::class => SuperAdminGuardRails::class,
            GdprComplianceService::class => GdprComplianceService::class,
            AccountCloneDetectionService::class => AccountCloneDetectionService::class,
            StatusChangePolicy::class => StatusChangePolicy::class,
            RoleBasedAccessPolicy::class => RoleBasedAccessPolicy::class,
        ];
    }
}
