<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Access Manager Language Lines
    |--------------------------------------------------------------------------
    */

    'access_manager' => [
        'title' => 'Access Management',
        'subtitle' => 'Manage roles and permissions.',
        'manageable' => 'Can manage',
        'no_access' => 'No access',
        'manage' => 'Manage Permissions',

        'table' => [
            'role' => 'Role',
            'description' => 'Description',
            'permissions' => 'Permissions',
            'users' => 'Users',
        ],

        'modal' => [
            'title' => 'Manage Permissions',
            'subtitle' => 'Select permissions to assign to this role.',
        ],

        'cannot_manage' => 'You do not have permission to manage this role.',
        'saved' => 'Permissions updated successfully.',
    ],

    'menu' => [
        'access' => 'Access Manager',
    ],
];
