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
        'support_email' => 'Email Dukungan Teknis',

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
        'base_color' => 'Warna Latar',
    ],

    'hints' => [
        'brand_logo' => 'Disarankan: PNG persegi, maks 1MB. Digunakan untuk sidebar dan laporan.',
        'site_favicon' => 'Disarankan: PNG atau ICO persegi, 32x32px. Digunakan untuk tab browser.',
        'color_scheme' => 'Sesuaikan warna brand dan latar di seluruh antarmuka.',
        'mail' => 'Konfigurasi pengaturan SMTP untuk notifikasi email.',
        'support_email' => 'Digunakan sebagai alamat kontak dalam notifikasi keamanan yang dikirim ke pengguna.',
    ],

    'presets_title' => 'Palet Prasetel',
    'custom_title' => 'Warna Kustom',

    'buttons' => [
        'test_mail' => 'Uji Koneksi SMTP',
        'save' => 'Simpan Perubahan',
    ],

    'test_mail' => [
        'subject' => 'Email Uji Coba dari :app_name',
        'greeting' => 'Halo!',
        'line1' => 'Ini adalah email uji coba untuk memverifikasi konfigurasi SMTP Anda.',
        'line2' => 'Jika Anda menerima email ini, pengaturan SMTP Anda sudah benar.',
        'action' => 'Buka Pengaturan',
    ],

    'messages' => [
        'saved' => 'Pengaturan sistem berhasil diperbarui.',
        'logo_saved' => 'Logo brand berhasil disimpan.',
        'logo_removed' => 'Logo brand berhasil dihapus.',
        'favicon_saved' => 'Favicon berhasil disimpan.',
        'favicon_removed' => 'Favicon berhasil dihapus.',
        'remove_asset_confirm' => 'Apakah Anda yakin ingin menghapus aset ini?',
        'test_email_sent' => 'Email uji coba berhasil dikirim. Silakan periksa kotak masuk Anda.',
        'test_email_failed' => 'Gagal mengirim email uji coba.',
    ],

    'guide' => [
        'title' => 'Panduan Pengaturan',
        'intro' => 'Konfigurasi sistem agar sesuai dengan kebutuhan institusi Anda:',
        'general_title' => 'Pengaturan Umum',
        'general_desc' => 'Atur nama aplikasi, judul situs, bahasa default, dan tahun ajaran aktif. Nama aplikasi muncul di judul browser dan kop surat.',
        'branding_title' => 'Tampilan & Warna',
        'branding_desc' => 'Pilih skema warna dari prasetel atau atur warna kustom. Unggah logo dan ikon untuk identitas institusi.',
        'mail_title' => 'Email',
        'mail_desc' => 'Konfigurasi server SMTP untuk mengirim notifikasi email. Gunakan tombol Uji Coba untuk memverifikasi pengaturan.',
        'identity_title' => 'Aset Identitas',
        'identity_desc' => 'Logo dan favicon digunakan di seluruh sistem. Format PNG atau WebP disarankan untuk hasil terbaik.',
    ],
];
