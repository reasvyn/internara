<?php

declare(strict_types=1);

return [
    'title' => 'Pengaturan Sistem',
    'subtitle' => 'Konfigurasi identitas aplikasi dan preferensi global.',

    'groups' => [
        'general' => 'Konfigurasi Umum',
        'identity' => 'Identitas Visual',
        'operational' => 'Aturan Operasional',
        'mail' => 'Layanan Email',
        'system' => 'Informasi Sistem',
    ],

    'fields' => [
        'app_name' => 'Nama Aplikasi',
        'brand_name' => 'Nama Brand',
        'site_title' => 'Judul Situs (Tab Browser)',
        'app_version' => 'Versi Aplikasi',
        'brand_logo' => 'Logo Brand',
        'site_favicon' => 'Favicon Situs',
        'default_locale' => 'Bahasa Default',

        'active_academic_year' => 'Tahun Akademik Aktif',
        'attendance_check_in_start' => 'Waktu Check-in Mulai',
        'attendance_late_threshold' => 'Batas Waktu Terlambat',

        'mail_from_address' => 'Alamat Pengirim Email',
        'mail_from_name' => 'Nama Pengirim Email',
        'mail_host' => 'Host SMTP',
        'mail_port' => 'Port SMTP',
        'mail_encryption' => 'Enkripsi SMTP',
        'mail_username' => 'Username SMTP',
        'mail_password' => 'Password SMTP',
    ],

    'hints' => [
        'brand_logo' => 'Disarankan: PNG persegi, maks 1MB.',
        'site_favicon' => 'Disarankan: PNG atau ICO persegi, 32x32px.',
    ],

    'messages' => [
        'saved' => 'Pengaturan sistem berhasil diperbarui.',
    ],
];
