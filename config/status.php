<?php

return [
    /**
     * Idle Account Detection Settings
     * Controls automatic status transitions based on inactivity
     */
    'idle' => [
        // Days before account becomes INACTIVE (warning sent at warning_days)
        'inactive_days' => env('ACCOUNT_INACTIVE_DAYS', 180),

        // Days before sending idle warning (5 days before inactive threshold)
        'warning_days' => env('ACCOUNT_IDLE_WARNING_DAYS', 175),

        // Days before account becomes ARCHIVED (no login for 1 year)
        'archive_days' => env('ACCOUNT_ARCHIVE_DAYS', 365),
    ],

    /**
     * GDPR Compliance Settings
     * Controls data retention and anonymization policies
     */
    'gdpr' => [
        // Years to retain account data before anonymization
        'retention_years' => env('GDPR_RETENTION_YEARS', 7),

        // Whether to anonymize or permanently delete
        // true = anonymize (change name/email to random hash)
        // false = permanently delete (hard delete from database)
        'anonymize_instead_of_delete' => env('GDPR_ANONYMIZE', true),

        // Email to notify when account is anonymized
        'notification_email' => env('GDPR_NOTIFICATION_EMAIL', null),
    ],

    /**
     * Activation Settings
     * Controls account activation workflow
     */
    'activation' => [
        // Token expiration time in hours
        'token_expires_hours' => env('ACTIVATION_TOKEN_EXPIRES_HOURS', 24),

        // Maximum activation token generation per 24 hours
        'max_tokens_per_day' => env('ACTIVATION_MAX_TOKENS_PER_DAY', 3),

        // Maximum failed token attempts before rate limit
        'max_failed_attempts' => env('ACTIVATION_MAX_FAILED_ATTEMPTS', 5),

        // Time window for rate limiting (minutes)
        'rate_limit_window_minutes' => env('ACTIVATION_RATE_LIMIT_MINUTES', 60),
    ],
];
