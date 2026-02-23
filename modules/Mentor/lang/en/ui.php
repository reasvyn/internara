<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Industry Mentor Dashboard',
        'subtitle' => 'Monitor the activity and attendance of interns at your company.',
        'total_interns' => 'Total Interns',
        'assigned_interns' => 'Assigned Interns',
        'table' => [
            'student_name' => 'Student Name',
            'program' => 'Internship Program',
            'status' => 'Status',
        ],
        'actions' => [
            'mentoring' => 'Mentoring',
            'evaluate' => 'Evaluate',
        ],
    ],
    'manager' => [
        'title' => 'Mentoring Management',
        'record_visit' => 'Record Visit',
        'give_feedback' => 'Give Feedback',
        'stats' => [
            'total_visits' => 'Total Visits',
            'total_logs' => 'Total Logs/Feedback',
            'last_visit' => 'Last Visit',
        ],
        'timeline' => [
            'title' => 'Mentoring Timeline',
            'subtitle' => 'Chronological combination of guidance logs and site visits.',
            'findings' => 'Field Findings:',
            'empty' => 'No mentoring activities recorded yet.',
        ],
        'visit_modal' => [
            'title' => 'Record Site Visit',
            'subtitle' => 'Document findings during physical site visits.',
            'date' => 'Visit Date',
            'notes' => 'Finding Notes',
            'notes_placeholder' => 'Describe student condition and progress in the industry...',
            'save' => 'Save Visit',
        ],
        'log_modal' => [
            'title' => 'Provide Guidance Feedback',
            'subtitle' => 'Record consultation sessions or provide guidance feedback.',
            'type' => 'Log Type',
            'types' => [
                'feedback' => 'Routine Feedback',
                'session' => 'Guidance Session',
                'advisory' => 'Problem Consultation',
            ],
            'subject' => 'Subject',
            'subject_placeholder' => 'Example: Week 1 Report Review',
            'content' => 'Feedback Content',
            'content_placeholder' => 'Write guidance details or feedback...',
            'save' => 'Save Log',
        ],
    ],
];
