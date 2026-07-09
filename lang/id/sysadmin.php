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

    'recover' => [
        'description' => 'Pulihkan akses super administrator',
        'subtitle' => 'Pulihkan Akses Super Administrator',
        'guide' => 'Perintah ini memulihkan akses ke akun super administrator saat kata sandi hilang atau akun terkunci.',
        'invalid_email' => 'Alamat email tidak valid.',
        'password_min' => 'Kata sandi minimal 8 karakter.',
        'password_mismatch' => 'Kata sandi tidak cocok.',
        'not_found' => "Pengguna dengan email ':email' tidak ditemukan.",
        'key_required' => 'Kunci pemulihan diperlukan. Berikan --key atau pastikan storage/app/private/.recovery-key ada.',
        'key_invalid' => 'Kunci pemulihan tidak valid.',
        'key_detected' => 'Kunci pemulihan terdeteksi dari file penyimpanan. Melanjutkan pemulihan.',
        'file_regenerated' => 'File kunci pemulihan ditulis ulang ke: :path',
        'confirm_prompt' => 'Ketik email di atas untuk konfirmasi:',
        'confirm_warning' => 'Anda akan mereset kata sandi untuk: :email',
        'aborted' => 'Pemulihan dibatalkan.',
        'success_reset' => 'Kata sandi berhasil direset.',
        'change_password' => 'Harap ganti kata sandi setelah login pertama.',
        'recovery_key_title' => 'Kunci Pemulihan Baru',
        'recovery_key_desc' => 'Kunci pemulihan telah dibuat ulang. Simpan kunci ini di tempat yang aman.',
        'file_regenerated_failed' => 'Gagal menyimpan kunci pemulihan ke file.',
        'otp_sent' => 'Kode sekali pakai telah dikirim ke email super admin.',
        'otp_prompt' => 'Masukkan kode sekali pakai dari email',
        'otp_invalid' => 'Kode sekali pakai tidak valid.',
        'otp_expired' => 'Kode sekali pakai telah kedaluwarsa. Silakan mulai ulang proses pemulihan.',
        'otp_send_failed' => 'Gagal mengirim kode sekali pakai. Periksa konfigurasi email.',
    ],

    'recovery_path' => [
        'description' => 'Tampilkan lokasi file penyimpanan kunci pemulihan',
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

    'activity_title' => 'Log Aktivitas',
    'activity_subtitle' => 'Pelacakan aktivitas pengguna',
    'activity_filter_user' => 'Semua pengguna',
    'activity_filter_module' => 'Semua modul',
    'activity_filter_action' => 'Semua aksi',

    'recovery_show' => [
        'description' => 'Tampilkan kunci pemulihan dari file penyimpanan',
        'warning' => 'Kunci pemulihan memberikan akses super admin. Hanya bagikan dengan administrator server tepercaya.',
        'confirm' => 'Anda yakin ingin menampilkan kunci pemulihan?',
        'aborted' => 'Tampilan dibatalkan.',
        'no_setup' => 'Sistem tampaknya belum terinstal.',
        'key_label' => 'Kunci Pemulihan',
    ],

    'guide' => [
        'backup_title' => 'Panduan Cadangan',
        'backup_intro' => 'Kelola cadangan sistem untuk melindungi data Anda:',
        'backup_create_title' => 'Membuat Cadangan',
        'backup_create_desc' => 'Buat cadangan sistem lengkap termasuk database dan file yang diunggah. Lakukan pencadangan secara rutin.',
        'backup_download_title' => 'Mengunduh',
        'backup_download_desc' => 'Unduh file cadangan untuk penyimpanan di luar lokasi. Simpan salinan di tempat yang aman.',
        'backup_restore_title' => 'Pemulihan',
        'backup_restore_desc' => 'Pulihkan dari cadangan jika diperlukan. Hubungi administrator sistem sebelum melakukan pemulihan.',
        'audit_title' => 'Panduan Log Audit',
        'audit_intro' => 'Lihat dan analisis log aktivitas sistem:',
        'audit_filter_title' => 'Menyaring Log',
        'audit_filter_desc' => 'Gunakan filter untuk mempersempit log berdasarkan rentang tanggal, modul, jenis peristiwa, atau pengguna.',
        'audit_detail_title' => 'Detail Log',
        'audit_detail_desc' => 'Klik entri log untuk melihat detail lengkap termasuk data permintaan, konteks pengguna, dan jejak tumpukan.',
        'application_title' => 'Panduan Tinjauan Aplikasi',
        'application_intro' => 'Tinjau dan proses aplikasi akun:',
        'application_approve_title' => 'Menyetujui',
        'application_approve_desc' => 'Setujui aplikasi yang sah untuk memberikan akses. Verifikasi informasi pemohon sebelum menyetujui.',
        'application_reject_title' => 'Menolak',
        'application_reject_desc' => 'Tolak aplikasi yang mencurigakan atau tidak lengkap. Berikan alasan penolakan.',
    ],
];
