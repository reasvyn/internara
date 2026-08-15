<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Dummy Data Demo Configuration
    |--------------------------------------------------------------------------
    |
    | Deterministic values used by the Tests\Support\DummyData generator and the
    | Database\Seeders\DummySeeder entry point (docs/specs/3UOZP-dummy-data.md §6.3).
    | Kept out of the generator so demo identity is configurable, not hardcoded.
    |
    */

    'password' => 'password',

    'accounts' => [
        'admin_email' => 'admin@example.com',
        'teacher_count' => 4,
        'supervisor_count' => 6,
        'student_count' => 24,
    ],
];
