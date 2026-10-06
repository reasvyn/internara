<?php

declare(strict_types=1);

return [
    'default' => env('CONCURRENCY_DRIVER', 'process'),

    'enabled' => (bool) env('CONCURRENCY_GATE_ENABLED', true),
    'lock_ttl_seconds' => (int) env('CONCURRENCY_LOCK_TTL', 30),
    'wait_timeout_seconds' => (int) env('CONCURRENCY_WAIT_TIMEOUT', 15),
    'global_write_lock' => (bool) env('CONCURRENCY_GLOBAL_WRITE_LOCK', false),
    'exempt_routes' => [
        'health',
        'up',
    ],
];
