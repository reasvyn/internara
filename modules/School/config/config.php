<?php

declare(strict_types=1);

return [
    'name' => 'School',
    'single_record' => true,

    /*
    |--------------------------------------------------------------------------
    | Validation Standards
    |--------------------------------------------------------------------------
    |
    | These values define the enterprise-grade standards for institutional data.
    | They are enforced strictly in production environments.
    |
    */
    'validation' => [
        'institutional_code' => [
            'pattern' => '/^[A-Z0-9.\/-]+$/i',
            'min_length' => 3,
            'max_length' => 50,
        ],
        'name' => [
            'min_length' => 3,
            'max_length' => 255,
        ],
        'phone' => [
            'pattern' => '/^\+?[0-9\s\-()]+$/',
            'min_length' => 8,
            'max_length' => 20,
        ],
        'fax' => [
            'pattern' => '/^\+?[0-9\s\-()]+$/',
            'min_length' => 8,
            'max_length' => 20,
        ],
    ],
];
