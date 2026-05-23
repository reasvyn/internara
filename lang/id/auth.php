<?php

declare(strict_types=1);

return [
    'failed' => 'Kredensial ini tidak cocok dengan catatan kami.',
    'password' => 'Kata sandi yang diberikan salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik.',
    'title' => 'Autentikasi',
    'login' => [
        'title' => 'Masuk',
        'subtitle' => 'Akses Aman ke Gerbang Aplikasi',
        'identifier' => 'Identitas (Username/Email)',
        'password' => 'Kata Sandi',
        'remember' => 'Ingat saya',
        'forgot_password' => 'Atur Ulang Kata Sandi',
        'submit' => 'Masuk ke Dasbor',
        'welcome_back' => 'Selamat datang kembali, :name!',
        'back_to_login' => 'Kembali ke halaman masuk',
    ],
    'forgot_password' => [
        'subtitle' => 'Masukkan email Anda untuk menerima tautan atur ulang kata sandi',
        'email' => 'Alamat Email',
    ],
    'reset_password' => [
        'subtitle' => 'Buat kata sandi baru yang kuat untuk akun Anda',
        'email' => 'Alamat Email',
        'password' => 'Kata sandi baru',
        'password_confirmation' => 'Konfirmasi kata sandi baru',
    ],
    'confirm_password' => [
        'title' => 'Konfirmasi Kata Sandi',
        'subtitle' => 'Masukkan kata sandi Anda untuk melanjutkan',
        'password' => 'Kata sandi saat ini',
        'confirm' => 'Konfirmasi',
    ],
    'account_recovery' => [
        'title' => 'Pemulihan Akun',
        'subtitle' => 'Tebus slip kredensial Anda',
        'username' => 'Nama Pengguna',
        'recovery_code' => 'Kode Pemulihan',
        'new_password' => 'Kata Sandi Baru',
        'confirm_password' => 'Konfirmasi Kata Sandi Baru',
        'submit' => 'Pulihkan Akun',
        'back_to_login' => 'Kembali ke Login',
    ],
    'logout' => 'Keluar',
    'login_success' => 'Masuk berhasil!',
    'logout_success' => 'Anda telah keluar.',
    'invalid_credentials' => 'Email atau kata sandi salah.',
    'email_reset_link' => 'Tautan atur ulang kata sandi telah dikirim ke email Anda.',
    'password_reset_success' => 'Kata sandi Anda telah diatur ulang.',
    'password_confirmed' => 'Kata sandi dikonfirmasi.',
    'account_locked' => 'Akun berhasil dikunci.',
    'account_unlocked' => 'Akun berhasil dibuka.',
    'recovery_slip_generated' => 'Slip pemulihan berhasil dibuat.',
    'permissions_updated' => 'Izin berhasil diperbarui.',

    // Recovery slip manager
    'recovery_slip' => [
        'title' => 'Slip Pemulihan',
        'subtitle' => 'Hasilkan slip kredensial satu kali untuk pengiriman offline',
        'generated_title' => 'Slip Kredensial Berhasil Dibuat',
        'generated_desc' => 'Kirimkan kode ini ke pengguna secara offline. Kedaluwarsa: :date',
        'security_note' => 'Catatan Keamanan',
        'security_note_desc' => 'Kode ini tidak akan ditampilkan lagi. Kedaluwarsa dalam 24 jam. Verifikasi identitas penerima sebelum memberikan kode.',
        'generate_another' => 'Hasilkan Lagi',
        'search_user' => 'Cari Pengguna',
        'no_users_found' => 'Tidak ada pengguna ditemukan.',
        'selected_user' => 'Dipilih: :name (:username)',
        'generate_slip' => 'Hasilkan Slip Pemulihan',
        'back_to_dashboard' => 'Kembali ke Dasbor',
    ],

    // Access management
    'access_management' => [
        'title' => 'Manajemen Akses',
        'subtitle' => 'Kelola peran dan izin granularnya',
        'users_assigned' => ':count pengguna ditugaskan',
        'permissions_granted' => ':count izin diberikan.',
        'manage_permissions' => 'Kelola Izin',
        'manage_title' => 'Kelola Izin: :name',
        'cancel' => 'Batal',
        'save' => 'Simpan Izin',
    ],
];
