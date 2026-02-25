<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Core & System
            'core.manage' => ['Manage core settings', 'Core'],
            'core.view-dashboard' => ['View dashboard', 'Core'],
            'school.manage' => ['Full school management', 'School'],

            // User & Stakeholder Management
            'user.view' => ['View users list', 'User'],
            'user.manage' => ['General user management', 'User'],
            'student.manage' => ['Manage student records', 'Student'],
            'teacher.manage' => ['Manage teacher records', 'Teacher'],
            'mentor.manage' => ['Manage industry mentors', 'Mentor'],
            'admin.manage' => ['Manage administrative accounts', 'Admin'],

            // Department
            'department.view' => ['View departments list', 'Department'],
            'department.create' => ['Create new departments', 'Department'],
            'department.update' => ['Edit department details', 'Department'],
            'department.delete' => ['Delete departments', 'Department'],
            'department.manage' => ['Full department management', 'Department'],

            // Internship & Placement
            'internship.view' => ['View internship programs', 'Internship'],
            'internship.manage' => ['Manage internship programs', 'Internship'],
            'placement.view' => ['View student placements', 'Internship'],
            'placement.manage' => ['Manage student placements', 'Internship'],
            'registration.view' => ['View internship registrations', 'Internship'],
            'registration.manage' => ['Manage internship registrations', 'Internship'],
            'company.view' => ['View industry partners', 'Internship'],
            'company.manage' => ['Manage industry partners', 'Internship'],

            // Academic Operations
            'attendance.view' => ['View attendance logs', 'Attendance'],
            'attendance.manage' => ['Manage attendance records', 'Attendance'],
            'journal.view' => ['View student journals', 'Journal'],
            'journal.manage' => ['Manage and verify journals', 'Journal'],
            'mentoring.manage' => ['Manage mentoring visits and logs', 'Mentor'],
            'assessment.manage' => ['Manage competency assessments', 'Assessment'],

            // Reporting
            'report.view' => ['Access system reports', 'Report'],
        ];

        foreach ($permissions as $name => $data) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'description' => $data[0],
                    'module' => $data[1],
                ],
            );
        }
    }
}
