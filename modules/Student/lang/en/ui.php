<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Student Dashboard',
        'welcome' => 'Welcome back, :name!',
        'my_program' => 'My Internship Program',
        'requirements_incomplete' => [
            'title' => 'Incomplete Requirements',
            'description' => 'Please complete the administrative requirements below to proceed with the internship process.',
        ],
        'waiting_placement' => [
            'title' => 'Waiting for Placement',
            'description' => 'Your administrative requirements are complete.',
            'extra' => 'Please wait for the admin/coordinator to place you at an internship location.',
        ],
        'not_registered' => 'You are not registered in any active internship program.',
        'score' => [
            'final_grade' => 'Final Grade',
            'processing' => 'Your assessment is currently being processed by the supervisor.',
            'download_certificate' => 'Download Certificate',
            'download_transcript' => 'Download Transcript',
        ],
        'quick_links' => 'Quick Links',
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
            'links_sent' => ':count student setup links sent successfully.',
            'code_reissued' => 'Activation code reissued.',
            'codes_reissued' => ':count activation code(s) reissued.',
            'activated' => ':count student accounts activated successfully.',
            'archived' => ':count student accounts archived successfully.',
        ],
        'form' => [
            'password_setup_notice' => 'Student accounts should not be managed through administrator-known passwords. Save the record first, then send a secure access setup link.',
            'password_reset_notice' => 'Use the send-setup-link action from the table to reset student access securely without viewing the password.',
            'archive_hint' => 'For annual archiving, prefer setting students to Inactive so their history remains preserved.',
        ],
    ],
];
