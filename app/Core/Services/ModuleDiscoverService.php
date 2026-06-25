<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Policies\BasePolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;

class ModuleDiscoverService
{
    private const MODULE_PATH = __DIR__.'/..';

    public function discoverLivewireComponents(): void
    {
        $components = Cache::remember(config('cache-keys.module_livewire'), 86400, function () {
            $result = [];
            $moduleDir = realpath(self::MODULE_PATH);
            if ($moduleDir === false) {
                return $result;
            }

            $directory = config('module.livewire.directory', 'Livewire');
            $excludePaths = config('module.livewire.exclude_paths', ['Concerns', 'Traits']);
            $files = $this->scanPhpFiles($moduleDir);

            foreach ($files as $filePath) {
                if (! str_contains($filePath, '/'.$directory.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($filePath, $excludePaths)) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if (! preg_match('/^namespace\s+(.+?);$/m', $content, $nsMatch)) {
                    continue;
                }

                $className = basename($filePath, '.php');
                $fqcn = $nsMatch[1].'\\'.$className;

                if (! is_subclass_of($fqcn, 'Livewire\Component')) {
                    continue;
                }

                $relativePath = str_replace($moduleDir.'/', '', $filePath);
                $parts = explode('/', $relativePath);

                if (($parts[0] ?? '') === $directory) {
                    continue;
                }

                // If structure: Module/Submodule/Livewire/Class.php
                // index: 0=Module, 1=Submodule, 2=Livewire, 3=Class.php (or index 1 is Livewire)
                $module = $parts[0] ?? '';
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
        $policies = Cache::remember(config('cache-keys.module_policies'), 86400, function () {
            $result = [];
            $moduleDir = realpath(self::MODULE_PATH);
            if ($moduleDir === false) {
                return $result;
            }

            $directory = config('module.policies.directory', 'Policies');
            $excludePaths = config('module.policies.exclude_paths', ['Concerns', 'Traits']);
            $files = $this->scanPhpFiles($moduleDir);

            foreach ($files as $filePath) {
                if (! str_contains($filePath, '/'.$directory.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($filePath, $excludePaths)) {
                    continue;
                }

                $className = basename($filePath, '.php');
                if (! str_ends_with($className, 'Policy')) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if (! preg_match('/^namespace\s+(.+?);$/m', $content, $nsMatch)) {
                    continue;
                }

                $policyClass = $nsMatch[1].'\\'.$className;
                if (! is_subclass_of($policyClass, BasePolicy::class)) {
                    continue;
                }

                $relativePath = str_replace($moduleDir.'/', '', $filePath);
                $parts = explode('/', $relativePath);
                $module = $parts[0] ?? '';
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
        $namespaces = Cache::remember(config('cache-keys.module_views'), 86400, function () {
            $result = [];
            $viewsDir = realpath(
                config('module.paths.views', self::MODULE_PATH.'/../resources/views'),
            );
            if ($viewsDir === false) {
                return $result;
            }

            $excluded = config('module.views.exclude_directories', [
                'components',
                'emails',
                'errors',
                'mcp',
                'pdf',
                'vendor',
            ]);

            $moduleDirs = glob($viewsDir.'/*', GLOB_ONLYDIR);
            foreach ($moduleDirs as $dir) {
                $name = basename($dir);
                if (in_array($name, $excluded, true)) {
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
