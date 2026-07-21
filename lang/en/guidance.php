<?php

declare(strict_types=1);

return [
    'student' => 'Student',
    'teacher' => 'Teacher',
    'status' => 'Status',
    'date' => 'Date',
    'topic' => 'Topic',
    'feedback' => 'Feedback',
    'method' => 'Method',
    'location' => 'Location',

    'status_draft' => 'Draft',
    'status_submitted' => 'Submitted',
    'status_reviewed' => 'Reviewed',
    'status_acknowledged' => 'Acknowledged',
    'status_verified' => 'Verified',
    'status_completed' => 'Completed',

    'log_created' => 'Supervision log created successfully.',
    'log_deleted' => 'Supervision log deleted successfully.',
    'log_not_submitted' => 'Only submitted logs can be reviewed.',
    'log_reviewed' => 'Supervision log reviewed successfully.',
    'only_draft_can_be_deleted' => 'Only draft logs can be deleted.',
    'no_active_registration' => 'No active registration found.',

    'visit_date' => 'Visit Date',
    'visit_created' => 'Visit scheduled successfully.',
    'visit_verified' => 'Visit verified successfully.',
    'visit_already_verified' => 'Visit already verified.',
    'visit_method' => [
        'site_visit' => 'Site Visit',
        'virtual_meeting' => 'Virtual Meeting',
        'phone_call' => 'Phone Call',
    ],
];
