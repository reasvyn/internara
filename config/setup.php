<?php

declare(strict_types=1);
use App\Domain\Core\Enums\AuditCategory;

return [

    /*
    |--------------------------------------------------------------------------
    | System Requirements
    |--------------------------------------------------------------------------
    */

    'requirements' => [
        'php_version' => '8.4.0',
        'extensions' => ['bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip'],
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
        'step_keys' => ['welcome', 'school', 'department', 'account', 'internship', 'finalize', 'complete'],
        'finalize_steps' => ['school', 'department', 'account'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values for Wizard Forms
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'admin_name' => 'Administrator',
        'admin_username' => 'admin',
        'super_admin_default_name' => 'Super Administrator',
        'recovery_admin_name' => 'Recovery Admin',
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
        'finalization_window_minutes' => 5,
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
