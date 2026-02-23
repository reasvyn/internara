<?php

declare(strict_types=1);

return [
    'index' => [
        'title' => 'Attendance Log',
        'subtitle' => 'Attendance history during the internship period.',
        'quick_check_in' => 'Present Today',
        'fill_attendance' => 'Fill Attendance',
        'search_student' => 'Search student...',
        'date_from' => 'From Date',
        'date_to' => 'To Date',
        'table' => [
            'date' => 'Date',
            'student' => 'Student',
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
            'status' => 'Status',
            'notes' => 'Notes',
        ],
        'modal' => [
            'title' => 'Record Attendance',
            'date' => 'Attendance Date',
            'status' => 'Attendance Status',
            'notes' => 'Notes / Reason',
            'notes_placeholder' => 'e.g., Family business, illness, or other additional information.',
            'submit' => 'Save Attendance',
        ],
    ],
    'manager' => [
        'title' => 'Daily Attendance',
        'check_in' => 'Check In',
        'check_out' => 'Check Out',
        'label_check_in' => 'Check In Time',
        'label_check_out' => 'Check Out Time',
        'status' => 'Status',
        'not_checked_in' => 'You haven\'t checked in today.',
        'completed' => 'You have completed your attendance for today.',
    ],
];
