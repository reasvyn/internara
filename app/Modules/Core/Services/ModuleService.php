<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Core\Support\ModuleManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;

/**
 * Orchestrates runtime module discovery (Livewire, policies, Blade namespaces).
 *
 * The single place that scans module directories. All configuration reads are
 * delegated to ModuleManager; results are cached via the injected cache repository.
 */
final readonly class ModuleService
{
    private const CACHE_TTL_SECONDS = 86400;

    public function __construct(private Repository $cache) {}

    /**
     * Cache a discovery result, but never persist an empty one.
     *
     * An empty map means the scan found nothing — a transient deploy state, not a
     * valid registry. Caching it would disable every policy (turning authorize()
     * into a blanket 403), Livewire component or view namespace for the whole TTL.
     *
     * @param callable(): array<string, string> $discover
     * @return array<string, string>
     */
    private function rememberDiscovery(string $key, callable $discover): array
    {
        $cached = $this->cache->get($key);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $result = $discover();

        if ($result !== []) {
            $this->cache->put($key, $result, self::CACHE_TTL_SECONDS);
        }

        return $result;
    }

    public function discoverLivewireComponents(): void
    {
        $components = $this->rememberDiscovery(config('cache-keys.module_livewire'), function () {
            $result = [];
            $moduleDir = ModuleManager::basePath();

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

                if (! is_subclass_of($fqcn, Component::class)) {
                    continue;
                }

                if ($parts[0] === $directory) {
                    continue;
                }

                // Structure: Module/Domain/{Domain}/Livewire/Class.php  or  Module/Livewire/Class.php
                $livewireIndex = array_search($directory, $parts, true);
                $domain = '';
                if ($livewireIndex !== false && isset($parts[1]) && $parts[1] === 'Domain' && $livewireIndex >= 3 && isset($parts[2])) {
                    $domain = $parts[2];
                }

                $alias = $domain
                    ? Str::kebab($module).
                        '.'.
                        Str::kebab($domain).
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
        $policies = $this->rememberDiscovery(config('cache-keys.module_policies'), function () {
            $result = [];
            $moduleDir = ModuleManager::basePath();

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

                $policiesIndex = array_search($directory, $parts, true);
                $domain = '';
                if ($policiesIndex !== false && isset($parts[1]) && $parts[1] === 'Domain' && $policiesIndex >= 3 && isset($parts[2])) {
                    $domain = $parts[2];
                }

                $modelName = preg_replace('/Policy$/', '', $className);
                // Resolve model class via ModuleManager pattern or direct check
                $pattern = ModuleManager::policyModelNamespace();
                // Pattern is like App\Modules\{module}\Domain\{domain}\Models\{model}
                $modelClass = $pattern;
                $modelClass = str_replace('{module}', $module, $modelClass);
                $modelClass = str_replace('{domain}', $domain, $modelClass);
                $modelClass = str_replace('{model}', $modelName, $modelClass);
                // Fallback for flat module without domain: remove \Domain\ segment if domain empty
                if ($domain === '') {
                    $modelClass = str_replace('\\Domain\\', '\\', $modelClass);
                    $modelClass = str_replace('\\\\', '\\', $modelClass);
                }
                // Also handle legacy flat without Domain
                if (! class_exists($modelClass)) {
                    // Try flat module model
                    $flatModel = "App\\Modules\\{$module}\\Models\\{$modelName}";
                    if (class_exists($flatModel)) {
                        $modelClass = $flatModel;
                    } else {
                        continue;
                    }
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
        $namespaces = $this->rememberDiscovery(config('cache-keys.module_views'), function () {
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
