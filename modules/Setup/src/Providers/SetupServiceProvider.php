<?php

declare(strict_types=1);

namespace Modules\Setup\Providers;

use Illuminate\Support\ServiceProvider;
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
            \Modules\Setup\Services\Contracts\SetupService::class =>
                \Modules\Setup\Services\SetupService::class,
            \Modules\Setup\Services\Contracts\InstallerService::class =>
                \Modules\Setup\Services\InstallerService::class,
            \Modules\Setup\Services\Contracts\SystemAuditor::class =>
                \Modules\Setup\Services\SystemAuditor::class,
        ];
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Setup\Console\Commands\AppInstallCommand::class,
            \Modules\Setup\Console\Commands\SetupResetCommand::class,
        ]);
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
