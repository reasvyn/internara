<?php

declare(strict_types=1);

return [
    'role' => [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'teacher' => 'Guru',
        'student' => 'Siswa',
        'supervisor' => 'Pembimbing',
    ],

    'permission' => [
        // User management
        'users.view' => 'Lihat Pengguna',
        'users.create' => 'Buat Pengguna',
        'users.edit' => 'Ubah Pengguna',
        'users.delete' => 'Hapus Pengguna',

        // Student management
        'students.view' => 'Lihat Siswa',
        'students.create' => 'Buat Siswa',
        'students.edit' => 'Ubah Siswa',
        'students.delete' => 'Hapus Siswa',

        // Teacher management
        'teachers.view' => 'Lihat Guru',
        'teachers.create' => 'Buat Guru',
        'teachers.edit' => 'Ubah Guru',
        'teachers.delete' => 'Hapus Guru',

        // Supervisor management
        'supervisors.view' => 'Lihat Pembimbing',
        'supervisors.create' => 'Buat Pembimbing',
        'supervisors.edit' => 'Ubah Pembimbing',
        'supervisors.delete' => 'Hapus Pembimbing',

        // Company management
        'companies.view' => 'Lihat Perusahaan',
        'companies.create' => 'Buat Perusahaan',
        'companies.edit' => 'Ubah Perusahaan',
        'companies.delete' => 'Hapus Perusahaan',

        // Department management
        'departments.view' => 'Lihat Jurusan',
        'departments.create' => 'Buat Jurusan',
        'departments.edit' => 'Ubah Jurusan',
        'departments.delete' => 'Hapus Jurusan',

        // School management
        'schools.view' => 'Lihat Sekolah',
        'schools.edit' => 'Ubah Sekolah',

        // Internship management
        'internships.view' => 'Lihat Magang',
        'internships.create' => 'Buat Magang',
        'internships.edit' => 'Ubah Magang',
        'internships.delete' => 'Hapus Magang',
        'internships.manage' => 'Kelola Magang',

        // Assignment management
        'assignments.view' => 'Lihat Tugas',
        'assignments.create' => 'Buat Tugas',
        'assignments.edit' => 'Ubah Tugas',
        'assignments.delete' => 'Hapus Tugas',
        'assignments.grade' => 'Nilai Tugas',

        // Assessment management
        'assessments.view' => 'Lihat Penilaian',
        'assessments.create' => 'Buat Penilaian',
        'assessments.edit' => 'Ubah Penilaian',
        'assessments.delete' => 'Hapus Penilaian',
        'assessments.grade' => 'Nilai Penilaian',

        // Attendance management
        'attendance.view' => 'Lihat Kehadiran',
        'attendance.clockin' => 'Absen Masuk',
        'attendance.clockout' => 'Absen Pulang',
        'attendance.manage' => 'Kelola Kehadiran',

        // Journal management
        'journals.view' => 'Lihat Jurnal',
        'journals.create' => 'Buat Jurnal',
        'journals.edit' => 'Ubah Jurnal',
        'journals.verify' => 'Verifikasi Jurnal',

        // Report management
        'reports.view' => 'Lihat Laporan',
        'reports.create' => 'Buat Laporan',
        'reports.export' => 'Ekspor Laporan',

        // Notification management
        'notifications.view' => 'Lihat Notifikasi',
        'notifications.manage' => 'Kelola Notifikasi',

        // System settings
        'settings.view' => 'Lihat Pengaturan',
        'settings.edit' => 'Ubah Pengaturan',

        // Audit & Logs
        'audit.view' => 'Lihat Audit',
        'audit.export' => 'Ekspor Audit',

        // Dashboard access
        'dashboard.student' => 'Dasbor Siswa',
        'dashboard.teacher' => 'Dasbor Guru',
        'dashboard.mentor' => 'Dasbor Pembimbing',
        'dashboard.admin' => 'Dasbor Admin',
        'dashboard.supervisor' => 'Dasbor Pembimbing',
    ],
];
