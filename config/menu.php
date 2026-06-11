<?php

declare(strict_types=1);

return [
    'groups' => [
        // Dashboard
        'dashboard' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'student'],
            'title' => 'common.sidebar.navigation',
            'items' => [['route' => 'dashboard', 'icon' => 'o-home', 'label' => 'dashboard.title']],
        ],

        // Institution Foundation
        'foundation' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.foundation',
            'items' => [
                [
                    'route' => 'sysadmin.school',
                    'icon' => 'o-academic-cap',
                    'label' => 'school.title',
                ],
                [
                    'route' => 'sysadmin.academic-years',
                    'icon' => 'o-calendar',
                    'label' => 'common.sidebar.academic_years',
                ],
                [
                    'route' => 'sysadmin.departments',
                    'icon' => 'o-rectangle-group',
                    'label' => 'department.title',
                ],
            ],
        ],

        // Internship Planning
        'internship' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.internship',
            'items' => [
                [
                    'route' => 'sysadmin.internships',
                    'icon' => 'o-briefcase',
                    'label' => 'internship.title',
                ],
                [
                    'route' => 'sysadmin.internships.groups',
                    'icon' => 'o-user-group',
                    'label' => 'common.sidebar.groups',
                ],
                [
                    'route' => 'sysadmin.internships.phases',
                    'icon' => 'o-list-bullet',
                    'label' => 'common.sidebar.phases',
                ],
                [
                    'route' => 'sysadmin.companies',
                    'icon' => 'o-building-office',
                    'label' => 'company.title',
                ],
                [
                    'route' => 'sysadmin.partnerships',
                    'icon' => 'o-hand-raised',
                    'label' => 'common.sidebar.partnerships',
                ],
                [
                    'route' => 'sysadmin.internships.placements',
                    'icon' => 'o-map-pin',
                    'label' => 'common.sidebar.placements',
                ],
                [
                    'route' => 'sysadmin.internships.placements.changes',
                    'icon' => 'o-arrows-right-left',
                    'label' => 'common.sidebar.placement_changes',
                ],
                [
                    'route' => 'sysadmin.internships.requirements',
                    'icon' => 'o-document-text',
                    'label' => 'common.sidebar.requirements',
                ],
            ],
        ],

        // Registration
        'registration' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.registration',
            'items' => [
                [
                    'route' => 'sysadmin.applications',
                    'icon' => 'o-document-plus',
                    'label' => 'common.sidebar.applications',
                ],
                [
                    'route' => 'sysadmin.internships.registrations.pending',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.registrations',
                ],
                [
                    'route' => 'sysadmin.internships.placements.direct',
                    'icon' => 'o-arrow-right-circle',
                    'label' => 'common.sidebar.direct_placement',
                ],
            ],
        ],

        // People Management
        'people' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.people',
            'items' => [
                [
                    'route' => 'sysadmin.users.index',
                    'icon' => 'o-users',
                    'label' => 'user.manager.title',
                    'roles' => ['super_admin'],
                ],
                [
                    'route' => 'sysadmin.users.students',
                    'icon' => 'o-user-group',
                    'label' => 'user.student.title',
                ],
                [
                    'route' => 'sysadmin.users.teachers',
                    'icon' => 'o-academic-cap',
                    'label' => 'user.teacher.title',
                ],
                [
                    'route' => 'sysadmin.users.supervisors',
                    'icon' => 'o-eye',
                    'label' => 'user.supervisor.title',
                ],
                [
                    'route' => 'sysadmin.users.mentors',
                    'icon' => 'o-user-plus',
                    'label' => 'user.mentor.title',
                ],
                [
                    'route' => 'sysadmin.users.mentees',
                    'icon' => 'o-user',
                    'label' => 'user.mentee.title',
                ],
                [
                    'route' => 'sysadmin.users.admins',
                    'icon' => 'o-user-circle',
                    'label' => 'user.admin.title',
                ],
            ],
        ],

        // Operations & Assessment
        'assessment' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.assessment',
            'items' => [
                [
                    'route' => 'sysadmin.assessments.rubrics',
                    'icon' => 'o-clipboard-document-list',
                    'label' => 'common.sidebar.rubrics',
                ],
                [
                    'route' => 'sysadmin.submissions.grading',
                    'icon' => 'o-check-badge',
                    'label' => 'common.sidebar.submissions',
                ],
                [
                    'route' => 'sysadmin.presentations',
                    'icon' => 'o-presentation-chart-line',
                    'label' => 'common.sidebar.presentations',
                ],
                [
                    'route' => 'sysadmin.evaluations',
                    'icon' => 'o-star',
                    'label' => 'common.sidebar.evaluations',
                ],
            ],
        ],

        'operations' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor'],
            'title' => 'common.sidebar.operations',
            'items' => [
                [
                    'route' => 'notifications',
                    'icon' => 'o-bell',
                    'label' => 'common.sidebar.notifications',
                ],
                [
                    'route' => 'sysadmin.announcements',
                    'icon' => 'o-megaphone',
                    'label' => 'common.sidebar.announcements',
                ],
                [
                    'route' => 'sysadmin.attendance',
                    'icon' => 'o-clock',
                    'label' => 'common.sidebar.attendance',
                ],
                [
                    'route' => 'sysadmin.assignments',
                    'icon' => 'o-document-duplicate',
                    'label' => 'common.sidebar.assignments',
                ],
                [
                    'route' => 'sysadmin.logbook',
                    'icon' => 'o-book-open',
                    'label' => 'common.sidebar.logbook',
                ],
                [
                    'route' => 'sysadmin.incidents',
                    'icon' => 'o-exclamation-triangle',
                    'label' => 'common.sidebar.incidents',
                ],
            ],
        ],

        // Student Portal
        'student_portal' => [
            'roles' => ['student'],
            'title' => 'common.sidebar.student_portal',
            'items' => [
                [
                    'route' => 'student.logbook',
                    'icon' => 'o-book-open',
                    'label' => 'common.sidebar.logbook',
                ],
                [
                    'route' => 'student.attendance',
                    'icon' => 'o-clock',
                    'label' => 'common.sidebar.attendance',
                ],
                [
                    'route' => 'student.attendance.absence',
                    'icon' => 'o-exclamation-circle',
                    'label' => 'common.sidebar.absence',
                ],
                [
                    'route' => 'student.assignments',
                    'icon' => 'o-document-duplicate',
                    'label' => 'common.sidebar.assignments',
                ],
                [
                    'route' => 'student.supervision',
                    'icon' => 'o-user-group',
                    'label' => 'common.sidebar.supervision',
                ],
                [
                    'route' => 'student.assessments',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.assessments',
                ],
                [
                    'route' => 'student.incidents.report',
                    'icon' => 'o-exclamation-triangle',
                    'label' => 'common.sidebar.report_incident',
                ],
                [
                    'route' => 'student.reports',
                    'icon' => 'o-document-text',
                    'label' => 'common.sidebar.my_report',
                ],
                [
                    'route' => 'student.internships.placement-change',
                    'icon' => 'o-arrows-right-left',
                    'label' => 'common.sidebar.request_placement_change',
                ],
                [
                    'route' => 'student.certificates',
                    'icon' => 'o-document-check',
                    'label' => 'common.sidebar.my_certificates',
                ],
                [
                    'route' => 'student.handbooks',
                    'icon' => 'o-bookmark-square',
                    'label' => 'common.sidebar.handbooks',
                ],
                [
                    'route' => 'registration.center',
                    'icon' => 'o-briefcase',
                    'label' => 'common.sidebar.browse_programs',
                ],
                [
                    'route' => 'registration.wizard',
                    'icon' => 'o-document-plus',
                    'label' => 'common.sidebar.register_internship',
                ],
                [
                    'route' => 'registration.documents',
                    'icon' => 'o-document-arrow-up',
                    'label' => 'common.sidebar.my_documents',
                ],
            ],
        ],

        // Teacher Portal
        'teacher_portal' => [
            'roles' => ['teacher'],
            'title' => 'common.sidebar.teacher_portal',
            'items' => [
                [
                    'route' => 'teacher.submissions.grading',
                    'icon' => 'o-check-badge',
                    'label' => 'common.sidebar.submissions',
                ],
                [
                    'route' => 'teacher.assess-internship',
                    'icon' => 'o-clipboard-document-list',
                    'label' => 'common.sidebar.assess',
                ],
                [
                    'route' => 'supervision.logs',
                    'icon' => 'o-clipboard-check',
                    'label' => 'common.sidebar.guidance_logs',
                ],
            ],
        ],

        // Supervisor Portal
        'supervisor_portal' => [
            'roles' => ['supervisor'],
            'title' => 'common.sidebar.supervisor_portal',
            'items' => [
                [
                    'route' => 'supervision.logs',
                    'icon' => 'o-clipboard-check',
                    'label' => 'common.sidebar.guidance_logs',
                ],
                [
                    'route' => 'supervision.submissions.grading',
                    'icon' => 'o-check-badge',
                    'label' => 'common.sidebar.submissions',
                ],
                [
                    'route' => 'supervisor.reports.notes',
                    'icon' => 'o-document-text',
                    'label' => 'common.sidebar.report_notes',
                ],
            ],
        ],

        // Reports & Archive
        'reports' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.reports',
            'items' => [
                [
                    'route' => 'sysadmin.reports.index',
                    'icon' => 'o-document-chart-bar',
                    'label' => 'common.sidebar.reports',
                ],
                [
                    'route' => 'sysadmin.reports.review',
                    'icon' => 'o-eye',
                    'label' => 'common.sidebar.final_reports',
                ],
                [
                    'route' => 'sysadmin.certificates',
                    'icon' => 'o-document-check',
                    'label' => 'common.sidebar.certificates',
                ],
                [
                    'route' => 'sysadmin.certificates.templates',
                    'icon' => 'o-document-duplicate',
                    'label' => 'common.sidebar.certificate_templates',
                ],
                [
                    'route' => 'sysadmin.accounts.lifecycle',
                    'icon' => 'o-arrow-path',
                    'label' => 'common.sidebar.account_lifecycle',
                ],
                [
                    'route' => 'sysadmin.gdpr-logs',
                    'icon' => 'o-shield-exclamation',
                    'label' => 'common.sidebar.gdpr_logs',
                ],
            ],
        ],

        // Configuration (rarely changed, at bottom)
        'system' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.system',
            'items' => [
                [
                    'route' => 'admin.settings',
                    'icon' => 'o-cog-6-tooth',
                    'label' => 'setting.title',
                    'roles' => ['super_admin'],
                ],
                [
                    'route' => 'sysadmin.handbooks.index',
                    'icon' => 'o-bookmark-square',
                    'label' => 'common.sidebar.handbooks',
                ],
                [
                    'route' => 'sysadmin.schedules.index',
                    'icon' => 'o-calendar-days',
                    'label' => 'common.sidebar.schedules',
                ],
                [
                    'route' => 'sysadmin.recovery-slips',
                    'icon' => 'o-key',
                    'label' => 'common.sidebar.recovery_slips',
                ],
            ],
        ],
    ],
];
