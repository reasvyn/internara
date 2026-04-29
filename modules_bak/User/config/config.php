<?php

declare(strict_types=1);

return [
    'name' => 'User',

    /*
    |--------------------------------------------------------------------------
    | User ID Type
    |--------------------------------------------------------------------------
    |
    | This option defines the type of the primary key for the User model.
    | Supported: "uuid", "id"
    | Default: "uuid"
    |
    */
    'type_id' => env('USER_TYPE_ID', 'uuid'),

    /*
    |--------------------------------------------------------------------------
    | Account Security Standards
    |--------------------------------------------------------------------------
    |
    | Define the complexity and integrity requirements for user accounts.
    |
    */
    'security' => [
        'password' => [
            'min_length' => 12, // Enterprise standard
            'require_uppercase' => true,
            'require_numeric' => true,
            'require_special' => true,
            'require_uncompromised' => true,
        ],
        'username' => [
            'pattern' => '/^[a-zA-Z0-9._-]+$/',
            'min_length' => 4,
            'max_length' => 30,
        ],
        'name' => [
            'min_length' => 2,
            'max_length' => 100,
        ],
    ],
];
