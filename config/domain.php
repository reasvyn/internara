<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Domain List
    |--------------------------------------------------------------------------
    |
    | All registered domains in dependency order. Foundation domains first,
    | then identity, institution, business lifecycle, and administration.
    | Used by DomainServiceProvider for auto-discovery and registration.
    |
    */

    'list' => [
        'Core',
        'User',
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
        'SysAdmin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Mapping
    |--------------------------------------------------------------------------
    |
    | Base paths for domain code. Override if your directory structure differs
    | from the standard app/Domain/{Domain}/ layout.
    |
    */

    'paths' => [
        'base' => app_path('Domain'),
        'views' => resource_path('views'),
        'routes' => base_path('routes/web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Components
    |--------------------------------------------------------------------------
    |
    | Auto-discover Livewire components from each domain's Livewire/
    | directory. Components are registered with the alias pattern:
    | {kebab-domain}.{kebab-class-name}.
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
    | a domain's Policies/ directory gates {Model} in the same domain's
    | Models/ directory. Cross-domain policies are registered manually
    | in AppServiceProvider.
    |
    */

    'policies' => [
        'enabled' => true,

        'directory' => 'Policies',

        'exclude_paths' => ['Concerns', 'Traits'],

        'model_namespace' => 'App\\Domain\\{domain}\\Models\\{model}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Factories
    |--------------------------------------------------------------------------
    |
    | Laravel 11+ uses convention-based factory discovery, but explicit
    | newFactory() methods prevent refactoring surprises. When enabled,
    | DomainServiceProvider verifies that domain-first naming conventions
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
    | Register each domain's view directory as an anonymous component
    | namespace (x-{domain}::). Excludes directories that are not domains
    | (layouts, components, emails, errors, etc.).
    |
    */

    'views' => [
        'enabled' => true,

        'exclude_directories' => [
            'components', 'emails', 'errors', 'layouts', 'mcp', 'pdf', 'vendor',
        ],
    ],
];
