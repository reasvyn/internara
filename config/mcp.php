<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Redirect Modules
    |--------------------------------------------------------------------------
    |
    | These modules are the modules that OAuth clients are permitted to use
    | for redirect URIs. Each module should be specified with its scheme
    | and host. Modules not in this list will raise validation errors.
    |
    | Restrict this to known application modules in production.
    |
    */

    'redirect_domains' => explode(',', (string) env('MCP_REDIRECT_DOMAINS', 'localhost')),

    /*
    |--------------------------------------------------------------------------
    | Allowed Custom Schemes
    |--------------------------------------------------------------------------
    |
    | Native desktop OAuth clients like Cursor and VS Code use private-use URI
    | schemes (RFC 8252) for redirect callbacks instead of standard schemes
    | like HTTPS. Here, you may list which custom schemes you will allow.
    |
    */

    'custom_schemes' => [
        // 'claude',
        // 'cursor',
        // 'vscode',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Server
    |--------------------------------------------------------------------------
    |
    | Here you may configure the OAuth authorization server issuer identifier
    | per RFC 8414. This value appears in your protected resource and auth
    | server metadata endpoints. When null, this defaults to `url('/')`.
    |
    */

    'authorization_server' => null,

];
