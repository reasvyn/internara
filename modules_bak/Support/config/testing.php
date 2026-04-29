<?php

declare(strict_types=1);

/*
 * Configuration for Internara Modular Testing Infrastructure
 *
 * S1 (Secure): Sensitive data masking, secure process isolation
 * S2 (Sustain): Resource limits, cleanup policies
 * S3 (Scalable): Support for 29+ modules with parallel execution
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Process Execution
    |--------------------------------------------------------------------------
    |
    | Configuration for test process execution with memory leak prevention.
    |
    */

    'process' => [
        // Default timeout in seconds for each test segment
        'timeout' => (int) env('TEST_TIMEOUT', 1200),

        // Memory limit for child processes in bytes (512MB default)
        'memory_limit' => (int) env('TEST_MEMORY_LIMIT', 536870912),

        // Maximum retry attempts for transient failures
        'max_retries' => (int) env('TEST_MAX_RETRIES', 2),

        // Enable parallel execution by default
        'parallel' => (bool) env('TEST_PARALLEL', false),

        // Transient failure patterns that trigger retry
        'transient_patterns' => [
            'timeout',
            'memory exhausted',
            'allowed memory size',
            'connection refused',
            'too many open files',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    |
    | Configuration for persistent test session management.
    |
    */

    'session' => [
        // Base path for session storage
        'path' => storage_path('framework/testing/sessions'),

        // Maximum number of sessions to keep
        'max_sessions' => (int) env('TEST_MAX_SESSIONS', 10),

        // Maximum age of sessions in days before auto-cleanup
        'max_age_days' => (int) env('TEST_MAX_AGE_DAYS', 30),

        // Age in days for prune cleanup
        'prune_age_days' => (int) env('TEST_PRUNE_AGE_DAYS', 7),

        // Whether to validate integrity on session resume
        'validate_integrity' => (bool) env('TEST_VALIDATE_INTEGRITY', true),

        // Maximum output size to store per segment (prevents disk bloat)
        'max_output_length' => (int) env('TEST_MAX_OUTPUT_LENGTH', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery
    |--------------------------------------------------------------------------
    |
    | Configuration for test target discovery.
    |
    */

    'discovery' => [
        // Path to module status file
        'status_file' => base_path('modules_statuses.json'),

        // Default test segments to look for
        'segments' => ['Arch', 'Unit', 'Feature', 'Browser'],

        // Whether to cache discovery results
        'cache_enabled' => (bool) env('TEST_DISCOVERY_CACHE', false),

        // Cache TTL in seconds
        'cache_ttl' => (int) env('TEST_DISCOVERY_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting
    |--------------------------------------------------------------------------
    |
    | Configuration for test result reporting.
    |
    */

    'reporting' => [
        // Default export formats
        'default_exports' => ['junit', 'json'],

        // JUnit XML export path
        'junit_path' => storage_path('framework/testing/reports/junit'),

        // JSON export path
        'json_path' => storage_path('framework/testing/reports/json'),

        // Whether to display coverage summary
        'show_coverage' => (bool) env('TEST_SHOW_COVERAGE', true),

        // Stability thresholds
        'stability_thresholds' => [
            'stable' => 100.0,
            'refining' => 80.0,
            'unstable' => 50.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Policies
    |--------------------------------------------------------------------------
    |
    | Automated cleanup to prevent resource exhaustion (S2 Sustainability).
    |
    */

    'cleanup' => [
        // Whether to auto-cleanup on construction
        'auto_cleanup' => (bool) env('TEST_AUTO_CLEANUP', true),

        // Maximum disk usage in bytes before forced cleanup (100MB default)
        'max_disk_usage' => (int) env('TEST_MAX_DISK_USAGE', 104857600),

        // Whether to sanitize sensitive data in reports (S1)
        'sanitize_output' => (bool) env('TEST_SANITIZE_OUTPUT', true),

        // Patterns to mask in output
        'sensitive_patterns' => [
            '/"password"\s*:\s*"[^"]+"/i',
            '/"token"\s*:\s*"[^"]+"/i',
            '/"secret"\s*:\s*"[^"]+"/i',
            '/"api_key"\s*:\s*"[^"]+"/i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Track resource usage for continuous improvement (S2 Sustainability).
    |
    */

    'monitoring' => [
        // Whether to track peak memory usage
        'track_memory' => (bool) env('TEST_TRACK_MEMORY', true),

        // Whether to log slow segments (> threshold in seconds)
        'log_slow_segments' => (bool) env('TEST_LOG_SLOW_SEGMENTS', true),

        // Threshold in seconds for slow segment detection
        'slow_threshold' => (float) env('TEST_SLOW_THRESHOLD', 60.0),

        // Whether to collect garbage after each segment
        'gc_after_segment' => (bool) env('TEST_GC_AFTER_SEGMENT', true),
    ],
];
