<?php

declare(strict_types=1);

return [
    'title' => 'Pengaturan Sistem',
    'subtitle' => 'Konfigurasikan identitas inti aplikasi dan preferensi global.',
    'groups' => [
        'general' => 'Konfigurasi Umum',
        'identity' => 'Identitas Visual',
    ],
    'fields' => [
        'brand_name' => 'Nama Brand',
        'site_title' => 'Judul Situs (Tab Browser)',
        'app_version' => 'Versi Aplikasi',
        'brand_logo' => 'Logo Brand',
        'site_favicon' => 'Favicon Situs',
        'default_locale' => 'Bahasa Utama',
    ],
    'hints' => [
        'brand_logo' => 'Disarankan: PNG kotak, maks 1MB.',
        'site_favicon' => 'Disarankan: PNG atau ICO kotak, 32x32px.',
    ],
    'messages' => [
        'saved' => 'Pengaturan sistem berhasil diperbarui.',
    ],
];
