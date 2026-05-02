<?php

declare(strict_types=1);

return [
    'banner' => [
        'engine' => 'MESIN INISIALISASI SISTEM',
        'tool' => 'Alat Penggelaran Enterprise v:version',
    ],
    'preflight' => [
        'php' => 'Versi PHP',
        'env' => 'Lingkungan',
        'db' => 'Driver Database',
        'tz' => 'Zona Waktu',
    ],
    'tasks' => [
        'cleanup' => 'Pemeliharaan: Membersihkan status aplikasi dan cache',
        'env' => 'Infrastruktur: Menyiapkan konfigurasi lingkungan',
        'validation' => 'Keamanan: Memvalidasi persyaratan dan integritas sistem',
        'key' => 'Keamanan: Menghasilkan kunci kriptografi aplikasi',
        'schema' => 'Database: Menginisialisasi skema dan struktur',
        'seeding' => 'Inti: Menanamkan dataset dasar dan token',
        'storage' => 'Filesystem: Mengintegrasikan lapisan persistensi penyimpanan',
    ],
    'warnings' => [
        'aborted' => 'Instalasi dibatalkan oleh pengguna.',
        'production_title' => 'PERINGATAN KRITIS',
        'production_env' => 'Anda menjalankan perintah ini di lingkungan PRODUKSI.',
        'production_loss' => 'Tindakan ini akan mengakibatkan KEHILANGAN DATA YANG TIDAK DAPAT DIKEMBALIKAN dengan mereset database Anda.',
        'production_confirm' => 'Apakah Anda benar-benar yakin ingin melanjutkan operasi destruktif ini?',
        'env_notice' => 'Pemberitahuan Lingkungan: URL Sistem dikonfigurasi ke localhost. Pemetaan port mungkin diperlukan untuk akses eksternal.',
    ],
    'confirmation' => 'Prosedur ini akan mereset database dan menginisialisasi sistem. Apakah Anda ingin melanjutkan?',
    'success' => 'Inisialisasi sistem inti berhasil diselesaikan.',
    'auth_required' => 'Otorisasi Diperlukan',
    'auth_description' => 'Silakan gunakan tautan terautentikasi berikut untuk memfinalisasi konfigurasi sistem:',
    'audit_logs' => [
        'migrations_executed' => 'Instalasi teknis: Migrasi database dijalankan menggunakan [:command].',
        'seeding_completed' => 'Instalasi teknis: Seeding database selesai dan token setup dibuat.',
        'key_exists_skipping' => 'Instalasi teknis: Kunci APP_KEY sudah ada, melewati pembuatan baru.',
        'env_created' => 'Instalasi teknis: File .env dibuat dari .env.example.',
    ],
];
