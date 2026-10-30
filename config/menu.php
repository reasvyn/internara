<?php

declare(strict_types=1);

return [
    'groups' => [
        // ==========================================
        // 0. Dashboard / Navigation (All Roles)
        // ==========================================
        'dashboard' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'student'],
            'title' => 'common.sidebar.navigation',
            'items' => [
                [
                    'route' => 'dashboard',
                    'icon' => 'o-home',
                    'label' => 'dashboard.title',
                ],
            ],
        ],

        // ==========================================
        // STUDENT JOURNEY
        // 1. Persiapan PKL (Placement onboarding & handbooks)
        // 2. Pelaksanaan Magang (Active Internship Activities)
        // 3. Penilaian & Kelulusan (Assessment & Certification)
        // ==========================================
        'student_preparation' => [
            'roles' => ['student'],
            'title' => 'common.sidebar.preparation',
            'items' => [
                [
                    'route' => 'student.preparation',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.pkl_preparation',
                ],
                [
                    'route' => 'student.handbooks',
                    'icon' => 'o-bookmark-square',
                    'label' => 'common.sidebar.handbooks',
                ],
            ],
        ],

        'student_portal' => [
            'roles' => ['student'],
            'title' => 'common.sidebar.active_internship',
            'items' => [
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
                    'route' => 'student.logbook',
                    'icon' => 'o-book-open',
                    'label' => 'common.sidebar.logbook',
                ],
                [
                    'route' => 'student.assignments',
                    'icon' => 'o-document-duplicate',
                    'label' => 'common.sidebar.assignments',
                ],
                [
                    'route' => 'student.supervision-logs',
                    'icon' => 'o-user-group',
                    'label' => 'common.sidebar.supervision',
                ],
            ],
        ],

        'student_assessment' => [
            'roles' => ['student'],
            'title' => 'common.sidebar.assessment_certification',
            'items' => [
                [
                    'route' => 'assessments',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.assessments',
                ],
                [
                    'route' => 'student.certificates',
                    'icon' => 'o-document-check',
                    'label' => 'common.sidebar.my_certificates',
                ],
            ],
        ],

        // ==========================================
        // TEACHER JOURNEY
        // Mentoring, Visits, Student Logbook & Grading Portal
        // ==========================================
        'teacher_portal' => [
            'roles' => ['teacher'],
            'title' => 'common.sidebar.teacher_portal',
            'items' => [
                [
                    'route' => 'teacher.handbooks',
                    'icon' => 'o-bookmark-square',
                    'label' => 'common.sidebar.handbooks',
                ],
                [
                    'route' => 'monitoring-visits.index',
                    'icon' => 'o-map-pin',
                    'label' => 'common.sidebar.visits',
                ],
                [
                    'route' => 'supervision.logs',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.supervision_logs',
                ],
                [
                    'route' => 'sysadmin.logbook',
                    'icon' => 'o-book-open',
                    'label' => 'common.sidebar.logbook',
                ],
                [
                    'route' => 'teacher.submissions.grading',
                    'icon' => 'o-check-badge',
                    'label' => 'common.sidebar.submissions',
                ],
            ],
        ],

        // ==========================================
        // SUPERVISOR (INDUSTRY) JOURNEY
        // Student Daily Logbook Verification, Mentoring & Grading
        // ==========================================
        'supervisor_portal' => [
            'roles' => ['supervisor'],
            'title' => 'common.sidebar.supervisor_portal',
            'items' => [
                [
                    'route' => 'sysadmin.logbook',
                    'icon' => 'o-book-open',
                    'label' => 'common.sidebar.logbook',
                ],
                [
                    'route' => 'supervision.logs',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.supervision_logs',
                ],
                [
                    'route' => 'supervision.submissions.grading',
                    'icon' => 'o-check-badge',
                    'label' => 'common.sidebar.submissions',
                ],
            ],
        ],

        // ==========================================
        // ADMIN JOURNEY
        // 1. Landasan & Master Data (Foundation)
        // 2. Manajemen Pengguna (People)
        // 3. Program Magang & Mitra DUDI (Internship & Partnerships)
        // 4. Pendaftaran & Penempatan (Registration & Placements)
        // 5. Operasional & Monitoring (Operations & Daily Supervision)
        // 6. Penilaian & Evaluasi (Assessment)
        // 7. Laporan & Sertifikasi (Reports & Certificates)
        // 8. Sistem & Konfigurasi (System)
        // ==========================================
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

        'people' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.people',
            'items' => [
                [
                    'route' => 'admin.users.index',
                    'icon' => 'o-users',
                    'label' => 'user.manager.title',
                    'roles' => ['super_admin'],
                ],
                [
                    'route' => 'admin.users.students',
                    'icon' => 'o-user-group',
                    'label' => 'user.student.title',
                ],
                [
                    'route' => 'admin.users.teachers',
                    'icon' => 'o-academic-cap',
                    'label' => 'user.teacher.title',
                ],
                [
                    'route' => 'admin.users.admins',
                    'icon' => 'o-user-circle',
                    'label' => 'user.admin.title',
                ],
            ],
        ],

        'internship' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.internship',
            'items' => [
                [
                    'route' => 'partner.companies',
                    'icon' => 'o-building-office',
                    'label' => 'company.title',
                ],
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
            ],
        ],

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
                    'route' => 'enrollment.internships.registrations.pending',
                    'icon' => 'o-clipboard-document-check',
                    'label' => 'common.sidebar.registrations',
                ],
                [
                    'route' => 'enrollment.internships.placements',
                    'icon' => 'o-map-pin',
                    'label' => 'common.sidebar.placements',
                ],
                [
                    'route' => 'enrollment.internships.placements.direct',
                    'icon' => 'o-arrow-right-circle',
                    'label' => 'common.sidebar.direct_placement',
                ],
                [
                    'route' => 'enrollment.internships.placements.changes',
                    'icon' => 'o-arrows-right-left',
                    'label' => 'common.sidebar.placement_changes',
                ],
            ],
        ],

        'operations' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.operations',
            'items' => [
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
                    'route' => 'sysadmin.logbook',
                    'icon' => 'o-book-open',
                    'label' => 'common.sidebar.logbook',
                ],
                [
                    'route' => 'sysadmin.assignments',
                    'icon' => 'o-document-duplicate',
                    'label' => 'common.sidebar.assignments',
                ],
                [
                    'route' => 'monitoring-visits.index',
                    'icon' => 'o-map-pin',
                    'label' => 'common.sidebar.visits',
                ],
                [
                    'route' => 'notifications',
                    'icon' => 'o-bell',
                    'label' => 'common.sidebar.notifications',
                ],
            ],
        ],

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
            ],
        ],

        'reports' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'common.sidebar.reports',
            'items' => [
                [
                    'route' => 'sysadmin.student-report.index',
                    'icon' => 'o-academic-cap',
                    'label' => 'common.sidebar.student_reports',
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
                    'route' => 'sysadmin.reports.index',
                    'icon' => 'o-document-chart-bar',
                    'label' => 'common.sidebar.reports',
                ],
            ],
        ],

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
                    'route' => 'sysadmin.recovery-slips',
                    'icon' => 'o-key',
                    'label' => 'common.sidebar.recovery_slips',
                ],
                [
                    'route' => 'sysadmin.accounts.lifecycle',
                    'icon' => 'o-arrow-path',
                    'label' => 'common.sidebar.account_lifecycle',
                ],
                [
                    'route' => 'admin.gdpr-logs',
                    'icon' => 'o-shield-exclamation',
                    'label' => 'common.sidebar.gdpr_logs',
                ],
            ],
        ],

        // ==========================================
        // INFORMATION (All Roles)
        // ==========================================
        'information' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'student'],
            'title' => 'common.sidebar.information',
            'items' => [
                [
                    'route' => 'about',
                    'icon' => 'o-information-circle',
                    'label' => 'about.title',
                ],
            ],
        ],
    ],
];
