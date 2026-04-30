<?php

declare(strict_types=1);

namespace Modules\Assignment\Database\Seeders;

use Illuminate\Database\Seeder;

class AssignmentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([AssignmentSeeder::class]);
    }
}
