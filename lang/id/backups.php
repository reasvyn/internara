<?php

declare(strict_types=1);

return [
    'title' => 'Cadangan Sistem',
    'subtitle' => 'Kelola cadangan sistem',

    'create_button' => 'Buat Cadangan',

    'total' => 'Total Cadangan',
    'completed' => 'Berhasil',
    'failed' => 'Gagal',
    'latest' => 'Ukuran Terakhir',

    'type_label' => 'Tipe',
    'status_label' => 'Status',
    'size_label' => 'Ukuran',
    'created_by_label' => 'Dibuat Oleh',
    'date_label' => 'Tanggal',

    'filter_type' => 'Tipe',
    'filter_status' => 'Status',

    'type' => [
        'database' => 'Database',
        'storage' => 'Penyimpanan',
        'both' => 'Lengkap',
    ],

    'status' => [
        'pending' => 'Menunggu',
        'running' => 'Berjalan',
        'completed' => 'Selesai',
        'failed' => 'Gagal',
    ],

    'create_success' => 'Cadangan berhasil dibuat.',
    'create_failed' => 'Cadangan gagal',
    'delete_success' => 'Cadangan berhasil dihapus.',
    'cannot_delete_active' => 'Tidak dapat menghapus cadangan yang masih berjalan.',
    'confirm_delete_title' => 'Hapus Cadangan',
    'confirm_delete_message' => 'Apakah Anda yakin ingin menghapus cadangan ini? Tindakan ini tidak dapat dibatalkan.',

    'disabled' => 'Cadangan sistem dinonaktifkan. Aktifkan di pengaturan.',
    'starting' => 'Memulai cadangan :type...',
    'completed_info' => 'Cadangan selesai. Ukuran: :size',
    'cleanup_completed' => 'Pembersihan selesai. :count cadangan lama dihapus.',

    'notification_failed' => 'Cadangan sistem gagal (:type). Periksa log untuk detail.',
];
