<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Dasbor Mentor Industri',
        'subtitle' => 'Pantau aktivitas dan kehadiran siswa magang di perusahaan Anda.',
        'total_interns' => 'Total Siswa Magang',
        'assigned_interns' => 'Siswa Magang',
        'table' => [
            'student_name' => 'Nama Siswa',
            'program' => 'Program Magang',
            'status' => 'Status',
        ],
        'actions' => [
            'mentoring' => 'Mentoring',
            'evaluate' => 'Evaluasi',
        ],
    ],
    'manager' => [
        'title' => 'Manajemen Pembimbingan',
        'record_visit' => 'Catat Kunjungan',
        'give_feedback' => 'Berikan Feedback',
        'stats' => [
            'total_visits' => 'Total Kunjungan',
            'total_logs' => 'Total Log/Feedback',
            'last_visit' => 'Kunjungan Terakhir',
        ],
        'timeline' => [
            'title' => 'Timeline Pembimbingan',
            'subtitle' => 'Gabungan log bimbingan dan kunjungan lapangan secara kronologis.',
            'findings' => 'Temuan Lapangan:',
            'empty' => 'Belum ada aktivitas pembimbingan yang tercatat.',
        ],
        'visit_modal' => [
            'title' => 'Catat Kunjungan Lapangan',
            'subtitle' => 'Dokumentasikan temuan saat kunjungan fisik.',
            'date' => 'Tanggal Kunjungan',
            'notes' => 'Catatan Temuan',
            'notes_placeholder' => 'Jelaskan kondisi siswa dan progres di industri...',
            'save' => 'Simpan Kunjungan',
        ],
        'log_modal' => [
            'title' => 'Berikan Log/Feedback Bimbingan',
            'subtitle' => 'Catat sesi konsultasi atau berikan masukan bimbingan.',
            'type' => 'Tipe Log',
            'types' => [
                'feedback' => 'Feedback Rutin',
                'session' => 'Sesi Bimbingan',
                'advisory' => 'Konsultasi Masalah',
            ],
            'subject' => 'Subjek',
            'subject_placeholder' => 'Contoh: Review Laporan Minggu 1',
            'content' => 'Isi Feedback/Log',
            'content_placeholder' => 'Tuliskan detail bimbingan atau feedback...',
            'save' => 'Simpan Log',
        ],
    ],
];
