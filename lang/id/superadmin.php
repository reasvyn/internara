<?php

declare(strict_types=1);

return [
    'title' => 'Pusat Kendali Administrator',
    'version' => 'v:version',
    'field_email' => 'Alamat Email',
    'field_email_result' => 'Email',
    'field_password' => 'Kata Sandi',
    'field_username' => 'Nama Pengguna',
    'field_confirm_password' => 'Konfirmasi Kata Sandi',
    'create' => [
        'description' => 'Buat akun super administrator',
        'subtitle' => 'Buat Super Administrator',
        'guide' => 'Super administrator memiliki akses penuh ke seluruh sistem, termasuk mengelola sekolah, jurusan, pengguna, dan konfigurasi lainnya. Akun ini akan menjadi akun utama yang digunakan untuk mengatur dan mengawasi jalannya sistem. Silakan siapkan alamat email dan kata sandi untuk akun super administrator baru.',
        'already_exists' => 'Super administrator sudah ada.',
        'invalid_email' => 'Alamat email tidak valid.',
        'password_min' => 'Kata sandi minimal 8 karakter.',
        'password_mismatch' => 'Kata sandi tidak cocok.',
        'success' => 'Akun super administrator berhasil dibuat.',
        'recovery_key_title' => 'Kunci Pemulihan',
        'recovery_key_desc' => 'Simpan kunci ini di tempat yang aman. Anda akan membutuhkannya untuk memulihkan akses administrator jika kata sandi hilang.',
        'recovery_file_failed' => 'Gagal menyimpan kunci pemulihan ke file.',
        'change_password' => 'Harap ganti kata sandi setelah login pertama.',
    ],
];
