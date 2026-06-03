<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as RoleModel;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRoles();
    }

    protected function createRoles(): void
    {
        foreach (Role::cases() as $role) {
            RoleModel::firstOrCreate(['name' => $role->value]);
        }
    }

    public static function assignAdminRoleToUser(User $user): void
    {
        $user->assignRole('admin');
    }
}
