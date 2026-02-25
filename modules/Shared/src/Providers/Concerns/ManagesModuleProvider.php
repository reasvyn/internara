<?php

declare(strict_types=1);

namespace Modules\Shared\Providers\Concerns;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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
     *
     * Should be invoked within the provider's register() method.
     */
    protected function registerModule(): void
    {
        $this->registerBindings();
    }

    /**
     * Standard module boot logic.
     *
     * Should be invoked within the provider's boot() method.
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
     * Registers the module's authorization policies.
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
     * Registers the module's service bindings into the container.
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
     * Defines service bindings for the module.
     *
     * @return array<string, string|\Closure>
     */
    protected function bindings(): array
    {
        return [];
    }

    /**
     * Registers Artisan commands provided by the module.
     */
    protected function registerCommands(): void
    {
        // Intended to be overridden by the module provider.
    }

    /**
     * Registers the module's command schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // Intended to be overridden by the module provider.
    }

    /**
     * Registers the module's translation files.
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);

            return;
        }

        $moduleLangPath = module_path($this->name, 'lang');

        if (is_dir($moduleLangPath)) {
            $this->loadTranslationsFrom($moduleLangPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($moduleLangPath);
        }
    }

    /**
     * Registers the module's configuration files recursively.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path(
            $this->name,
            config('modules.paths.generator.config.path', 'config'),
        );

        if (! is_dir($configPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configPath, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(
                $configPath.DIRECTORY_SEPARATOR,
                '',
                $file->getPathname(),
            );
            $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);

            // Construct the base key (module.filename)
            $segments = explode('.', $this->nameLower.'.'.$configKey);

            // De-duplicate adjacent identical segments (e.g., user.user -> user)
            $normalized = [];
            foreach ($segments as $segment) {
                if (empty($normalized) || end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = $relativePath === 'config.php' ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
            $this->mergeConfigFromRecursive($file->getPathname(), $key);
        }
    }

    /**
     * Merges configuration from the specified path recursively into the existing config.
     */
    protected function mergeConfigFromRecursive(string $path, string $key): void
    {
        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }

    /**
     * Registers the module's views and component namespaces.
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        if (! is_dir($sourcePath)) {
            return;
        }

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->nameLower,
        );

        $componentPath = $sourcePath.DIRECTORY_SEPARATOR.'components';
        if (is_dir($componentPath)) {
            Blade::anonymousComponentPath($componentPath, $this->nameLower);
        }

        Blade::componentNamespace(
            config('modules.namespace').'\\'.$this->name.'\\View\\Components',
            $this->nameLower,
        );
    }

    /**
     * Registers the module's database migrations.
     */
    protected function registerMigrations(): void
    {
        $path = module_path($this->name, 'database/migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Retrieves paths for publishable module views.
     *
     * @return array<int, string>
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (config('view.paths', []) as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Registers the module's UI slot configurations.
     */
    protected function registerViewSlots(): void
    {
        if (class_exists(\Modules\UI\Facades\SlotRegistry::class)) {
            \Modules\UI\Facades\SlotRegistry::configure($this->viewSlots());
        }
    }

    /**
     * Defines the UI slots provided by this module.
     *
     * @return array<string, string|array<int, string>>
     */
    protected function viewSlots(): array
    {
        return [];
    }
}
