<?php

declare(strict_types=1);

return [
    'title' => 'Pengaturan Sistem',
    'subtitle' => 'Konfigurasi identitas aplikasi dan preferensi global.',

    'groups' => [
        'general' => 'Konfigurasi Umum',
        'identity' => 'Identitas Visual',
        'color_scheme' => 'Skema Warna',
        'mail' => 'Layanan Email',
        'system' => 'Informasi Sistem',
    ],

    'fields' => [
        'app_name' => 'Nama Aplikasi',
        'brand_name' => 'Nama Brand (Instansi)',
        'site_title' => 'Judul Situs (Tab Browser)',
        'app_version' => 'Versi Aplikasi',
        'brand_logo' => 'Logo Brand',
        'site_favicon' => 'Favicon Situs',
        'default_locale' => 'Bahasa Default',
        'active_academic_year' => 'Tahun Akademik Aktif',

        'mail_from_address' => 'Alamat Pengirim Email',
        'mail_from_name' => 'Nama Pengirim Email',
        'mail_host' => 'Host SMTP',
        'mail_port' => 'Port SMTP',
        'mail_encryption' => 'Enkripsi SMTP',
        'mail_username' => 'Username SMTP',
        'mail_password' => 'Password SMTP',

        'primary_color' => 'Warna Utama',
        'secondary_color' => 'Warna Sekunder',
        'accent_color' => 'Warna Aksen',
    ],

    'hints' => [
        'brand_logo' => 'Disarankan: PNG persegi, maks 1MB. Digunakan untuk sidebar dan laporan.',
        'site_favicon' => 'Disarankan: PNG atau ICO persegi, 32x32px. Digunakan untuk tab browser.',
    ],

    'buttons' => [
        'test_mail' => 'Uji Koneksi SMTP',
        'save' => 'Simpan Perubahan',
    ],

    'messages' => [
        'saved' => 'Pengaturan sistem berhasil diperbarui.',
        'test_email_sent' => 'Email uji coba berhasil dikirim. Silakan periksa kotak masuk Anda.',
        'test_email_failed' => 'Gagal mengirim email uji coba.',
    ],
];
