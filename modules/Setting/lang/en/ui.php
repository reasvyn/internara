<?php

declare(strict_types=1);

return [
    'title' => 'System Settings',
    'subtitle' => 'Configure core application identity and global preferences.',
    'groups' => [
        'general' => 'General Configuration',
        'identity' => 'Visual Identity',
        'operational' => 'Operational Rules',
        'mail' => 'Mail Services',
        'system' => 'System Information',
    ],
    'fields' => [
        'app_name' => 'Application Name',
        'brand_name' => 'Brand Name',
        'site_title' => 'Site Title (Browser Tab)',
        'app_version' => 'Application Version',
        'app_series' => 'Series Code',
        'brand_logo' => 'Brand Logo',
        'site_favicon' => 'Site Favicon',
        'default_locale' => 'Default Language',

        'active_academic_year' => 'Active Academic Year',
        'attendance_check_in_start' => 'Check-in Start Time',
        'attendance_late_threshold' => 'Late Threshold Time',

        'mail_from_address' => 'Mail From Address',
        'mail_from_name' => 'Mail From Name',
        'mail_host' => 'SMTP Host',
        'mail_port' => 'SMTP Port',
        'mail_encryption' => 'SMTP Encryption',
        'mail_username' => 'SMTP Username',
        'mail_password' => 'SMTP Password',
    ],
    'hints' => [
        'brand_logo' => 'Recommended: Square PNG, max 1MB.',
        'site_favicon' => 'Recommended: Square PNG or ICO, 32x32px.',
    ],
    'messages' => [
        'saved' => 'System settings have been updated successfully.',
    ],
];
