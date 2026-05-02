<?php

declare(strict_types=1);

return [
    'title' => 'System Settings',
    'subtitle' => 'Configure core application identity and global preferences.',

    'groups' => [
        'general' => 'General Configuration',
        'identity' => 'Visual Identity',
        'color_scheme' => 'Color Scheme',
        'mail' => 'Mail Services',
        'system' => 'System Information',
    ],

    'fields' => [
        'app_name' => 'Application Name',
        'brand_name' => 'Brand Name (Institution)',
        'site_title' => 'Site Title (Browser Tab)',
        'app_version' => 'Application Version',
        'brand_logo' => 'Brand Logo',
        'site_favicon' => 'Site Favicon',
        'default_locale' => 'Default Language',
        'active_academic_year' => 'Active Academic Year',

        'mail_from_address' => 'Mail From Address',
        'mail_from_name' => 'Mail From Name',
        'mail_host' => 'SMTP Host',
        'mail_port' => 'SMTP Port',
        'mail_encryption' => 'SMTP Encryption',
        'mail_username' => 'SMTP Username',
        'mail_password' => 'SMTP Password',

        'primary_color' => 'Primary Color',
        'secondary_color' => 'Secondary Color',
        'accent_color' => 'Accent Color',
    ],

    'hints' => [
        'brand_logo' => 'Recommended: Square PNG, max 1MB. Used for sidebar and reports.',
        'site_favicon' => 'Recommended: Square PNG or ICO, 32x32px. Used for browser tabs.',
    ],

    'buttons' => [
        'test_mail' => 'Test SMTP Connection',
        'save' => 'Save Changes',
    ],

    'messages' => [
        'saved' => 'System settings have been updated successfully.',
        'test_email_sent' => 'Test email sent successfully. Please check your inbox.',
        'test_email_failed' => 'Failed to send test email.',
    ],
];
