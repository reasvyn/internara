<?php

declare(strict_types=1);

return [
    'title' => 'Tentang',
    'about_app' => 'Tentang :app',
    'subtitle' => 'Sistem Informasi Manajemen Praktik Kerja Lapangan',
    'lead' => 'Platform manajemen praktik kerja lapangan vokasi mandiri, single-tenant, dan berdaulat atas privasi yang dirancang khusus untuk sekolah kejuruan dan politeknik.',
    'overview' => [
        'heading' => 'Ikhtisar Aplikasi',
        'subheading' => 'Dirancang untuk memajukan tata kelola magang vokasi dengan martabat dan keandalan tinggi.',
        'description' => ':app adalah sistem manajemen terpadu yang dirancang untuk mengoordinasikan seluruh siklus hidup Praktik Kerja Lapangan (PKL) siswa. Mulai dari kemitraan dunia usaha/industri (DUDI), penempatan pembimbing, jurnal logbook harian, presensi kehadiran, penilaian berbasis rubrik, hingga penerbitan sertifikat digital resmi, :app menempatkan kedaulatan pendidikan dan keandalan operasional sebagai prioritas utama.',
    ],
    'author' => [
        'heading' => 'Author & Atribusi',
        'subheading' => 'Pencipta sistem dan pemelihara arsitektur.',
        'role' => 'Pencipta & Arsitek Utama',
        'bio' => 'Arsitek dan pengembang utama :app. Berdedikasi menghadirkan solusi digital yang tangguh, mudah diakses, dan self-hosted bagi institusi vokasi tanpa keterikatan vendor (vendor lock-in).',
        'email_label' => 'Kontak Pengembang',
        'github_label' => 'Profil GitHub',
        'attribution_notice' => 'Atribusi sistem merupakan invariansi arsitektural yang dilindungi oleh AppIntegrity. Hak atribusi kepada pencipta asli diwajibkan oleh lisensi proyek dan invariansi inti sistem.',
    ],
    'philosophy' => [
        'heading' => 'Prinsip & Filosofi',
        'subheading' => 'Filosofi dasar yang melandasi setiap alur kerja di :app.',
        'education_first' => [
            'title' => 'Pendidikan Utama, Teknologi Penunjang',
            'desc' => 'Teknologi hadir melayani pendidikan, bukan sebaliknya. Setiap fitur diukur dari seberapa besar manfaatnya dalam mempermudah sekolah menyelenggarakan program PKL.',
        ],
        'great_tools' => [
            'title' => 'Setiap Sekolah Berhak Atas Perangkat Terbaik',
            'desc' => 'Dirancang agar efisien dijalankan dari shared hosting ekonomis hingga kluster komputasi awan, memastikan sekolah dengan keterbatasan sumber daya tetap mendapatkan kapabilitas terbaik.',
        ],
        'simplicity' => [
            'title' => 'Kesederhanaan Mengalahkan Kompleksitas',
            'desc' => 'Mengutamakan Action berprinsip tanggung jawab tunggal (SRP) dan batas modular yang transparan daripada lapisan abstraksi rumit yang membebani pemeliharaan.',
        ],
        'maintainability' => [
            'title' => 'Keterpeliharaan adalah Fitur Utama',
            'desc' => 'Standar kode tinggi, type-safety ketat, cakupan pengujian lengkap, dan spesifikasi hidup memastikan sistem ini tetap mudah dipelihara dari tahun ke tahun.',
        ],
        'data_sovereignty' => [
            'title' => 'Kedaulatan Data & Privasi',
            'desc' => 'Seluruh data siswa, presensi, jurnal, dan penilaian tersimpan di infrastruktur sekolah sendiri. Bebas telemetri eksternal dan pelacakan pihak ketiga tanpa izin.',
        ],
    ],
    'tech_stack' => [
        'heading' => 'Arsitektur Teknologi',
        'subheading' => 'Dibangun dengan fondasi teknologi sumber terbuka modern dan teruji.',
        'php' => 'PHP 8.4 dengan strict typing dan atribut modern',
        'laravel' => 'Kerangka kerja inti Laravel 13',
        'livewire' => 'Livewire 4 untuk antarmuka reaktif tanpa beban SPA berlebih',
        'tallstackui' => 'TallstackUI v4 untuk pustaka komponen yang aksesibel',
        'tailwind' => 'Tailwind CSS v4 mesin utilitas modern',
        'alpine' => 'Alpine.js 3 untuk reaktivitas ringan pada peramban',
        'pest' => 'Pest 4 kerangka kerja pengujian berbasis spesifikasi',
    ],
    'diagnostics' => [
        'heading' => 'Diagnostik Sistem',
        'subheading' => 'Metadata lingkungan non-sensitif untuk kemudahan pelaporan dan dukungan teknis.',
        'app_version' => 'Versi Aplikasi',
        'environment' => 'Lingkungan Sistem',
        'php_version' => 'Runtime PHP',
        'laravel_version' => 'Versi Kerangka Kerja',
        'license' => 'Lisensi Perangkat Lunak',
        'repository' => 'Repositori Resmi',
        'copy_button' => 'Salin Informasi Diagnostik',
        'copied_toast' => 'Informasi diagnostik berhasil disalin ke papan klip.',
    ],
    'license' => [
        'heading' => 'Lisensi & Sumber Terbuka',
        'text' => ':app adalah perangkat lunak bebas dan sumber terbuka yang didistribusikan di bawah Lisensi MIT. Anda bebas meng-host mandiri, memodifikasi, dan menggunakan sistem ini untuk institusi pendidikan Anda.',
        'view_repo' => 'Lihat di GitHub',
    ],
];
