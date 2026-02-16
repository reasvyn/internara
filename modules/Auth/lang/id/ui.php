<?php

declare(strict_types=1);

return [
    'register_super_admin' => [
        'title' => 'Daftar Akun Administrator',
        'subtitle' => 'Buat akun utama untuk mengelola seluruh data sistem.',
        'form' => [
            'name' => 'Nama Lengkap',
            'name_placeholder' => 'Masukkan Nama Lengkap Anda',
            'email' => 'Alamat Email',
            'email_placeholder' => 'Masukkan Alamat Email Anda',
            'password' => 'Kata Sandi',
            'password_placeholder' => 'Buat Kata Sandi Anda',
            'password_hint' => 'Perhatian! Gunakan kombinasi kata sandi yang sulit ditebak.',
            'password_confirmation' => 'Konfirmasi Kata Sandi',
            'password_confirmation_placeholder' => 'Ulangi Kata Sandi Anda',
            'submit' => 'Buat Akun',
            'footer_warning' => 'Hati-hati! Jangan berikan informasi akun kepada siapapun.',
        ],
    ],
    'login' => [
        'title' => 'Masuk ke Akun Anda',
        'subtitle' => 'Gunakan email atau nama pengguna Anda untuk melanjutkan.',
        'form' => [
            'identifier' => 'Email atau Nama Pengguna',
            'identifier_placeholder' => 'Masukkan Email atau Nama Pengguna Anda',
            'password' => 'Kata Sandi',
            'password_placeholder' => 'Masukkan Kata Sandi Anda',
            'forgot_password' => 'Lupa kata sandi Anda?',
            'remember_me' => 'Ingat saya',
            'submit' => 'Masuk Sekarang',
            'no_account' => 'Belum punya akun?',
            'register_now' => 'Daftar Sekarang',
        ],
    ],
    'register' => [
        'title' => 'Daftar Akun Siswa',
        'subtitle' => 'Isi data diri Anda untuk memulai perjalanan magang.',
        'form' => [
            'name' => 'Nama Lengkap',
            'name_placeholder' => 'Masukkan Nama Lengkap Anda',
            'email' => 'Alamat Email',
            'email_placeholder' => 'Masukkan Alamat Email Anda',
            'password' => 'Kata Sandi',
            'password_placeholder' => 'Buat Kata Sandi Anda',
            'password_confirmation' => 'Konfirmasi Kata Sandi',
            'password_confirmation_placeholder' => 'Ulangi Kata Sandi Anda',
            'submit' => 'Daftar Sekarang',
            'policy_agreement' =>
                'Dengan menekan tombol **Daftar Sekarang**, Anda otomatis menyetujui [Kebijakan Privasi kami](:url).',
            'has_account' => 'Sudah memiliki akun?',
            'login_now' => 'Masuk',
        ],
    ],
    'verification' => [
        'title' => 'Verifikasi Email Anda',
        'subtitle' => 'Periksa kotak masuk Anda untuk tautan verifikasi.',
        'notice' => 'Sebelum melanjutkan, harap periksa email Anda untuk tautan verifikasi.',
        'resend_prompt' => 'Tidak menerima email?',
        'resend_button' => 'Klik di sini untuk mengirim ulang',
        'verify_button' => 'Verifikasi Email',
    ],
];
