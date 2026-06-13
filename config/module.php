<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module List
    |--------------------------------------------------------------------------
    |
    | All registered modules in dependency order. Foundation modules first,
    | then identity, institution, business lifecycle, and administration.
    | Used by AppServiceProvider for auto-discovery and registration.
    |
    */

    'list' => [
        'Core',
        'Setup',
        'Settings',
        'Auth',
        'User',
        'SysAdmin',
        'Academics',
        'Partners',
        'Program',
        'Enrollment',
        'Guidance',
        'Journals',
        'Assignment',
        'Reports',
        'Assessment',
        'Evaluation',
        'Certification',
        'Incident',
        'Document',
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Mapping
    |--------------------------------------------------------------------------
    |
    | Base paths for module code. Override if your directory structure differs
    | from the standard app/{Module}/ layout.
    |
    */

    'paths' => [
        'base' => app_path(),
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

        'model_namespace' => 'App\\{domain}\\Models\\{model}',
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
