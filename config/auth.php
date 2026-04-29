<?php

declare(strict_types=1);
use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /**
     * Super Admin Protection Settings
     * Additional safeguards for Super Admin accounts
     */
    'super_admin' => [
        // Require 2 Super Admin approvals for changes
        'require_dual_approval' => env('SUPER_ADMIN_DUAL_APPROVAL', true),

        // IP whitelist for Super Admin login (comma-separated, supports CIDR)
        // Example: "192.168.1.0/24,10.0.0.5"
        'ip_whitelist' => env('SUPER_ADMIN_IP_WHITELIST', ''),

        // Force session isolation (only one active session per Super Admin)
        'enforce_session_isolation' => env('SUPER_ADMIN_SESSION_ISOLATION', true),

        // Maximum concurrent sessions per Super Admin
        'max_concurrent_sessions' => env('SUPER_ADMIN_MAX_SESSIONS', 1),

        // Require MFA for Super Admin (DISABLED - not required)
        'require_mfa' => env('SUPER_ADMIN_REQUIRE_MFA', false),

        // Password change frequency (days)
        'password_change_frequency' => env('SUPER_ADMIN_PASSWORD_CHANGE_DAYS', 30),
    ],

    /**
     * Account Lockout Protection
     * Prevent brute-force attacks
     */
    'lockout' => [
        // Failed attempts before lockout
        'failed_attempts' => env('LOGIN_FAILED_ATTEMPTS', 5),

        // Lockout duration (minutes)
        'lockout_duration' => env('LOGIN_LOCKOUT_MINUTES', 30),

        // Time window for counting attempts (minutes)
        'attempt_window' => env('LOGIN_ATTEMPT_WINDOW_MINUTES', 30),

        // Auto-unlock after duration expires
        'auto_unlock' => true,
    ],
];
