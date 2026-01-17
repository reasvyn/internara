<?php

declare(strict_types=1);

namespace Modules\Shared\Providers\Concerns;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\UI\Facades\SlotRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Trait ManagesModuleProvider
 *
 * Provides standardized methods for module service providers to handle
 * configuration, translations, views, migrations, and bindings.
 */
trait ManagesModuleProvider
{
    /**
     * Standard module registration logic.
     * Call this in your provider's register() method.
     */
    protected function registerModule(): void
    {
        $this->registerBindings();
    }

    /**
     * Standard module boot logic.
     * Call this in your provider's boot() method.
     */
    protected function bootModule(): void
    {
        $this->registerPolicies();
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerViewSlots();
    }

    /**
     * Register the module's policies.
     */
    public function registerPolicies(): void
    {
        if (property_exists($this, 'policies')) {
            foreach ($this->policies as $model => $policy) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Register the module's service bindings.
     */
    protected function registerBindings(): void
    {
        if (! $this instanceof ServiceProvider) {
            throw new \LogicException(
                'The ManagesModuleProvider trait must be used in a class that extends Illuminate\Support\ServiceProvider.',
            );
        }

        foreach ($this->bindings() as $abstract => $concrete) {
            if (
                is_string($concrete) &&
                class_exists($concrete) &&
                (new \ReflectionClass($concrete))->isInstantiable()
            ) {
                $this->app->singleton($abstract, $concrete);
            } else {
                $this->app->bind($abstract, $concrete);
            }
        }
    }

    /**
     * Get the service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [];
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // To be overridden by the module provider if needed.
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // To be overridden by the module provider if needed.
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path(
            $this->name,
            config('modules.paths.generator.config.path', 'config'),
        );

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace(
                        $configPath.DIRECTORY_SEPARATOR,
                        '',
                        $file->getPathname(),
                    );
                    $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$configKey);

                    // Remove duplicated adjacent segments (e.g. user.user -> user)
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = $config === 'config.php' ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->mergeConfigFromRecursive($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function mergeConfigFromRecursive(string $path, string $key): void
    {
        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->nameLower,
        );

        Blade::componentNamespace(
            config('modules.namespace').'\\'.$this->name.'\\View\\Components',
            $this->nameLower,
        );
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get paths for publishable views.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Register components for UI slots.
     */
    protected function registerViewSlots(): void
    {
        if (class_exists(SlotRegistry::class)) {
            SlotRegistry::configure($this->viewSlots());
        }
    }

    /**
     * Define view slots for UI injection.
     */
    protected function viewSlots(): array
    {
        return [];
    }
}
