<?php

declare(strict_types=1);

return [
    'title' => 'Pusat Kendali Administrator',
    'version' => 'v:version',

    'section_account' => 'Informasi Akun',
    'field_email' => 'Alamat Email',
    'field_email_result' => 'Email',
    'field_name' => 'Nama Lengkap',
    'field_username' => 'Nama Pengguna',
    'field_password' => 'Kata Sandi',
    'field_new_password' => 'Kata Sandi Baru',
    'field_confirm_password' => 'Konfirmasi Kata Sandi',

    'create' => [
        'description' => 'Buat akun super administrator',
        'subtitle' => 'Buat Super Administrator',
        'guide' => 'Super administrator memiliki akses penuh ke seluruh sistem, termasuk mengelola sekolah, jurusan, pengguna, dan konfigurasi lainnya. Akun ini akan menjadi akun utama yang digunakan untuk mengatur dan mengawasi jalannya sistem. Silakan siapkan alamat email dan kata sandi untuk akun super administrator baru.',
        'already_exists' => 'Super administrator sudah ada.',
        'invalid_email' => 'Alamat email tidak valid.',
        'password_min' => 'Kata sandi minimal 8 karakter.',
        'success' => 'Akun super administrator berhasil dibuat.',
        'change_password' => 'Harap ganti kata sandi setelah login pertama.',
    ],

    'recover' => [
        'description' => 'Pulihkan akses super administrator',
        'subtitle' => 'Pulihkan Akses Super Administrator',
        'guide' => 'Akses super administrator yang hilang dapat dipulihkan melalui perintah ini. Jika akun dengan email yang dimasukkan sudah ada, gunakan opsi --reset untuk mereset kata sandinya. Jika belum ada, akun baru akan dibuat.',
        'section_reset' => 'Reset Kata Sandi',
        'section_set_password' => 'Atur Kata Sandi',
        'invalid_email' => 'Alamat email tidak valid.',
        'password_min' => 'Kata sandi minimal 8 karakter.',
        'password_mismatch' => 'Kata sandi tidak cocok.',
        'already_exists' => "Pengguna dengan email ':email' sudah ada. Gunakan --reset untuk mereset kata sandi.",
        'not_found' => "Pengguna dengan email ':email' tidak ditemukan.",
        'key_required' => 'Kunci pemulihan diperlukan. Berikan --key atau pastikan storage/app/private/.recovery-key ada.',
        'key_invalid' => 'Kunci pemulihan tidak valid.',
        'key_detected' => 'Kunci pemulihan terdeteksi dari file penyimpanan. Melanjutkan pemulihan.',
        'file_regenerated' => 'File kunci pemulihan ditulis ulang ke: :path',
        'confirm_prompt' => 'Ketik email di atas untuk konfirmasi:',
        'confirm_mode_create' => 'BUAT BARU',
        'confirm_mode_reset' => 'RESET KATA SANDI',
        'confirm_warning' => 'Anda akan :mode untuk: :email',
        'aborted' => 'Pemulihan dibatalkan.',
        'success_create' => 'Akun super administrator berhasil dibuat.',
        'success_reset' => 'Kata sandi berhasil direset.',
        'change_password' => 'Harap ganti kata sandi setelah login pertama.',
    ],

    'recovery_path' => [
        'info' => 'Lokasi file kunci pemulihan:',
        'status' => 'Status file',
        'exists' => 'File tersedia',
        'missing' => 'File tidak ditemukan',
    ],

    'promote' => [
        'user_not_found' => "Pengguna dengan identifier ':identifier' tidak ditemukan.",
        'invalid_role' => "Peran tidak valid: ':role'. Hanya admin atau super_admin yang diizinkan.",
        'role_absent' => "Peran ':role' tidak ditemukan di database.",
        'super_admin_exists' => 'Super admin sudah ada. Hanya satu akun super admin yang diizinkan.',
        'already_has_role' => "Pengguna :name sudah memiliki peran ':role'.",
        'success' => 'Berhasil menaikkan pangkat :name (:email) menjadi :role.',
    ],

    'auto_inactivate' => [
        'none_found' => 'Tidak ada akun yang tidak aktif.',
        'found' => 'Ditemukan :count akun yang tidak aktif selama lebih dari :days hari.',
        'dry_run' => 'Akan menonaktifkan: :email (:name)',
        'completed' => 'Menonaktifkan :count akun.',
    ],

    'prune_notifications' => [
        'invalid_days' => 'Hari retensi minimal 1.',
        'completed' => 'Membersihkan :count notifikasi terbaca lebih lama dari :days hari.',
    ],

    'publish_announcements' => [
        'none_found' => 'Tidak ada pengumuman terjadwal yang akan dipublikasikan.',
        'published' => 'Mempublikasikan: :title',
        'completed' => 'Mempublikasikan :count pengumuman terjadwal.',
    ],

    'pulse_record' => [
        'started' => 'Merekam snapshot Pulse...',
        'completed' => 'Snapshot berhasil direkam.',
    ],

    'account_slip' => [
        'title' => 'Aktivasi Akun',
        'name' => 'Nama',
        'username' => 'Nama Pengguna',
        'email' => 'Email',
        'activation_code' => 'Kode Aktivasi',
        'instruction' => 'Kunjungi /activate dan masukkan kode ini untuk mengklaim akun Anda.',
        'code_expiry' => 'Kedaluwarsa dalam :days hari',
    ],

    'gdpr_logs' => [
        'title' => 'Log Penghapusan GDPR',
        'search_placeholder' => 'Cari berdasarkan email...',
        'type_placeholder' => 'Semua tipe',
    ],

    'clone_detection' => [
        'title' => 'Deteksi Klon Akun',
        'subtitle' => 'Akun duplikat yang mencurigakan',
    ],

    'recovery_show' => [
        'warning' => 'Kunci pemulihan memberikan akses super admin. Hanya bagikan dengan administrator server tepercaya.',
        'confirm' => 'Anda yakin ingin menampilkan kunci pemulihan?',
        'aborted' => 'Tampilan dibatalkan.',
        'no_setup' => 'Sistem tampaknya belum terinstal.',
    ],
];
