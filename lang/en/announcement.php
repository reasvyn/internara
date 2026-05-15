<?php

declare(strict_types=1);

return [
    'title' => 'Announcements',
    'subtitle' => 'Create and send announcements to users',
    'create' => 'New Announcement',
    'send' => 'Send Announcement',
    'sent' => 'Announcement sent successfully.',
    'empty' => 'No announcements yet.',
    'send_to_all' => 'Send to all users',
    'all_users' => 'All users',
    'markdown_hint' => 'Supports Markdown formatting: **bold**, *italic*, `code`, [links](https://), etc.',
    'roles_hint' => 'Leave empty to send to all users within selected roles',
    'fields' => [
        'title' => 'Title',
        'message' => 'Message',
        'type' => 'Type',
        'link' => 'Link (optional)',
        'target_roles' => 'Target Roles',
    ],
];
