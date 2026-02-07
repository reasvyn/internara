<?php

declare(strict_types=1);

return [
    'index' => [
        'title' => 'Attendance Log',
        'subtitle' => 'Attendance history during the internship period.',
        'request_absence' => 'Request Leave / Sick',
        'search_student' => 'Search student...',
        'date_from' => 'From Date',
        'date_to' => 'To Date',
        'table' => [
            'date' => 'Date',
            'student' => 'Student',
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
            'status' => 'Status',
        ],
        'modal' => [
            'title' => 'Request Leave / Sick',
            'date' => 'Date',
            'type' => 'Type',
            'reason' => 'Reason',
            'reason_placeholder' => 'Explain your reason...',
            'submit' => 'Submit Request',
            'types' => [
                'leave' => 'Leave',
                'sick' => 'Sick',
                'permit' => 'School Permit',
            ],
        ],
    ],
];
