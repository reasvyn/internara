<?php

declare(strict_types=1);

return [
    'name' => 'Department',

    /*
    |--------------------------------------------------------------------------
    | Validation Standards
    |--------------------------------------------------------------------------
    |
    | Define the constraints for academic department data.
    | These standards are enforced strictly in production.
    |
    */
    'validation' => [
        'name' => [
            'min_length' => 2,
            'max_length' => 255,
        ],
        'description' => [
            'max_length' => 1000,
        ],
    ],
];
