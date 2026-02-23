<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the structure, hierarchy, and visibility of the
    | application's management sidebar. Items are registered into the
    | UI SlotRegistry during the boot phase.
    |
    */

    'menu' => [
        // ==========================================
        // 1. CORE (Dashboard & Batches)
        // ==========================================
        'ui::menu-separator#core' => [
            'title' => 'admin::ui.menu.group_core',
            'order' => 5,
        ],
        'ui::menu-item#admin-dashboard' => [
            'title' => 'admin::ui.menu.dashboard',
            'icon' => 'tabler.layout-dashboard',
            'link' => '/admin',
            'role' => 'admin|super-admin',
            'exact' => true,
            'order' => 10,
        ],
        'ui::menu-item#teacher-dashboard' => [
            'title' => 'admin::ui.menu.dashboard',
            'icon' => 'tabler.layout-dashboard',
            'link' => '/teacher',
            'role' => 'teacher',
            'exact' => true,
            'order' => 11,
        ],
        'ui::menu-item#mentor-dashboard' => [
            'title' => 'admin::ui.menu.dashboard',
            'icon' => 'tabler.layout-dashboard',
            'link' => '/mentor',
            'role' => 'mentor',
            'exact' => true,
            'order' => 12,
        ],
        'ui::menu-item#student-dashboard' => [
            'title' => 'admin::ui.menu.dashboard',
            'icon' => 'tabler.layout-dashboard',
            'link' => '/student',
            'role' => 'student',
            'exact' => true,
            'order' => 13,
        ],
        'ui::menu-item#internships' => [
            'title' => 'admin::ui.menu.internships',
            'icon' => 'tabler.calendar-event',
            'link' => '/internships',
            'permission' => 'internship.view',
            'role' => 'admin|super-admin',
            'exact' => true,
            'order' => 15,
        ],

        // ==========================================
        // 2. OPERATIONS (Daily Workflow)
        // ==========================================
        'ui::menu-separator#operations' => [
            'title' => 'admin::ui.menu.group_operations',
            'role' => 'admin|super-admin|teacher|mentor',
            'order' => 25,
        ],
        'ui::menu-item#registrations' => [
            'title' => 'admin::ui.menu.registrations',
            'icon' => 'tabler.user-plus',
            'link' => '/internships/registrations',
            'permission' => 'registration.view',
            'role' => 'admin|super-admin',
            'order' => 30,
        ],
        'ui::menu-item#placements' => [
            'title' => 'admin::ui.menu.placements',
            'icon' => 'tabler.building-community',
            'link' => '/internships/placements',
            'permission' => 'placement.view',
            'role' => 'admin|super-admin',
            'order' => 35,
        ],
        'ui::menu-item#requirements' => [
            'title' => 'admin::ui.menu.requirements',
            'icon' => 'tabler.clipboard-check',
            'link' => '/internships/requirements',
            'permission' => 'internship.view',
            'role' => 'admin|super-admin|student',
            'order' => 40,
        ],
        'ui::menu-item#assignments' => [
            'title' => 'assignment::ui.menu.assignments',
            'icon' => 'tabler.checklist',
            'link' => '/assignments',
            'permission' => 'journal.view',
            'role' => 'admin|super-admin|student|teacher',
            'order' => 45,
        ],

        // ==========================================
        // 3. RESOURCES (Master Data)
        // ==========================================
        'ui::menu-separator#resources' => [
            'title' => 'admin::ui.menu.group_management',
            'role' => 'admin|super-admin',
            'order' => 50,
        ],
        'ui::menu-item#all-users' => [
            'title' => 'admin::ui.dashboard.user_management',
            'icon' => 'tabler.users-group',
            'link' => '/admin/users',
            'permission' => 'user.view',
            'role' => 'admin|super-admin',
            'order' => 52,
        ],
        'ui::menu-item#students' => [
            'title' => 'admin::ui.menu.students',
            'icon' => 'tabler.users',
            'link' => '/admin/students',
            'permission' => 'student.manage',
            'role' => 'admin|super-admin',
            'order' => 55,
        ],
        'ui::menu-item#teachers' => [
            'title' => 'admin::ui.menu.teachers',
            'icon' => 'tabler.school',
            'link' => '/admin/teachers',
            'permission' => 'teacher.manage',
            'role' => 'admin|super-admin',
            'order' => 56,
        ],
        'ui::menu-item#mentors' => [
            'title' => 'admin::ui.menu.mentors',
            'icon' => 'tabler.briefcase',
            'link' => '/admin/mentors',
            'permission' => 'mentor.manage',
            'role' => 'admin|super-admin',
            'order' => 57,
        ],
        'ui::menu-item#departments' => [
            'title' => 'admin::ui.menu.departments',
            'icon' => 'tabler.category-2',
            'link' => '/departments',
            'permission' => 'department.manage',
            'role' => 'admin|super-admin',
            'order' => 60,
        ],

        // ==========================================
        // 4. INTELLIGENCE (Reports)
        // ==========================================
        'ui::menu-separator#intelligence' => [
            'title' => 'admin::ui.menu.group_intelligence',
            'role' => 'admin|super-admin|teacher',
            'order' => 70,
        ],
        'ui::menu-item#reports' => [
            'title' => 'report::ui.title',
            'icon' => 'tabler.file-analytics',
            'link' => '/admin/reports',
            'permission' => 'report.view',
            'role' => 'admin|super-admin|teacher',
            'order' => 75,
        ],
        'ui::menu-item#readiness' => [
            'title' => 'admin::ui.menu.readiness',
            'icon' => 'tabler.user-check',
            'link' => '/admin/readiness',
            'permission' => 'internship.view',
            'role' => 'admin|super-admin',
            'order' => 80,
        ],

        // ==========================================
        // 5. ADMINISTRATOR (Tools)
        // ==========================================
        'ui::menu-separator#administrator' => [
            'title' => 'admin::ui.menu.group_administrator',
            'role' => 'admin|super-admin',
            'order' => 85,
        ],
        'ui::menu-item#administrators' => [
            'title' => 'admin::ui.menu.administrators',
            'icon' => 'tabler.shield-lock',
            'link' => '/admin/administrators',
            'permission' => 'admin.manage',
            'role' => 'super-admin',
            'order' => 87,
        ],
        'ui::menu-item#job-monitor' => [
            'title' => 'admin::ui.menu.job_monitor',
            'icon' => 'tabler.activity',
            'link' => '/admin/jobs',
            'role' => 'admin|super-admin',
            'order' => 88,
        ],
        'ui::menu-item#activity-log' => [
            'title' => 'log::ui.activity_log',
            'icon' => 'tabler.history',
            'link' => '/admin/activities',
            'permission' => 'core.manage',
            'role' => 'admin|super-admin',
            'order' => 89,
        ],

        // ==========================================
        // 6. SYSTEM (Config)
        // ==========================================
        'ui::menu-separator#system' => [
            'title' => 'admin::ui.menu.group_system',
            'role' => 'admin|super-admin',
            'order' => 95,
        ],
        'ui::menu-item#school-settings' => [
            'title' => 'admin::ui.menu.school_settings',
            'icon' => 'tabler.settings-automation',
            'link' => '/school/settings',
            'permission' => 'school.manage',
            'role' => 'admin|super-admin',
            'order' => 96,
        ],
        'ui::menu-item#system-settings' => [
            'title' => 'setting::ui.title',
            'icon' => 'tabler.settings-2',
            'link' => '/admin/settings',
            'permission' => 'core.manage',
            'role' => 'admin|super-admin',
            'order' => 97,
        ],
    ],
];
