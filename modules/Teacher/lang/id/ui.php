<?php

declare(strict_types=1);

return [
    'management_title' => 'Manajemen Guru',
    'stats' => [
        'total' => 'Total Guru',
        'active' => 'Akun Aktif',
        'pending' => 'Tertunda/Baru',
    ],
    'dashboard' => [
        'title' => 'Dasbor Guru',
        'subtitle' => 'Pantau aktivitas dan kehadiran siswa bimbingan Anda.',
        'total_students' => 'Total Siswa Bimbingan',
        'assigned_students' => 'Siswa Bimbingan',
        'table' => [
            'student_name' => 'Nama Siswa',
            'placement' => 'Tempat Magang',
            'status' => 'Status',
            'readiness' => 'Kesiapan Lulus',
        ],
        'readiness' => [
            'ready' => 'Siap Lulus',
            'not_ready' => 'Belum Siap',
        ],
        'actions' => [
            'supervise' => 'Supervisi',
            'assess' => 'Penilaian',
            'transcript' => 'Transkrip',
        ],
        'assess_student' => 'Penilaian Siswa',
        'evaluation' => 'Evaluasi Akademik',
        'competency_recap' => 'Rekap Kompetensi',
        'competency_recap_subtitle' => 'Keahlian yang diklaim dalam jurnal harian',
        'submit_evaluation' => 'Simpan Penilaian',
        'placeholder_notes' => 'Tuliskan catatan atau umpan balik untuk siswa...',
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
            'links_sent' => ':count tautan setup guru berhasil dikirim.',
            'code_reissued' => 'Kode aktivasi berhasil diterbitkan ulang.',
            'codes_reissued' => ':count kode aktivasi berhasil diterbitkan ulang.',
            'activated' => ':count akun guru berhasil diaktifkan.',
            'archived' => ':count akun guru berhasil diarsipkan.',
        ],
        'form' => [
            'password_setup_notice' =>
                'Akun guru tidak dikelola dengan kata sandi yang diketahui admin. Simpan data guru lalu kirim tautan setup akses secara aman.',
            'password_reset_notice' =>
                'Gunakan aksi kirim tautan setup dari tabel untuk mereset akses guru dengan aman tanpa melihat kata sandinya.',
            'archive_hint' =>
                'Untuk pengarsipan tahunan atau pergantian penugasan, gunakan status Nonaktif agar riwayat guru tetap tersimpan.',
        ],
    ],
];
