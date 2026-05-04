<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Define all system permissions.
     */
    private const PERMISSIONS = [
        // User management
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',

        // Student management
        'students.view',
        'students.create',
        'students.edit',
        'students.delete',

        // Teacher management
        'teachers.view',
        'teachers.create',
        'teachers.edit',
        'teachers.delete',

        // Supervisor management
        'supervisors.view',
        'supervisors.create',
        'supervisors.edit',
        'supervisors.delete',

        // Company management
        'companies.view',
        'companies.create',
        'companies.edit',
        'companies.delete',

        // Department management
        'departments.view',
        'departments.create',
        'departments.edit',
        'departments.delete',

        // School management
        'schools.view',
        'schools.edit',

        // Internship management
        'internships.view',
        'internships.create',
        'internships.edit',
        'internships.delete',
        'internships.manage',

        // Assignment management
        'assignments.view',
        'assignments.create',
        'assignments.edit',
        'assignments.delete',
        'assignments.grade',

        // Assessment management
        'assessments.view',
        'assessments.create',
        'assessments.edit',
        'assessments.delete',
        'assessments.grade',

        // Attendance management
        'attendance.view',
        'attendance.clockin',
        'attendance.clockout',
        'attendance.manage',

        // Journal management
        'journals.view',
        'journals.create',
        'journals.edit',
        'journals.verify',

        // Report management
        'reports.view',
        'reports.create',
        'reports.export',

        // Notification management
        'notifications.view',
        'notifications.manage',

        // System settings
        'settings.view',
        'settings.edit',

        // Audit & Logs
        'audit.view',
        'audit.export',

        // Dashboard access
        'dashboard.student',
        'dashboard.teacher',
        'dashboard.mentor',
        'dashboard.admin',
    ];

    /**
     * Define role-permission mappings.
     */
    private function roles(): array
    {
        return [
            \App\Domain\Auth\Enums\Role::SUPER_ADMIN->value => [
                // Super Admin gets all permissions
                'all' => true,
            ],
            \App\Domain\Auth\Enums\Role::ADMIN->value => [
                // Admin gets most permissions except system-level ones
                'users.view',
                'users.create',
                'users.edit',
                'students.view',
                'students.create',
                'students.edit',
                'teachers.view',
                'teachers.create',
                'teachers.edit',
                'supervisors.view',
                'supervisors.create',
                'supervisors.edit',
                'companies.view',
                'companies.create',
                'companies.edit',
                'departments.view',
                'departments.create',
                'departments.edit',
                'schools.view',
                'schools.edit',
                'internships.view',
                'internships.create',
                'internships.edit',
                'internships.manage',
                'assignments.view',
                'assignments.create',
                'assignments.edit',
                'assignments.grade',
                'assessments.view',
                'assessments.create',
                'assessments.edit',
                'assessments.grade',
                'attendance.view',
                'attendance.manage',
                'journals.view',
                'journals.verify',
                'reports.view',
                'reports.create',
                'reports.export',
                'notifications.view',
                'notifications.manage',
                'settings.view',
                'settings.edit',
                'dashboard.admin',
            ],
            \App\Domain\Auth\Enums\Role::TEACHER->value => [
                'students.view',
                'students.create',
                'students.edit',
                'teachers.view',
                'companies.view',
                'companies.create',
                'companies.edit',
                'departments.view',
                'internships.view',
                'internships.manage',
                'assignments.view',
                'assignments.create',
                'assignments.edit',
                'assignments.grade',
                'assessments.view',
                'assessments.create',
                'assessments.edit',
                'assessments.grade',
                'attendance.view',
                'attendance.manage',
                'journals.view',
                'journals.verify',
                'reports.view',
                'reports.create',
                'notifications.view',
                'dashboard.teacher',
            ],
            \App\Domain\Auth\Enums\Role::STUDENT->value => [
                'companies.view',
                'internships.view',
                'assignments.view',
                'assessments.view',
                'attendance.clockin',
                'attendance.clockout',
                'attendance.view',
                'journals.view',
                'journals.create',
                'journals.edit',
                'reports.view',
                'reports.create',
                'notifications.view',
                'dashboard.student',
            ],
            \App\Domain\Auth\Enums\Role::SUPERVISOR->value => [
                'students.view',
                'companies.view',
                'internships.view',
                'assignments.view',
                'assessments.view',
                'attendance.view',
                'attendance.manage',
                'journals.view',
                'journals.verify',
                'reports.view',
                'notifications.view',
                'dashboard.supervisor',
            ],
        ];
    }

    public function run(): void
    {
        // S1: Use transaction for atomic setup
        DB::transaction(function () {
            $this->createPermissions();
            $this->createRoles();
            $this->assignPermissionsToRoles();
        });
    }

    /**
     * Create all permissions.
     */
    protected function createPermissions(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    /**
     * Create all roles.
     */
    protected function createRoles(): void
    {
        foreach (array_keys($this->roles()) as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }

    /**
     * Assign permissions to roles.
     */
    protected function assignPermissionsToRoles(): void
    {
        foreach ($this->roles() as $roleName => $config) {
            $role = Role::where('name', $roleName)->firstOrFail();

            if (isset($config['all']) && $config['all'] === true) {
                // Admin gets all permissions
                $role->syncPermissions(Permission::all());
            } else {
                $permissions = Permission::whereIn('name', $config)->get();
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * Assign admin role to the first user (used during setup).
     */
    public static function assignAdminRoleToUser(User $user): void
    {
        $user->assignRole('admin');
    }
}
