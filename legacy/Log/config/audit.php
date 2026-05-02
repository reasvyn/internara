<?php

declare(strict_types=1);
use Modules\Log\Concerns\HandlesAuditLog;
use Modules\Log\Concerns\InteractsWithActivityLog;

/*
 * Configuration for Internara Audit & Logging System
 *
 * S1 (Secure): PII masking, immutable audit trails, integrity verification
 * S2 (Sustain): Retention policies, automated cleanup, forensic capabilities
 * S3 (Scalable): Support for 29+ modules with event-driven architecture
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    |
    | How long to keep audit logs before automated purging.
    |
    */

    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | PII Masking
    |--------------------------------------------------------------------------
    |
    | Configure which fields are considered sensitive and should be masked.
    |
    */

    'sensitive_fields' => [
        'email',
        'password',
        'password_confirmation',
        'phone',
        'address',
        'national_identifier',
        'nip',
        'nisn',
        'token',
        'secret',
        'api_key',
        'bank_account',
        'emergency_contact',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Traits
    |--------------------------------------------------------------------------
    |
    | Available audit traits for modules to use.
    |
    */

    'traits' => [
        'activity' => InteractsWithActivityLog::class,
        'audit' => HandlesAuditLog::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Auditing
    |--------------------------------------------------------------------------
    |
    | All 29+ modules that should be audited by default.
    |
    */

    'audited_modules' => [
        'Shared',
        'Core',
        'Exception',
        'Status',
        'UI',
        'Support',
        'Auth',
        'User',
        'Profile',
        'Permission',
        'Setup',
        'Setting',
        'School',
        'Department',
        'Teacher',
        'Mentor',
        'Student',
        'Internship',
        'Schedule',
        'Attendance',
        'Journal',
        'Assignment',
        'Assessment',
        'Report',
        'Notification',
        'Media',
        'Log',
        'Guidance',
        'Admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Events
    |--------------------------------------------------------------------------
    |
    | Critical security events that should trigger immediate alerts.
    |
    */

    'security_events' => [
        'login_failed',
        'permission_denied',
        'unauthorized_access',
        'password_changed',
        'role_changed',
        'setup_reset',
        'data_export',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrity Verification
    |--------------------------------------------------------------------------
    |
    | Configure audit trail integrity checks.
    |
    */

    'integrity' => [
        'enabled' => (bool) env('AUDIT_INTEGRITY_ENABLED', true),
        'check_interval_days' => (int) env('AUDIT_CHECK_INTERVAL', 7),
        'alert_on_issues' => (bool) env('AUDIT_ALERT_ON_ISSUES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for compliance reporting exports.
    |
    */

    'export' => [
        'default_format' => 'csv',
        'max_records' => (int) env('AUDIT_EXPORT_MAX', 50000),
        'path' => storage_path('app/audit/exports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Track audit system health and performance.
    |
    */

    'monitoring' => [
        'track_module_stats' => true,
        'alert_on_high_volume' => (bool) env('AUDIT_ALERT_HIGH_VOLUME', true),
        'high_volume_threshold' => (int) env('AUDIT_HIGH_VOLUME_THRESHOLD', 1000), // per day
    ],
];
