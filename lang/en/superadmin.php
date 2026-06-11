<?php

declare(strict_types=1);

return [
    'title' => 'Administrator Console',
    'version' => 'v:version',
    'field_email' => 'Email Address',
    'field_email_result' => 'Email',
    'field_password' => 'Password',
    'field_username' => 'Username',
    'field_confirm_password' => 'Confirm Password',
    'create' => [
        'description' => 'Create a super administrator account',
        'subtitle' => 'Create Super Administrator',
        'guide' => 'The super administrator account has full access to all system features, including managing schools, departments, users, and system configuration. This will be the primary account used to oversee and administer the entire system. Please prepare an email address and password for the new super administrator account.',
        'already_exists' => 'A super administrator already exists.',
        'invalid_email' => 'Invalid email address.',
        'password_min' => 'Password must be at least 8 characters.',
        'password_mismatch' => 'Passwords do not match.',
        'success' => 'Super administrator account created successfully.',
        'recovery_key_title' => 'Recovery Key',
        'recovery_key_desc' => 'Save this key in a secure place. You will need it to recover administrator access if the password is lost.',
        'recovery_file_failed' => 'Failed to save recovery key to file.',
        'change_password' => 'Please change the password after first login.',
    ],
];
