<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default PHPFlasher configuration
    |--------------------------------------------------------------------------
    */

    'default' => 'flasher',

    'main_script' => '/vendor/flasher/flasher.min.js',

    'styles' => [
        '/vendor/flasher/flasher.min.css',
    ],

    'options' => [
        'theme' => 'emerald',
        'timeout' => 5000,
        'position' => 'bottom-right',
        'darkMode' => true, // Enable dark mode support
    ],

    'plugins' => [
        'flasher' => [
            'scripts' => [
                '/vendor/flasher/flasher.min.js',
                '/vendor/flasher/themes/emerald/emerald.min.js',
            ],
            'styles' => [
                '/vendor/flasher/flasher.min.css',
                '/vendor/flasher/themes/emerald/emerald.min.css',
            ],
            'options' => [
                'theme' => 'emerald',
            ],
        ],
    ],

    'flash_bag' => [
        'success' => ['success'],
        'error' => ['error', 'danger'],
        'warning' => ['warning', 'alarm'],
        'info' => ['info', 'notice', 'alert'],
    ],

    'filter' => [],
];
