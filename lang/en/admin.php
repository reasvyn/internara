<?php

declare(strict_types=1);

return [
    'title' => 'Administrator Console',
    'version' => 'v:version',

    'section_account' => 'Account Information',
    'field_email' => 'Email Address',
    'field_email_result' => 'Email',
    'field_name' => 'Full Name',
    'field_username' => 'Username',
    'field_password' => 'Password',
    'field_new_password' => 'New Password',
    'field_confirm_password' => 'Confirm Password',

    'create' => [
        'description' => 'Create a super administrator account',
        'subtitle' => 'Create Super Administrator',
        'guide' => 'The super administrator account has full access to all system features, including managing schools, departments, users, and system configuration. This will be the primary account used to oversee and administer the entire system. Please prepare an email address and password for the new super administrator account.',
        'already_exists' => 'A super administrator already exists.',
        'invalid_email' => 'Invalid email address.',
        'password_min' => 'Password must be at least 8 characters.',
        'success' => 'Super administrator account created successfully.',
        'change_password' => 'Please change the password after first login.',
    ],

    'recover' => [
        'description' => 'Recover super administrator access',
        'subtitle' => 'Recover Super Administrator Access',
        'guide' => 'This command restores access to the super administrator account when the password is lost or the account is locked. If the email you enter already has an account, use the --reset option to reset its password. Otherwise, a new account will be created.',
        'section_reset' => 'Reset Password',
        'section_set_password' => 'Set Password',
        'invalid_email' => 'Invalid email address.',
        'password_min' => 'Password must be at least 8 characters.',
        'password_mismatch' => 'Passwords do not match.',
        'already_exists' => "User with email ':email' already exists. Use --reset to reset password instead.",
        'not_found' => "User with email ':email' not found.",
        'key_required' => 'Recovery key is required. Provide --key or ensure storage/app/private/.recovery-key exists.',
        'key_invalid' => 'Invalid recovery key.',
        'key_detected' => 'Recovery key detected in storage file. Proceeding with recovery.',
        'file_regenerated' => 'Recovery key file re-written to: :path',
        'confirm_prompt' => 'Type the email above to confirm:',
        'confirm_mode_create' => 'CREATE NEW',
        'confirm_mode_reset' => 'RESET PASSWORD',
        'confirm_warning' => 'You are about to :mode for: :email',
        'aborted' => 'Recovery aborted.',
        'success_create' => 'Super administrator account created successfully.',
        'success_reset' => 'Password reset successfully.',
        'change_password' => 'Please change the password after first login.',
    ],

    'recovery_path' => [
        'info' => 'Recovery key file location:',
        'status' => 'File status',
        'exists' => 'File exists',
        'missing' => 'File not found',
    ],

    'recovery_show' => [
        'warning' => 'The recovery key grants super admin access. Only share this with trusted server administrators.',
        'confirm' => 'Are you sure you want to display the recovery key?',
        'aborted' => 'Display cancelled.',
        'no_setup' => 'System does not appear to be installed yet.',
    ],
];
