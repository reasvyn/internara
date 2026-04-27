<?php

declare(strict_types=1);

return [
    'page_title' => 'Klaim Akun',
    'title' => 'Aktifkan Akun Anda',
    'subtitle_step1' => 'Masukkan username dan kode aktivasi yang Anda terima.',
    'subtitle_step2' => 'Buat kata sandi pribadi untuk mengamankan akun Anda.',
    'step_verify' => 'Verifikasi Kode',
    'step_password' => 'Buat Kata Sandi',

    'info_step1' => 'Kode aktivasi diberikan oleh administrator institusi Anda. Formatnya seperti: XXXX-XXXX-XXXX.',
    'info_step2' => 'Pilih kata sandi yang kuat. Anda akan menggunakannya untuk masuk mulai saat ini.',
    'code_verified' => 'Kode terverifikasi! Sekarang buat kata sandi pribadi Anda.',
    'back_to_login' => 'Kembali ke halaman masuk',

    'form' => [
        'username' => 'Username',
        'username_placeholder' => 'Masukkan username Anda',
        'activation_code' => 'Kode Aktivasi',
        'code_placeholder' => 'mis. XXXX-XXXX-XXXX',
        'code_hint' => 'Tidak peka huruf besar/kecil — tanda hubung opsional.',
        'verify' => 'Verifikasi Kode',
        'password' => 'Kata Sandi Baru',
        'password_placeholder' => 'Pilih kata sandi yang kuat',
        'password_confirmation' => 'Konfirmasi Kata Sandi',
        'password_confirmation_placeholder' => 'Ulangi kata sandi baru Anda',
        'activate' => 'Aktifkan Akun',
    ],

    'invalid_code' => 'Username atau kode aktivasi tidak sesuai, atau kode telah kedaluwarsa.',
    'token_expired' => 'Kode aktivasi Anda kedaluwarsa sebelum proses selesai. Hubungi administrator untuk mendapatkan kode baru.',
    'throttled' => 'Terlalu banyak percobaan. Tunggu beberapa menit sebelum mencoba kembali.',
    'success' => 'Akun Anda berhasil diaktifkan! Silakan masuk dengan kata sandi baru Anda.',
];
