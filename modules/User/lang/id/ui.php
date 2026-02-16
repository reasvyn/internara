<?php

declare(strict_types=1);

return [
    'manager' => [
        'title' => 'Manajemen Pengguna',
        'subtitle' => 'Kelola pengguna platform dan peran mereka.',
        'add_user' => 'Tambah Pengguna',
        'edit_user' => 'Ubah Pengguna',
        'search_placeholder' => 'Cari pengguna...',
        'table' => [
            'name' => 'Nama',
            'email' => 'Email',
            'username' => 'Username',
            'roles' => 'Peran',
            'status' => 'Status',
        ],
        'form' => [
            'full_name' => 'Nama Lengkap',
            'email' => 'Alamat Email',
            'username' => 'Nama Pengguna',
            'password' => 'Kata Sandi',
            'password_hint' => 'Kosongkan jika tidak ingin mengubah kata sandi',
            'identity_number' => 'Nomor Identitas',
            'department' => 'Jurusan',
            'phone' => 'Nomor Telepon',
            'select_department' => 'Pilih Jurusan',
            'roles' => 'Peran Terpilih',
            'status' => 'Status Akun',
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ],
        'delete' => [
            'title' => 'Konfirmasi Penghapusan',
            'message' =>
                'Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.',
        ],
    ],
];
