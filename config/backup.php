<?php

declare(strict_types=1);

return [
    'enabled' => env('BACKUP_ENABLED', false),

    'frequency' => env('BACKUP_FREQUENCY', 'daily'),

    'schedule_time' => env('BACKUP_SCHEDULE_TIME', '02:00'),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    'include_database' => (bool) env('BACKUP_INCLUDE_DATABASE', true),

    'include_storage' => (bool) env('BACKUP_INCLUDE_STORAGE', true),
];
