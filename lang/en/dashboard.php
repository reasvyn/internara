<?php

declare(strict_types=1);

return [
    'title' => 'Dashboard',
    'subtitle' => 'Welcome back, :name!',

    'stats' => [
        'total_students' => 'Total Students',
        'instructors' => 'Instructors',
        'departments' => 'Departments',
        'active_programs' => 'Active Programs',
        'supervised_students' => 'Supervised Students',
        'pending_journals' => 'Pending Journals',
        'active_companies' => 'Active Companies',
        'active_interns' => 'Active Interns',
        'pending_evaluations' => 'Pending Evaluations',
        'verified_journals' => 'Verified Journals',
    ],

    'readiness' => [
        'title' => 'System Readiness',
        'subtitle' => 'Quick check on your core configurations',
        'database' => 'Database Connection',
        'mail' => 'Mail Configuration',
        'cache' => 'Cache System',
        'queue' => 'Queue Worker',
        'storage' => 'Storage Link',
    ],

    'profile' => [
        'title' => 'Admin Information',
        'edit' => 'Edit Profile',
    ],

    'student' => [
        'welcome' => 'Welcome back, :name!',
        'company' => 'Assigned Company',
        'position' => 'Position',
        'batch' => 'Batch',
        'journal_verification' => 'Journal Verification',
        'journal_hint' => 'Keep your journals updated for timely verification.',
        'no_registration' => 'Not yet assigned to an internship.',
        'no_registration_hint' => 'Contact your coordinator for placement details.',
        'write_journal' => 'Write Daily Journal',
        'request_absence' => 'Request Absence',
        'my_documents' => 'My Documents',
        'handbooks' => 'Handbooks',
        'timeline' => 'Timeline Activity',
        'timeline_empty' => 'No recent activity',
    ],

    'teacher' => [
        'recent_journals' => 'Recent Student Journals',
        'no_journals' => 'No journals pending review.',
        'guidance_logs' => 'Guidance Logs',
    ],

    'supervisor' => [
        'verification_queue' => 'Internship Verification Queue',
        'no_verifications' => 'No pending verifications.',
    ],

    'quick_actions' => 'Quick Actions',
    'help_title' => 'Need Help?',
    'help_desc' => 'Explore the documentation to master :app features.',
    'read_docs' => 'Read Documentation',
];
