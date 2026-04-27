<?php

declare(strict_types=1);

namespace Modules\Setup\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Setup\Console\Commands\AppInstallCommand;
use Modules\Setup\Console\Commands\SetupResetCommand;
use Modules\Setup\Onboarding\Services\OnboardingService;
use Modules\Setup\Services\AppSetupService;
use Modules\Setup\Services\InstallationAuditor;
use Modules\Setup\Services\SetupRequirementRegistry;
use Modules\Setup\Services\SystemInstaller;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Nwidart\Modules\Traits\PathNamespace;

class SetupServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Setup';

    protected string $nameLower = 'setup';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
        $this->registerSetupGates();
    }

    /**
     * Register authorization gates for the setup process.
     */
    protected function registerSetupGates(): void
    {
        // Define common authorization for all setup actions
        $setupAuth = function (?Authenticatable $user) {
            return session()->get(
                \Modules\Setup\Services\Contracts\AppAppSetupService::SESSION_SETUP_AUTHORIZED,
            ) === true;
        };

        Gate::define('performStep', $setupAuth);
        Gate::define('saveSettings', $setupAuth);
        Gate::define('finalize', $setupAuth);
        Gate::define('install', function ($user = null) {
            // Allow installation only if the app is not yet installed,
            // or from the console (where user is null).
            $isInstalled = resolve(
                \Modules\Setup\Services\Contracts\AppAppSetupService::class,
            )->isAppInstalled();

            return !$isInstalled || is_null($user);
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerModule();

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(SetupRequirementRegistry::class);

        $this->app->singleton(
            \Modules\Setup\Services\Contracts\AppAppSetupService::class,
            AppAppSetupService::class,
        );
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [
            \Modules\Setup\Onboarding\Services\Contracts\OnboardingService::class =>
                OnboardingService::class,
            \Modules\Setup\Services\Contracts\SystemInstaller::class => SystemInstaller::class,
            \Modules\Setup\Services\Contracts\InstallationAuditor::class =>
                InstallationAuditor::class,
        ];
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([SetupResetCommand::class, AppInstallCommand::class]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        //
    }

    /**
     * Define view slots for UI injection.
     */
    protected function viewSlots(): array
    {
        return [];
    }
}
