<?php

declare(strict_types=1);

namespace App\Core\Support;

use Illuminate\Support\Str;

/**
 * Static gateway for all module configuration reads.
 *
 * The single entry point for module infrastructure and modular configuration.
 * Consumers must use this class instead of calling `config('module.*')` directly.
 * Pure config reads only — no filesystem scanning (see ModuleService).
 */
final class ModuleManager
{
    /**
     * Get the list of registered module names in dependency order.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return config('module.list', []);
    }

    /**
     * Check if a module name is registered (strict match).
     */
    public static function isModule(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    /**
     * Get the module → submodule registry mapping.
     *
     * @return array<string, list<string>>
     */
    public static function registry(): array
    {
        return config('module.registry', []);
    }

    /**
     * Get the submodules for a module.
     *
     * @return list<string>
     */
    public static function submodules(string $module): array
    {
        return self::registry()[$module] ?? [];
    }

    /**
     * Get the non-module test directories.
     *
     * @return list<string>
     */
    public static function testDirs(): array
    {
        return config('module.test_dirs', []);
    }

    /**
     * Get the base path for module code.
     */
    public static function basePath(): string
    {
        return (string) config('module.paths.base', app_path());
    }

    /**
     * Get the base path for module views.
     */
    public static function viewsPath(): string
    {
        return (string) config('module.paths.views', resource_path('views'));
    }

    /**
     * Get the base path for module route files.
     */
    public static function routesPath(): string
    {
        return (string) config('module.paths.routes', base_path('routes/web'));
    }

    /**
     * Check whether policy auto-discovery is enabled.
     */
    public static function policiesEnabled(): bool
    {
        return (bool) config('module.policies.enabled', true);
    }

    /**
     * Check whether Livewire component auto-discovery is enabled.
     */
    public static function livewireEnabled(): bool
    {
        return (bool) config('module.livewire.enabled', true);
    }

    /**
     * Check whether Blade view namespace registration is enabled.
     */
    public static function viewsEnabled(): bool
    {
        return (bool) config('module.views.enabled', true);
    }

    /**
     * Check whether factory verification is enabled.
     */
    public static function factoriesEnabled(): bool
    {
        return (bool) config('module.factories.enabled', true);
    }

    /**
     * Get the directory name that holds Livewire components.
     */
    public static function livewireDirectory(): string
    {
        return (string) config('module.livewire.directory', 'Livewire');
    }

    /**
     * Get the subdirectories excluded from Livewire discovery.
     *
     * @return list<string>
     */
    public static function livewireExcludePaths(): array
    {
        return config('module.livewire.exclude_paths', ['Concerns', 'Traits']);
    }

    /**
     * Get the directory name that holds authorization policies.
     */
    public static function policiesDirectory(): string
    {
        return (string) config('module.policies.directory', 'Policies');
    }

    /**
     * Get the subdirectories excluded from policy discovery.
     *
     * @return list<string>
     */
    public static function policiesExcludePaths(): array
    {
        return config('module.policies.exclude_paths', ['Concerns', 'Traits']);
    }

    /**
     * Get the model namespace pattern used to resolve policies to models.
     */
    public static function policyModelNamespace(): string
    {
        return (string) config('module.policies.model_namespace', 'App\\{domain}\\Models\\{model}');
    }

    /**
     * Get the directories excluded from view namespace registration.
     *
     * @return list<string>
     */
    public static function viewsExcludeDirectories(): array
    {
        return config('module.views.exclude_directories', [
            'components',
            'emails',
            'errors',
            'layouts',
            'mcp',
            'pdf',
            'vendor',
        ]);
    }

    /**
     * Get the route file path for a module.
     *
     * Convention: `{routesPath}/{lowercase-module}.php`.
     */
    public static function routeFilePath(string $module): string
    {
        return self::routesPath().'/'.Str::lower($module).'.php';
    }

    /**
     * Check if a directory name (e.g., a view directory) belongs to a registered module.
     *
     * View directories use lowercase module names, so the comparison is
     * case-insensitive against the registered module list.
     */
    public static function isRegisteredDirectory(string $directoryName): bool
    {
        $lowered = Str::lower($directoryName);

        return in_array($lowered, array_map(fn (string $name) => Str::lower($name), self::names()), true);
    }
}
