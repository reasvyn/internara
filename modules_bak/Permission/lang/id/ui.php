<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Access Manager Language Lines
    |--------------------------------------------------------------------------
    */

    'access_manager' => [
        'title' => 'Manajemen Akses',
        'subtitle' => 'Kelola peran dan izin.',
        'manageable' => 'Dapat kelola',
        'no_access' => 'Tidak ada akses',
        'manage' => 'Kelola',

        'table' => [
            'role' => 'Peran',
            'description' => 'Deskripsi',
            'permissions' => 'Izin',
            'users' => 'Pengguna',
        ],

        'modal' => [
            'title' => 'Kelola Izin',
            'subtitle' => 'Pilih izin untuk peran ini.',
        ],

        'cannot_manage' => 'Anda tidak memiliki izin untuk mengelola peran ini.',
        'saved' => 'Izin berhasil diperbarui.',
        'synced' => 'Semua izin telah disinkronkan.',
        'revoked' => 'Semua izin telah dicabut.',
    ],

    'menu' => [
        'access' => 'Manajemen Akses',
    ],
];
