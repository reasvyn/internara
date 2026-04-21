<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Teacher Dashboard',
        'subtitle' => 'Monitor activity and attendance of your assigned students.',
        'total_students' => 'Total Assigned Students',
        'assigned_students' => 'Assigned Students',
        'table' => [
            'student_name' => 'Student Name',
            'placement' => 'Internship Placement',
            'status' => 'Status',
            'readiness' => 'Graduation Readiness',
        ],
        'readiness' => [
            'ready' => 'Ready to Graduate',
            'not_ready' => 'Not Ready',
        ],
        'actions' => [
            'supervise' => 'Supervise',
            'assess' => 'Assess',
            'transcript' => 'Transcript',
        ],
        'assess_student' => 'Assess Student',
        'evaluation' => 'Academic Evaluation',
        'competency_recap' => 'Competency Recap',
        'competency_recap_subtitle' => 'Skills claimed in daily journals',
        'submit_evaluation' => 'Submit Evaluation',
        'placeholder_notes' => 'Write notes or feedback for the student...',
    ],
    'manager' => [
        'table' => [
            'department' => 'Department',
            'registration_number' => 'Registration Number',
        ],
        'filters' => [
            'all_departments' => 'All Departments',
        ],
        'bulk' => [
            'reissue_codes' => 'Reissue Activation Codes',
            'activate_selected' => 'Activate selected',
            'archive_selected' => 'Archive selected',
        ],
        'messages' => [
            'links_sent' => ':count teacher setup links sent successfully.',
            'code_reissued' => 'Activation code reissued.',
            'codes_reissued' => ':count activation code(s) reissued.',
            'activated' => ':count teacher accounts activated successfully.',
            'archived' => ':count teacher accounts archived successfully.',
        ],
        'form' => [
            'password_setup_notice' => 'Teacher accounts should not be managed through administrator-known passwords. Save the record first, then send a secure access setup link.',
            'password_reset_notice' => 'Use the send-setup-link action from the table to reset teacher access securely without viewing the password.',
            'archive_hint' => 'For annual archiving or assignment rotation, prefer setting teachers to Inactive so their history remains preserved.',
        ],
    ],
];
