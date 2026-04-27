<?php

declare(strict_types=1);

namespace Modules\Support\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Modules\Support\Scaffolding\Console\Commands\MakeClassCommand;
use Modules\Support\Scaffolding\Console\Commands\MakeDuskCommand;
use Modules\Support\Scaffolding\Console\Commands\MakeInterfaceCommand;
use Modules\Support\Scaffolding\Console\Commands\MakeTraitCommand;
use Modules\Support\Testing\Console\Commands\AppTestCommand;
use Nwidart\Modules\Traits\PathNamespace;

class SupportServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;
    use PathNamespace;

    protected string $name = 'Support';

    protected string $nameLower = 'support';

    /**
     * Register commands for the module.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            AppTestCommand::class,
            MakeClassCommand::class,
            MakeInterfaceCommand::class,
            MakeTraitCommand::class,
            MakeDuskCommand::class,
        ]);
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
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
                //
            ];
    }
}
