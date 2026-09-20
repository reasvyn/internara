<?php

declare(strict_types=1);

return [
    'title' => 'Backups',
    'subtitle' => 'Manage system backups',

    'create_button' => 'Create Backup',

    'total' => 'Total Backups',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'latest' => 'Latest Size',

    'type_label' => 'Type',
    'status_label' => 'Status',
    'size_label' => 'Size',
    'created_by_label' => 'Created By',
    'date_label' => 'Date',

    'filter_type' => 'Type',
    'filter_status' => 'Status',

    'type' => [
        'database' => 'Database',
        'storage' => 'Storage',
        'both' => 'Full',
    ],

    'status' => [
        'pending' => 'Pending',
        'running' => 'Running',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'create_success' => 'Backup created successfully.',
    'create_failed' => 'Backup failed',
    'delete_success' => 'Backup deleted successfully.',
    'cannot_delete_active' => 'Cannot delete a backup that is still running.',
    'confirm_delete_title' => 'Delete Backup',
    'confirm_delete_message' => 'Are you sure you want to delete this backup? This action cannot be undone.',

    'disabled' => 'System backups are disabled. Enable them in settings.',
    'starting' => 'Starting :type backup...',
    'completed_info' => 'Backup completed. Size: :size',
    'cleanup_completed' => 'Cleanup completed. :count old backup(s) removed.',

    'notification_failed' => 'System backup failed (:type). Check the backup logs for details.',
];
