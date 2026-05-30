<?php

declare(strict_types=1);

return [
    'title' => 'Manajemen Jurusan',
    'subtitle' => 'Kelola unit organisasi akademik (Jurusan)',
    'add' => 'Tambah Jurusan',
    'edit' => 'Edit Jurusan',
    'new' => 'Jurusan Baru',
    'delete_confirm' => 'Apakah Anda yakin ingin menghapus jurusan ini? Tindakan ini tidak dapat dibatalkan.',
    'delete_selected_confirm' => 'Apakah Anda yakin ingin menghapus jurusan yang dipilih? Hanya jurusan tanpa siswa yang akan dihapus.',
    'confirm_delete_selected' => 'Apakah Anda yakin ingin menghapus jurusan yang dipilih? Hanya jurusan tanpa siswa yang akan dihapus.',
    'delete_blocked' => 'Tidak dapat menghapus: jurusan ini memiliki :count profil siswa terkait.',
    'selected_count' => '{0} jurusan dipilih|{1} jurusan dipilih|[2,*] jurusan dipilih',
    'stats' => [
        'total' => 'Total Jurusan',
        'with_students' => 'Memiliki Siswa',
    ],
    'search_placeholder' => 'Cari jurusan...',
    'name' => 'Nama Jurusan',
    'name_placeholder' => 'contoh: Rekayasa Perangkat Lunak',
    'description' => 'Deskripsi',
    'created_at' => 'Dibuat',
    'save_success_created' => 'Jurusan berhasil dibuat.',
    'save_success_updated' => 'Jurusan berhasil diperbarui.',
    'delete_success' => 'Jurusan berhasil dihapus.',
    'cancel' => 'Batal',
    'save' => 'Simpan',
    'delete_success_bulk' => '{0} Tidak ada jurusan yang dihapus|{1} 1 jurusan dihapus|[2,*] :count jurusan dihapus.',
    'delete_blocked_bulk' => '{0} Tidak ada jurusan yang dilewati|{1} 1 jurusan dilewati (memiliki profil)|[2,*] :count jurusan dilewati (memiliki profil).',
    'import_invalid' => 'Format CSV tidak valid. File harus memiliki kolom "name".',
    'import_summary' => ':created jurusan diimpor, :skipped dilewati (duplikat).',
    'template_example_name' => 'contoh: Rekayasa Perangkat Lunak',
    'template_example_description' => 'contoh: Jurusan yang berfokus pada pengembangan perangkat lunak',

    'guide' => [
        'title' => 'Panduan Jurusan',
        'intro' => 'Kelola unit organisasi akademik (jurusan/kompetensi keahlian):',
        'create_title' => 'Menambah Jurusan',
        'create_desc' => 'Buat jurusan baru dengan nama dan deskripsi. Nama jurusan harus unik dalam sistem.',
        'edit_title' => 'Mengubah Jurusan',
        'edit_desc' => 'Perbarui nama atau deskripsi jurusan yang sudah ada. Perubahan langsung diterapkan di seluruh sistem.',
        'import_title' => 'Impor CSV',
        'import_desc' => 'Impor banyak jurusan sekaligus dari file CSV. Kolom pertama: nama, kolom kedua: deskripsi (opsional).',
        'delete_title' => 'Menghapus Jurusan',
        'delete_desc' => 'Jurusan yang masih memiliki profil siswa tidak dapat dihapus. Pindahkan siswa terlebih dahulu jika perlu.',
    ],
];
