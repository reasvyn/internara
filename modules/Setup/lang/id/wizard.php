<?php

declare(strict_types=1);

return [
    'steps' => 'Langkah :current dari :total',
    'status' => [
        'passed' => 'Lulus',
        'failed' => 'Gagal',
        'writable' => 'Bisa Ditulis',
        'not_writable' => 'Tidak Bisa Ditulis',
        'connected' => 'Terhubung',
        'disconnected' => 'Terputus',
    ],
    'buttons' => [
        'back' => 'Kembali',
        'next' => 'Lanjut',
        'continue' => 'Lanjutkan',
        'save_continue' => 'Simpan & Lanjutkan',
        'finish' => 'Selesai',
    ],
    'common' => [
        'back' => 'Kembali',
        'continue' => 'Lanjutkan',
        'save_continue' => 'Simpan & Lanjutkan',
        'finish' => 'Selesai',
        'later_at_settings' => 'Anda dapat mengubah pengaturan ini nanti melalui halaman pengaturan.',
    ],
    'welcome' => [
        'title' => 'Mulai Instalasi',
        'headline' => 'Akhiri Kerumitan, Sambut Program Magang yang Bermakna.',
        'problem' => [
            'title' => 'Memahami Kerumitan Anda',
            'description' => 'Kami tahu mengelola program magang terasa seperti menyatukan ribuan keping puzzle yang rumit.',
        ],
        'solution' => [
            'title' => 'Solusi Cerdas Kami',
            'description' => 'Internara hadir sebagai partner Anda, menata setiap detail agar Anda bisa fokus membimbing masa depan siswa.',
        ],
        'journey' => [
            'title' => 'Perjalanan Dimulai',
            'description' => 'Proses setup ini adalah langkah pertama Anda menuju program magang yang lebih terorganisir dan berdampak.',
        ],
        'cta' => 'Mulai Instalasi',
    ],
    'environment' => [
        'title' => 'Pengecekan Lingkungan',
        'description' => 'Kami perlu memastikan server Anda siap untuk menjalankan Internara dengan lancar.',
        'requirements' => 'Persyaratan Sistem',
        'permissions' => 'Izin Direktori',
        'database' => 'Konektivitas Database',
        'db_connection' => 'Koneksi Database',
    ],
    'account' => [
        'title' => 'Buat Akun Administrator',
        'headline' => 'Setiap Perjalanan Hebat Butuh Seorang Pemimpin.',
        'description' => 'Akun ini akan menjadi pusat kendali Anda. Dengan akun inilah Anda akan mengarahkan alur program magang, mengelola pengguna, dan memastikan semuanya berjalan lancar. Mari kita siapkan akun administrator utama Anda.',
    ],
    'school' => [
        'title' => 'Atur Data Sekolah',
        'headline' => 'Membangun Identitas Sekolah Anda.',
        'description' => 'Informasi ini akan menjadi fondasi dari seluruh sistem, memastikan setiap dokumen, laporan, dan komunikasi membawa identitas unik sekolah Anda. Mari kita perkenalkan institusi Anda pada Internara.',
    ],
    'department' => [
        'title' => 'Atur Data Jurusan',
        'headline' => 'Menyiapkan Jalur-Jalur Keahlian.',
        'description' => 'Setiap jurusan adalah jalur unik yang akan ditempuh siswa. Dengan mendefinisikan jurusan-jurusan ini, kita memudahkan penempatan magang yang sesuai dengan keahlian mereka. Masukkan jurusan-jurusan yang ada di sekolah Anda.',
    ],
    'internship' => [
        'title' => 'Atur Data PKL',
        'headline' => 'Menentukan Periode Magang.',
        'description' => 'Sekarang, mari kita tentukan periode atau tahun ajaran program magang yang akan dikelola. Ini akan menjadi \'wadah\' utama untuk semua aktivitas magang yang akan datang.',
    ],
    'system' => [
        'title' => 'Pengaturan Sistem',
        'headline' => 'Pastikan Jalur Komunikasi Terbuka.',
        'description' => 'Internara perlu mengirimkan notifikasi penting, laporan, dan konfirmasi akun melalui email. Konfigurasikan server SMTP Anda untuk memastikan setiap pesan sampai ke tujuannya.',
        'description_extra' => 'Anda dapat menggunakan penyedia layanan SMTP gratis atau yang disediakan oleh institusi Anda.',
        'test_connection' => 'Tes Koneksi',
        'skip' => 'Lewati Dulu',
        'fields' => [
            'smtp_host' => 'SMTP Host',
            'smtp_port' => 'SMTP Port',
            'encryption' => 'Enkripsi',
            'username' => 'Nama Pengguna',
            'password' => 'Kata Sandi',
            'from_email' => 'Email Pengirim',
            'from_name' => 'Nama Pengirim',
        ],
    ],
    'complete' => [
        'title' => 'Setup Selesai',
        'badge' => '🎉 Satu Sentuhan Terakhir! 🎉',
        'headline' => 'Finalisasi dan Sinkronisasi: Internara Siap Beraksi! ✨',
        'description' => 'Ini adalah sentuhan akhir—seperti seorang seniman yang membubuhkan tanda tangannya. Langkah ini akan menyatukan semua yang telah kita siapkan, mengaktifkan seluruh modul, dan memastikan Internara siap melayani Anda sepenuhnya.',
        'description_extra' => 'Dengan satu klik terakhir, Anda akan membuka pintu menuju pengalaman manajemen magang yang baru. Siap untuk memulai babak baru ini?',
        'cta' => 'Selesaikan & Mulai Petualangan',
    ],
];