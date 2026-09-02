<?php

declare(strict_types=1);

return [
    'audit' => [
        'status' => [
            'pass' => 'Pass',
            'fail' => 'Fail',
            'warn' => 'Warn',
        ],
    ],

    'csv' => [
        'created' => 'Created',
        'skipped' => 'Skipped',
    ],

    'discover' => [
        'service_provider_not_registered' => 'The application service provider is not registered.',
        'service_provider_not_registered_hint' => 'Ensure App\\Providers\\AppServiceProvider is registered in config/app.php before running module discovery.',
    ],

    'errors' => [
        'action_failed_hint' => 'An unexpected error occurred while performing this action.',
    ],

    'exceptions' => [
        'unauthorized_hint' => 'You are not authorized to perform this action.',
        'validation_failed_hint' => 'Please review the provided data and try again.',
    ],
];
