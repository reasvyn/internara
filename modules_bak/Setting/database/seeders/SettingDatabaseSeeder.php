<?php

declare(strict_types=1);

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;

class SettingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([AppSettingSeeder::class]);
    }
}
