<?php

declare(strict_types=1);

namespace Modules\Assignment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assignment\Models\AssignmentType;

class AssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Laporan Kegiatan PKL',
                'slug' => 'laporan-pkl',
                'group' => 'assignment',
                'description' => 'Mandatory internship final report in PDF format.',
            ],
            [
                'name' => 'Presentasi Kegiatan PKL',
                'slug' => 'presentasi-pkl',
                'group' => 'assignment',
                'description' => 'Mandatory internship presentation materials in PPT/PDF format.',
            ],
        ];

        foreach ($defaults as $type) {
            AssignmentType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
