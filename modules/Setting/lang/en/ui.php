<?php

declare(strict_types=1);

return [
    'title' => 'System Settings',
    'subtitle' => 'Configure core application identity and global preferences.',
    'groups' => [
        'general' => 'General Configuration',
        'identity' => 'Visual Identity',
    ],
    'fields' => [
        'brand_name' => 'Brand Name',
        'site_title' => 'Site Title (Browser Tab)',
        'app_version' => 'Application Version',
        'brand_logo' => 'Brand Logo',
        'site_favicon' => 'Site Favicon',
        'default_locale' => 'Default Language',
    ],
    'hints' => [
        'brand_logo' => 'Recommended: Square PNG, max 1MB.',
        'site_favicon' => 'Recommended: Square PNG or ICO, 32x32px.',
    ],
    'messages' => [
        'saved' => 'System settings have been updated successfully.',
    ],
];
