<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $realDepartments = [
            [
                'name' => 'Sistem Informasi, Jaringan, dan Aplikasi',
                'description' => 'Pengembangan aplikasi berbasis web/cloud, administrasi server jaringan enterprise, integrasi sistem cerdas, dan keamanan siber.',
            ],
            [
                'name' => 'Desain Pemodelan dan Informasi Bangunan',
                'description' => 'Pemodelan 3D arsitektur, penyusunan rancangan teknik gedung (BIM), estimasi anggaran biaya (RAB), dan penggambaran konstruksi digital.',
            ],
            [
                'name' => 'Konstruksi Gedung, Sanitasi, dan Perawatan',
                'description' => 'Pelaksanaan dan pengawasan konstruksi beton bertulang, sistem utilitas dan sanitasi gedung bertingkat, serta inspeksi perawatan fasilitas bangunan.',
            ],
            [
                'name' => 'Teknik Tenaga Listrik',
                'description' => 'Instalasi tenaga listrik tegangan menengah dan rendah, otomatisasi industri berbasis PLC dan SCADA, serta pemeliharaan trafo dan panel distribusi daya.',
            ],
            [
                'name' => 'Teknik Elektronika Daya dan Komunikasi',
                'description' => 'Perekayasaan sistem kendali elektronika industri, inverter daya, instrumentasi otomatis, serta sistem transmisi komunikasi nirkabel dan fiber optik.',
            ],
            [
                'name' => 'Teknik Fabrikasi Logam dan Manufaktur',
                'description' => 'Proses pemesinan konvensional dan CNC, perancangan cetakan logam (mould & die), perakitan konstruksi mekanikal presisi, dan fabrikasi lembaran logam.',
            ],
            [
                'name' => 'Teknik Pengecoran Logam',
                'description' => 'Teknologi metalurgi pengecoran fero dan non-fero, pembuatan pola dan cetakan pasir/resin, peleburan tanur induksi (furnace), serta uji metalografi material cetak.',
            ],
            [
                'name' => 'Teknik dan Manajemen Perawatan Otomotif',
                'description' => 'Diagnosis sistem manajemen mesin modern (EFI/CRDi), pemeliharaan sasis dan pemindah tenaga otomotif, manajemen bengkel resmi, dan sistem kelistrikan kendaraan listrik/hibrida.',
            ],
        ];

        $realNames = array_column($realDepartments, 'name');

        // Identify and clean placeholder departments
        $placeholderDepartmentIds = DB::table('departments')
            ->whereNotIn('name', $realNames)
            ->pluck('id')
            ->all();

        if (! empty($placeholderDepartmentIds)) {
            // Nullify or delete relations pointing to placeholder departments
            DB::table('account_applications')
                ->whereIn('department_id', $placeholderDepartmentIds)
                ->delete();

            DB::table('profiles')
                ->whereIn('department_id', $placeholderDepartmentIds)
                ->update(['department_id' => null]);

            DB::table('departments')
                ->whereIn('id', $placeholderDepartmentIds)
                ->delete();
        }

        // Ensure all 8 real departments exist
        $now = now();
        foreach ($realDepartments as $dept) {
            $exists = DB::table('departments')->where('name', $dept['name'])->exists();
            if (! $exists) {
                DB::table('departments')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data cleanup
    }
};
