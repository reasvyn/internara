<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'super-admin' => ['Full system ownership', 'Core'],
            'admin' => ['General management', 'Core'],
            'teacher' => ['Student supervisor', 'Core'],
            'mentor' => ['Industry mentor', 'Core'],
            'student' => ['Internship participant', 'Core'],
        ];

        foreach ($roles as $name => $data) {
            /** @var Role $role */
            $role = Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'description' => $data[0],
                    'module' => $data[1],
                ],
            );

            $this->assignPermissionsToRole($role);
        }
    }

    /**
     * Assign relevant permissions based on role name.
     */
    protected function assignPermissionsToRole(Role $role): void
    {
        if ($role->name === 'super-admin') {
            // SuperAdmin has Gate::before bypass, but we seed all permissions anyway for UI clarity
            $role->syncPermissions(\Modules\Permission\Models\Permission::all());

            return;
        }

        $permissions = match ($role->name) {
            'admin' => [
                'core.view-dashboard',
                'user.view',
                'user.create',
                'user.update',
                'school.view',
                'school.update',
                'department.view',
                'department.create',
                'department.update',
                'internship.view',
            ],
            'teacher' => ['core.view-dashboard', 'internship.view', 'internship.approve'],
            'student' => ['internship.view', 'internship.create'],
            default => [],
        };

        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }
    }
}
