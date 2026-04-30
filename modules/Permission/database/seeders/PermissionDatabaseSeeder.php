<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main seeder for Permission module.
 *
 * Calls PermissionSeeder which handles both permissions AND roles
 * in a single transaction for data integrity.
 */
class PermissionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Single seeder handles both permissions and roles
        $this->call(PermissionSeeder::class);
    }
}
