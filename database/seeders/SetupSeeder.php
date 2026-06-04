<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as RoleModel;

class SetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->call(AcademicYearSeeder::class);
    }

    protected function seedRoles(): void
    {
        $userRoles = Role::userRoles();

        foreach ($userRoles as $role) {
            RoleModel::firstOrCreate(['name' => $role->value]);
        }
    }
}
