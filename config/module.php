<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Module Registry — Auto-discovered from filesystem
|--------------------------------------------------------------------------
|
| Single source of truth for all Modules and their Domains. Discovered
| directly from the real directory structure:
|   app/Modules/{Module}/Domain/{Domain}/
| so no manual list is needed. Adding a new Module/Domain is just
| creating the directory.
|
| Used by:
|   - ModuleManager (module config gateway)
|   - ModuleService (Livewire, Policy, View auto-discovery)
|   - routes/web.php (auto-include route files)
|   - tests/Pest.php (test directory registration)
|
*/

$modulesBase = app_path('Modules');

$modules = [];
if (is_dir($modulesBase)) {
    foreach (scandir($modulesBase) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $modulePath = $modulesBase.DIRECTORY_SEPARATOR.$entry;
        if (! is_dir($modulePath)) {
            continue;
        }
        // Only consider directories that look like modules (PascalCase, contains PHP or Domain)
        $domainPath = $modulePath.DIRECTORY_SEPARATOR.'Domain';
        $domains = [];
        if (is_dir($domainPath)) {
            foreach (scandir($domainPath) as $d) {
                if ($d === '.' || $d === '..') {
                    continue;
                }
                if (is_dir($domainPath.DIRECTORY_SEPARATOR.$d)) {
                    $domains[] = $d;
                }
            }
            sort($domains);
        }
        $modules[$entry] = $domains;
    }
    ksort($modules);
}

return [

    /*
    |--------------------------------------------------------------------------
    | Module List
    |--------------------------------------------------------------------------
    |
    | Module names in dependency order. Derived from $modules array keys.
    |
    */

    'list' => array_keys($modules),

    /*
    |--------------------------------------------------------------------------
    | Module Registry
    |--------------------------------------------------------------------------
    |
    | Full module → submodule mapping. Used by ModuleManager/ModuleService to scope
    | filesystem scanning to registered modules only.
    |
    */

    'registry' => $modules,

    /*
    |--------------------------------------------------------------------------
    | Extra Test Directories
    |--------------------------------------------------------------------------
    |
    | Additional directories under tests/ that are not domain modules but
    | contain test code (e.g. Providers, Stubs, Support).
    |
    */

    'test_dirs' => ['Providers', 'Stubs', 'Support'],

    /*
    |--------------------------------------------------------------------------
    | Path Mapping
    |--------------------------------------------------------------------------
    |
    | Base paths for module code. Override if your directory structure differs
    | from the standard app/Modules/{Module}/Domain/{Domain}/ layout.
    |
    */

    'paths' => [
        'base' => app_path('Modules'),
        'views' => resource_path('views'),
        'routes' => base_path('routes/web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Components
    |--------------------------------------------------------------------------
    |
    | Auto-discover Livewire components from each module's Livewire/
    | directory. Components are registered with the alias pattern:
    | {kebab-module}.{kebab-class-name}.
    |
    */

    'livewire' => [
        'enabled' => true,

        'directory' => 'Livewire',

        'exclude_paths' => ['Concerns', 'Traits'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Auto-discover authorization policies. Convention: {Model}Policy in
    | a module's Policies/ directory gates {Model} in the same module's
    | Models/ directory. Cross-module policies are registered manually
    | in AppServiceProvider.
    |
    */

    'policies' => [
        'enabled' => true,

        'directory' => 'Policies',

        'exclude_paths' => ['Concerns', 'Traits'],

        'model_namespace' => 'App\\Modules\\{module}\\Domain\\{domain}\\Models\\{model}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Factories
    |--------------------------------------------------------------------------
    |
    | Laravel 11+ uses convention-based factory discovery, but explicit
    | newFactory() methods prevent refactoring surprises. When enabled,
    | AppServiceProvider verifies that module-first naming conventions
    | align with factory resolution.
    |
    */

    'factories' => [
        'enabled' => true,

        'namespace' => 'Database\\Factories',

        'suffix' => 'Factory',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blade View Namespaces
    |--------------------------------------------------------------------------
    |
    | Register each module's view directory as an anonymous component
    | namespace (x-{module}::). Excludes directories that are not modules
    | (layouts, components, emails, errors, etc.).
    |
    */

    'views' => [
        'enabled' => true,

        'exclude_directories' => [
            'components',
            'emails',
            'errors',
            'layouts',
            'mcp',
            'pdf',
            'vendor',
        ],
    ],
];
