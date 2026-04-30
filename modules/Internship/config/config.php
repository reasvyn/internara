<?php

declare(strict_types=1);

return [
    'name' => 'Internship',

    /*
    |--------------------------------------------------------------------------
    | Validation Standards
    |--------------------------------------------------------------------------
    |
    | Define the constraints for internship programs.
    |
    */
    'validation' => [
        'title' => [
            'min_length' => 5,
            'max_length' => 255,
        ],
        'semesters' => ['Ganjil', 'Genap', 'Tahunan'],
    ],
];
