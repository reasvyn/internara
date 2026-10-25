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
    | The base policy. The SecurityHeaders middleware prepends a per-request
    | nonce to script-src, so scripts never rely on 'unsafe-inline' — a nonce
    | makes 'unsafe-inline' inert anyway, and injected inline scripts from an
    | XSS hole carry no nonce. 'unsafe-eval' remains because Alpine 3 compiles
    | x-data / x-on expressions with new Function(); removing it requires the
    | Alpine CSP build (see spec 1PGM4 FR-SEC-003).
    |
    | style-src keeps 'unsafe-inline' for inline style attributes (progress
    | bars, Alpine x-bind:style), which a nonce cannot cover.
    |
    */
    'csp' => env(
        'CSP_POLICY',
        "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self' ws: wss:;",
    ),

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
    | CSP Nonce
    |--------------------------------------------------------------------------
    |
    | Generates a fresh nonce per request and publishes it through
    | Vite::useCspNonce() before rendering, so @vite, @livewireStyles and
    | @livewireScripts tag their output automatically. Set to false only when
    | CSP_POLICY is supplied externally with its own nonce strategy.
    |
    */
    'csp_nonce_enabled' => (bool) env('CSP_NONCE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | HSTS tells browsers to only connect via HTTPS for a specified period.
    | Enabled by default in production and disabled elsewhere; HSTS_ENABLED
    | overrides the default in either direction. The header is only emitted on
    | secure requests, so plain-HTTP responses never advertise it.
    |
    */
    'hsts_enabled' => (bool) env('HSTS_ENABLED', env('APP_ENV', 'production') === 'production'),

    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),

    'hsts_include_subdomains' => (bool) env('HSTS_INCLUDE_SUBDOMAINS', true),

    'hsts_preload' => (bool) env('HSTS_PRELOAD', false),
];
