<?php

declare(strict_types=1);

return [
    'welcome' => [
        'title' => 'Selamat datang di Sistem!',
        'broadcast' => 'Akun Anda telah berhasil dibuat. Selamat magang!',
        'database' => 'Akun Anda telah dibuat. Lengkapi profil Anda untuk memulai.',
        'mail_subject' => 'Selamat Datang di Sistem',
        'mail_greeting' => 'Halo :name!',
        'mail_line1' => 'Akun Anda untuk Sistem Manajemen Magang telah dibuat.',
        'mail_username' => 'Username: :username',
        'mail_password' => 'Kata Sandi Sementara: :password',
        'mail_line2' => 'Silakan ganti kata sandi Anda setelah masuk.',
        'mail_action' => 'Masuk Sekarang',
    ],
    'account_status' => [
        'title' => 'Status Akun Diperbarui',
        'broadcast' => 'Status akun Anda sekarang adalah :status',
        'database' => 'Status akun Anda sekarang adalah :status. Alasan: :reason',
        'mail_subject' => 'Pembaruan Status Akun',
        'mail_line1' => 'Status akun Anda telah diperbarui menjadi: :status',
        'mail_reason' => 'Alasan: :reason',
    ],
    'internship_registration' => [
        'title' => 'Pembaruan Status Magang',
        'message' => "Pembaruan pada ':internship': :status",
        'mail_subject' => 'Pembaruan Pendaftaran Magang',
        'mail_line1' => "Pendaftaran Anda untuk ':internship' telah diperbarui menjadi: :status",
    ],
    'assignment' => [
        'title' => 'Tugas Baru Diterbitkan',
        'broadcast' => "Tugas baru ':title' untuk :internship",
        'database' => "Tugas ':title' sekarang tersedia.",
        'mail_subject' => 'Tugas Baru: :title',
        'mail_line1' => "Tugas baru telah diterbitkan untuk program magang Anda ':internship'.",
        'mail_title' => 'Judul: :title',
        'mail_due_date' => 'Tenggat Waktu: :due_date',
    ],
    'submission_feedback' => [
        'title' => 'Umpan Balik Tugas Diterima',
        'broadcast' => "Pengajuan Anda untuk ':title' telah ditandai sebagai :status",
        'database' => "Pembaruan pada ':title': :status",
        'mail_subject' => 'Umpan Balik pada Tugas: :title',
        'mail_line1' => "Pengajuan Anda untuk ':title' telah ditinjau dan ditandai sebagai: :status",
        'mail_feedback' => 'Umpan Balik: :feedback',
    ],
    'report_generated' => [
        'title' => 'Laporan Siap',
        'message' => 'Laporan :type Anda siap untuk diunduh.',
        'mail_subject' => 'Laporan :type Anda Siap',
        'mail_line1' => 'Laporan :type yang Anda minta telah berhasil dibuat dan sekarang siap untuk diunduh.',
    ],
    'job_failed' => [
        'title' => 'Tugas Latar Belakang Gagal',
        'broadcast' => "Tugas ':task' mengalami kesalahan.",
        'database' => "Tugas ':task' gagal: :error",
    ],
    'ui' => [
        'title' => 'Pusat Notifikasi',
        'subtitle' => 'Tetap terinformasi dengan aktivitas sistem',
        'mark_all_read' => 'Tandai Semua Dibaca',
        'delete_selected' => 'Hapus Terpilih',
        'are_you_sure' => 'Apakah Anda yakin?',
        'all_status' => 'Semua Status',
        'unread' => 'Belum Dibaca',
        'read' => 'Dibaca',
        'message_col' => 'Pesan',
        'received_col' => 'Diterima',
        'success_mark_all' => 'Semua notifikasi telah ditandai sebagai dibaca.',
    ],
];
