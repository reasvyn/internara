<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Module Registry
|--------------------------------------------------------------------------
|
| Single source of truth for all modules and their submodules. Keys are
| module names in dependency order (foundation → identity → institution
| → business lifecycle → administration). Each value lists the module's
| submodules — directories that contain domain-specific code beyond the
| standard Action/Model/Entity/Enum/Livewire/Policy layers.
|
| Used by:
|   - ModuleDiscoverService (Livewire, Policy, View auto-discovery)
|   - routes/web.php (auto-include route files)
|   - tests/Pest.php (test directory registration)
|
*/

$modules = [
    'Core' => [
        'Channels', 'Console', 'Contracts', 'Exceptions',
    ],
    'Setup' => [
        'Installation', 'SetupWizard',
    ],
    'Settings' => [
        'Branding', 'Casts', 'Locale', 'Rules', 'Theme',
    ],
    'Auth' => [
        'AccessTokens', 'Account', 'AccountRecovery', 'Login',
        'Notifications', 'Password', 'Permissions', 'SuperAdmin',
    ],
    'User' => [
        'AccountStatus', 'Dashboard', 'Mentor', 'Notifications',
        'Profile', 'Rules', 'UserManagement',
    ],
    'SysAdmin' => [
        'Announcement', 'Backups', 'Console', 'Observability',
    ],
    'Academics' => [
        'AcademicYear', 'Department', 'School',
    ],
    'Partners' => [
        'Company', 'Partnership',
    ],
    'Program' => [
        'Internship', 'InternshipGroup', 'Notifications',
    ],
    'Enrollment' => [
        'AccountApplication', 'Placement', 'Registration',
    ],
    'Journals' => [
        'AbsenceRequest', 'Attendance', 'Logbook', 'MonitoringVisit', 'SupervisionLog',
    ],
    'Assignment' => [
        'Notifications', 'Submission',
    ],
    'Reports' => [
        'Report',
    ],
    'Assessment' => [
        'Rubric',
    ],
    'Evaluation' => [],
    'Certification' => [
        'Certificate',
    ],
    'Incident' => [
        'IncidentReport',
    ],
    'Document' => [
        'Handbook', 'OfficialDocument',
    ],
];

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
    | Full module → submodule mapping. Used by ModuleDiscoverService to scope
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
