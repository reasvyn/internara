<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Metadata\Console\Commands\AppInfoCommand;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;

class CoreServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

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

        $this->app->register(AliasServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands for the module.
     */
    protected function registerCommands(): void
    {
        $this->commands([AppInfoCommand::class]);
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [
            \Modules\Core\Metadata\Services\Contracts\MetadataService::class => \Modules\Core\Metadata\Services\MetadataService::class,
        ];
    }
}
