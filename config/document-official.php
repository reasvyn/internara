<?php

declare(strict_types=1);

return [
    'letter_prefix' => env('DOCUMENT_LETTER_PREFIX', 'SMK'),
    'retention' => [
        'parent_consent_years' => 5,
        'completion_letter_years' => 10,
    ],
    'batch_queue' => 'documents',
    'batch_threshold' => 10,
    'required_per_phase' => [
        'enrollment' => ['introduction_letter', 'parent_consent', 'acceptance_letter'],
        'daily_ops' => ['supervisor_assignment'],
        'certification' => ['completion_letter'],
    ],
];
