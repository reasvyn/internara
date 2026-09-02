<?php

declare(strict_types=1);

return [
    'audit' => [
        'status' => [
            'pass' => 'Lulus',
            'fail' => 'Gagal',
            'warn' => 'Peringatan',
        ],
    ],

    'csv' => [
        'created' => 'Dibuat',
        'skipped' => 'Dilewati',
    ],

    'discover' => [
        'service_provider_not_registered' => 'Service provider aplikasi tidak terdaftar.',
        'service_provider_not_registered_hint' => 'Pastikan App\\Providers\\AppServiceProvider terdaftar di config/app.php sebelum menjalankan discovery modul.',
    ],

    'errors' => [
        'action_failed_hint' => 'Terjadi kesalahan tak terduga saat melakukan aksi ini.',
    ],

    'exceptions' => [
        'unauthorized_hint' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
        'validation_failed_hint' => 'Periksa kembali data yang Anda masukkan dan coba lagi.',
    ],
];
