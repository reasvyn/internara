<?php

declare(strict_types=1);

namespace Modules\UI\Core\Navigation;

use Modules\UI\Facades\SlotRegistry;

/**
 * Class SidebarRegistry
 *
 * Centralized registry for all management sidebar menu items.
 * This class keeps ServiceProviders thin and provides a single SSoT for navigation.
 */
class SidebarRegistry
{
    /**
     * Register all sidebar menu items into the SlotRegistry.
     */
    public static function register(): void
    {
        SlotRegistry::configure([
            'sidebar.menu' => self::items(),
        ]);
    }

    /**
     * Define the sidebar menu items and their hierarchy.
     *
     * @return array<string, array>
     */
    public static function items(): array
    {
        return [
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
                'order' => 10,
            ],
            'ui::menu-item#teacher-dashboard' => [
                'title' => 'admin::ui.menu.dashboard',
                'icon' => 'tabler.layout-dashboard',
                'link' => '/teacher',
                'role' => 'teacher',
                'order' => 11,
            ],
            'ui::menu-item#mentor-dashboard' => [
                'title' => 'admin::ui.menu.dashboard',
                'icon' => 'tabler.layout-dashboard',
                'link' => '/mentor',
                'role' => 'mentor',
                'order' => 12,
            ],
            'ui::menu-item#student-dashboard' => [
                'title' => 'admin::ui.menu.dashboard',
                'icon' => 'tabler.layout-dashboard',
                'link' => '/student',
                'role' => 'student',
                'order' => 13,
            ],
            'ui::menu-item#internships' => [
                'title' => 'admin::ui.menu.internships',
                'icon' => 'tabler.calendar-event',
                'link' => '/internships',
                'role' => 'admin|super-admin',
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
                'role' => 'admin|super-admin',
                'order' => 30,
            ],
            'ui::menu-item#placements' => [
                'title' => 'admin::ui.menu.placements',
                'icon' => 'tabler.building-community',
                'link' => '/internships/placements',
                'role' => 'admin|super-admin',
                'order' => 35,
            ],
            'ui::menu-item#requirements' => [
                'title' => 'admin::ui.menu.requirements',
                'icon' => 'tabler.clipboard-check',
                'link' => '/internships/requirements',
                'role' => 'admin|super-admin|student',
                'order' => 40,
            ],
            'ui::menu-item#assignments' => [
                'title' => 'assignment::ui.menu.assignments',
                'icon' => 'tabler.checklist',
                'link' => '/assignments',
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
            'ui::menu-item#students' => [
                'title' => 'admin::ui.menu.students',
                'icon' => 'tabler.users',
                'link' => '/admin/students',
                'role' => 'admin|super-admin',
                'order' => 55,
            ],
            'ui::menu-item#teachers' => [
                'title' => 'admin::ui.menu.teachers',
                'icon' => 'tabler.school',
                'link' => '/admin/teachers',
                'role' => 'admin|super-admin',
                'order' => 56,
            ],
            'ui::menu-item#mentors' => [
                'title' => 'admin::ui.menu.mentors',
                'icon' => 'tabler.briefcase',
                'link' => '/admin/mentors',
                'role' => 'admin|super-admin',
                'order' => 57,
            ],
            'ui::menu-item#departments' => [
                'title' => 'admin::ui.menu.departments',
                'icon' => 'tabler.category-2',
                'link' => '/departments',
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
                'role' => 'admin|super-admin|teacher',
                'order' => 75,
            ],
            'ui::menu-item#readiness' => [
                'title' => 'admin::ui.menu.readiness',
                'icon' => 'tabler.user-check',
                'link' => '/admin/readiness',
                'role' => 'admin|super-admin',
                'order' => 80,
            ],

            // ==========================================
            // 5. SYSTEM (Config)
            // ==========================================
            'ui::menu-separator#system' => [
                'title' => 'admin::ui.menu.group_system',
                'role' => 'admin|super-admin',
                'order' => 90,
            ],
            'ui::menu-item#school-settings' => [
                'title' => 'admin::ui.menu.school_settings',
                'icon' => 'tabler.settings-automation',
                'link' => '/school/settings',
                'role' => 'admin|super-admin',
                'order' => 91,
            ],
            'ui::menu-item#administrators' => [
                'title' => 'admin::ui.menu.administrators',
                'icon' => 'tabler.shield-lock',
                'link' => '/admin/administrators',
                'role' => 'super-admin',
                'order' => 95,
            ],
            'ui::menu-item#job-monitor' => [
                'title' => 'admin::ui.menu.job_monitor',
                'icon' => 'tabler.activity',
                'link' => '/admin/jobs',
                'role' => 'admin|super-admin',
                'order' => 100,
            ],
            'ui::menu-item#activity-log' => [
                'title' => 'log::ui.activity_log',
                'icon' => 'tabler.history',
                'link' => '/admin/activities',
                'role' => 'admin|super-admin',
                'order' => 105,
            ],
        ];
    }
}
