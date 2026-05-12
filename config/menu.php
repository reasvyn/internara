<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Sidebar Navigation Groups
    |--------------------------------------------------------------------------
    |
    | Each group has:
    |   - roles: which user roles can see this group
    |   - title: translation key for the group header
    |   - items: array of menu items with route name, icon, and label
    |
    | Groups are ordered by internship lifecycle phase.
    | Configuration menus that rarely change are at the bottom.
    |
    */

    'groups' => [
        // Phase 1: Foundation
        'foundation' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.foundation',
            'items' => [
                ['route' => 'admin.school', 'icon' => 'o-academic-cap', 'label' => 'school.title'],
                ['route' => 'admin.academic-years.index', 'icon' => 'o-calendar', 'label' => 'sidebar.academic_years'],
                ['route' => 'admin.departments', 'icon' => 'o-rectangle-group', 'label' => 'department.title'],
            ],
        ],

        // Phase 2: Internship Planning
        'internship' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.internship',
            'items' => [
                ['route' => 'admin.internships', 'icon' => 'o-briefcase', 'label' => 'internship.title'],
                ['route' => 'admin.companies', 'icon' => 'o-building-office', 'label' => 'company.title'],
                ['route' => 'admin.internships.placements', 'icon' => 'o-map-pin', 'label' => 'sidebar.placements'],
                ['route' => 'admin.internships.requirements', 'icon' => 'o-document-text', 'label' => 'sidebar.requirements'],
            ],
        ],

        // Phase 3: Registration
        'registration' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.registration',
            'items' => [
                ['route' => 'admin.applications', 'icon' => 'o-document-plus', 'label' => 'sidebar.applications'],
                ['route' => 'admin.internships.registrations.pending', 'icon' => 'o-clipboard-document-check', 'label' => 'sidebar.registrations'],
                ['route' => 'admin.internships.placements.direct', 'icon' => 'o-arrow-right-circle', 'label' => 'sidebar.direct_placement'],
            ],
        ],

        // People Management
        'people' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.people',
            'items' => [
                ['route' => 'admin.users.admins', 'icon' => 'o-user-circle', 'label' => 'user.admin.title'],
                ['route' => 'admin.users.teachers', 'icon' => 'o-academic-cap', 'label' => 'user.teacher.title'],
                ['route' => 'admin.users.mentors', 'icon' => 'o-user-plus', 'label' => 'user.mentor.title'],
                ['route' => 'admin.users.students', 'icon' => 'o-user-group', 'label' => 'user.student.title'],
                ['route' => 'admin.users.mentees', 'icon' => 'o-user', 'label' => 'user.mentee.title'],
            ],
        ],

        // Phase 4-5: Operations & Assessment
        'assessment' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.assessment',
            'items' => [
                ['route' => 'admin.assessments.rubrics', 'icon' => 'o-clipboard-document-list', 'label' => 'sidebar.rubrics'],
                ['route' => 'admin.submissions.grading', 'icon' => 'o-check-badge', 'label' => 'sidebar.submissions'],
            ],
        ],

        'operations' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.operations',
            'items' => [
                ['route' => 'admin.attendance', 'icon' => 'o-clock', 'label' => 'sidebar.attendance'],
                ['route' => 'admin.assignments', 'icon' => 'o-document-duplicate', 'label' => 'sidebar.assignments'],
                ['route' => 'admin.logbook', 'icon' => 'o-book-open', 'label' => 'sidebar.logbook'],
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
                ['route' => 'student.internships.register', 'icon' => 'o-document-plus', 'label' => 'sidebar.register_internship'],
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
            ],
        ],

        // Phase 6-7: Reports & Archive
        'reports' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.reports',
            'items' => [
                ['route' => 'admin.reports.index', 'icon' => 'o-document-chart-bar', 'label' => 'sidebar.reports'],
                ['route' => 'admin.accounts.lifecycle', 'icon' => 'o-arrow-path', 'label' => 'sidebar.account_lifecycle'],
                ['route' => 'admin.gdpr-logs', 'icon' => 'o-shield-exclamation', 'label' => 'sidebar.gdpr_logs'],
            ],
        ],

        // Configuration (rarely changed, at bottom)
        'system' => [
            'roles' => ['super_admin', 'admin'],
            'title' => 'sidebar.system',
            'items' => [
                ['route' => 'admin.settings', 'icon' => 'o-cog-6-tooth', 'label' => 'setting.title'],
                ['route' => 'admin.handbooks.index', 'icon' => 'o-bookmark-square', 'label' => 'sidebar.handbooks'],
                ['route' => 'admin.schedules.index', 'icon' => 'o-calendar-days', 'label' => 'sidebar.schedules'],
                ['route' => 'admin.recovery-slips', 'icon' => 'o-key', 'label' => 'sidebar.recovery_slips'],
            ],
        ],
    ],

];
