<?php

declare(strict_types=1);

return [
    // Lifecycle states
    'pending' => 'Menunggu Aktivasi',
    'activated' => 'Diaktifkan',
    'verified' => 'Terverifikasi',
    'protected' => 'Terlindungi (Super Admin)',
    'restricted' => 'Dibatasi',
    'suspended' => 'Ditangguhkan',
    'inactive' => 'Tidak Aktif',
    'archived' => 'Diarsipkan',

    // Generic
    'unknown' => 'Tidak Diketahui',

    // Descriptions (for UI tooltips)
    'descriptions' => [
        'pending' => 'Akun baru, menunggu aktivasi',
        'activated' => 'Diaktifkan oleh pengguna, menunggu verifikasi',
        'verified' => 'Akun aktif dan terverifikasi',
        'protected' => 'Akun Super Admin - tidak dapat diubah',
        'restricted' => 'Pembatasan sementara - fungsionalitas terbatas',
        'suspended' => 'Akun ditangguhkan - tidak memiliki akses',
        'inactive' => 'Tidak login 180+ hari',
        'archived' => 'Arsip permanen - menunggu penghapusan',
    ],
];
