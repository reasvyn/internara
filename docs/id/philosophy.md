# Filosofi Proyek — Prinsip Pemandu, Nilai & Visi

## Deskripsi

Prinsip pemandu, nilai, dan visi yang membentuk Internara — melampaui arsitektur, melampaui kode.

## 1. Pendidikan Dulu, Teknologi Kemudian

Internara ada untuk melayani pendidikan, bukan sebaliknya. Setiap fitur, setiap alur kerja, setiap
keputusan diukur dengan satu pertanyaan: _Apakah ini memudahkan sekolah menjalankan program PKL
yang lebih baik?_

Teknologi bukanlah tujuan. Teknologi adalah pemungkin. Kami mengutamakan alur kerja yang
mencerminkan operasional sekolah di dunia nyata dibandingkan abstraksi yang elegan secara teknis.
Jika sebuah fitur menuntut pengguna mengubah cara mereka bekerja, kami telah gagal.

## 2. Setiap Sekolah Berhak atas Perangkat yang Baik

Sekolah SMA/SMK di Indonesia menghadapi kesenjangan lebar dalam sumber daya, infrastruktur, dan
kemampuan teknis. Sekolah di kota besar dengan staf TI khusus dan sekolah di distrik terpencil
dengan satu komputer bersama harus sama-sama dapat menggunakan Internara secara efektif.

Artinya:

- **Tanpa layanan eksternal secara bawaan.** SQLite, cache berbasis file, antrean sinkron —
  berjalan pada paket shared hosting seharga $5/bulan.
- **Peningkatan progresif.** Mulai sederhana, tambahkan Redis, MySQL, worker, S3 hanya saat Anda
  membutuhkannya. Tidak ada fitur yang terkunci di balik infrastruktur.
- **Operasional yang tahan offline.** Alur kerja inti (kehadiran, logbook) menurun secara anggun
  ketika konektivitas terputus-putus.
- **Tanpa keterikatan vendor.** Self-hosted, single-tenant. Data Anda tetap di infrastruktur Anda.

## 3. Kesederhanaan di Atas Kerumitan, Selalu

Kerumitan adalah musuh keandalan, keamanan, dan kemudahan pemeliharaan. Setiap fitur, setiap
abstraksi, setiap pola harus membenarkan keberadaannya.

- **Action di atas Service.** Kelas dengan tanggung jawab tunggal dan satu metode `execute()` lebih
  mudah dipahami, diuji, dan diubah dibandingkan kelas Service dengan banyak metode.
- **Kolokasi modul di atas pelapisan datar.** Menyimpan semua yang berkaitan dengan "Enrollment" di
  satu tempat lebih sederhana daripada menyebarkannya ke delapan direktori.
- **Impor langsung di atas pemisahan yang berbelit.** Impor kelas dari modul lain secara langsung.
  Tambahkan event, kontrak, atau service provider hanya ketika Anda punya kebutuhan yang terbukti.
- **Cukup baik hari ini di atas sempurna pekan depan.** Bangun versi sederhana, rilis, iterasi.
  Hindari generalitas spekulatif.

## 4. Kemudahan Pemeliharaan adalah Fitur

Basis kode akan hidup lebih lama daripada pengembang aslinya. Sekolah menjalankan program PKL tahun
demi tahun. Perangkat lunak harus dapat dipelihara oleh tim baru dengan masa adaptasi minimal.

Artinya:

- **Konvensi yang konsisten.** Satu cara menulis Action, satu cara mendefinisikan Enum, satu cara
  menyusun modul. Tanpa kejutan.
- **Kode yang mendokumentasikan diri.** Type hint, penamaan deskriptif, struktur terkolokasi. Kode
  terbaca seperti spesifikasi.
- **Dokumentasi menyeluruh.** Setiap modul punya dokumen gambaran umum dan referensi. Setiap
  keputusan arsitektur punya ADR. Dokumentasi bersifat otoritatif — kode mengimplementasikan apa
  yang dijelaskan dokumentasi.
- **Stabilitas framework.** Kami mengikuti rilis Laravel LTS, tetapi kami mematok versi dan menguji
  peningkatan secara menyeluruh.

## 5. Kedaulatan Data dan Kepercayaan

Sekolah mempercayakan data siswa yang sensitif kepada Internara — nilai, catatan kehadiran, laporan
insiden, informasi pribadi. Kepercayaan ini tidak dapat ditawar.

- **Data berada di infrastruktur sekolah.** Tanpa telemetri, tanpa pelaporan penggunaan, tanpa
  panggilan API eksternal untuk fungsionalitas inti.
- **Privasi sejak desain.** Penyamaran PII, kebijakan retensi yang dapat dikonfigurasi, alur kerja
  penghapusan yang sesuai GDPR.
- **Kepatuhan yang transparan.** Proxy lintas peran, jejak audit, log aktivitas yang tidak dapat
  diubah — setiap tindakan dapat dilacak dan dipertanggungjawabkan.
- **Tanpa pola gelap.** Tanpa upsell, tanpa pengumpulan data, tanpa ping analitik.

## 6. Dibangun untuk Pendidikan Indonesia, Dirancang untuk Adaptasi

Internara dibangun khusus untuk program PKL (_Praktik Kerja Lapangan_) SMA/SMK di Indonesia, dengan
kebutuhan spesifiknya:

- Pendampingan dwiperan (guru + pembimbing industri)
- Penilaian berbasis kompetensi dengan rubrik
- Penempatan multi-perusahaan per angkatan siswa
- Penerbitan sertifikat dengan verifikasi QR
- Kepatuhan terhadap regulasi pendidikan Indonesia (retensi catatan 5 tahun, integrasi NPSN)

Namun arsitekturnya — MVC berbasis Action, kolokasi modul, infrastruktur progresif — dirancang untuk
beradaptasi. Polanya universal, meskipun domain awalnya spesifik.

## 7. Kemajuan, Bukan Kesempurnaan

Kami merilis secara berkelanjutan. Setiap iterasi membuat sistem lebih baik bagi sekolah, siswa,
guru, dan pembimbing.

- **Pragmatis di atas dogmatis.** Dependensi framework di dalam entity? Diizinkan bila praktis.
  Impor langsung lintas modul? Dianjurkan. Kemurnian sempurna kurang penting dibandingkan kecepatan
  pengembang.
- **Migrasi bertahap.** Setiap pola punya jalur migrasi yang jelas dari sederhana ke canggih. Jangan
  pernah membiarkan yang sempurna menjadi musuh yang baik.
- **Teruji, bukan teoretis.** Setiap perubahan didukung oleh tes. Jika tidak diuji, berarti tidak
  berfungsi. Jika tidak berfungsi, tidak dirilis.
- **Kontribusi terbuka.** Proyek menyambut kontribusi yang selaras dengan prinsip-prinsip ini.
  Dokumentasi sama pentingnya dengan kode. Tes sama pentingnya dengan fitur.

---

## Nilai Inti

| Nilai                    | Dalam Praktik                                                                       |
| ------------------------ | ----------------------------------------------------------------------------------- |
| **Keterjangkauan**       | Berjalan di shared hosting $5/bulan, bawaan SQLite, tanpa layanan eksternal wajib   |
| **Kesederhanaan**        | Action bertanggung jawab tunggal, modul terkolokasi, impor langsung                 |
| **Kemudahan Pemeliharaan** | Konvensi konsisten, dokumentasi menyeluruh, kode yang mendokumentasikan diri      |
| **Kepercayaan**          | Self-hosted, kedaulatan data, penyamaran PII, jejak audit yang tidak dapat diubah   |
| **Pragmatisme**          | Dependensi framework di entity diizinkan, jalur migrasi bertahap, rilis berkelanjutan |
| **Kualitas**             | Didukung tes, dokumentasi lebih dulu, ditinjau kode                                 |

---

## Yang Tidak Kami Lakukan

- Kami tidak menawarkan versi SaaS multi-tenant.
- Kami tidak mengumpulkan telemetri atau data penggunaan.
- Kami tidak melakukan upsell fitur atau mengunci fungsionalitas di balik tingkatan.
- Kami tidak mengorbankan keandalan demi kemurnian arsitektur.
- Kami tidak merilis kode yang tidak teruji.
- Kami tidak menghentikan pola tanpa jalur migrasi yang terdokumentasi.

---

## Referensi Cepat

- [index.md](index.md) — Indeks dokumentasi
- [issue spesifikasi](https://github.com/reasvyn/internara/issues?q=label%3Aspec) — Issue spesifikasi fitur (#442–#509)
- [adr/index.md](adr/index.md) — Catatan Keputusan Arsitektur
- [guides/index.md](guides/index.md) — Indeks panduan
- [refs/modules/index.md](refs/modules/index.md) — Indeks referensi modul
