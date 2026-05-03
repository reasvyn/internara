<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Binding
    |--------------------------------------------------------------------------
    |
    | If enabled, the provider automatically discovers and binds interfaces
    | from both contract locations to their matching implementations based
    | on the naming patterns defined below.
    |
    | Contract locations:
    |   - `App/Contracts/{Domain}/`    — centralized (shared across domains)
    |   - `App/{Domain}/Contracts/`    — domain-scoped (owned by one domain)
    |
    | Example: `App\Contracts\Services\UserServiceInterface` or
    | `App\Services\Contracts\UserServiceInterface` both resolve to
    | `App\Services\UserService`.
    |
    */

    'autobind' => true,

    /*
    |--------------------------------------------------------------------------
    | Bind as Singleton
    |--------------------------------------------------------------------------
    |
    | If true, services are registered as singletons. Set to false for
    | transient (new instance per resolution) bindings.
    |
    */

    'bind_as_singleton' => true,

    /*
    |--------------------------------------------------------------------------
    | Concrete Class Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns used to resolve an interface to its concrete implementation.
    | Scanning covers `App/Contracts/{Domain}/` (centralized) and
    | `App/{Domain}/Contracts/` (domain-scoped). The provider derives the
    | domain namespace from the interface path, then applies each pattern.
    |
    | Placeholders:
    |   {{domain}} — Domain namespace (e.g. `App\Services` from either
    |                `App\Services\Contracts\` or `App\Contracts\Services\`)
    |   {{base}}   — Interface name without Interface/Contract suffix
    |   {{name}}   — Full interface short name
    |
    | Patterns are tried in order. The first existing class wins.
    |
    */

    'patterns' => [
        // Services: App\Contracts\Services\XxxInterface → App\Services\XxxService
        '{{domain}}\{{base}}',
        '{{domain}}\{{name}}',

        // Domain-scoped services: App\Contracts\Services\XxxInterface → App\Services\Domain\XxxService
        '{{domain}}\{{base}}',

        // Repositories: App\Contracts\Repositories\XxxInterface → App\Repositories\XxxRepository
        '{{domain}}\{{base}}Repository',

        // Actions: App\Contracts\Actions\XxxInterface → App\Actions\XxxAction
        '{{domain}}\{{base}}Action',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Namespaces
    |--------------------------------------------------------------------------
    |
    | Interface namespace prefixes excluded from auto-discovery.
    | Use this to prevent internal or test contracts from being auto-bound.
    |
    */

    'ignored_namespaces' => [
        'App\Contracts\Testing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Bindings
    |--------------------------------------------------------------------------
    |
    | Explicit interface-to-implementation bindings registered regardless
    | of auto-discovery. Useful for non-conventional mappings.
    |
    */

    'default' => [
        // 'App\Contracts\XxxInterface' => 'App\Services\CustomXxx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contextual Bindings
    |--------------------------------------------------------------------------
    |
    | Bindings scoped to a specific consumer class using Laravel's
    | When-Needs-Give syntax.
    |
    */

    'contextual' => [
        // [
        //     'when'  => 'App\Http\Controllers\PhotoController',
        //     'needs' => 'App\Contracts\FilesystemInterface',
        //     'give'  => 'App\Services\LocalFilesystem',
        // ],
    ],
];
