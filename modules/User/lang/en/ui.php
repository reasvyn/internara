<?php

declare(strict_types=1);

return [
    'user_management' => 'User Management',
    'student_management' => 'Student Management',
    'teacher_management' => 'Academic Teacher Management',
    'mentor_management' => 'Industry Mentor Management',
    'admin_management' => 'Administrator Management',

    'manager' => [
        'title' => 'User Management',
        'subtitle' => 'Manage platform users and roles.',
        'add_user' => 'Add User',
        'add_student' => 'Add Student',
        'add_teacher' => 'Add Teacher',
        'add_mentor' => 'Add Mentor',
        'add_admin' => 'Add Admin',
        'edit_user' => 'Edit User',
        'edit_student' => 'Edit Student Data',
        'edit_teacher' => 'Edit Teacher Data',
        'edit_mentor' => 'Edit Mentor Data',
        'edit_admin' => 'Edit Admin Data',
        'search_placeholder' => 'Search records...',
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
            'message' => 'Are you sure you want to delete this record? This action cannot be undone.',
        ],
    ],
];
