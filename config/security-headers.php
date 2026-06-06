<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Security Headers
    |--------------------------------------------------------------------------
    |
    | These headers are applied to every HTTP response by the SecurityHeaders
    | middleware. Override any value via env() or by publishing the config.
    |
    */

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | The CSP policy string. This default is permissive enough for Livewire
    | and maryUI. Override in your .env or config for stricter protection.
    |
    */
    'csp' => env('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self' ws: wss:;"),

    /*
    |--------------------------------------------------------------------------
    | CSP Enabled
    |--------------------------------------------------------------------------
    |
    | Set to false to skip sending the Content-Security-Policy header.
    | Useful during local development when browser extensions conflict.
    |
    */
    'csp_enabled' => (bool) env('CSP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | HSTS tells browsers to only connect via HTTPS for a specified period.
    | Only enable in production when you have a valid SSL certificate.
    |
    */
    'hsts_enabled' => (bool) env('HSTS_ENABLED', false),

    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),

    'hsts_include_subdomains' => (bool) env('HSTS_INCLUDE_SUBDOMAINS', true),

    'hsts_preload' => (bool) env('HSTS_PRELOAD', false),

];
