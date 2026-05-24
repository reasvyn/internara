<?php

declare(strict_types=1);

return [
    'title' => 'Announcements',
    'subtitle' => 'Create and send announcements to users',
    'create' => 'New Announcement',
    'send' => 'Send Announcement',
    'sent' => 'Announcement sent successfully.',
    'published' => 'Announcement published.',
    'deleted' => 'Announcement deleted.',
    'empty' => 'No announcements yet.',
    'send_to_all' => 'Send to all users',
    'all_users' => 'All users',
    'delivery' => 'Delivery',
    'publish_now' => 'Publish Now',
    'cannot_publish' => 'This announcement cannot be published.',
    'confirm_publish' => 'Are you sure you want to publish this announcement? It will be sent to all recipients.',
    'confirm_delete' => 'Are you sure you want to delete this announcement? This cannot be undone.',
    'scheduled_for' => 'Scheduled for',
    'schedule_hint' => 'Date and time when the announcement should be published automatically.',
    'markdown_hint' => 'Supports Markdown formatting: **bold**, *italic*, `code`, [links](https://), etc.',
    'roles_hint' => 'Leave empty to send to all users within selected roles',
    'status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
    ],
    'fields' => [
        'title' => 'Title',
        'message' => 'Message',
        'type' => 'Type',
        'link' => 'Link (optional)',
        'scheduled_at' => 'Schedule Date & Time',
        'target_roles' => 'Target Roles',
    ],
];
