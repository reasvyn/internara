<?php

declare(strict_types=1);

/**
 * Session Expiration Configuration
 *
 * Defines role-based session timeout durations following NIST SP 800-63B
 * and enterprise security best practices. Shorter timeouts for admin roles
 * reduce the risk of unauthorized dashboard access.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Session Timeout Durations (in minutes)
    |--------------------------------------------------------------------------
    |
    | Role-based session durations. Admin roles have shorter timeouts for
    | enhanced security. Adjust these values based on your organization's
    | security policy.
    |
    */
    'timeouts' => [
        'super_admin' => env('SESSION_TIMEOUT_SUPER_ADMIN', 12 * 60), // 12 hours
        'admin' => env('SESSION_TIMEOUT_ADMIN', 12 * 60), // 12 hours
        'teacher' => env('SESSION_TIMEOUT_TEACHER', 24 * 60), // 24 hours
        'supervisor' => env('SESSION_TIMEOUT_SUPERVISOR', 24 * 60), // 24 hours
        'student' => env('SESSION_TIMEOUT_STUDENT', 24 * 60), // 24 hours
        'default' => env('SESSION_TIMEOUT_DEFAULT', 24 * 60), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Inactivity Warning Duration (in minutes)
    |--------------------------------------------------------------------------
    |
    | Show user a warning when session will expire in this many minutes.
    | This allows users to extend their session before being logged out.
    |
    */
    'warning_minutes' => env('SESSION_WARNING_MINUTES', 2),

    /*
    |--------------------------------------------------------------------------
    | Enable Session Expiration
    |--------------------------------------------------------------------------
    |
    | Toggle session expiration feature globally. Useful for development or
    | if you need to disable it temporarily.
    |
    */
    'enabled' => env('SESSION_EXPIRATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Roles to Apply Expiration
    |--------------------------------------------------------------------------
    |
    | Only these roles will have session expiration enforced.
    | Typically limited to admin roles for security.
    |
    */
    'apply_to_roles' => explode(',', env('SESSION_EXPIRATION_APPLY_TO_ROLES', 'super_admin,admin')),
];
