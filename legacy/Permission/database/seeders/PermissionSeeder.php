<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Enums\Permission as PermissionEnum;
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
        foreach (PermissionEnum::grouped() as $module => $permissions) {
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
    protected function getDescription(PermissionEnum $permission): string
    {
        return match ($permission) {
            // Core
            PermissionEnum::CORE_VIEW_DASHBOARD => 'View the main dashboard',
            PermissionEnum::CORE_VIEW_AUDIT => 'View system audit logs',
            PermissionEnum::CORE_EXPORT_DATA => 'Export system data',

            // User
            PermissionEnum::USER_VIEW => 'View user list',
            PermissionEnum::USER_CREATE => 'Create new users',
            PermissionEnum::USER_UPDATE => 'Edit user details',
            PermissionEnum::USER_DELETE => 'Delete users',
            PermissionEnum::USER_MANAGE => 'Full user management',

            // Profile
            PermissionEnum::PROFILE_VIEW => 'View profiles',
            PermissionEnum::PROFILE_UPDATE => 'Update profiles',

            // School
            PermissionEnum::SCHOOL_VIEW => 'View school information',
            PermissionEnum::SCHOOL_CREATE => 'Create school records',
            PermissionEnum::SCHOOL_UPDATE => 'Edit school details',
            PermissionEnum::SCHOOL_DELETE => 'Delete schools',
            PermissionEnum::SCHOOL_MANAGE => 'Full school management',

            // Department
            PermissionEnum::DEPARTMENT_VIEW => 'View departments',
            PermissionEnum::DEPARTMENT_CREATE => 'Create departments',
            PermissionEnum::DEPARTMENT_UPDATE => 'Edit department details',
            PermissionEnum::DEPARTMENT_DELETE => 'Delete departments',
            PermissionEnum::DEPARTMENT_MANAGE => 'Full department management',

            // Internship
            PermissionEnum::INTERNSHIP_VIEW => 'View internship programs',
            PermissionEnum::INTERNSHIP_CREATE => 'Create internship programs',
            PermissionEnum::INTERNSHIP_UPDATE => 'Edit internship details',
            PermissionEnum::INTERNSHIP_DELETE => 'Delete internships',
            PermissionEnum::INTERNSHIP_MANAGE => 'Full internship management',
            PermissionEnum::INTERNSHIP_APPROVE => 'Approve internship applications',

            // Registration
            PermissionEnum::REGISTRATION_VIEW => 'View registrations',
            PermissionEnum::REGISTRATION_CREATE => 'Register for internships',
            PermissionEnum::REGISTRATION_UPDATE => 'Edit registration details',
            PermissionEnum::REGISTRATION_CANCEL => 'Cancel registrations',
            PermissionEnum::REGISTRATION_APPROVE => 'Approve registrations',

            // Placement
            PermissionEnum::PLACEMENT_VIEW => 'View student placements',
            PermissionEnum::PLACEMENT_CREATE => 'Create placements',
            PermissionEnum::PLACEMENT_UPDATE => 'Edit placements',
            PermissionEnum::PLACEMENT_DELETE => 'Delete placements',
            PermissionEnum::PLACEMENT_MANAGE => 'Full placement management',

            // Company
            PermissionEnum::COMPANY_VIEW => 'View company profiles',
            PermissionEnum::COMPANY_CREATE => 'Create company records',
            PermissionEnum::COMPANY_UPDATE => 'Edit company details',
            PermissionEnum::COMPANY_DELETE => 'Delete companies',
            PermissionEnum::COMPANY_MANAGE => 'Full company management',

            // Attendance
            PermissionEnum::ATTENDANCE_VIEW => 'View attendance records',
            PermissionEnum::ATTENDANCE_CREATE => 'Record attendance',
            PermissionEnum::ATTENDANCE_UPDATE => 'Edit attendance records',
            PermissionEnum::ATTENDANCE_MANAGE => 'Full attendance management',
            PermissionEnum::ATTENDANCE_APPROVE => 'Approve attendance corrections',

            // Journal
            PermissionEnum::JOURNAL_VIEW => 'View journal entries',
            PermissionEnum::JOURNAL_CREATE => 'Create journal entries',
            PermissionEnum::JOURNAL_UPDATE => 'Edit journal entries',
            PermissionEnum::JOURNAL_MANAGE => 'Full journal management',
            PermissionEnum::JOURNAL_APPROVE => 'Approve journal entries',

            // Assessment
            PermissionEnum::ASSESSMENT_VIEW => 'View assessments',
            PermissionEnum::ASSESSMENT_CREATE => 'Create assessments',
            PermissionEnum::ASSESSMENT_UPDATE => 'Edit assessments',
            PermissionEnum::ASSESSMENT_MANAGE => 'Full assessment management',
            PermissionEnum::ASSESSMENT_GRADE => 'Grade student assessments',

            // Assignment
            PermissionEnum::ASSIGNMENT_VIEW => 'View assignments',
            PermissionEnum::ASSIGNMENT_CREATE => 'Create assignments',
            PermissionEnum::ASSIGNMENT_UPDATE => 'Edit assignments',
            PermissionEnum::ASSIGNMENT_MANAGE => 'Full assignment management',
            PermissionEnum::ASSIGNMENT_GRADE => 'Grade assignments',

            // Schedule
            PermissionEnum::SCHEDULE_VIEW => 'View schedules',
            PermissionEnum::SCHEDULE_CREATE => 'Create schedules',
            PermissionEnum::SCHEDULE_UPDATE => 'Edit schedules',
            PermissionEnum::SCHEDULE_MANAGE => 'Full schedule management',

            // Guidance
            PermissionEnum::GUIDANCE_VIEW => 'View guidance materials',
            PermissionEnum::GUIDANCE_MANAGE => 'Full guidance management',

            // Report
            PermissionEnum::REPORT_VIEW => 'View reports',
            PermissionEnum::REPORT_GENERATE => 'Generate reports',
            PermissionEnum::REPORT_EXPORT => 'Export reports',

            // Setting
            PermissionEnum::SETTING_VIEW => 'View system settings',
            PermissionEnum::SETTING_MANAGE => 'Full settings management',

            // Media
            PermissionEnum::MEDIA_VIEW => 'View media files',
            PermissionEnum::MEDIA_UPLOAD => 'Upload media files',
            PermissionEnum::MEDIA_DELETE => 'Delete media files',

            // Notification
            PermissionEnum::NOTIFICATION_VIEW => 'View notifications',
            PermissionEnum::NOTIFICATION_SEND => 'Send notifications',
            PermissionEnum::NOTIFICATION_MANAGE => 'Full notification management',

            // Admin
            PermissionEnum::ADMIN_VIEW => 'View admin accounts',
            PermissionEnum::ADMIN_MANAGE => 'Full admin management',

            // Student
            PermissionEnum::STUDENT_VIEW => 'View student records',
            PermissionEnum::STUDENT_MANAGE => 'Full student management',

            // Teacher
            PermissionEnum::TEACHER_VIEW => 'View teacher records',
            PermissionEnum::TEACHER_MANAGE => 'Full teacher management',

            // Mentor
            PermissionEnum::MENTOR_VIEW => 'View mentor records',
            PermissionEnum::MENTOR_MANAGE => 'Full mentor management',
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
