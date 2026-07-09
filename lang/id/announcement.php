<?php

declare(strict_types=1);

return [
    'title' => 'Pengumuman',
    'subtitle' => 'Buat dan kirim pengumuman ke pengguna',
    'create' => 'Pengumuman Baru',
    'send' => 'Kirim Pengumuman',
    'sent' => 'Pengumuman berhasil dikirim.',
    'published' => 'Pengumuman berhasil dipublikasikan.',
    'deleted' => 'Pengumuman berhasil dihapus.',
    'empty' => 'Belum ada pengumuman.',
    'send_to_all' => 'Kirim ke semua pengguna',
    'all_users' => 'Semua pengguna',
    'delivery' => 'Pengiriman',
    'publish_now' => 'Publikasikan Sekarang',
    'cannot_publish' => 'Pengumuman ini tidak dapat dipublikasikan.',
    'confirm_publish' => 'Yakin ingin mempublikasikan pengumuman ini? Pengumuman akan dikirim ke semua penerima.',
    'confirm_delete' => 'Yakin ingin menghapus pengumuman ini? Tindakan ini tidak dapat dibatalkan.',
    'scheduled_for' => 'Dijadwalkan pada',
    'schedule_hint' => 'Tanggal dan waktu pengumuman akan dipublikasikan secara otomatis.',
    'markdown_hint' => 'Mendukung format Markdown: **tebal**, *miring*, `kode`, [tautan](https://), dll.',
    'roles_hint' => 'Kosongkan untuk mengirim ke semua pengguna dalam peran yang dipilih',
    'status' => [
        'draft' => 'Draf',
        'scheduled' => 'Terjadwal',
        'published' => 'Terkirim',
    ],
    'fields' => [
        'title' => 'Judul',
        'message' => 'Pesan',
        'type' => 'Tipe',
        'link' => 'Tautan (opsional)',
        'scheduled_at' => 'Jadwal Tanggal dan Waktu',
        'link_placeholder' => 'https://...',
        'target_roles' => 'Peran Target',
    ],

    'guide' => [
        'title' => 'Panduan Pengumuman',
        'intro' => 'Buat dan kelola pengumuman di seluruh sistem:',
        'create_title' => 'Membuat Pengumuman',
        'create_desc' => 'Tulis pengumuman Anda menggunakan format Markdown. Tambahkan judul, pesan, dan pilih tipe (Info, Sukses, Peringatan, Error).',
        'schedule_title' => 'Penjadwalan',
        'schedule_desc' => 'Simpan sebagai draf untuk diselesaikan nanti, jadwalkan untuk tanggal tertentu, atau publikasikan segera.',
        'publish_title' => 'Publikasi',
        'publish_desc' => 'Pengumuman yang dipublikasikan dikirim ke pengguna yang ditargetkan melalui notifikasi. Anda dapat mempublikasikan draf kapan saja.',
        'target_title' => 'Penargetan',
        'target_desc' => 'Kirim ke semua pengguna atau pilih peran tertentu. Pilih dengan bijak untuk menghindari kelelahan notifikasi.',
    ],
];
