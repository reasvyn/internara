<?php

declare(strict_types=1);

return [
    'welcome' => [
        'title' => 'Welcome to Internara!',
        'broadcast' => 'Your account has been successfully created. Happy interning!',
        'database' => 'Your account has been created. Complete your profile to get started.',
        'mail_subject' => 'Welcome to Internara',
        'mail_greeting' => 'Hello :name!',
        'mail_line1' => 'Your account for the Internara Internship Management System has been created.',
        'mail_username' => 'Username: :username',
        'mail_password' => 'Temporary Password: :password',
        'mail_line2' => 'Please change your password after logging in.',
        'mail_action' => 'Log In Now',
    ],
    'account_status' => [
        'title' => 'Account Status Updated',
        'broadcast' => 'Your account status is now :status',
        'database' => 'Your account status is now :status. Reason: :reason',
        'mail_subject' => 'Account Status Update',
        'mail_line1' => 'Your account status has been updated to: :status',
        'mail_reason' => 'Reason: :reason',
    ],
    'internship_registration' => [
        'title' => 'Internship Status Update',
        'message' => "Update on ':internship': :status",
        'mail_subject' => 'Internship Registration Update',
        'mail_line1' => "Your registration for ':internship' has been updated to: :status",
    ],
    'assignment' => [
        'title' => 'New Assignment Published',
        'broadcast' => "New assignment ':title' for :internship",
        'database' => "Assignment ':title' is now available.",
        'mail_subject' => 'New Assignment: :title',
        'mail_line1' => "A new assignment has been published for your internship program ':internship'.",
        'mail_title' => 'Title: :title',
        'mail_due_date' => 'Due Date: :due_date',
    ],
    'submission_feedback' => [
        'title' => 'Assignment Feedback Received',
        'broadcast' => "Your submission for ':title' has been marked as :status",
        'database' => "Update on ':title': :status",
        'mail_subject' => 'Feedback on Assignment: :title',
        'mail_line1' => "Your submission for ':title' has been reviewed and marked as: :status",
        'mail_feedback' => 'Feedback: :feedback',
    ],
    'report_generated' => [
        'title' => 'Report Ready',
        'message' => 'Your :type report is ready to download.',
        'mail_subject' => 'Your :type Report is Ready',
        'mail_line1' => 'The :type report you requested has been generated successfully and is now ready for download.',
    ],
    'job_failed' => [
        'title' => 'Background Task Failed',
        'broadcast' => "Task ':task' encountered an error.",
        'database' => "Task ':task' failed: :error",
    ],
    'ui' => [
        'title' => 'Notification Center',
        'subtitle' => 'Stay updated with system activities',
        'mark_all_read' => 'Mark All as Read',
        'delete_selected' => 'Delete Selected',
        'are_you_sure' => 'Are you sure?',
        'all_status' => 'All Status',
        'unread' => 'Unread',
        'read' => 'Read',
        'message_col' => 'Message',
        'received_col' => 'Received',
        'success_mark_all' => 'All notifications marked as read.',
    ],
];
