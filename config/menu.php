<?php

declare(strict_types=1);

return [

    'groups' => [
        // Dashboard
        'dashboard' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'student'],
            'title' => 'sidebar.navigation',
            'items' => [
                ['route' => 'dashboard', 'icon' => 'o-home', 'label' => 'dashboard.title'],
            ],
        ],

        // Institution Foundation
        'foundation' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.foundation',
            'items' => [
                ['route' => 'sysadmin.school', 'icon' => 'o-academic-cap', 'label' => 'school.title'],
                ['route' => 'sysadmin.academic-years', 'icon' => 'o-calendar', 'label' => 'sidebar.academic_years'],
                ['route' => 'sysadmin.departments', 'icon' => 'o-rectangle-group', 'label' => 'department.title'],
            ],
        ],

        // Internship Planning
        'internship' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.internship',
            'items' => [
                ['route' => 'sysadmin.internships', 'icon' => 'o-briefcase', 'label' => 'internship.title'],
                ['route' => 'sysadmin.internships.groups', 'icon' => 'o-user-group', 'label' => 'sidebar.groups'],
                ['route' => 'sysadmin.internships.phases', 'icon' => 'o-list-bullet', 'label' => 'sidebar.phases'],
                ['route' => 'sysadmin.companies', 'icon' => 'o-building-office', 'label' => 'company.title'],
                ['route' => 'sysadmin.partnerships', 'icon' => 'o-hand-raised', 'label' => 'sidebar.partnerships'],
                ['route' => 'sysadmin.internships.placements', 'icon' => 'o-map-pin', 'label' => 'sidebar.placements'],
                ['route' => 'sysadmin.internships.placements.changes', 'icon' => 'o-arrows-right-left', 'label' => 'sidebar.placement_changes'],
                ['route' => 'sysadmin.internships.requirements', 'icon' => 'o-document-text', 'label' => 'sidebar.requirements'],
            ],
        ],

        // Registration
        'registration' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.registration',
            'items' => [
                ['route' => 'sysadmin.applications', 'icon' => 'o-document-plus', 'label' => 'sidebar.applications'],
                ['route' => 'sysadmin.internships.registrations.pending', 'icon' => 'o-clipboard-document-check', 'label' => 'sidebar.registrations'],
                ['route' => 'sysadmin.internships.placements.direct', 'icon' => 'o-arrow-right-circle', 'label' => 'sidebar.direct_placement'],
            ],
        ],

        // People Management
        'people' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.people',
            'items' => [
                ['route' => 'sysadmin.users.index', 'icon' => 'o-users', 'label' => 'user.manager.title', 'roles' => ['super_admin']],
                ['route' => 'sysadmin.users.students', 'icon' => 'o-user-group', 'label' => 'user.student.title'],
                ['route' => 'sysadmin.users.teachers', 'icon' => 'o-academic-cap', 'label' => 'user.teacher.title'],
                ['route' => 'sysadmin.users.supervisors', 'icon' => 'o-eye', 'label' => 'user.supervisor.title'],
                ['route' => 'sysadmin.users.mentors', 'icon' => 'o-user-plus', 'label' => 'user.mentor.title'],
                ['route' => 'sysadmin.users.mentees', 'icon' => 'o-user', 'label' => 'user.mentee.title'],
                ['route' => 'sysadmin.users.admins', 'icon' => 'o-user-circle', 'label' => 'user.admin.title'],
            ],
        ],

        // Operations & Assessment
        'assessment' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.assessment',
            'items' => [
                ['route' => 'sysadmin.assessments.rubrics', 'icon' => 'o-clipboard-document-list', 'label' => 'sidebar.rubrics'],
                ['route' => 'sysadmin.submissions.grading', 'icon' => 'o-check-badge', 'label' => 'sidebar.submissions'],
                ['route' => 'sysadmin.presentations', 'icon' => 'o-presentation-chart-line', 'label' => 'sidebar.presentations'],
                ['route' => 'sysadmin.evaluations', 'icon' => 'o-star', 'label' => 'sidebar.evaluations'],
            ],
        ],

        'operations' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor'],
            'title' => 'sidebar.operations',
            'items' => [
                ['route' => 'notifications', 'icon' => 'o-bell', 'label' => 'sidebar.notifications'],
                ['route' => 'sysadmin.announcements', 'icon' => 'o-megaphone', 'label' => 'sidebar.announcements'],
                ['route' => 'sysadmin.attendance', 'icon' => 'o-clock', 'label' => 'sidebar.attendance'],
                ['route' => 'sysadmin.assignments', 'icon' => 'o-document-duplicate', 'label' => 'sidebar.assignments'],
                ['route' => 'sysadmin.logbook', 'icon' => 'o-book-open', 'label' => 'sidebar.logbook'],
                ['route' => 'sysadmin.incidents', 'icon' => 'o-exclamation-triangle', 'label' => 'sidebar.incidents'],
            ],
        ],

        // Student Portal
        'student_portal' => [
            'roles' => ['student'],
            'title' => 'sidebar.student_portal',
            'items' => [
                ['route' => 'student.logbook', 'icon' => 'o-book-open', 'label' => 'sidebar.logbook'],
                ['route' => 'student.attendance', 'icon' => 'o-clock', 'label' => 'sidebar.attendance'],
                ['route' => 'student.attendance.absence', 'icon' => 'o-exclamation-circle', 'label' => 'sidebar.absence'],
                ['route' => 'student.assignments', 'icon' => 'o-document-duplicate', 'label' => 'sidebar.assignments'],
                ['route' => 'student.supervision', 'icon' => 'o-user-group', 'label' => 'sidebar.supervision'],
                ['route' => 'student.assessments', 'icon' => 'o-clipboard-document-check', 'label' => 'sidebar.assessments'],
                ['route' => 'student.incidents.report', 'icon' => 'o-exclamation-triangle', 'label' => 'sidebar.report_incident'],
                ['route' => 'student.reports', 'icon' => 'o-document-text', 'label' => 'sidebar.my_report'],
                ['route' => 'student.internships.placement-change', 'icon' => 'o-arrows-right-left', 'label' => 'sidebar.request_placement_change'],
                ['route' => 'student.certificates', 'icon' => 'o-document-check', 'label' => 'sidebar.my_certificates'],
                ['route' => 'student.handbooks', 'icon' => 'o-bookmark-square', 'label' => 'sidebar.handbooks'],
                ['route' => 'registration.center', 'icon' => 'o-briefcase', 'label' => 'sidebar.browse_programs'],
                ['route' => 'registration.wizard', 'icon' => 'o-document-plus', 'label' => 'sidebar.register_internship'],
                ['route' => 'registration.documents', 'icon' => 'o-document-arrow-up', 'label' => 'sidebar.my_documents'],
            ],
        ],

        // Teacher Portal
        'teacher_portal' => [
            'roles' => ['teacher'],
            'title' => 'sidebar.teacher_portal',
            'items' => [
                ['route' => 'teacher.submissions.grading', 'icon' => 'o-check-badge', 'label' => 'sidebar.submissions'],
                ['route' => 'teacher.assess-internship', 'icon' => 'o-clipboard-document-list', 'label' => 'sidebar.assess'],
                ['route' => 'supervision.logs', 'icon' => 'o-clipboard-check', 'label' => 'sidebar.guidance_logs'],
            ],
        ],

        // Supervisor Portal
        'supervisor_portal' => [
            'roles' => ['supervisor'],
            'title' => 'sidebar.supervisor_portal',
            'items' => [
                ['route' => 'supervision.logs', 'icon' => 'o-clipboard-check', 'label' => 'sidebar.guidance_logs'],
                ['route' => 'supervision.submissions.grading', 'icon' => 'o-check-badge', 'label' => 'sidebar.submissions'],
                ['route' => 'supervisor.reports.notes', 'icon' => 'o-document-text', 'label' => 'sidebar.report_notes'],
            ],
        ],

        // Reports & Archive
        'reports' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.reports',
            'items' => [
                ['route' => 'sysadmin.reports.index', 'icon' => 'o-document-chart-bar', 'label' => 'sidebar.reports'],
                ['route' => 'sysadmin.reports.review', 'icon' => 'o-eye', 'label' => 'sidebar.final_reports'],
                ['route' => 'sysadmin.certificates', 'icon' => 'o-document-check', 'label' => 'sidebar.certificates'],
                ['route' => 'sysadmin.certificates.templates', 'icon' => 'o-document-duplicate', 'label' => 'sidebar.certificate_templates'],
                ['route' => 'sysadmin.accounts.lifecycle', 'icon' => 'o-arrow-path', 'label' => 'sidebar.account_lifecycle'],
                ['route' => 'sysadmin.gdpr-logs', 'icon' => 'o-shield-exclamation', 'label' => 'sidebar.gdpr_logs'],
            ],
        ],

        // Configuration (rarely changed, at bottom)
        'system' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.system',
            'items' => [
                ['route' => 'sysadmin.settings', 'icon' => 'o-cog-6-tooth', 'label' => 'setting.title', 'roles' => ['super_admin']],
                ['route' => 'sysadmin.handbooks.index', 'icon' => 'o-bookmark-square', 'label' => 'sidebar.handbooks'],
                ['route' => 'sysadmin.schedules.index', 'icon' => 'o-calendar-days', 'label' => 'sidebar.schedules'],
                ['route' => 'sysadmin.recovery-slips', 'icon' => 'o-key', 'label' => 'sidebar.recovery_slips'],
            ],
        ],
    ],

];
