<?php

declare(strict_types=1);

return [
    'title' => 'Dashboard',
    'subtitle' => 'Welcome back, :name!',

    'stats' => [
        'total_students' => 'Total Students',
        'instructors' => 'Teachers',
        'supervisors' => 'Supervisors',
        'departments' => 'Departments',
        'companies' => 'Companies',
        'internships' => 'Internships',
        'active_programs' => 'Active Programs',
        'active' => 'Active',
        'supervised_students' => 'Supervised Students',
        'pending_journals' => 'Pending Journals',
        'active_companies' => 'Active Companies',
        'active_interns' => 'Active Interns',
        'pending_evaluations' => 'Pending Evaluations',
        'verified_journals' => 'Verified Journals',
    ],

    'pipeline' => [
        'title' => 'PKL Pipeline',
        'pending' => 'Registrations',
        'active' => 'Active Internships',
        'placement' => 'Placements',
        'logbook' => 'Logbook Entries',
        'certificate' => 'Certificates',
    ],

    'funnel' => [
        'registration' => 'Registration',
        'activity' => 'Activity',
        'completion' => 'Completion',
        'total' => 'Total Registered',
        'active' => 'Active',
        'completed' => 'Completed',
        'attendance' => 'Verified Attendance',
        'logbook' => 'Verified Logbooks',
        'pending' => 'Pending Review',
        'placement_fill' => 'Placements Filled',
        'certificates' => 'Certificates Issued',
        'companies_active' => 'Active Companies',
        'partnerships' => 'Active Partnerships',
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

    'welcome_back' => 'Welcome back, :name!',
    'recent_activity' => 'Recent Activity',
    'no_activity' => 'No recent activity found.',
    'your_profile' => 'Your Profile',
    'edit_profile' => 'Edit Profile',
    'quick_links' => 'Quick Links',
    'notifications' => 'Notifications',

    'system_settings' => 'System Settings',
    'quick_actions' => 'Quick Actions',
    'help_title' => 'Need Help?',
    'help_desc' => 'Explore the documentation to master :app features.',
    'read_docs' => 'Read Documentation',

    'guide' => [
        'title' => 'Dashboard Guide',
        'intro' => 'A quick guide to help you navigate the system:',
        'nav_title' => 'Navigation',
        'nav_desc' => 'Use the sidebar menu to switch between pages. Each role has a tailored menu based on their responsibilities.',
        'notif_title' => 'Notifications',
        'notif_desc' => 'New notifications appear on the bell icon. Click to view details or mark as read.',
        'profile_title' => 'Profile & Settings',
        'profile_desc' => 'Manage your profile, change passwords, and set language preferences in the settings menu.',
        'support_title' => 'Help Center',
        'support_desc' => 'Comprehensive documentation is available to help you make the most of the system.',
    ],
];
