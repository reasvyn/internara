# Berkontribusi ke Internara

Terima kasih atas minat Anda untuk berkontribusi! Panduan ini mencakup semua yang diperlukan agar
pull request pertama Anda dapat digabungkan — dari penyiapan lingkungan hingga ekspektasi tinjauan.

> Terjemahan bahasa Indonesia dari [`CONTRIBUTING.md`](CONTRIBUTING.md).

---

## Kode Etik

Dengan berpartisipasi dalam proyek ini, Anda setuju untuk mematuhi
[CODE_OF_CONDUCT.id.md](CODE_OF_CONDUCT.id.md). Laporkan perilaku yang tidak dapat diterima ke
[reasvyn@gmail.com](mailto:reasvyn@gmail.com).

---

## Cara Berkontribusi

- **Laporkan bug** — buka issue dengan langkah reproduksi, perilaku yang diharapkan vs. aktual, dan
  versi yang terdampak
- **Usulkan fitur** — buka issue yang menjelaskan masalahnya terlebih dahulu, baru solusi yang
  diusulkan
- **Perbaiki dokumentasi** — perbaikan salah ketik, klarifikasi, dan panduan baru selalu diterima
- **Kontribusikan kode** — pilih issue terbuka atau usulkan satu; lihat
  [GitHub Issues](https://github.com/reasvyn/internara/issues) untuk issue ramah pemula

Buka issue **sebelum** memulai pekerjaan signifikan (fitur baru, refaktor, perubahan arsitektur) agar
pendekatannya dapat didiskusikan lebih dulu.

---

## Penyiapan Pengembangan

```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan setup:install
composer run dev
```

`setup:install` mengaudit lingkungan Anda, menjalankan migrasi, mengisi data bawaan, dan menghasilkan
URL penyiapan bertanda tangan — buka di browser untuk menyelesaikan wizard 6 langkah. Verifikasi dengan
`php artisan system:health`.

Prasyarat: PHP 8.4+, Composer 2.x, Node.js 20+, npm 10+ (daftar lengkap di
[`docs/id/getting-started.md`](docs/id/getting-started.md)).

---

## Yang Harus Dibaca Dulu

| Topik                                              | Dokumen                                                          |
|----------------------------------------------------|------------------------------------------------------------------|
| Arsitektur — model 4 lapisan, Action Triad          | [`docs/id/architecture.md`](docs/id/architecture.md)             |
| Konvensi pengodean & invarian (C1–C8, D1–D6)        | [`docs/id/conventions.md`](docs/id/conventions.md)               |
| Peta modul                                          | [`docs/refs/modules/index.md`](docs/refs/modules/index.md)       |
| Spesifikasi fitur (sumber kebenaran kebutuhan)      | [GitHub Issues `label:spec`](https://github.com/reasvyn/internara/issues?q=label%3Aspec) (#442–#509) |

---

## Standar Pengodean

- `declare(strict_types=1)` di setiap berkas PHP kecuali migrasi dan konfigurasi
- Ikuti **Action Triad** — Command (transaksi + log), Read (hanya kueri), Process (orkestrasi);
  setiap Action memiliki tepat satu metode `execute()`
- Aturan bisnis berada di **Entity** (`final readonly`), bukan di Model
- Gunakan atribut `#[Fillable]` pada Model, jangan pernah `$fillable`/`$guarded`
- Semua string yang dilihat pengguna menggunakan `__()` — tambahkan kunci ke `lang/en/` dan `lang/id/`
- Tanpa panggilan debug (`dd()`, `dump()`, `ray()`, `var_dump()`, `die()`) dalam kode yang di-commit
- Kunci cache harus terdaftar di `config/cache-keys.php`, jangan pernah berupa string inline
- DTO untuk 3+ parameter; Action mengembalikan `ActionResponse`

Jalankan perapi gaya sebelum commit:

```bash
vendor/bin/pint --dirty --format agent
```

---

## Alur Kerja Git

### Penamaan Branch

```
feat/{kebab-description}       Fitur baru
fix/{description}              Perbaikan bug
hotfix/{description}           Perbaikan produksi kritis
refactor/{module}-{scope}      Refaktor
docs/{what}                    Dokumentasi
chore/{task}                   Pemeliharaan, dependensi, perkakas
```

### Pesan Commit

```
type(scope): Short description

- Bullet points for details (optional)
- Reference issues: #123
```

Tipe: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

---

## Pengujian

Tes memverifikasi spesifikasi — setiap tes terlacak ke ID kebutuhan (`FR-*` / `NFR-*` / `UC-*`) di
issue spesifikasinya (`label:spec`, #442–#509). Tanpa kebutuhan spesifikasi berarti tanpa tes (tanpa
tes yatim, tanpa pengisi).

```bash
composer run test                                # Rangkaian tes penuh
vendor/bin/pest --testsuite={ModuleName}         # Rangkaian satu modul
php artisan test --compact --filter={ClassName}  # Satu kelas tes
```

Konvensi tes:

- Lokasi: `tests/{Type}/{Module}/{SubModule}/{Name}Test.php` ({Type} ∈ {Arch, Unit, Feature, Browser})
- Gunakan `LazilyRefreshDatabase` alih-alih `RefreshDatabase`; factory alih-alih mock model
- Mock hanya batas eksternal (HTTP, mail, antrean, filesystem)

---

## Checklist Pra-Commit

Jalankan gerbang kualitas sebelum push:

```bash
composer run quality   # Lint + tes
npm run build          # Untuk perubahan frontend
```

Pemeriksaan manual yang tidak dicakup perkakas: tanpa kueri N+1 (eager loading terverifikasi), dokumen
relevan diperbarui (pendekatan dokumentasi-dulu di `docs/id/conventions.md`).

---

## Proses Pull Request

1. Pastikan gerbang kualitas lulus dan checklist di atas bersih
2. Jaga PR tetap fokus pada satu hal — jangan mencampur refaktor dengan fitur
3. Rujuk issue terkait di deskripsi PR
4. Pemelihara akan meninjau dalam beberapa hari
5. Tanggapi umpan balik tinjauan dengan commit tambahan (di-squash saat penggabungan)

---

## Pengembangan dengan Agen AI

Internara dikembangkan dengan bantuan agen AI yang intensif. Invarian seluruh proyek berada di
`AGENTS.md`, dan aturan serta template per tipe dokumen berada di `docs/templates/`. Saat
memperluasnya: rujuk aturan di `docs/` alih-alih menduplikasinya, dan perbarui `AGENTS.md` jika
invarian seluruh proyek berubah.

---

## Mendapatkan Bantuan & Lisensi

Ada pertanyaan? Buka [diskusi](https://github.com/reasvyn/internara/discussions) atau kirim email ke
[reasvyn@gmail.com](mailto:reasvyn@gmail.com). Masalah keamanan mengikuti
[SECURITY.id.md](SECURITY.id.md) — jangan pernah melalui issue publik.

Dengan berkontribusi, Anda setuju bahwa kontribusi Anda akan dilisensikan di bawah
[Lisensi MIT](LICENSE).
