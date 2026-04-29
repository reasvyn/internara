<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\RolePermission;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\AccessManagementService;

/**
 * Seeds all permissions and assigns them to roles.
 *
 * Uses Permission enum for single source of truth (DRY principle).
 * Uses RolePermission enum for role-permission mappings (DRY principle).
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
    }

    /**
     * Seed all permissions from Permission enum.
     */
    protected function seedPermissions(): void
    {
        $accessService = app(AccessManagementService::class);

        // Group permissions by module for description
        foreach (Permission::grouped() as $module => $permissions) {
            foreach ($permissions as $permission) {
                $accessService->createPermission(
                    $permission->value,
                    $this->getDescription($permission),
                    $module,
                );
            }
        }
    }

    /**
     * Seed roles and assign permissions.
     */
    protected function seedRoles(): void
    {
        $accessService = app(AccessManagementService::class);

        $roleMappings = [
            'super-admin' => RolePermission::SUPER_ADMIN,
            'admin' => RolePermission::ADMIN,
            'teacher' => RolePermission::TEACHER,
            'mentor' => RolePermission::MENTOR,
            'student' => RolePermission::STUDENT,
        ];

        foreach ($roleMappings as $roleName => $roleCase) {
            // Create or update role
            $role = $accessService->createRole(
                $roleName,
                $this->getRoleDescription($roleName),
                'Core',
            );

            // Assign permissions from enum
            $accessService->assignPermissionsToRole(
                $roleName,
                $roleCase->permissions(),
            );
        }
    }

    /**
     * Get human-readable description for permission.
     */
    protected function getDescription(Permission $permission): string
    {
        return match ($permission) {
            // Core
            Permission::CORE_VIEW_DASHBOARD => 'View the main dashboard',
            Permission::CORE_VIEW_AUDIT => 'View system audit logs',
            Permission::CORE_EXPORT_DATA => 'Export system data',

            // User
            Permission::USER_VIEW => 'View user list',
            Permission::USER_CREATE => 'Create new users',
            Permission::USER_UPDATE => 'Edit user details',
            Permission::USER_DELETE => 'Delete users',
            Permission::USER_MANAGE => 'Full user management',

            // Profile
            Permission::PROFILE_VIEW => 'View profiles',
            Permission::PROFILE_UPDATE => 'Update profiles',

            // School
            Permission::SCHOOL_VIEW => 'View school information',
            Permission::SCHOOL_CREATE => 'Create school records',
            Permission::SCHOOL_UPDATE => 'Edit school details',
            Permission::SCHOOL_DELETE => 'Delete schools',
            Permission::SCHOOL_MANAGE => 'Full school management',

            // Department
            Permission::DEPARTMENT_VIEW => 'View departments',
            Permission::DEPARTMENT_CREATE => 'Create departments',
            Permission::DEPARTMENT_UPDATE => 'Edit department details',
            Permission::DEPARTMENT_DELETE => 'Delete departments',
            Permission::DEPARTMENT_MANAGE => 'Full department management',

            // Internship
            Permission::INTERNSHIP_VIEW => 'View internship programs',
            Permission::INTERNSHIP_CREATE => 'Create internship programs',
            Permission::INTERNSHIP_UPDATE => 'Edit internship details',
            Permission::INTERNSHIP_DELETE => 'Delete internships',
            Permission::INTERNSHIP_MANAGE => 'Full internship management',
            Permission::INTERNSHIP_APPROVE => 'Approve internship applications',

            // Registration
            Permission::REGISTRATION_VIEW => 'View registrations',
            Permission::REGISTRATION_CREATE => 'Register for internships',
            Permission::REGISTRATION_UPDATE => 'Edit registration details',
            Permission::REGISTRATION_CANCEL => 'Cancel registrations',
            Permission::REGISTRATION_APPROVE => 'Approve registrations',

            // Placement
            Permission::PLACEMENT_VIEW => 'View student placements',
            Permission::PLACEMENT_CREATE => 'Create placements',
            Permission::PLACEMENT_UPDATE => 'Edit placements',
            Permission::PLACEMENT_DELETE => 'Delete placements',
            Permission::PLACEMENT_MANAGE => 'Full placement management',

            // Company
            Permission::COMPANY_VIEW => 'View company profiles',
            Permission::COMPANY_CREATE => 'Create company records',
            Permission::COMPANY_UPDATE => 'Edit company details',
            Permission::COMPANY_DELETE => 'Delete companies',
            Permission::COMPANY_MANAGE => 'Full company management',

            // Attendance
            Permission::ATTENDANCE_VIEW => 'View attendance records',
            Permission::ATTENDANCE_CREATE => 'Record attendance',
            Permission::ATTENDANCE_UPDATE => 'Edit attendance records',
            Permission::ATTENDANCE_MANAGE => 'Full attendance management',
            Permission::ATTENDANCE_APPROVE => 'Approve attendance corrections',

            // Journal
            Permission::JOURNAL_VIEW => 'View journal entries',
            Permission::JOURNAL_CREATE => 'Create journal entries',
            Permission::JOURNAL_UPDATE => 'Edit journal entries',
            Permission::JOURNAL_MANAGE => 'Full journal management',
            Permission::JOURNAL_APPROVE => 'Approve journal entries',

            // Assessment
            Permission::ASSESSMENT_VIEW => 'View assessments',
            Permission::ASSESSMENT_CREATE => 'Create assessments',
            Permission::ASSESSMENT_UPDATE => 'Edit assessments',
            Permission::ASSESSMENT_MANAGE => 'Full assessment management',
            Permission::ASSESSMENT_GRADE => 'Grade student assessments',

            // Assignment
            Permission::ASSIGNMENT_VIEW => 'View assignments',
            Permission::ASSIGNMENT_CREATE => 'Create assignments',
            Permission::ASSIGNMENT_UPDATE => 'Edit assignments',
            Permission::ASSIGNMENT_MANAGE => 'Full assignment management',
            Permission::ASSIGNMENT_GRADE => 'Grade assignments',

            // Schedule
            Permission::SCHEDULE_VIEW => 'View schedules',
            Permission::SCHEDULE_CREATE => 'Create schedules',
            Permission::SCHEDULE_UPDATE => 'Edit schedules',
            Permission::SCHEDULE_MANAGE => 'Full schedule management',

            // Guidance
            Permission::GUIDANCE_VIEW => 'View guidance materials',
            Permission::GUIDANCE_MANAGE => 'Full guidance management',

            // Report
            Permission::REPORT_VIEW => 'View reports',
            Permission::REPORT_GENERATE => 'Generate reports',
            Permission::REPORT_EXPORT => 'Export reports',

            // Setting
            Permission::SETTING_VIEW => 'View system settings',
            Permission::SETTING_MANAGE => 'Full settings management',

            // Media
            Permission::MEDIA_VIEW => 'View media files',
            Permission::MEDIA_UPLOAD => 'Upload media files',
            Permission::MEDIA_DELETE => 'Delete media files',

            // Notification
            Permission::NOTIFICATION_VIEW => 'View notifications',
            Permission::NOTIFICATION_SEND => 'Send notifications',
            Permission::NOTIFICATION_MANAGE => 'Full notification management',
        };
    }

    /**
     * Get role description.
     */
    protected function getRoleDescription(string $roleName): string
    {
        return match ($roleName) {
            'super-admin' => 'Full system ownership',
            'admin' => 'General management',
            'teacher' => 'Student supervisor',
            'mentor' => 'Industry mentor',
            'student' => 'Internship participant',
        };
    }
}
