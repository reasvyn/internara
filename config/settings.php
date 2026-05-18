<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Valid Setting Types
    |--------------------------------------------------------------------------
    */

    'valid_types' => ['string', 'integer', 'float', 'boolean', 'json', 'encrypted', 'null'],

    /*
    |--------------------------------------------------------------------------
    | Brand Color Defaults
    |--------------------------------------------------------------------------
    */

    'colors' => [
        'defaults' => [
            'primary' => '#059669',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#ffffff',
            'content' => '#1a1a1a',
        ],
        'presets' => [
            'sky' => [
                'label' => 'Sky',
                'colors' => [
                    'primary' => '#0ea5e9',
                    'secondary' => '#64748b',
                    'accent' => '#f59e0b',
                    'base' => '#ffffff',
                    'content' => '#1a1a1a',
                ],
            ],
            'emerald' => [
                'label' => 'Emerald',
                'colors' => [
                    'primary' => '#059669',
                    'secondary' => '#6b7280',
                    'accent' => '#f97316',
                    'base' => '#ffffff',
                    'content' => '#1a1a1a',
                ],
            ],
            'violet' => [
                'label' => 'Violet',
                'colors' => [
                    'primary' => '#7c3aed',
                    'secondary' => '#71717a',
                    'accent' => '#ec4899',
                    'base' => '#ffffff',
                    'content' => '#1a1a1a',
                ],
            ],
            'rose' => [
                'label' => 'Rose',
                'colors' => [
                    'primary' => '#e11d48',
                    'secondary' => '#78716c',
                    'accent' => '#f59e0b',
                    'base' => '#ffffff',
                    'content' => '#1a1a1a',
                ],
            ],
            'ocean' => [
                'label' => 'Ocean',
                'colors' => [
                    'primary' => '#0891b2',
                    'secondary' => '#64748b',
                    'accent' => '#7c3aed',
                    'base' => '#ffffff',
                    'content' => '#1a1a1a',
                ],
            ],
            'slate' => [
                'label' => 'Slate',
                'colors' => [
                    'primary' => '#475569',
                    'secondary' => '#57534e',
                    'accent' => '#d97706',
                    'base' => '#ffffff',
                    'content' => '#1a1a1a',
                ],
            ],
        ],
    ],

];
