<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use DirectoryIterator;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;
use Throwable;

/**
 * Class ModuleServiceProvider
 *
 * The autonomous loader for the Internara Modular Monolith.
 * This provider orchestrates modular discovery, cross-module synchronization,
 * and foundational infrastructure that spans across all domain modules.
 */
class ModuleServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;

    /**
     * Boot the application modular infrastructure.
     */
    public function boot(): void
    {
        $this->registerAllModuleTranslations();
        $this->registerAllModuleViews();
        $this->registerAllModuleVoltComponents();
    }

    /**
     * Automatically register Volt components for all active modules.
     */
    protected function registerAllModuleVoltComponents(): void
    {
        if (! class_exists(\Livewire\Volt\Volt::class)) {
            return;
        }

        $this->traverseModules(function (string $name, string $path) {
            $voltPath = $path . '/resources/views/livewire';
            if (is_dir($voltPath)) {
                \Livewire\Volt\Volt::mount([$voltPath]);
            }
        });
    }

    /**
     * Register modular services.
     */
    public function register(): void
    {
        // Infrastructure registration logic here
    }

    /**
     * Automatically register translation namespaces for all active modules.
     */
    protected function registerAllModuleTranslations(): void
    {
        $this->traverseModules(function (string $name, string $path) {
            $langPath = $path . '/lang';
            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, strtolower($name));
            }
        });
    }

    /**
     * Automatically register view namespaces for all active modules.
     */
    protected function registerAllModuleViews(): void
    {
        $this->traverseModules(function (string $name, string $path) {
            $viewPath = $path . '/resources/views';
            if (is_dir($viewPath)) {
                $this->loadViewsFrom($viewPath, strtolower($name));
            }
        });
    }

    /**
     * Helper to traverse through all domain modules.
     */
    protected function traverseModules(callable $callback): void
    {
        $modulesPath = base_path('modules');
        
        if (! is_dir($modulesPath)) {
            return;
        }

        try {
            foreach (new DirectoryIterator($modulesPath) as $moduleDir) {
                if ($moduleDir->isDir() && ! $moduleDir->isDot()) {
                    $callback($moduleDir->getBasename(), $moduleDir->getPathname());
                }
            }
        } catch (Throwable $e) {
            if (is_debug_mode()) {
                Log::debug('ModuleServiceProvider: Traversal failed. ' . $e->getMessage());
            }
        }
    }
}
