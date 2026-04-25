<?php

declare(strict_types=1);

return [
    'reset' => [
        'header' => 'RECOVERY: System Initialization Reset',
        'production_warning' => 'System is in PRODUCTION. Resetting setup is highly destructive and requires the --force flag.',
        'confirm_question' => 'This will unlock the setup routes and allow reconfiguration. Continue?',
        'tasks' => [
            'deauthorizing' => 'De-authorizing installation status',
            'regenerating_token' => 'Regenerating sovereign setup token',
        ],
        'success' => 'Success: Setup infrastructure has been unlocked.',
        'link_label' => 'One-time secure access link generated (Expires in :minutes minutes):',
        'audit_log' => 'System setup state has been reset via emergency CLI command.',
    ],
];
