<?php

declare(strict_types=1);

return [
    'reset' => [
        'header' => 'PEMULIHAN: Reset Inisialisasi Sistem',
        'production_warning' => 'Sistem berada di lingkungan PRODUKSI. Mereset pengaturan sangat destruktif dan memerlukan flag --force.',
        'confirm_question' => 'Tindakan ini akan membuka akses rute setup dan memungkinkan konfigurasi ulang. Lanjutkan?',
        'tasks' => [
            'deauthorizing' => 'Membatalkan otorisasi status instalasi',
            'regenerating_token' => 'Meregenerasi token setup berdaulat',
        ],
        'success' => 'Berhasil: Infrastruktur setup telah dibuka kembali.',
        'link_label' => 'Tautan akses aman sekali pakai telah dibuat (Kedaluwarsa dalam :minutes menit):',
        'audit_log' => 'Status setup sistem telah direset melalui perintah darurat CLI.',
    ],
];
