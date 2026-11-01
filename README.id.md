<p align="center">
    <img src="https://img.shields.io/badge/version-0.17.0-blue?style=flat-square" alt="Version 0.17.0">
    <img src="https://github.com/reasvyn/internara/actions/workflows/release.yml/badge.svg" alt="CI">
    <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
    <img src="https://img.shields.io/badge/Livewire-4-fb70a9?style=flat-square&logo=livewire" alt="Livewire 4">
</p>

# Internara — Sistem Manajemen Praktik Kerja Lapangan

Platform self-hosted, single-tenant untuk mengelola program praktik kerja lapangan wajib (PKL —
_Praktik Kerja Lapangan_) di sekolah menengah kejuruan (SMK) Indonesia. Berlisensi MIT, dirancang
untuk berjalan sepenuhnya di infrastruktur milik sekolah — dari shared host $5/bulan dengan SQLite
hingga tumpukan Docker Compose — tanpa biaya berulang, kedaulatan data penuh, dan ketahanan offline.

> Terjemahan bahasa Indonesia dari [`README.md`](README.md).

---

## Mengapa

SMK di Indonesia diwajibkan secara hukum (Kemendikbud) menyelenggarakan PKL: 3–6 bulan praktik kerja
terbimbing di perusahaan mitra (DUDI). Sekolah menengah hingga besar mengoordinasikan **500–1.000
siswa di 150–300 perusahaan per periode** — saat ini dikelola dengan kertas, Excel, WhatsApp, dan
email ad-hoc. Penyusunan kartu nilai saja memakan 2–3 pekan per angkatan, dan akreditasi menuntut
catatan siap audit yang tidak dapat dihasilkan secara andal oleh alur kerja kertas.

Internara menggantikan alur kerja tersebut dengan:

- **Sumber kebenaran tunggal** — admin, guru, pembimbing, dan siswa berbagi data real time
- **Penegakan alur kerja otomatis** — kapasitas slot, jendela kehadiran, dan kelengkapan evaluasi
  adalah aturan sistem, bukan kedisiplinan spreadsheet
- **Catatan siap audit** — kehadiran bertanda GPS, logbook terkirim yang tidak dapat diubah, log
  aktivitas pengguna/IP, sertifikat terverifikasi QR
- **Pelaporan seketika** — kartu nilai disusun dalam hitungan detik, verifikasi sertifikat via pindai QR
- **Kedaulatan data** — self-hosted, tanpa ketergantungan cloud, berjalan di LAN sekolah tanpa internet
- **Fondasi operasional** — alur kerja pencadangan, antrean, pemeliharaan, GDPR, pengarsipan,
  observabilitas, dan pemulihan sudah terpasang di platform

Telaah mendalam: [analisis masalah & persona](docs/id/project-vision.md) · [filosofi desain](docs/id/philosophy.md)

## Fitur

- **Siklus hidup siswa** — wizard pendaftaran, penempatan perusahaan berbasis slot, permohonan pindah
- **Operasional harian** — kehadiran bertanda lokasi, logbook reflektif dua pembimbing, persetujuan izin
- **Penilaian** — rubrik kompetensi, penilaian multi-evaluator, agregasi nilai berbobot
- **Kemitraan** — registri perusahaan, siklus hidup MoU, pelacakan kuota slot dengan peringatan kedaluwarsa
- **Sertifikasi** — template PDF, penerbitan massal, verifikasi kode QR
- **Pelaporan** — penyusunan kartu nilai akhir dengan pengesahan koordinator, ekspor audit
- **Manajemen akun** — RBAC dengan 5 peran datar, siklus hidup akun 8 status, mekanisme pemulihan
- **Lokalisasi** — basis kode Inggris, UI dua bahasa (EN/ID)
- **Observabilitas** — Laravel Pulse, SmartLogger dua kanal (file + DB)

## Kebutuhan

- PHP **8.4+** dengan `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`,
  `pdo`, `tokenizer`, `xml`, `zip`
- Composer **2.x**, Node.js **20+**, npm **10+**
- Basis data: SQLite (tanpa konfigurasi), MySQL 8+, MariaDB 10.6+, atau PostgreSQL 15+

## Instalasi

**Satu baris (disarankan):**

```bash
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash
# → git clone → composer install → npm install && npm run build → php artisan setup:install
cd internara
composer run dev
```

Direktori / branch khusus / diteruskan ke `setup:install`:

```bash
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash -s -- --dir my-pkl --branch main -- --force
# alternatif lokal di dalam checkout yang sudah ada:
bash scripts/install.sh --help
```

**Manual:**

```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

# Untuk SQLite: set DB_CONNECTION=sqlite dan buat berkasnya
touch database/database.sqlite

# Mengaudit env, menjalankan migrasi, mengisi bawaan — menghasilkan URL wizard penyiapan bertanda tangan
php artisan setup:install

# Menjalankan app + queue worker + log + Vite secara bersamaan
composer run dev
```

Buka URL bertanda tangan yang dicetak oleh `setup:install` dan selesaikan wizard penyiapan 6 langkah.
Verifikasi dengan `php artisan system:health`. Penelusuran lengkap termasuk konfigurasi
MySQL/MariaDB/PostgreSQL: [`docs/id/getting-started.md`](docs/id/getting-started.md).

## Arsitektur

MVC berbasis Action dengan pengirisan vertikal: 19 modul (17 modul bisnis ditambah UI dan Core)
memiliki tumpukan lengkapnya sendiri (Model, Action, Livewire, Event, Policy) yang terkolokasi di
bawah `app/Modules/`. Setiap mutasi mengikuti satu jalur:

```text
Livewire → Command Action → Entity (aturan bisnis) → Model → DB
```

Platform mencakup batas modul, aturan lapisan, dan peta dependensi lengkap yang dipelihara di
[`docs/id/architecture.md`](docs/id/architecture.md) dan
[`docs/refs/modules/index.md`](docs/refs/modules/index.md).

Pola kunci: Action Triad (Command/Read/Process), Entity tak berubah yang membawa aturan bisnis, batas
DTO antar lapisan, state machine yang digerakkan enum. Model lengkap, diagram lapisan, dan referensi
pola: [`docs/id/architecture.md`](docs/id/architecture.md).

## Deployment

| Model                | Terbaik untuk                        | Tumpukan                                                          |
|----------------------|--------------------------------------|-------------------------------------------------------------------|
| **Shared hosting**   | Deployment beranggaran               | PHP-FPM + SQLite, antrean sinkron, endpoint cron                  |
| **VPS / VM**         | Sebagian besar sekolah (**disarankan**)| PHP-FPM + Nginx, SQLite/MySQL, Redis opsional                   |
| **Docker Compose**   | Operator yang paham teknis           | satu container PHP-FPM + SQLite, nginx host FastCGI               |

SQLite adalah bawaan tanpa konfigurasi; beralih ke MySQL/MariaDB/PostgreSQL cukup mengubah `.env`,
tanpa kode. Checklist produksi dan panduan per jalur:
[`docs/guides/infra/deployment.md`](docs/guides/infra/deployment.md).

## Lokalisasi

Dua bahasa EN/ID: Inggris untuk kode dan dokumentasi, Indonesia sebagai bahasa UI yang diutamakan.
Setiap string yang dilihat pengguna melewati `__()`; locale dapat diubah melalui Pengaturan. Istilah
domain (NISN, NPSN, DUDI, PKL, …) dipetakan di
[glosarium spesifikasi payung](https://github.com/reasvyn/internara/issues/442) (`[QLHDO]`, `label:spec`).

## Dokumentasi

Semua dokumentasi berada di [`docs/`](docs/id/index.md):

| Topik                | Dokumen                                                          |
|----------------------|------------------------------------------------------------------|
| Memulai              | [`docs/id/getting-started.md`](docs/id/getting-started.md)       |
| Arsitektur           | [`docs/id/architecture.md`](docs/id/architecture.md)             |
| Gambaran modul       | [`docs/refs/modules/index.md`](docs/refs/modules/index.md)       |
| Konvensi pengodean   | [`docs/id/conventions.md`](docs/id/conventions.md)               |
| Deployment           | [`docs/guides/infra/deployment.md`](docs/guides/infra/deployment.md) |
| Panduan pengujian    | [`docs/guides/infra/testing.md`](docs/guides/infra/testing.md)   |
| Indeks dokumen penuh | [`docs/id/index.md`](docs/id/index.md)                           |

## Pengujian & Kualitas

```bash
composer run test      # Rangkaian tes Pest penuh
composer run quality   # Lint + tes
vendor/bin/pint --dirty --format agent  # Perapi gaya kode
```

Pemindai arsitektur dan dokumentasi tetap tersedia melalui `composer run arch`; temuannya harus
ditinjau sebelum rilis.

Pest 4 dengan cakupan fitur + unit per Action, Laravel Pint. Perkakas pemindai arsitektur
(`scripts/py/scan_*.py`): [`docs/guides/infra/tools.md`](docs/guides/infra/tools.md).

## Status Proyek

**v0.17.0** (`composer.json`) / **v0.17.0** (`package.json`) — seluruh 19 modul (17 bisnis + UI +
Core) memiliki tumpukan lengkap; masalah yang diketahui dilacak di
[GitHub Issues](https://github.com/reasvyn/internara/issues).

## Berkontribusi

Kontribusi diterima — perbaikan salah ketik, tes, modul baru. Baca
[CONTRIBUTING.id.md](CONTRIBUTING.id.md) untuk percabangan, format commit
(`type(scope): description`), dan gerbang kualitas; pindai sekilas
[`docs/id/architecture.md`](docs/id/architecture.md) dan
[`docs/id/conventions.md`](docs/id/conventions.md) sebelum PR pertama Anda. Semua peserta harus
mematuhi [Kode Etik](CODE_OF_CONDUCT.id.md). Issue ramah pemula diberi label di
[GitHub Issues](https://github.com/reasvyn/internara/issues).

Bahasa: Inggris untuk kode, komentar, commit, dan dokumentasi; Indonesia hanya di `lang/id/`.

## Keamanan

Laporkan kerentanan secara privat — lihat [SECURITY.id.md](SECURITY.id.md) atau kirim email ke
[reasvyn@gmail.com](mailto:reasvyn@gmail.com). Jangan membuka issue publik untuk laporan keamanan.

## Lisensi

[MIT](LICENSE) © 2025–2026 Reas Vyn. Sekolah dapat melakukan fork, menyesuaikan (mis. integrasi
Dapodik, template sertifikat regional), dan men-deploy secara bebas; kontribusi ke hulu bermanfaat
bagi setiap SMK.
