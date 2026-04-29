<?php

declare(strict_types=1);

namespace Modules\Setup\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Setup\Services\Contracts\SystemInstaller;
use Modules\Setup\Services\Contracts\InstallationAuditor;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Setup\Services\SystemInstaller as SystemInstallerImpl;
use Modules\Setup\Services\InstallationAuditor as InstallationAuditorImpl;
use Modules\Setup\Services\SetupService as SetupServiceImpl;

/**
 * Setup Service Provider
 *
 * [S1 - Secure] Proper service binding
 * [S2 - Sustain] Clear registration
 * [S3 - Scalable] Contract-based bindings
 */
class SetupServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind contracts to implementations
        $this->app->bind(SystemInstaller::class, SystemInstallerImpl::class);
        $this->app->bind(InstallationAuditor::class, InstallationAuditorImpl::class);
        $this->app->bind(SetupService::class, SetupServiceImpl::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'setup');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'setup');

        // Register middleware aliases
        $this->app['router']->aliasMiddleware('setup.protect', \Modules\Setup\Http\Middleware\ProtectSetupRoute::class);
        $this->app['router']->aliasMiddleware('setup.require', \Modules\Setup\Http\Middleware\RequireSetupAccess::class);
    }
}
