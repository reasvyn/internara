<?php

declare(strict_types=1);

return [
    'install' => [
        'banner' => [
            'engine' => 'SYSTEM INITIALIZATION ENGINE',
            'tool' => 'Enterprise Deployment Tool v:version',
        ],
        'preflight' => [
            'php' => 'PHP Version',
            'env' => 'Environment',
            'db' => 'Database Driver',
            'tz' => 'Timezone',
        ],
        'tasks' => [
            'cleanup' => 'Maintenance: Purging application state and caches',
            'env' => 'Infrastructure: Provisioning environment configuration',
            'validation' => 'Security: Validating system requirements and integrity',
            'key' => 'Security: Generating application cryptographic key',
            'schema' => 'Database: Initializing schema and structure',
            'seeding' => 'Core: Seeding foundational datasets and tokens',
            'storage' => 'Filesystem: Integrating storage persistence layer',
        ],
        'warnings' => [
            'aborted' => 'Installation aborted by user.',
            'production_title' => 'CRITICAL WARNING',
            'production_env' => 'You are running this command in a PRODUCTION environment.',
            'production_loss' => 'This action will result in IRREVERSIBLE DATA LOSS by resetting your database.',
            'production_confirm' => 'Are you absolutely certain you want to proceed with this destructive operation?',
            'env_notice' => 'Environment Notice: System URL is configured to localhost. Port mapping may be required for external access.',
        ],
        'confirmation' => 'This procedure will reset the database and initialize the system. Do you want to proceed?',
        'success' => 'Core system initialization completed successfully.',
        'auth_required' => 'Authorization Required',
        'auth_description' => 'Please use the following authenticated link to finalize the system configuration:',
    ],
];
