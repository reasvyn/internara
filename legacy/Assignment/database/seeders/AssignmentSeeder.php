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
                'group' => 'report',
                'description' => 'Laporan akhir pelaksanaan Praktik Kerja Lapangan (PKL) dalam format PDF.',
            ],
            [
                'name' => 'Presentasi Kegiatan PKL',
                'slug' => 'presentasi-pkl',
                'group' => 'presentation',
                'description' => 'Materi presentasi hasil pelaksanaan PKL dalam format PPT atau PDF.',
            ],
            [
                'name' => 'Sertifikat Industri',
                'slug' => 'sertifikat-industri',
                'group' => 'certification',
                'description' => 'Bukti sertifikasi atau piagam penghargaan dari mitra industri.',
            ],
            [
                'name' => 'Dokumentasi Teknis',
                'slug' => 'dokumentasi-teknis',
                'group' => 'report',
                'description' => 'Kumpulan dokumentasi teknis atau portofolio pekerjaan selama PKL.',
            ],
        ];

        foreach ($defaults as $type) {
            AssignmentType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
