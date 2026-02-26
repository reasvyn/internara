<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class BindServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $autoBindings = [];

        if (config('bindings.autobind', true)) {
            $autoBindings = $this->scanForBindings();
        }

        // Register all bindings (auto-discovered + default)
        $bindings = array_merge($autoBindings, config('bindings.default', []));

        // method to use for binding (e.g. bind vs singleton)
        $method = config('bindings.bind_as_singleton', false) ? 'singleton' : 'bind';

        foreach ($bindings as $abstract => $concrete) {
            /** Ensure that the abstract is an existing interface and the concrete is an existing class */
            if (
                interface_exists($abstract) &&
                class_exists($concrete) &&
                ! interface_exists($concrete)
            ) {
                $this->app->{$method}($abstract, $concrete);
            }
        }

        $this->whenCall();
    }

    /**
     * Scan filesystem for bindings.
     *
     * @return array<string, string>
     */
    protected function scanForBindings(): array
    {
        $discoveredBindings = [];
        $paths = $this->getPathsToScan();

        foreach ($paths as $path => $rootNamespace) {
            $discoveredBindings = array_merge(
                $discoveredBindings,
                $this->scanDirectory($path, $rootNamespace),
            );
        }

        return $discoveredBindings;
    }

    /**
     * Get all paths that should be scanned for contracts.
     *
     * @return array<string, string> Map of path => base namespace
     */
    protected function getPathsToScan(): array
    {
        $paths = [];

        // 1. App/Contracts
        $appContractPath = app_path('Contracts');
        if (file_exists($appContractPath)) {
            $paths[$appContractPath] = 'App';
        }

        // 2. Modules/*/src/Contracts
        return array_merge($paths, $this->getModuleContractPaths());
    }

    /**
     * Get contract paths for all active modules.
     *
     * @return array<string, string>
     */
    protected function getModuleContractPaths(): array
    {
        $paths = [];
        $modulesPath = config('modules.paths.modules', base_path('modules'));
        $moduleAppPath = config('modules.paths.app_folder', 'src/');
        $modulesNamespace = config('modules.namespace', 'Modules');

        if (! is_dir($modulesPath)) {
            return $paths;
        }

        try {
            foreach (new DirectoryIterator($modulesPath) as $moduleDir) {
                if ($moduleDir->isDir() && ! $moduleDir->isDot()) {
                    $moduleName = $moduleDir->getBasename();
                    $baseNamespace = $modulesNamespace . '\\' . $moduleName;
                    $srcPath = $moduleDir->getPathname() . '/' . trim($moduleAppPath, '/');

                    if (is_dir($srcPath)) {
                        $this->findContractDirectories($srcPath, $baseNamespace, $paths);
                    }
                }
            }
        } catch (Throwable $e) {
            if (is_debug_mode()) {
                Log::debug('BindServiceProvider: Failed to scan modules. ' . $e->getMessage());
            }
        }

        return $paths;
    }

    /**
     * Recursively find all 'Contracts' directories.
     */
    protected function findContractDirectories(string $dir, string $namespace, array &$paths): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && $file->getBasename() === 'Contracts') {
                $paths[$file->getPathname()] = $namespace;
            }
        }
    }

    /**
     * Scan a directory and return found bindings.
     *
     * @return array<string, string>
     */
    protected function scanDirectory(string $path, string $baseNamespace): array
    {
        $bindings = [];
        $ignoredNamespaces = config('bindings.ignored_namespaces', []);

        if (! is_dir($path)) {
            return $bindings;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $interfaceInfo = $this->findInterfaceInFile($file->getPathname());

            if (! $interfaceInfo) {
                continue;
            }

            [$abstract, $interfaceName] = $interfaceInfo;

            // Security: Check ignored namespaces
            if (Str::startsWith($abstract, $ignoredNamespaces)) {
                continue;
            }

            // Determine Root Namespace (remove \Contracts...)
            $rootNamespace = Str::before($abstract, '\\Contracts');
            if ($rootNamespace === $abstract) {
                $rootNamespace = $baseNamespace;
            }

            $concrete = $this->deriveConcreteClass($rootNamespace, $interfaceName);

            if ($concrete) {
                $bindings[$abstract] = $concrete;
            }
        }

        return $bindings;
    }

    /**
     * Parse a PHP file to find the interface FQCN.
     *
     * @return array{0: string, 1: string}|null [Full Abstract Name, Short Interface Name]
     */
    protected function findInterfaceInFile(string $filePath): ?array
    {
        try {
            $content = File::get($filePath);

            if (! preg_match('/namespace\s+([^;]+);/', $content, $nsMatches)) {
                return null;
            }
            if (! preg_match('/interface\s+(\w+)/', $content, $ifMatches)) {
                return null;
            }

            $namespace = trim($nsMatches[1]);
            $name = trim($ifMatches[1]);
            $abstract = $namespace.'\\'.$name;

            if (! interface_exists($abstract)) {
                return null;
            }

            return [$abstract, $name];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Guess the concrete class based on the interface name.
     */
    protected function deriveConcreteClass(string $rootNamespace, string $interfaceName): ?string
    {
        $shortName = $interfaceName;
        if (Str::endsWith($interfaceName, 'Interface')) {
            $shortName = Str::replaceLast('Interface', '', $interfaceName);
        } elseif (Str::endsWith($interfaceName, 'Contract')) {
            $shortName = Str::replaceLast('Contract', '', $interfaceName);
        }

        $candidates = $this->getConcreteCandidates($rootNamespace, $shortName);

        foreach ($candidates as $candidate) {
            if (class_exists($candidate) && ! interface_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Get list of potential concrete classes based on config patterns.
     *
     * @return array<string>
     */
    protected function getConcreteCandidates(string $rootNamespace, string $shortName): array
    {
        // If shortName already ends with 'Service', we also want to try without the redundant suffix
        $shortBase = $shortName;
        if (str_ends_with($shortName, 'Service')) {
            $shortBase = substr($shortName, 0, -7);
        }

        $patterns = config('bindings.patterns', [
            '{{root}}\Services\{{short}}Service',
            '{{root}}\Services\{{short}}',
            '{{root}}\{{short}}Service',
            '{{root}}\{{short}}',
        ]);

        $candidates = [];
        foreach ($patterns as $pattern) {
            // Try with the base name (e.g. Metadata)
            $candidates[] = str_replace(['{{root}}', '{{short}}'], [$rootNamespace, $shortBase], $pattern);
            
            // If shortName was different (e.g. MetadataService), also try with that exactly
            if ($shortBase !== $shortName) {
                $candidates[] = str_replace(['{{root}}', '{{short}}'], [$rootNamespace, $shortName], $pattern);
            }
        }

        return array_unique($candidates);
    }

    /**
     * Register contextual service bindings (When-Call bindings).
     */
    protected function whenCall(): void
    {
        $contextualBindings = config('bindings.contextual', []);

        foreach ($contextualBindings as $binding) {
            if (isset($binding['when'], $binding['needs'], $binding['give'])) {
                $this->app
                    ->when($binding['when'])
                    ->needs($binding['needs'])
                    ->give($binding['give']);
            }
        }
    }
}
