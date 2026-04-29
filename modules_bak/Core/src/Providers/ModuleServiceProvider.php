<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use DirectoryIterator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
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
     *
     * Validates each directory is a legitimate module by checking for
     * composer.json or Module.php existence (S3 - domain isolation).
     */
    protected function traverseModules(callable $callback): void
    {
        $modulesPath = base_path('modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        try {
            foreach (new DirectoryIterator($modulesPath) as $moduleDir) {
                if (!$moduleDir->isDir() || $moduleDir->isDot()) {
                    continue;
                }

                $moduleName = $moduleDir->getBasename();
                $modulePath = $moduleDir->getPathname();

                // Validate: must be a legitimate module (S3 - explicit boundaries)
                $hasComposer = file_exists($modulePath . '/composer.json');
                $hasModuleFile = file_exists($modulePath . '/Module.php');

                if (!$hasComposer && !$hasModuleFile) {
                    if (is_debug_mode()) {
                        Log::debug("ModuleServiceProvider: Skipping non-module directory: {$moduleName}");
                    }
                    continue;
                }

                $callback($moduleName, $modulePath);
            }
        } catch (Throwable $e) {
            if (is_debug_mode()) {
                Log::debug('ModuleServiceProvider: Traversal failed. ' . $e->getMessage());
            }
        }
    }
}
