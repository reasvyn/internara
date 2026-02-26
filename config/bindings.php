<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Binding
    |--------------------------------------------------------------------------
    |
    | If enabled, the package will automatically discover and bind interfaces
    | to their matching implementations based on naming conventions.
    | e.g. UserServiceInterface -> UserService
    |
    */

    'autobind' => true,

    /*
    |--------------------------------------------------------------------------
    | Bind as Singleton
    |--------------------------------------------------------------------------
    |
    | If true, services will be registered as singletons instead of transient bindings.
    | This is generally more memory efficient for stateless services.
    |
    */

    'bind_as_singleton' => true,

    /*
    |--------------------------------------------------------------------------
    | Concrete Class Patterns
    |--------------------------------------------------------------------------
    |
    | Define the patterns used to guess the concrete class from the interface.
    | Available placeholders:
    | - {{root}}: The root namespace (e.g. App or Modules\User)
    | - {{short}}: The short name of the interface without Interface/Contract suffix (e.g. User from UserInterface)
    |
    */

    'patterns' => [
        // Services
        '{{root}}\Services\{{short}}Service',
        '{{root}}\Services\{{short}}',

        // Repositories
        '{{root}}\Repositories\Eloquent{{short}}Repository',
        '{{root}}\Repositories\Eloquent{{short}}',
        '{{root}}\Repositories\Eloquent\{{short}}Repository',
        '{{root}}\Repositories\{{short}}Repository',
        '{{root}}\Repositories\{{short}}',

        // Actions / Direct
        '{{root}}\Actions\{{short}}',
        '{{root}}\{{short}}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Namespaces
    |--------------------------------------------------------------------------
    |
    | List of namespaces prefixes that should be ignored during auto-discovery.
    | Use this to prevent sensitive or internal services from being auto-bound.
    |
    */

    'ignored_namespaces' => [],

    /*
    |--------------------------------------------------------------------------
    | Default Bindings
    |--------------------------------------------------------------------------
    |
    | Standard interface-to-implementation bindings.
    |
    */

    'default' => [
        // 'App\Contracts\SomeInterface' => 'App\Services\SomeService',
        // 'Modules\ModuleName\Contracts\SomeInterface' => 'Modules\ModuleName\Services\SomeService',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contextual Bindings
    |--------------------------------------------------------------------------
    |
    | Define implementation based on the consumer class.
    | Keys: 'when' (Consumer), 'needs' (Interface), 'give' (Implementation).
    |
    */

    'contextual' => [
        // [
        //     'when'  => 'App\Http\Controllers\PhotoController',
        //     'needs' => 'App\Contracts\Filesystem',
        //     'give'  => 'App\Services\LocalFilesystem',
        // ],
    ],
];
