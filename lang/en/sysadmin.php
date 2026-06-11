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

    'recover' => [
        'description' => 'Recover super administrator access',
        'subtitle' => 'Recover Super Administrator Access',
        'guide' => 'This command restores access to the super administrator account when the password is lost or the account is locked.',
        'invalid_email' => 'Invalid email address.',
        'password_min' => 'Password must be at least 8 characters.',
        'password_mismatch' => 'Passwords do not match.',
        'not_found' => "User with email ':email' not found.",
        'key_required' => 'Recovery key is required. Provide --key or ensure storage/app/private/.recovery-key exists.',
        'key_invalid' => 'Invalid recovery key.',
        'key_detected' => 'Recovery key detected in storage file. Proceeding with recovery.',
        'file_regenerated' => 'Recovery key file re-written to: :path',
        'confirm_prompt' => 'Type the email above to confirm:',
        'confirm_warning' => 'You are about to reset the password for: :email',
        'aborted' => 'Recovery aborted.',
        'success_reset' => 'Password reset successfully.',
        'change_password' => 'Please change the password after first login.',
        'recovery_key_title' => 'New Recovery Key',
        'recovery_key_desc' => 'The recovery key has been regenerated. Save this key in a secure place.',
        'file_regenerated_failed' => 'Failed to save recovery key to file.',
        'otp_sent' => 'A one-time code has been sent to the super admin email.',
        'otp_prompt' => 'Enter the one-time code from email',
        'otp_invalid' => 'Invalid one-time code.',
        'otp_expired' => 'The one-time code has expired. Please restart the recovery process.',
        'otp_send_failed' => 'Failed to send the one-time code. Please check mail configuration.',
    ],

    'recovery_path' => [
        'description' => 'Show the file path where the recovery key is stored',
        'info' => 'Recovery key file location:',
        'status' => 'File status',
        'exists' => 'File exists',
        'missing' => 'File not found',
    ],

    'promote' => [
        'user_not_found' => "User not found with identifier: ':identifier'.",
        'invalid_role' => "Invalid role: ':role'. Only admin or super_admin are allowed.",
        'role_absent' => "Role ':role' does not exist in the database.",
        'super_admin_exists' => 'A super admin already exists. Only one super admin account is permitted.',
        'already_has_role' => "User :name already has the ':role' role.",
        'success' => 'Successfully promoted :name (:email) to :role.',
    ],

    'prune_notifications' => [
        'invalid_days' => 'Retention days must be at least 1.',
        'completed' => 'Pruned :count read notification(s) older than :days days.',
    ],

    'publish_announcements' => [
        'none_found' => 'No scheduled announcements due for publication.',
        'published' => 'Published: :title',
        'completed' => 'Published :count scheduled announcement(s).',
    ],

    'pulse_record' => [
        'started' => 'Recording Pulse snapshots...',
        'completed' => 'Snapshots recorded successfully.',
    ],

    'account_slip' => [
        'title' => 'Account Activation',
        'name' => 'Name',
        'username' => 'Username',
        'email' => 'Email',
        'activation_code' => 'Activation Code',
        'instruction' => 'Visit /activate and enter this code to claim your account.',
        'code_expiry' => 'Expires in :days days',
    ],

    'gdpr_logs' => [
        'title' => 'GDPR Deletion Logs',
        'search_placeholder' => 'Search by email...',
        'type_placeholder' => 'All types',
    ],

    'clone_detection' => [
        'title' => 'Account Clone Detection',
        'subtitle' => 'Suspicious duplicate accounts',
    ],

    'activity_title' => 'Activity Log',
    'activity_subtitle' => 'User activity tracking',
    'activity_filter_user' => 'All users',
    'activity_filter_module' => 'All modules',
    'activity_filter_action' => 'All actions',

    'recovery_show' => [
        'description' => 'Display the recovery key from the stored file',
        'warning' => 'The recovery key grants super admin access. Only share this with trusted server administrators.',
        'confirm' => 'Are you sure you want to display the recovery key?',
        'aborted' => 'Display cancelled.',
        'no_setup' => 'System does not appear to be installed yet.',
        'key_label' => 'Recovery Key',
    ],
];
