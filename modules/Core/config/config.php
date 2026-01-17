<?php

declare(strict_types=1);

return [
    'name' => 'Core',

    /*
    |--------------------------------------------------------------------------
    | Application Information
    |--------------------------------------------------------------------------
    */
    'info' => [
        'version' => env('APP_VERSION', 'v0.4.0-alpha'),
        'series_code' => env('APP_SERIES_CODE', 'ARC01-INST'),
        'status' => 'In Progress',
    ],

    /*
    |--------------------------------------------------------------------------
    | Author Information
    |--------------------------------------------------------------------------
    */
    'author' => [
        'name' => env('APP_AUTHOR_NAME', 'Reas Vyn'),
        'github' => env('APP_AUTHOR_GITHUB', 'https://github.com/reasnov'),
        'email' => env('APP_AUTHOR_EMAIL', 'reasnov.official@gmail.com'),
    ],
];
