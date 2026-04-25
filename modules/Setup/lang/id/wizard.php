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
        'save' => 'Simpan',
        'continue' => 'Lanjutkan',
        'save_continue' => 'Simpan & Lanjutkan',
        'finish' => 'Selesai',
        'later_at_settings' => 'Anda dapat mengubah pengaturan ini nanti melalui halaman pengaturan.',
    ],
    'welcome' => [
        'title' => 'Inisialisasi Sistem',
        'headline' => 'Berdayakan Institusi, Transformasikan Pengalaman Magang.',
        'problem' => [
            'title' => 'Melampaui Kerumitan',
            'description' => 'Mengelola praktik kerja industri tidak seharusnya terasa seperti menyatukan ribuan keping puzzle logistik yang rumit.',
        ],
        'solution' => [
            'title' => 'Ekosistem Terintegrasi',
            'description' => ':app hadir sebagai mitra strategis Anda, menata setiap alur kerja agar Anda bisa fokus pada pertumbuhan siswa.',
        ],
        'journey' => [
            'title' => 'Perjalanan Mulus',
            'description' => 'Proses inisialisasi ini adalah langkah pertama Anda menuju program magang yang terorganisir dan berbasis data.',
            'description_short' => 'Nikmati proses penggelaran yang ramping, dirancang untuk standar akademik dan korporat modern.',
        ],
        'cta' => 'Mulai Inisialisasi',
    ],
    'environment' => [
        'title' => 'Pengecekan Lingkungan',
        'description' => 'Kami perlu memastikan server Anda siap untuk menjalankan :app dengan lancar.',
        'requirements' => 'Persyaratan Sistem',
        'permissions' => 'Izin Direktori',
        'database' => 'Konektivitas Database',
        'db_connection' => 'Koneksi Database',
    ],
    'account' => [
        'title' => 'Buat Akun Administrator',
        'headline' => 'Setiap Perjalanan Hebat Butuh Seorang Pemimpin.',
        'description' => 'Akun ini akan menjadi pusat kendali Anda. Dengan akun inilah Anda akan mengarahkan alur program magang di :app, mengelola pengguna, dan memastikan semuanya berjalan lancar. Mari kita siapkan akun administrator utama Anda.',
    ],
    'school' => [
        'title' => 'Atur Data Sekolah',
        'headline' => 'Membangun Identitas Sekolah Anda.',
        'description' => 'Informasi ini akan menjadi fondasi dari seluruh sistem, memastikan setiap dokumen, laporan, dan komunikasi membawa identitas unik sekolah Anda. Mari kita perkenalkan institusi Anda pada :app.',
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
        'description' => ':app perlu mengirimkan notifikasi penting, laporan, dan konfirmasi akun melalui email. Konfigurasikan server SMTP Anda untuk memastikan setiap pesan sampai ke tujuannya.',
        'description_extra' => 'Anda dapat menggunakan penyedia layanan SMTP gratis atau yang disediakan oleh institusi Anda.',
        'smtp_configuration' => 'Konfigurasi SMTP',
        'sender_information' => 'Informasi Pengirim',
        'test_connection' => 'Tes Koneksi',
        'skip' => 'Lewati Dulu',
        'smtp_connection_success' => 'Koneksi SMTP berhasil!',
        'smtp_connection_failed' => 'Koneksi gagal: :message',
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
        'headline' => 'Finalisasi dan Sinkronisasi: :app Siap Beraksi! ✨',
        'description' => 'Ini adalah sentuhan akhir—seperti seorang seniman yang membubuhkan tanda tangannya. Langkah ini akan menyatukan semua yang telah kita siapkan, mengaktifkan seluruh modul, dan memastikan :app siap melayani Anda sepenuhnya.',
        'description_extra' => 'Dengan satu klik terakhir, Anda akan membuka pintu menuju pengalaman manajemen magang yang baru. Siap untuk memulai babak baru ini?',
        'cta' => 'Selesaikan & Mulai Petualangan',
    ],
];
