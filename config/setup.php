<?php

declare(strict_types=1);
use App\Core\Enums\AuditCategory;

return [
    /*
    |--------------------------------------------------------------------------
    | System Requirements
    |--------------------------------------------------------------------------
    */

    'requirements' => [
        'php_version' => '8.4.0',
        'extensions' => [
            'bcmath',
            'ctype',
            'fileinfo',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
            'curl',
            'gd',
            'intl',
            'zip',
        ],
        'recommended_extensions' => ['redis', 'pcntl', 'posix'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */

    'token' => [
        'length' => 64,
        'expiry_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recovery Key
    |--------------------------------------------------------------------------
    */

    'recovery_key' => [
        'length' => 64,
    ],

    /*
    |--------------------------------------------------------------------------
    | Setup Wizard
    |--------------------------------------------------------------------------
    */

    'wizard' => [
        'step_keys' => [
            'welcome',
            'account',
            'school',
            'department',
            'finalize',
            'complete',
        ],
        'finalize_steps' => ['account', 'school', 'department'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values for Wizard Forms
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'admin_name' => 'Administrator',
        'admin_username' => 'superadmin',
        'username_max_length' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Limits
    |--------------------------------------------------------------------------
    */

    'security' => [
        'rate_limit_attempts' => 20,
        'rate_limit_decay_seconds' => 60,
        'finalization_window_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provisioning
    |--------------------------------------------------------------------------
    */

    'provisioning' => [
        'paths' => [
            'env' => '.env',
            'env_example' => '.env.example',
            'storage_link' => 'storage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Display Order
    |--------------------------------------------------------------------------
    */

    'audit_categories' => [
        AuditCategory::REQUIREMENTS,
        AuditCategory::PERMISSIONS,
        AuditCategory::DATABASE,
        AuditCategory::TERMINAL,
        AuditCategory::RECOMMENDATIONS,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Critical Categories
    |--------------------------------------------------------------------------
    */

    'force_allowed_environments' => ['local', 'dev', 'development', 'testing'],
];
