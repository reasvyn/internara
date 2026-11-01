# Memulai — Dari Kloning hingga Aplikasi Berjalan

## Deskripsi

Penelusuran menyeluruh dari mengkloning repositori hingga menyelesaikan wizard penyiapan dan login
pertama.

## Prasyarat

| Kebutuhan  | Pengembangan        | Produksi                          |
| ---------- | ------------------- | --------------------------------- |
| PHP        | 8.5.0+              | 8.5.0+                            |
| Composer   | 2.5+                | 2.5+                              |
| Node.js    | 20+                 | 20+ (hanya build)                 |
| NPM        | 10+                 | 10+ (hanya build)                 |
| Basis data | SQLite (bawaan)     | MySQL 8+ / MariaDB 10.6+ (shared) |
| Antrean    | driver `database`   | `sync` (shared) / Redis (VPS+)    |
| Cache      | driver `database`   | `file` (shared) / Redis (VPS+)    |
| Web server | `php artisan serve` | Apache / Nginx (shared hosting)   |

**Ekstensi PHP yang diperlukan:** `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`,
`openssl`, `pdo`, `tokenizer`, `xml`, `zip`, ditambah driver basis data yang sesuai dengan mesin
pilihan Anda (`pdo_sqlite`, `pdo_mysql`, atau `pdo_pgsql`).

---

## Mulai Cepat (Pengembangan)

### Opsi A — Pemasang satu baris (disarankan)

```bash
# Mengambil scripts/install.sh dari GitHub, lalu: git clone → composer install → npm install && npm run build → php artisan setup:install
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash

# Dengan opsi (direktori / branch khusus / diteruskan ke setup:install):
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash -s -- --dir my-pkl --branch main -- --force

```

Pemasang akan mengkloning ke `./internara/`, memasang dependensi, membangun aset, dan menjalankan
`setup:install`. Salin URL bertanda tangan yang dicetaknya, lalu jalankan server pengembangan:

```bash
cd internara
composer run dev   # serve + queue + logs + vite

```

Buka URL bertanda tangan tersebut di browser untuk menyelesaikan wizard penyiapan 6 langkah.
`scripts/install.sh` juga dapat dijalankan secara lokal di dalam checkout yang sudah ada:
`bash scripts/install.sh --help`.

### Opsi B — Manual

```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
npm install && npm run build
php artisan setup:install

# Salin URL bertanda tangan dari keluaran, lalu jalankan server
php artisan serve
# Di terminal lain:
php artisan queue:work

```

Kedua opsi menghasilkan hasil yang sama; skrip satu baris hanya mengotomatiskan langkah manual.

---

## Panduan Langkah demi Langkah

### Langkah 1: Kloning Repositori

Satu baris (melakukan kloning + semua langkah di bawah melalui `scripts/install.sh`):

```bash
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash
# atau: bash scripts/install.sh   (ketika sudah berada di dalam checkout)

```

Alternatif manual:

```bash
git clone https://github.com/reasvyn/internara.git
cd internara

```

### Langkah 2: Pasang Dependensi PHP

```bash
# Pengembangan
composer install

# Produksi
composer install --optimize-autoloader --no-dev

```

### Langkah 3: Bangun Aset Frontend

```bash
npm install
npm run build

# Untuk pengembangan dengan hot module replacement:
npm run dev

```

### Langkah 4: Jalankan Pemasang

Perintah `setup:install` menangani seluruh instalasi teknis:

```bash
php artisan setup:install

```

Perintah ini akan:

1. **Mengaudit lingkungan** — memeriksa versi PHP (≥ 8.5.0), ekstensi yang diperlukan, izin
   penyimpanan, konektivitas basis data, dan perkakas CLI. Berhenti dengan keluaran rinci jika ada
   pemeriksaan yang gagal.
2. **Menyediakan sistem** — membuat `.env` dari `.env.example` bila belum ada, menghasilkan
   `APP_KEY`, menjalankan migrasi basis data, membuat symlink penyimpanan, dan mengisi data awal.
3. **Menghasilkan URL penyiapan** — menghasilkan URL bertanda tangan sekali pakai dengan token yang
   kedaluwarsa, dibatasi pada `APP_URL` Anda:

    ```
    https://internara.sekolah.sch.id/setup?setup_token=A1B2C3
    ```

Setelah perintah selesai, Anda akan melihat URL tercetak di terminal beserta token dan waktu
kedaluwarsanya (bawaan: 60 menit).

> **Opsi:**
>
> - `--check-only` — jalankan audit lingkungan tanpa memasang
> - `--force` — pasang ulang meskipun sudah terpasang (hanya untuk pengembangan)

### Langkah 5: Jalankan Proses Latar Belakang

```bash
# Pengembangan — jalankan di terminal terpisah:
php artisan serve       # Web server
php artisan queue:work  # Queue worker

```

Atau gunakan perintah all-in-one:

```bash
composer run dev

```

**Produksi pada shared hosting** menggunakan antrean sinkron (tanpa worker) dan cron berbasis
webhook. **Produksi pada VPS** memerlukan Supervisor (atau systemd) untuk worker antrean, cron
sistem, dan opsional Reverb untuk dukungan WebSocket. Lihat [Deployment](guides/infra/deployment.md).

### Langkah 6: Selesaikan Wizard Penyiapan

Buka URL bertanda tangan dari Langkah 4 di browser. URL tersebut menyertakan parameter `setup_token`
yang mengotorisasi akses. Wizard 6 langkah memandu Anda melalui:

| Langkah | Yang Anda Konfigurasi                          |
| ------- | ---------------------------------------------- |
| 1       | Tinjauan audit lingkungan                      |
| 2       | Akun super admin (email + kata sandi)          |
| 3       | Detail sekolah (nama, NPSN, alamat, email)     |
| 4       | Jurusan / program studi pertama                |
| 5       | Tinjauan akhir dan konfirmasi                  |
| 6       | Penyiapan selesai dengan kunci pemulihan       |

Ikuti panduan [Wizard Penyiapan](guides/setup-wizard.md) untuk penelusuran rinci.

---

## Referensi Perintah

| Perintah                                | Tujuan                                                             |
| --------------------------------------- | ------------------------------------------------------------------ |
| `php artisan setup:install`             | Audit, penyediaan, dan pembuatan URL penyiapan                     |
| `php artisan setup:reset-token`         | Hasilkan ulang token penyiapan (sebelum instalasi)                 |
| `php artisan admin:recover`             | Pulihkan akses super admin (mendeteksi kunci otomatis dari berkas penyimpanan) |
| `php artisan admin:recover --key=<key>` | Pulihkan akses super admin (penimpaan kunci secara manual)         |
| `php artisan admin:recovery-path`       | Tampilkan lokasi berkas kunci pemulihan                            |
| `php artisan admin:recovery-show`       | Tampilkan kunci pemulihan tersimpan (dengan konfirmasi)            |
| `php artisan system:health`             | Verifikasi semua kebutuhan sistem                                  |

---

## Langkah Berikutnya

| Dokumen                                            | Cakupannya                                                  |
| -------------------------------------------------- | ----------------------------------------------------------- |
| [Instalasi](guides/installation.md)                | Opsi deployment rinci, konfigurasi server, pemecahan masalah |
| [Wizard Penyiapan](guides/setup-wizard.md)         | Penelusuran lengkap seluruh 6 langkah wizard                |
| [Pasca-Penyiapan](guides/post-setup.md)            | Tindakan pertama setelah wizard selesai                     |
| [Arsitektur](architecture.md)                      | Desain sistem, modul, lapisan                               |
| [Infrastruktur](guides/infra/infrastructure.md)    | Opsi deployment, proses latar belakang, penyimpanan         |

---

## Referensi Cepat

- [Indeks dokumentasi](index.md) — Telusuri semua dokumentasi
- [Issue spesifikasi fitur](https://github.com/reasvyn/internara/issues?q=label%3Aspec) — Semua spesifikasi fitur (#442–#509)
- [Catatan Keputusan Arsitektur](adr/index.md) — Keputusan kunci beserta alasannya
- [Indeks panduan](guides/index.md) — Panduan operasional
- [Indeks referensi modul](refs/modules/index.md) — Referensi API modul
