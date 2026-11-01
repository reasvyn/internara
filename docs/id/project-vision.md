# Visi Proyek — Ke Mana Internara Menuju

## Deskripsi

Arah Internara — mengapa ia ada, ke mana ia menuju, dan bagaimana setiap keputusan dievaluasi.
Dokumen ini adalah bintang penuntun bagi kontributor, pemelihara, dan sekolah yang mengadopsi
platform. Ringkasan produk yang padat tersedia di `README.md`; prinsip sehari-hari tersedia di
`philosophy.md`; berkas ini menjawab *bagaimana wujud masa depan* dan *bagaimana kami tahu kami telah
tiba di sana*.

## Pernyataan Visi

> **Setiap sekolah kejuruan di Indonesia menjalankan PKL wajibnya dengan keyakinan yang sama seperti
> universitas berdana besar — pada infrastruktur yang sepenuhnya mereka kendalikan, dengan catatan
> yang siap audit, tanpa tumpukan kertas, dan tanpa menuntut guru menjadi administrator.**

Internara bukan sistem manajemen sekolah. Ia adalah sistem *tujuan tunggal yang mendalam* untuk satu
alur kerja yang diwajibkan hukum (PKL) yang saat ini menghabiskan 60–80% waktu guru untuk
administrasi dan 2–3 pekan per angkatan untuk penyusunan kartu nilai. Ketika PKL terselesaikan,
sekolah merebut kembali pekan-pekan itu untuk pendampingan.

## Misi

1. **Membuat PKL dapat dioperasikan dalam skala besar** — 500–1.000 siswa × 90 hari × 150–300
   perusahaan per periode tanpa konflik versi Excel, lembar kehadiran yang hilang, atau keterlambatan
   penemuan logbook yang belum diisi.
2. **Membuat kepatuhan menjadi mudah** — Permen Pendidikan mewajibkan catatan PKL yang dapat
   diaudit (kehadiran bertanda tangan, logbook reflektif, rubrik terstandardisasi, sertifikat yang
   dapat diverifikasi). Internara menghasilkannya sebagai produk sampingan dari penggunaan normal,
   bukan sebagai pekerjaan tambahan.
3. **Membuat data berdaulat** — Self-hosted, single-tenant, tanpa biaya berulang. Tanpa telemetri,
   tanpa ketergantungan cloud, tanpa keterikatan vendor. Sekolah di kota besar dengan staf TI dan
   sekolah di distrik terpencil dengan satu komputer bersama sama-sama menjalankannya di shared host
   $5/bulan (SQLite, cache file, antrean sinkron) dan bertumbuh ke MySQL/Redis/worker hanya ketika
   mereka membutuhkannya.

## Pilar Strategis

| Pilar | Artinya dalam praktik | Bagaimana kami mengukurnya |
|-------|-----------------------|----------------------------|
| **P1 — Kesetiaan Alur Kerja** | Mencerminkan operasional sekolah nyata (kuota MoU DUDI, dua pembimbing, permintaan pindah penempatan, persetujuan ketidakhadiran) alih-alih memaksa sekolah beradaptasi dengan perangkat lunak | Penelusuran persona lolos tanpa langkah "akal-akalan"; cakupan UC spesifikasi QLHDO = 100% |
| **P2 — Operasional Tanpa Admin** | Tindakan guru dan pembimbing cukup satu ketukan, tahan offline, dan menjelaskan diri sendiri; dasbor koordinator menampilkan tingkat penempatan, kapasitas slot, dan anomali secara real time | Penyusunan kartu nilai 2–3 pekan → seketika; tinjauan logbook <2 menit per entri |
| **P3 — Keamanan & Kepercayaan Dulu (S1)** | Otorisasi di setiap lapisan, penyamaran PII, log aktivitas yang tidak dapat diubah, kehadiran bertanda GPS, sertifikat terverifikasi QR | `scan_security.py` 0 temuan tinggi, `scan_violations.py` C1–C8 bersih; audit eksternal 0 kritis |
| **P4 — Kemudahan Pemeliharaan Berkelanjutan (S2)** | Kolokasi modul (19 modul memiliki tumpukan teknologinya sendiri), Action Triad (satu `execute()`), batas Entity/DTO — kontributor baru dapat merilis fitur setelah membaca `architecture.md` + `conventions.md` | `scan_class_contracts.py` 0 temuan tinggi, bus factor >2 per modul |
| **P5 — Skalabilitas Pragmatis (S3)** | Single-tenant (tanpa beban tenant-ID), triad Action terinspirasi CQRS menjaga jalur baca/tulis tetap terpisah — sistem "tidak runtuh seiring penambahan fitur" | Menangani 1.000 siswa aktif / 300 perusahaan pada VPS 1 vCPU / RAM 1,9G (produksi saat ini) tanpa saturasi antrean (`pm.max_children=4`) |

## Persona — Wujud Masa Depan

| Persona | Hari ini (kertas/Excel/WhatsApp) | Dengan Internara (visi) |
|---------|----------------------------------|--------------------------|
| **Peserta PKL (16–18 tahun, mobile-first)** | Mendaftar lewat kertas, kehilangan logbook WhatsApp, menunggu nilai berminggu-minggu | Mendaftar lewat wizard, kehadiran GPS, logbook reflektif dengan umpan balik pembimbing <48 jam, nilai/sertifikat dapat diverifikasi seketika |
| **Sekolah (Admin 2–5, Guru 10–30)** | 60–80% waktu untuk administrasi, 2–3 pekan menyusun kartu nilai | Dasbor real time, CSV massal, peringatan kedaluwarsa MoU otomatis, proxy lintas peran ketika pembimbing tidak aktif, pengesahan kartu nilai dalam satu sesi |
| **Perusahaan (Pembimbing, akses mingguan)** | Tidak aktif, evaluasi di kertas, verifikasi sertifikat lewat telepon | Verifikasi satu ketukan, rubrik kompetensi terpandu, pindai QR menggantikan panggilan telepon |

Semua persona berbagi *satu* sumber kebenaran real time — admin, guru, pembimbing, dan siswa melihat
penempatan, kehadiran, dan tingkat penyelesaian yang sama.

## Batasan — Apa yang Tidak Akan Kami Bangun

Kejelasan atas non-tujuan mencegah perluasan cakupan dan menjaga basis kode tetap mudah dipelihara.

| Tidak Akan Dibangun | Mengapa (dan apa yang dipakai sebagai gantinya) |
|---------------------|-------------------------------------------------|
| SaaS multi-tenant, penagihan, perutean tenant | Single-tenant adalah *fitur* — kedaulatan, tanpa beban tenant-ID, model mental sederhana |
| HR/ATS umum, penggajian, jalur perekrutan pascakelulusan | Cakupan berakhir pada penyelesaian PKL + sertifikat; integrasikan lewat CSV, bukan lewat platform |
| Obrolan real time / WebSocket | Hanya notifikasi terstruktur + email — menghindari pembengkakan infrastruktur dan beban moderasi |
| Aplikasi mobile native | Web responsif (Livewire + Alpine) — tanpa fragmentasi app store |
| Sinkronisasi langsung basis data Dapodik/NSP | Hanya impor/ekspor CSV — permukaan integrasi minimal, stabilitas maksimal |
| Telemetri, ping analitik, upsell, fitur bertingkat | Tanpa pola gelap — MIT, tanpa langganan, akses basis data penuh |

Setiap usulan yang melanggar batasan ini ditutup dengan rujukan ke bagian ini.

## Metrik Keberhasilan (bagaimana kami tahu kami telah tiba)

- **Operasional:** Median penyusunan kartu nilai <5 menit (dari 2–3 pekan); peringatan kedaluwarsa
  MoU 30/14/7 hari sebelumnya; anomali kehadiran ditandai <24 jam
- **Adopsi:** 50 sekolah produksi tanpa multi-tenant; 0 temuan kritis `scan_security.py` pada setiap
  rilis
- **Kualitas:** Rangkaian tes tetap hijau dan cakupan FR `specs/*` = 100% (tanpa celah spesifikasi,
  tanpa tes yatim)
- **Kepercayaan:** 100% sertifikat dapat diverifikasi QR; setiap mutasi memiliki entri
  `activity_log` (SmartLogger, PII tersamar, `systemOnly`); retensi PII 5 tahun lalu dihapus sesuai
  GDPR
- **Keberlanjutan:** Kontributor baru merilis `feat(enrollment):` dalam <1 pekan setelah membaca
  `architecture.md` + `conventions.md` + `modules/index.md`; bus factor ≥2 per modul yang sehat

Metrik ditinjau setiap kuartal; berkas ini diperbarui ketika metrik berubah, bukan ketika kode
berubah.

## Kompas Keputusan

Ketika permintaan fitur, refaktor, atau kompromi bersifat ambigu, terapkan secara berurutan:

1. **Apakah ini membantu sekolah menjalankan PKL lebih baik tanpa menambah administrasi?** Jika
   tidak, tutup.
2. **Apakah ini mempertahankan self-hosting di shared hosting $5 secara bawaan?** Jika ia
   membutuhkan Redis/S3/MySQL agar berfungsi, jadikan peningkatan progresif, bukan dependensi keras.
3. **Apakah ini menjaga modul tetap terkolokasi dan Action tetap bertujuan tunggal?** Jika ia
   memperkenalkan God class lintas sektor atau cara kedua untuk melakukan hal yang sama, ekstrak atau
   gunakan kembali.
4. **Apakah ia punya ID kebutuhan spesifikasi (FR/NFR/UC) dan tes yang melacaknya?** Jika tidak,
   tulis spesifikasinya lebih dulu (doktrin spesifikasi-dulu).
5. **Apakah ia menjaga data di infrastruktur sekolah dengan jejak audit?** Jika ia membocorkan PII
   atau melewati pencatatan log, ia tidak dirilis.

Untuk kasus ambigu, buka Discussion, tautkan berkas ini, dan catat keputusannya sebagai ADR di
`docs/adr/`.

## Peta Jalan — Sekarang vs Berikutnya (tautkan, jangan duplikasi)

- **Sekarang (`v0.17.0 Stabilisasi`):** Perbaiki crash runtime P0 di `Assessment`, `Certification`,
  `Document` (lihat `README.md#Project Status` + tingkatan kesehatan modul), tingkatkan cakupan tes
  domain, sinkronkan dokumentasi. Dilacak di GitHub Issues berlabel `critical`/`high`.
- **Berikutnya:** Pemurnian Reports (hanya kartu nilai, tesis tetap di `Assignment`), penguatan kuota
  slot kemitraan, deduplikasi log supervisi jurnal — masing-masing dengan ID spesifikasi di issue
  spesifikasi (`label:spec`, #442–#509).
- **Nanti:** Penguatan ekspor Dapodik, fork regional PKL Dinas, kerangka modul evaluasi (`Evaluation`
  masih `Skeleton` saat ini).

Urutan pembangunan terperinci tersedia di issue spesifikasi (`label:spec`, #442–#509) dan
`README.md#What's Next`; berkas ini tidak menduplikasinya.

## Di Mana Menemukannya

| Kebutuhan | Dokumen |
|-----------|---------|
| Cakupan produk, persona, batasan, deployment, lanskap modul | `README.md` |
| Prinsip pemandu, nilai, "yang tidak kami lakukan" secara mendalam | `philosophy.md` |
| Kebutuhan (FR/NFR/UC) yang dilacak visi | Issue spesifikasi (`label:spec`, #442–#509) → [QLHDO](https://github.com/reasvyn/internara/issues/442) |
| Arsitektur yang membuat visi dapat dibangun | `architecture.md` (4 lapisan, Action Triad) |
| Bagaimana kami membangun & merilis (gerbang kualitas, tag versi) | `AGENTS.md` (alur kerja 5 langkah), `.github/workflows/release.yml` (pipeline rilis 4 tahap berbasis tag), `infrastructure/deployment.md` |
| Kesehatan saat ini & di mana berkontribusi | Tingkatan kesehatan modul (memori agen) + `CONTRIBUTING.md` |

## Referensi Cepat

- [Indeks dokumentasi](index.md) — Telusuri semua dokumentasi
- [Issue spesifikasi fitur](https://github.com/reasvyn/internara/issues?q=label%3Aspec) — Semua spesifikasi fitur (#442–#509)
- [Catatan Keputusan Arsitektur](adr/index.md) — Keputusan kunci beserta alasannya
- [Indeks panduan](guides/index.md) — Panduan operasional
- [Indeks referensi modul](refs/modules/index.md) — Referensi API modul
