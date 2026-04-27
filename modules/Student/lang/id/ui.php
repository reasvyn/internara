<?php

declare(strict_types=1);

return [
    'management_title' => 'Manajemen Siswa',
    'stats' => [
        'total' => 'Total Siswa',
        'verified' => 'Email Terverifikasi',
        'active' => 'Akun Aktif',
        'pending' => 'Tertunda/Baru',
    ],
    'dashboard' => [
        'title' => 'Dasbor Siswa',
        'welcome' => 'Selamat datang kembali, :name!',
        'my_program' => 'Program Magang Saya',
        'requirements_incomplete' => [
            'title' => 'Persyaratan Belum Lengkap',
            'description' =>
                'Mohon lengkapi persyaratan administrasi di bawah ini untuk melanjutkan proses magang.',
        ],
        'waiting_placement' => [
            'title' => 'Menunggu Penempatan',
            'description' => 'Persyaratan administrasi Anda telah lengkap.',
            'extra' => 'Mohon tunggu admin/koordinator untuk menempatkan Anda di lokasi magang.',
        ],
        'not_registered' => 'Anda belum terdaftar dalam program magang aktif.',
        'score' => [
            'final_grade' => 'Nilai Akhir',
            'processing' => 'Penilaian Anda sedang dalam proses oleh pembimbing.',
            'download_certificate' => 'Unduh Sertifikat',
            'download_transcript' => 'Unduh Transkrip',
        ],
        'quick_links' => 'Tautan Cepat',
    ],
    'manager' => [
        'table' => [
            'department' => 'Jurusan',
            'registration_number' => 'Nomor Registrasi',
        ],
        'filters' => [
            'all_departments' => 'Semua Jurusan',
        ],
        'bulk' => [
            'reissue_codes' => 'Terbitkan Ulang Kode Aktivasi',
            'activate_selected' => 'Aktifkan terpilih',
            'archive_selected' => 'Arsipkan terpilih',
        ],
        'messages' => [
            'links_sent' => ':count tautan setup siswa berhasil dikirim.',
            'code_reissued' => 'Kode aktivasi berhasil diterbitkan ulang.',
            'codes_reissued' => ':count kode aktivasi berhasil diterbitkan ulang.',
            'activated' => ':count akun siswa berhasil diaktifkan.',
            'archived' => ':count akun siswa berhasil diarsipkan.',
        ],
        'form' => [
            'password_setup_notice' =>
                'Akun siswa tidak dikelola dengan kata sandi yang diketahui admin. Simpan data siswa lalu kirim tautan setup akses secara aman.',
            'password_reset_notice' =>
                'Gunakan aksi kirim tautan setup dari tabel untuk mereset akses siswa dengan aman tanpa melihat kata sandinya.',
            'archive_hint' =>
                'Untuk pengarsipan tahunan, gunakan status Nonaktif agar riwayat siswa tetap tersimpan.',
        ],
    ],
];
