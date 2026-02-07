<?php

declare(strict_types=1);

return [
    'manager' => [
        'title' => 'User Management',
        'subtitle' => 'Manage platform users and roles.',
        'add_user' => 'Add User',
        'edit_user' => 'Edit User',
        'search_placeholder' => 'Search users...',
        'table' => [
            'name' => 'Name',
            'email' => 'Email',
            'username' => 'Username',
            'roles' => 'Roles',
            'status' => 'Status',
        ],
        'form' => [
            'full_name' => 'Full Name',
            'email' => 'Email Address',
            'username' => 'Username',
            'password' => 'Password',
            'password_hint' => 'Leave blank to keep current password',
            'identity_number' => 'Identity Number',
            'department' => 'Department',
            'phone' => 'Phone Number',
            'select_department' => 'Select Department',
            'roles' => 'Assigned Roles',
            'status' => 'Account Status',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'delete' => [
            'title' => 'Confirm Deletion',
            'message' => 'Are you sure you want to delete this user? This action cannot be undone.',
        ],
    ],
];
