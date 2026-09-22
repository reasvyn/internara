<?php

declare(strict_types=1);

return [
    'default' => 'shared-hosting',

    'profiles' => [
        'shared-hosting' => [
            'queue' => 'sync',
            'cache' => 'file',
            'session' => 'database',
            'broadcast' => 'log',
            'scheduler' => 'webhook',
            'redis' => false,
        ],
        'vps-docker' => [
            'queue' => 'redis',
            'cache' => 'redis',
            'session' => 'redis',
            'broadcast' => 'log',
            'scheduler' => 'daemon',
            'redis' => true,
        ],
    ],
];
