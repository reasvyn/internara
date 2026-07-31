<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Policies\BasePolicy;
use App\Core\Support\ModuleManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Orchestrates runtime module discovery (Livewire, policies, Blade namespaces).
 *
 * The single place that scans module directories. All configuration reads are
 * delegated to ModuleManager; results are cached via the injected cache repository.
 */
final readonly class ModuleService
{
    public function __construct(private Repository $cache) {}

    public function discoverLivewireComponents(): void
    {
        $components = $this->cache->remember(config('cache-keys.module_livewire'), 86400, function () {
            $result = [];
            $moduleDir = app_path();

            $directory = ModuleManager::livewireDirectory();
            $excludePaths = ModuleManager::livewireExcludePaths();
            $registeredModules = ModuleManager::names();
            $files = $this->scanPhpFiles($moduleDir);

            foreach ($files as $filePath) {
                if (! str_contains($filePath, '/'.$directory.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($filePath, $excludePaths)) {
                    continue;
                }

                $relativePath = str_replace($moduleDir.'/', '', $filePath);
                $parts = explode('/', $relativePath);
                $module = $parts[0];

                if (! in_array($module, $registeredModules, true)) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if ($content === false || ! preg_match('/^namespace\s+(.+?);$/m', $content, $nsMatch)) {
                    continue;
                }

                $className = basename($filePath, '.php');
                $fqcn = $nsMatch[1].'\\'.$className;

                if (! is_subclass_of($fqcn, 'Livewire\Component')) {
                    continue;
                }

                if ($parts[0] === $directory) {
                    continue;
                }

                // If structure: Module/Submodule/Livewire/Class.php
                // index: 0=Module, 1=Submodule, 2=Livewire, 3=Class.php (or index 1 is Livewire)
                $submodule = $parts[1] !== $directory ? $parts[1] ?? '' : '';

                $alias = $submodule
                    ? Str::kebab($module).
                        '.'.
                        Str::kebab($submodule).
                        '.'.
                        Str::kebab($className)
                    : Str::kebab($module).'.'.Str::kebab($className);

                $result[$alias] = $fqcn;
            }

            return $result;
        });

        foreach ($components as $alias => $fqcn) {
            Livewire::component($alias, $fqcn);
        }
    }

    public function discoverPolicies(): void
    {
        $policies = $this->cache->remember(config('cache-keys.module_policies'), 86400, function () {
            $result = [];
            $moduleDir = app_path();

            $directory = ModuleManager::policiesDirectory();
            $excludePaths = ModuleManager::policiesExcludePaths();
            $registeredModules = ModuleManager::names();
            $files = $this->scanPhpFiles($moduleDir);

            foreach ($files as $filePath) {
                if (! str_contains($filePath, '/'.$directory.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($filePath, $excludePaths)) {
                    continue;
                }

                $relativePath = str_replace($moduleDir.'/', '', $filePath);
                $parts = explode('/', $relativePath);
                $module = $parts[0];

                if (! in_array($module, $registeredModules, true)) {
                    continue;
                }

                $className = basename($filePath, '.php');
                if (! str_ends_with($className, 'Policy')) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if ($content === false || ! preg_match('/^namespace\s+(.+?);$/m', $content, $nsMatch)) {
                    continue;
                }

                $policyClass = $nsMatch[1].'\\'.$className;
                if (! is_subclass_of($policyClass, BasePolicy::class)) {
                    continue;
                }

                $submodule = $parts[1] !== $directory ? $parts[1] ?? '' : '';

                $modelName = preg_replace('/Policy$/', '', $className);
                $modelClass = $submodule
                    ? "App\\{$module}\\{$submodule}\\Models\\{$modelName}"
                    : "App\\{$module}\\Models\\{$modelName}";

                if (! class_exists($modelClass)) {
                    continue;
                }

                $result[$modelClass] = $policyClass;
            }

            return $result;
        });

        foreach ($policies as $modelClass => $policyClass) {
            Gate::policy($modelClass, $policyClass);
        }
    }

    public function registerBladeNamespaces(): void
    {
        $namespaces = $this->cache->remember(config('cache-keys.module_views'), 86400, function () {
            $result = [];
            $viewsDir = realpath(ModuleManager::viewsPath());
            if ($viewsDir === false) {
                return $result;
            }

            $excluded = ModuleManager::viewsExcludeDirectories();
            $moduleDirs = glob($viewsDir.'/*', GLOB_ONLYDIR) ?: [];
            foreach ($moduleDirs as $dir) {
                $name = basename($dir);
                if (in_array($name, $excluded, true)) {
                    continue;
                }
                if (! ModuleManager::isRegisteredDirectory($name)) {
                    continue;
                }
                $result[] = ['name' => $name, 'path' => $dir];
            }

            return $result;
        });

        foreach ($namespaces as $ns) {
            if (is_dir($ns['path'])) {
                Blade::anonymousComponentPath($ns['path'], $ns['name']);
                View::addNamespace($ns['name'], $ns['path']);
            }
        }
    }

    /**
     * Scan a directory recursively for PHP files.
     *
     * @return list<string>
     */
    private function scanPhpFiles(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Check whether a file path is inside an excluded subdirectory.
     *
     * @param list<string> $excludePaths
     */
    private function isExcludedPath(string $filePath, array $excludePaths): bool
    {
        foreach ($excludePaths as $excluded) {
            if (str_contains($filePath, '/'.$excluded.'/')) {
                return true;
            }
        }

        return false;
    }
}
