# Konvensi Pengodean — Aturan PHP, Penamaan & Pengujian

## Deskripsi

Dokumen ini menjelaskan konvensi penulisan kode di basis kode Internara. Aturan-aturan ini ada untuk
menghasilkan kode yang konsisten dan dapat diprediksi sehingga dapat dibaca oleh anggota tim mana pun
tanpa perlu berpindah konteks.

**Pola arsitektur** (Action, Entity, Event, Livewire, caching, pengujian, dll.) berada di
`docs/guides/arch/*-pattern.md`. Berkas ini hanya mencakup konvensi yang tidak spesifik pola: gaya
kode, penamaan, struktur berkas, dan aturan proyek.

---

## Daftar Isi

- [0. Dokumentasi-Dulu](#0-dokumentasi-dulu)
- [1. Pola Arsitektur](#1-pola-arsitektur)
- [2. PHP Umum](#2-php-umum)
- [3. Konvensi Keamanan](#3-konvensi-keamanan)
  - [3.1 Pencegahan XSS](#31-pencegahan-xss)
  - [3.2 Pencegahan Injeksi SQL](#32-pencegahan-injeksi-sql)
  - [3.3 Perlindungan Mass Assignment](#33-perlindungan-mass-assignment)
  - [3.4 Perlindungan CSRF](#34-perlindungan-csrf)
  - [3.5 Content Security Policy](#35-content-security-policy)
  - [3.6 Keamanan Unggahan Berkas](#36-keamanan-unggahan-berkas)
  - [3.7 Pembatasan Laju](#37-pembatasan-laju)
- [4. Konvensi Penamaan](#4-konvensi-penamaan)
- [5. Perintah Console](#5-perintah-console)
- [6. Konvensi Kinerja](#6-konvensi-kinerja)
  - [6.1 Pencegahan Kueri N+1](#61-pencegahan-kueri-n1)
  - [6.2 Optimasi Kueri](#62-optimasi-kueri)
  - [6.3 Konvensi Eager Loading](#63-konvensi-eager-loading)
  - [6.4 Pembersihan Sumber Daya](#64-pembersihan-sumber-daya)
  - [6.5 Konvensi Caching](#65-konvensi-caching)
- [7. Migrasi, Factory & Seeder](#7-migrasi-factory--seeder)
- [8. Protokol Lintas Sektor](#8-protokol-lintas-sektor)
- [9. Konvensi HTTP & API](#9-konvensi-http--api)
- [10. Konvensi Injeksi Dependensi](#10-konvensi-injeksi-dependensi)
- [11. Penegakan Kualitas Kode](#11-penegakan-kualitas-kode)
- [12. Konvensi Pengujian](#12-konvensi-pengujian)
- [13. Tematisasi & Konsistensi Visual](#13-tematisasi--konsistensi-visual)
- [14. Presentasi Frontend & Blade](#14-presentasi-frontend--blade)
- [15. Lokalisasi](#15-lokalisasi)

---

## 0. Dokumentasi-Dulu

### Dokumentasi sebagai Sumber Kebenaran Tunggal

Dokumentasi adalah **referensi otoritatif** untuk sistem. Dokumentasi mendefinisikan apa yang
dilakukan sistem, bagaimana strukturnya, dan mengapa keputusan diambil. Kode mengimplementasikan apa
yang dijelaskan dokumentasi — bukan sebaliknya. Ketika dokumentasi dan implementasi berbeda,
dokumentasi adalah SSOT dan implementasi harus diperbaiki.

### Dokumentasikan Dulu, Baru Implementasikan

Setiap perubahan — fitur, refaktor, perbaikan bug — dimulai dengan dokumentasi. Sebelum menulis satu
baris kode, dokumen yang relevan harus diperbarui untuk menjelaskan hasil yang diinginkan. Ini
berlaku pada semua skala:

- **Fitur baru** → buka/perbarui issue spesifikasi yang berlaku (`label:spec`, #442–#509), perbarui
  dokumen konseptual modul (`{module}.md`) dan referensi API (`{module}-reference.md`)
- **Perubahan arsitektur** → perbarui `architecture.md` dan ADR yang terdampak
- **Perbaikan bug** → jika perbaikan mengubah perilaku, perbarui dokumen yang terdampak
- **Refaktor** → jika refaktor memindahkan kode antar modul, perbarui dokumen kedua modul dan
  `modules/index.md`

Implementasi mengikuti dokumentasi. Dokumen menjelaskan keadaan target; kode menyusul.

### Dua Tingkatan Dokumentasi

Setiap modul memiliki dua dokumen yang melayani audiens berbeda:

| Dokumen                                  | Audiens                              | Isi                                                                                             |
| ---------------------------------------- | ------------------------------------ | ----------------------------------------------------------------------------------------------- |
| `docs/refs/modules/{module}.md`          | Arsitek, pengembang, pemangku kepentingan | Tujuan, prinsip desain, batas modul — desain konseptual murni, tanpa detail implementasi   |
| `docs/refs/modules/{module}-reference.md`| Pengembang, peninjau                 | Referensi API lengkap — jalur berkas, nama kelas, skema tabel, grafik dependensi                |

Ketika menjelaskan perilaku modul, tulis dokumen konseptual. Ketika mendaftar berkas atau kelas,
tulis dokumen referensi. Jangan pernah mencampur detail implementasi ke dalam dokumen konseptual.

### Pembaruan Dokumentasi sebagai Bagian dari Definition of Done

Sebuah perubahan belum selesai sampai dokumentasi yang relevan diperbarui. Ini ditegakkan melalui
tinjauan kode — PR yang mengubah kode tanpa pembaruan dokumen yang sesuai belum lengkap dan tidak
boleh digabungkan.

---

## 1. Pola Arsitektur

Semua pola arsitektur didokumentasikan di `docs/guides/arch/`. Rujuk dokumen pola yang sesuai alih-alih
menduplikasi konvensi di sini:

| Konvensi                            | Referensi                                                                  |
| ----------------------------------- | -------------------------------------------------------------------------- |
| Struktur berkas, organisasi modul   | [Pola Modular](guides/arch/modular-pattern.md)                             |
| Aturan SRP & modularitas            | [Pola Modular §1.6](guides/arch/modular-pattern.md#16-single-responsibility--modularity-rules) |
| Action (Command / Read / Process)   | [Pola Action](guides/arch/action-pattern.md)                               |
| Model & relasi                      | [Pola Model](guides/arch/model-pattern.md)                                 |
| Entity (aturan bisnis)              | [Pola Entity](guides/arch/entity-pattern.md)                               |
| Enum (LabelEnum, StatusEnum)        | [Pola Enum](guides/arch/enum-pattern.md)                                   |
| Policy & otorisasi                  | [Pola Policy](guides/arch/policy-pattern.md)                               |
| Komponen Livewire                   | [Pola Livewire](guides/arch/livewire-pattern.md)                           |
| Data / DTO                          | [Pola Data](guides/arch/data-pattern.md)                                   |
| Event & listener                    | [Pola Event](guides/arch/event-pattern.md)                                 |
| Notifikasi                           | [Pola Event](guides/arch/event-pattern.md) (lihat bagian Notifikasi)       |
| Controller, rute, middleware        | [Pola Modular](guides/arch/modular-pattern.md)                             |
| View Blade & komponen anonim        | [Pola Livewire](guides/arch/livewire-pattern.md)                           |
| Kunci cache & invalidasi            | [Pola Cache](guides/arch/cache-pattern.md)                                 |
| Komunikasi lintas modul             | [Pola Modular](guides/arch/modular-pattern.md)                             |
| Hierarki exception & penanganannya  | [Pola Exception](guides/arch/exception-pattern.md)                         |
| Pengujian                           | [Pola Pengujian](guides/arch/testing-pattern.md)                           |
| Logging & SmartLogger               | [Pola Logging](guides/arch/logging-pattern.md)                             |
| Service vs Support                  | [Pola Service](guides/arch/service-pattern.md)                             |
| Konsistensi UI & visual             | [Pola UI](guides/arch/ui-pattern.md)                                       |
| Pengalaman pengguna & aksesibilitas | [Pola UX](guides/arch/ux-pattern.md)                                       |

---

## 2. PHP Umum

- Kurung kurawal `{ }` wajib untuk semua struktur kontrol (jangan menghilangkan kurung kurawal,
  bahkan untuk badan satu baris).
- `declare(strict_types=1)` di setiap berkas kecuali migrasi dan konfigurasi.
- Promosi properti konstruktor: `public function __construct(protected readonly X $x) {}`. Jangan
  biarkan konstruktor tanpa parameter yang kosong kecuali bersifat privat.
- Tipe kembalian eksplisit pada setiap metode: `function isAccessible(User $user): bool`.
- Type hint pada semua parameter: `function find(string $id): ?Model`.
- `===` alih-alih `==` kecuali perbandingan longgar memang disengaja.
- Koma di akhir pada array multi-baris, pemanggilan fungsi, dan parameter konstruktor.
- `__()` untuk semua string yang dilihat pengguna. Jangan pernah menuliskan teks tampilan secara
  hardcode.
- Tidak ada `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` dalam kode yang di-commit.
- Gunakan `match()` alih-alih blok `switch()` panjang ketika mengembalikan nilai dari ekspresi.
- `str_contains()` / `str_starts_with()` / `str_ends_with()` alih-alih `strpos() === 0`.
- Operator null-safe `?->` dan null coalescing `??` alih-alih pemeriksaan null eksplisit.
- Properti readonly lebih memilih parameter konstruktor yang dipromosikan dibandingkan `#[Readonly]`.

---

## 3. Konvensi Keamanan

> 📖 Referensi pola: [Pola Policy](guides/arch/policy-pattern.md) (otorisasi & RBAC); postur keamanan
> yang lebih luas di [infra/security.md](guides/infra/security.md).

### 3.1 Pencegahan XSS

- Gunakan `{{ $var }}` (kurung kurawal ganda) untuk **semua** konten yang berasal dari pengguna di
  Blade — mesin Blade Laravel secara otomatis meng-escape entitas HTML.
- `{!! $var !!}` (tanpa escape) **hanya** diizinkan untuk konten tepercaya yang telah secara eksplisit
  disanitasi (mis. teks hasil render markdown yang melewati purifier HTML berbasis whitelist).
- Setiap kemunculan `{!! $var !!}` harus memiliki komentar inline yang membenarkan mengapa aman:

    ```blade
    {{-- Aman: dirender dari Markdown dengan purifier HTML --}}
    {!! $content !!}
    ```

- **Jangan pernah** menggunakan `{!! $var !!}` dengan input pengguna mentah (`$_GET`, `$_POST`,
  `request()->input()`, kolom basis data yang berisi HTML kiriman pengguna).
- `x-html` di Alpine.js harus mengikuti aturan yang sama — hanya konten tersanitasi yang tepercaya.
- Hindari tag `<script>` inline di Blade; gunakan `x-data` dan `@entangle` Alpine.js sebagai gantinya.

### 3.2 Pencegahan Injeksi SQL

- SQL mentah (`DB::raw()`, `whereRaw()`, `orderByRaw()`, `havingRaw()`, `selectRaw()`) **dilarang**
  dalam kode aplikasi kecuali:
    1. SQL menggunakan **binding berparameter** secara eksklusif (`->whereRaw('col = ?', [$value])`).
    2. Pengecualian eksplisit didokumentasikan di docblock metode tersebut.
- Selalu gunakan query builder Eloquent atau sintaks `where('column', $value)` — keduanya
  menggunakan binding berparameter secara bawaan.
- Jangan pernah menggabungkan input pengguna ke dalam string kueri:

    ```php
    // ❌ Salah — vektor injeksi SQL
    User::whereRaw("name = '$input'")->get();

    // ✅ Benar — berparameter
    User::where('name', $input)->get();
    User::whereRaw('name = ?', [$input])->get();
    ```

### 3.3 Perlindungan Mass Assignment

- Setiap model **wajib** menggunakan atribut `#[Fillable]` (PHP 8.4). Properti `$fillable` warisan
  tidak digunakan di mana pun dalam basis kode ini.
- **Jangan pernah** meneruskan input permintaan mentah ke `create()` atau `update()`:

    ```php
    // ❌ Salah — memungkinkan mass assignment pada kolom apa pun
    Model::create($request->all());
    Model::create($this->all());

    // ✅ Benar — kunci yang diizinkan secara eksplisit
    Model::create($request->only(['name', 'email']));
    Model::create($this->form->toArray());
    ```

- Semua mass assignment dialirkan melalui `#[Fillable]` + pemilihan kunci eksplisit. `$guarded` tidak
  digunakan — selalu whitelist dengan `#[Fillable]`.

### 3.4 Perlindungan CSRF

- Semua formulir HTML yang mengubah status (`POST`, `PUT`, `PATCH`, `DELETE`) harus menyertakan
  `@csrf` atau menggunakan Livewire (yang mengelola CSRF secara otomatis).
- Rute yang dikecualikan (untuk penangan webhook, callback API eksternal) didaftarkan di
  `bootstrap/app.php` `validateCsrfTokens(except: [...])`. Setiap pengecualian harus memiliki komentar
  kode yang menjelaskan mengapa diperlukan.
- Konsumen API yang mengautentikasi via token (bukan sesi) dikecualikan.

### 3.5 Content Security Policy

- Middleware `SecurityHeadersMiddleware` (diterapkan secara global pada grup `web`) menetapkan header
  CSP yang ketat.
- Tag `<script>` inline diblokir secara bawaan. Gunakan `x-data` / `@click` / `x-on` Alpine.js untuk
  interaktivitas alih-alih handler `onclick` inline.
- Sumber daya eksternal (skrip, font, gambar) harus ditambahkan ke direktif CSP `default-src` /
  `script-src` / `img-src` di `SecurityHeadersMiddleware` sebelum digunakan.
- CSP ditegakkan melalui header `Content-Security-Policy`, bukan
  `Content-Security-Policy-Report-Only`. Pelanggaran merusak halaman — uji secara menyeluruh.

### 3.6 Keamanan Unggahan Berkas

- Semua unggahan berkas melalui Spatie MediaLibrary (`spatie/laravel-medialibrary`), jangan pernah
  `Storage::put()`.
- Setiap koleksi media mendefinisikan aturan validasinya sendiri (ukuran berkas maksimum, tipe MIME
  yang diizinkan) melalui `registerMediaCollections()`.
- Untuk validasi tipe berkas, periksa tipe MIME di sisi server (`$file->getMimeType()`), bukan hanya
  ekstensinya — berkas `.jpg` dapat berisi data sembarang.
- Nama berkas yang dihasilkan disanitasi:
  `Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))`.
- Path traversal dicegah secara otomatis oleh penanganan penyimpanan internal Spatie.

### 3.7 Pembatasan Laju

- Endpoint autentikasi (`login`, `recover-account`) menggunakan middleware `auth.throttle`.
- Validasi token wizard penyiapan menggunakan pembatas laju khusus (`setup:{$ip}`) dengan jumlah
  percobaan dan jendela decay yang dapat dikonfigurasi (`config/setup.php security.*`).
- Pembatas laju khusus didefinisikan di `bootstrap/app.php` dan dirujuk berdasarkan nama pada
  middleware rute.

---

## 4. Konvensi Penamaan

> 📖 Referensi pola: [Pola Modular §16 — Konvensi Penamaan](guides/arch/modular-pattern.md#16-naming-conventions).

| Elemen                     | Konvensi                                                      | Contoh                                                                |
| -------------------------- | ------------------------------------------------------------- | --------------------------------------------------------------------- |
| Direktori submodul         | Singular `{Name}` (konsep akar submodul)                      | `User`, `Profile`, `Internship`, `Placement`                          |
| Direktori Types            | `Types/` untuk value object kecil                             | `Types/Gender.php`, `Types/BloodType.php`                             |
| Model                      | Singular `{Name}`                                             | `User`, `AcademicYear`, `Internship`                                  |
| Command Action             | `{Verb}{Entity}Action`                                        | `CreateUserAction`, `ApproveRegistrationAction`                       |
| Read Action                | `Read{Entity}Action`                                          | `ReadTeacherDashboardAction`, `ReadActivityLogAction`                 |
| Process Action             | `Process{Entity}Action`                                       | `ProcessRegistrationAction`, `ProcessReportFinalizationAction`        |
| Entity                     | `{Name}`                                                      | `Apprentice`, `InternshipPeriod`, `RegistrationState`, `SchoolEntity` |
| Data / DTO                 | `{Verb}{Entity}Data` atau `{Entity}Data` (mewarisi `BaseData`) | `SetupTokenData`, `AuditCheck`                                        |
| Livewire                   | `{Name}` — berakhiran Manager, Editor, Center                 | `UserManager`, `ProfileEditor`, `RegistrationCenter`                  |
| Alias Livewire (submodul)  | `{kebab-module}.{kebab-submodule}.{kebab-name}`               | `admin.user.user-manager`                                             |
| Alias Livewire (root)      | `{kebab-module}.{kebab-name}`                                 | `user.profile-editor`                                                 |
| Livewire Form              | `{Entity}Form` (mewarisi `Livewire\Form`)                     | `AcademicYearForm`, `SchoolForm`                                      |
| Policy                     | `{Name}Policy`                                                | `UserPolicy`, `InternshipPolicy`                                      |
| Exception                  | `{Name}Exception`                                             | `RejectedException`, `ValidationFailedException`                      |
| Controller                 | `{Name}Controller`                                            | `DashboardController`, `ReportController`                             |
| Middleware                 | `{Name}Middleware`                                            | `CheckRoleMiddleware`, `SetLocaleMiddleware`                          |
| Event                      | `{Entity}{Actioned}` — bentuk lampau                          | `InternshipCreated`, `ReportApproved`, `StudentRegistered`            |
| Listener                   | `{Verb}{Entity}` atau bereaksi pada nama event                | `NotifyAdminsInternshipCreated`, `LogSetupFinalized`                  |
| Notification               | `{Entity}{NotificationType}Notification`                      | `InternshipCreatedNotification`, `WelcomeNotification`                |
| Perintah Console           | `{module}:{action}`                                           | `system:health`, `admin:recover`, `notifications:prune`               |
| Nama rute                  | deskriptif (mencerminkan jalur URL)                           | `login`, `admin.users.index`, `student.logbook`                       |
| Kunci konfigurasi          | `snake_case` dengan `{file}.{key}`                            | `app.name`, `database.default`                                        |
| Kolom / tabel              | `snake_case`                                                  | `user_id`, `academic_year_id`, `academic_years`                       |
| Metode boolean             | awalan `is`/`has`/`can`/`should`                              | `isActive()`, `allowsLogin()`, `canTransitionTo()`                    |
| Metode tes                 | Pest `test()` dengan awalan `{SpecID}-{ReqID}:`, dikelompokkan dengan `describe("{SpecID}: Test description...")` | `test("{SpecID}-{ReqID}: Test description...")`              |
| Berkas tes                 | `{Name}Test.php`                                              | `CreateUserActionTest.php`, `UserManagerTest.php`                     |
| Factory                    | `{Name}Factory`                                               | `UserFactory`, `InternshipFactory`                                    |
| Migrasi                    | `YYYY_MM_DD_HHMMSS_create_{table}_table.php`                  | `2026_04_29_092750_create_users_table.php`                            |

---

## 5. Perintah Console

- Signature perintah mengikuti penamaan `{module}:{action}`.
- Gunakan pasangan kata kerja-kata benda: `system:health`, `admin:recover`, `notifications:prune`.
- Argumen menggunakan kurung kurawal: `{email?}`, `{--force}`.
- Gunakan kelas dasar `Command` Laravel (bukan kelas dasar Core).
- Perintah berada di direktori `Console/Commands/` milik modul yang bersangkutan.

```php
class HealthCommand extends Command
{
    protected $signature = 'system:health
        {--json : Output results as JSON}';

    protected $description = 'Perform a comprehensive system health check';

    public function handle(): int
    {
        // ...
        return Command::SUCCESS;
    }
}

```

---

## 6. Konvensi Kinerja

### 6.1 Pencegahan Kueri N+1

- **Jangan pernah** mengakses relasi Eloquent di dalam perulangan Blade atau `@foreach` Livewire tanpa
  eager loading:

    ```blade
    {{-- ❌ Salah — N+1: setiap iterasi mengueri DB --}}
    @foreach ($users as $user)
        {{ $user->profile->bio }}
    @endforeach

    {{-- ✅ Benar — sudah dimuat sebelumnya --}}
    {{-- Controller/Livewire: User::with('profile')->get() --}}
    ```

- Selalu gunakan `->with()` pada kueri sebelum melakukan iterasi. `->load()` pada koleksi yang sudah
  ada dapat diterima tetapi `with()` lebih diutamakan (kueri lebih sedikit).
- Untuk eager loading bersyarat, gunakan `->when()` dengan `with()`:

    ```php
    User::query()->when($loadProfile, fn($q) => $q->with('profile'))->get();
    ```

- Metode `render()` Livewire tidak boleh memicu N+1. Gunakan `->with()` di kueri, jangan pernah
  `->load()` di dalam perulangan.
- Kueri Read Action dan Livewire harus diaudit untuk N+1 selama tinjauan kode.

### 6.2 Optimasi Kueri

- Untuk kumpulan data besar (≥1.000 baris), gunakan `chunk()` atau `lazy()` alih-alih `get()` untuk
  menghindari kehabisan memori:

    ```php
    // Pemrosesan batch
    User::chunk(200, function (Collection $users) { ... });

    // Koleksi lazy (hemat memori)
    foreach (User::lazy(200) as $user) { ... }
    ```

- Hindari `$collection->filter()` pada koleksi Eloquent besar — pindahkan filter ke basis data:

    ```php
    // ❌ Salah — memuat semua baris, memfilter di memori PHP
    Model::all()->filter(fn($m) => $m->isActive());

    // ✅ Benar — memfilter di tingkat basis data
    Model::where('is_active', true)->get();
    ```

- Gunakan `exists()` alih-alih `count() > 0` untuk pemeriksaan keberadaan.
- Gunakan `pluck()` alih-alih `get()->pluck()` untuk menghindari hidrasi model penuh.
- Strategi indeks: setiap kolom `WHERE` / `ORDER BY` / `JOIN` yang digunakan dalam kueri sering harus
  memiliki indeks basis data. Indeks komposit untuk filter multi-kolom.

### 6.3 Konvensi Eager Loading

- Bawaan: `->with()` untuk semua relasi reguler yang digunakan dalam tampilan/respons saat ini.
- Lazy eager loading (`->load()`) untuk relasi opsional yang dimuat secara bersyarat.
- Eager loading sub-relasi: `->with(['posts.comments'])`.
- Eager loading terbatas: `->with(['comments' => fn ($q) => $q->where('approved', true)])`.
- Hindari `->loadMissing()` di dalam perulangan — pindahkan ke `->with()` pada kueri awal.

### 6.4 Pembersihan Sumber Daya

- Berkas sementara yang dibuat selama pemrosesan permintaan harus dibersihkan di blok `finally`:

    ```php
    $tempFile = tempnam(sys_get_temp_dir(), 'export_');
    try {
        // ... hasilkan konten berkas
    } finally {
        @unlink($tempFile);
    }
    ```

- Perintah berjalan lama dan job antrean harus membebaskan variabel besar setelah digunakan:

    ```php
    $chunk = Model::lazy(200);
    foreach ($chunk as $row) {
        // proses $row
        unset($row); // bebaskan memori
    }
    ```

- Handle berkas dan stream yang terbuka harus ditutup secara eksplisit (`fclose()`, `curl_close()`).

### 6.5 Konvensi Caching

> 📖 Referensi pola: [Pola Cache](guides/arch/cache-pattern.md) — registri kunci lengkap, TTL, dan
> strategi invalidasi.

- Setiap kunci cache dideklarasikan di `config/cache-keys.php` — jangan pernah berupa string inline.
- Invalidasi cache mengikuti pola berbasis event: Command Action → event → listener →
  `Cache::forget()`.
- Kategori TTL: Pendek (<5 menit), Sedang (5 menit–1 jam), Panjang (1–24 jam), Selamanya.
- Gunakan `remember()` untuk pembacaan, `forget()` untuk invalidasi. Jangan pernah menggunakan
  `Cache::put()` dengan kunci mentah kecuali mendaftarkannya di `cache-keys.php` terlebih dahulu.

---

## 7. Migrasi, Factory & Seeder

> 📖 Referensi pola: [Pola Model](guides/arch/model-pattern.md) (Konvensi Migrasi & Basis Data,
> `#[Fillable]`, relasi); [Pola Pengujian](guides/arch/testing-pattern.md) (strategi factory).

### Migrasi

- Penamaan: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`.
- Semua foreign key menggunakan `foreignUuid()->constrained('{table}')`.
- Ikuti UUID FK dengan perilaku `onDelete()` / `onUpdate()` eksplisit:
    - `cascadeOnDelete()` — anak tidak dapat ada tanpa induk
    - `onDelete('set null')` — relasi bersifat opsional
    - `onDelete('restrict')` — penghapusan harus dicegah
- Indeks komposit untuk pola kueri umum: `->index(['user_id', 'date'])`.
- Setiap berkas migrasi menangani satu tabel atau satu perubahan logis.
- Indeks dibuat secara eksplisit, tidak mengandalkan pengindeksan otomatis FK.

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
    $table->date('date');
    $table->time('clock_in');
    $table->time('clock_out')->nullable();
    $table->string('status');
    $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('verified_at')->nullable();
    $table->index(['user_id', 'date']);
    $table->timestamps();
});

```

### Factory

- Satu factory per model, mewarisi `Illuminate\Database\Eloquent\Factories\Factory`.
- Definisikan state spesifik model melalui metode `state()`.
- Penamaan factory: `{Name}Factory` — `InternshipFactory`, `UserFactory`.

```php
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(6),
            'status' => InternshipStatus::DRAFT->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attrs) => ['status' => InternshipStatus::PUBLISHED->value]);
    }
}

```

### Seeder

- Seeder bersifat idempoten — menjalankannya berkali-kali tidak menduplikasi data.
- Urutan seeding menghormati dependensi modul: sekolah → pengguna → izin → magang.
- Gunakan `firstOrCreate()` untuk data referensi, `create()` untuk data uji.

---

## 8. Protokol Lintas Sektor

> 📖 Referensi pola: [Pola Policy §20 — Protokol Proxy Lintas Peran](guides/arch/policy-pattern.md)
> (proxy otorisasi); [Pola Modular](guides/arch/modular-pattern.md) (batas modul).

### Protokol Proxy Lintas Peran

Internara mengimplementasikan mekanisme **Cross-Role Proxy** (lihat [4](adr/adr-cross-role-proxy.md))
yang memungkinkan guru bertindak sebagai proxy bagi pembimbing, dan admin sebagai proxy bagi guru
maupun pembimbing — semuanya pada lapisan aplikasi tanpa penetapan multi-peran.

1. **Opsionalitas Action & Parameter** — Action apa pun yang melakukan verifikasi atau pengesahan
   harus menerima parameter nullable atau memiliki jalur proxy jika pembimbing industri tidak
   tersedia.
2. **Jendela Inaktivitas (Jurnal/Kehadiran)** — Logbook reflektif dan kehadiran mendukung proxy guru
   setelah jendela inaktivitas (bawaan: 48 jam). Command Action harus bertransisi ke `FINALIZED` /
   `VERIFIED`, mencatat `proxy_role = 'supervisor'` di log aktivitas, dan membersihkan antrean
   tertunda pembimbing.
3. **Proxy Penilaian & Rubrik** — Evaluasi akhir penempatan mendukung entri proxy dan redistribusi
   bobot melalui gerbang Cross-Role Proxy ketika pembimbing belum mengirimkan nilai.
4. **Stempel Kepatuhan Transparan** — Dokumen yang disusun menggunakan bobot proxy atau nilai proxy
   harus diberi stempel metadata (`proxy_weights` atau `proxy_scores`) untuk jejak audit.

---

## 9. Konvensi HTTP & API

> 📖 Referensi pola: [Pola Modular §13 — Pola Rute & Controller](guides/arch/modular-pattern.md#13-route--controller-patterns);
> [Pola Livewire](guides/arch/livewire-pattern.md) (penanganan HTTP/permintaan komponen).

### 7.1 Pemetaan Kode Status HTTP

| Skenario            | Kode Status                 | Kapan                                         |
| ------------------- | --------------------------- | --------------------------------------------- |
| Sumber daya dibuat  | `201 Created`               | `POST` mengembalikan sumber daya baru         |
| Sumber daya diubah  | `200 OK`                    | `PUT`/`PATCH` mengembalikan sumber daya terubah |
| Sumber daya dihapus | `204 No Content`            | `DELETE` — tanpa badan respons                |
| Daftar/baca         | `200 OK`                    | `GET` mengembalikan sumber daya               |
| Kesalahan validasi  | `422 Unprocessable Entity`  | `ValidationException`                         |
| Tidak terautentikasi| `401 Unauthorized`          | Autentikasi hilang atau tidak valid           |
| Terlarang           | `403 Forbidden`             | Terautentikasi tetapi tidak berwenang         |
| Tidak ditemukan     | `404 Not Found`             | Sumber daya tidak ada                         |
| Konflik             | `409 Conflict`              | Duplikat / konflik status                     |
| Dibatasi laju       | `429 Too Many Requests`     | Pembatas laju tercapai                        |
| Kesalahan server    | `500 Internal Server Error` | Exception tidak tertangani                    |

Kode status pada hierarki exception (`AppException::statusCode()`) sesuai dengan tabel ini. Controller
dan endpoint API harus mengembalikan kode status yang benar — jangan pernah memakai `200` sebagai
bawaan untuk kesalahan.

### 7.2 Format Respons Kesalahan (JSON)

Semua respons kesalahan JSON mengikuti envelope yang konsisten:

```json
{
    "message": "Human-readable description",
    "errors": {
        "field_name": ["Validation error 1", "Validation error 2"]
    }
}

```

- `message` selalu ada.
- `errors` hanya ada untuk kegagalan validasi (422).
- Kesalahan non-validasi menghilangkan kunci `errors`.
- Respons berhalaman menyertakan `meta` dengan `current_page`, `last_page`, `per_page`, `total`.

---

## 10. Konvensi Injeksi Dependensi

> 📖 Referensi pola: [Pola Service](guides/arch/service-pattern.md) (injeksi konstruktor);
> [Pola Action](guides/arch/action-pattern.md) (C2 — tanpa `app()->make`).

### 8.1 Injeksi Konstruktor (Diutamakan)

Dependensi wajib dan berumur panjang diinjeksi melalui promosi properti konstruktor:

```php
public function __construct(
    protected readonly SomeService $service,
    protected readonly AnotherService $another,
) {}

```

Ini berlaku untuk semua kelas yang diinstansiasi oleh container: Action, Service, Middleware, Perintah
Console, Controller, Listener, Repository.

### 8.2 Injeksi Metode (Kontekstual)

Dependensi yang bervariasi per pemanggilan atau hanya dibutuhkan dalam satu metode diinjeksi sebagai
parameter metode. Ini adalah pola standar untuk metode komponen Livewire:

```php
public function save(CreateUserAction $action): void
{
    $action->execute($this->form->toArray());
}

```

### 8.3 Pola yang Dilarang

- `app()->make(ClassName::class)` — gunakan injeksi konstruktor atau metode.
- `new ClassName()` di dalam Controller atau komponen Livewire — biarkan container yang menyelesaikan.
- `resolve(ClassName::class)` — gunakan injeksi metode.
- Facade statis untuk logika bisnis — `User::where(...)` dapat diterima untuk kueri sederhana tetapi
  logika kompleks harus berada di dalam Action (diinjeksi).

Pengecualian: `app()` diizinkan di service provider (`boot()`, `register()`) dan metode factory di mana
container tidak tersedia (mis. `database/factories/`).

---

## 11. Penegakan Kualitas Kode

> 📖 Referensi pola: [Pola Modular §18 — Penegakan Kualitas](guides/arch/modular-pattern.md#18-quality-enforcement).

| Perkakas         | Apa yang Ditegakkan                                           | Bagaimana                                    |
| ---------------- | ------------------------------------------------------------- | -------------------------------------------- |
| **Laravel Pint** | Gaya kode PHP (PSR-12 + konvensi Laravel)                     | `vendor/bin/pint --dirty` sebelum finalisasi |
| **Prettier**     | Pemformatan Markdown, JSON, YAML, Blade                       | `npm run format`                             |
| **Tinjauan Kode**| Konvensi arsitektur, kepatuhan pola, keamanan                 | Tinjauan manual setiap PR                    |

### Checklist Pra-Commit (Penulis)

- [ ] `declare(strict_types=1)` ada
- [ ] Tidak ada `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` tersisa di kode
- [ ] Semua string yang dilihat pengguna menggunakan helper `__()`
- [ ] Action mengikuti pola triad yang benar (Command/Read/Process)
- [ ] Command/Process Action menerima DTO (untuk 3+ parameter) dan mengembalikan ActionResponse
      (untuk umpan balik terstruktur)
- [ ] Kunci cache terdaftar di `config/cache-keys.php`
- [ ] Tidak ada kueri N+1 — eager loading terverifikasi
- [ ] Tidak ada `{!! !!}` tanpa escape untuk konten pengguna
- [ ] Tes lulus: `php artisan test --compact` (setiap tes terlacak ke kebutuhan spesifikasi — tanpa
      tes yatim, tanpa pengisi)
- [ ] Pint bersih: `vendor/bin/pint --dirty --format agent`
- [ ] Dokumen relevan diperbarui (lihat §0 Dokumentasi-Dulu)

### Checklist Tinjauan Kode (Peninjau)

- [ ] **Kepatuhan pola**: Action menggunakan kelas dasar yang benar, satu `execute()`, `transaction()`
      + `log()` untuk command, tanpa `transaction()`/`log()` untuk read
- [ ] **Pemindaian keamanan**: Tanpa vektor XSS (`{!! $userContent !!}`), tanpa injeksi SQL
      (`whereRaw()` dengan penggabungan), tanpa mass assignment (`create($request->all())`)
- [ ] **Audit N+1**: Semua akses relasi di dalam perulangan memiliki `->with()` yang sesuai pada kueri
- [ ] **Penanganan exception**: Aturan bisnis melempar `RejectedException`, bukan `RuntimeException`;
      Livewire menangkap `RejectedException` secara spesifik
- [ ] **Invalidasi cache**: Setiap mutasi yang mengubah data ter-cache memiliki `Cache::forget()` yang
      sesuai atau invalidasi berbasis event
- [ ] **Cakupan tes**: Action baru memiliki berkas tes; tes yang ada tetap lulus
- [ ] **Cakupan spesifikasi**: setiap kebutuhan dari spesifikasi terdampak memiliki tes yang terlacak;
      tanpa tes yatim (tes tanpa pemetaan kebutuhan)
- [ ] **Dokumentasi**: Dokumen modul atau dokumen referensi diperbarui jika perilaku berubah

---

## 12. Konvensi Pengujian

> 📖 Referensi pola: [Pola Pengujian](guides/arch/testing-pattern.md) — strategi pengujian lengkap,
> isolasi cakupan, dan titik masuk per lapisan.

### 12.1 Strategi Mocking

| Skenario                     | Pendekatan                              | Contoh                                                                       |
| ---------------------------- | --------------------------------------- | ---------------------------------------------------------------------------- |
| Panggilan HTTP eksternal     | `Http::fake()`                          | `Http::fake(['api.example.com/*' => Http::response(...)])`                   |
| SmartLogger (tes unit)       | `Event::fake()` atau mock parsial       | `Mail::fake()`, `Notification::fake()`                                       |
| SmartLogger (tes fitur)      | Gunakan implementasi nyata              | Biarkan SmartLogger menulis ke log — asersi diverifikasi via model `ActivityLog` |
| Model Eloquent               | Gunakan factory + basis data nyata      | Jangan pernah mock Eloquent — selalu uji terhadap DB nyata (tes fitur)       |
| Sistem berkas                | `Storage::fake()`                       | `Storage::fake('local')` → `Storage::disk('local')->assertExists(...)`       |
| Cache                        | `Cache::shouldReceive()` (hanya di unit)| Utamakan asersi `Cache::forget()` via listener event di tes fitur            |
| Antrean                      | `Queue::fake()`                         | `Queue::assertPushed(Job::class)`                                            |
| Notifikasi                   | `Notification::fake()`                  | `Notification::assertSentTo(User::class, ...)`                               |
| Event                        | `Event::fake([SpecificEvent::class])`   | Pastikan event dikirim dengan payload yang benar                             |

**Aturan:**

- Jangan pernah mock model Eloquent atau Query Builder — gunakan basis data nyata di tes fitur.
- Jangan pernah gunakan `Mockery::spy()` untuk asersi — gunakan metode `fake()` bawaan Laravel.
- Mock hanya batas eksternal (HTTP, mail, antrean, filesystem, cache).
- Logika bisnis di Action diuji dengan dependensi nyata — hanya batas infrastruktur yang di-mock.

### 12.2 Cakupan Berbasis Kebutuhan

Tes memverifikasi spesifikasi — tidak lebih. Sebuah tes ada karena kebutuhan dalam issue spesifikasi
(`label:spec`, #442–#509; `FR-*` / `NFR-*` / `UC-*` / kontrak data §6) menuntutnya. Deskripsi tes
menggunakan format `{SpecID}-{ReqID}: Test description...`, dikelompokkan di bawah
`describe("{SpecID}: Test description...")`. ID muncul di judul issue spesifikasi (`[{ID}]`), deskripsi
tes, dan laporan ketertelusuran (`scan_spec_tests.py`).

**Minimalisme secara desain:** tulis hanya tes yang dibutuhkan spesifikasi, lalu berhenti. Ini
mempercepat pengembangan dan verifikasi (tes bercakupan spesifikasi berjalan dalam hitungan detik vs.
10+ menit untuk rangkaian penuh), mengurangi penggunaan sumber daya (~2GB+ RAM untuk rangkaian penuh),
dan mengurangi beban kognitif — rangkaian yang memetakan 1:1 ke ID kebutuhan menjelaskan dirinya
sendiri dan tidak perlu arkeologi untuk dipahami. Lebih sedikit yang ditulis, lebih sedikit yang
dibaca, lebih sedikit yang dipelihara.

| Sinyal                        | Arti              | Tindakan                                          |
| ----------------------------- | ----------------- | ------------------------------------------------- |
| Kebutuhan tanpa tes           | **Celah spesifikasi** | Tulis tes, terlacak ke ID kebutuhan           |
| Tes tanpa kebutuhan           | **Kebisingan yatim**  | Kandidat penghapusan                          |
| Skenario di luar spesifikasi  | **Pengisi**           | Jangan tulis / hapus                          |

Setiap deskripsi tes memberi awalan ID spesifikasi + kebutuhannya sehingga ketertelusuran terlihat di
keluaran tes:

```php
test("{SpecID}-{ReqID}: Test description...", function () { ... });

```

Tes dikelompokkan di bawah `describe("{SpecID}: Test description...")`, yang membawa ID spesifikasi
plus deskripsi singkat; setiap `test()` di dalamnya tetap memberi awalan `{SpecID}-{ReqID}:` lengkap.

```php
describe("{SpecID}: Test description...", function () {
    test("{SpecID}-{ReqID}: Test description...", function () { ... });
});

```

Cakupan kode (`php artisan test --coverage`) hanyalah **diagnostik** — jangan pernah menjadi mandat
untuk menulis tes pengisi. PR dinilai berdasarkan kebutuhan spesifikasi yang tercakup, bukan
berdasarkan persentase cakupan baris. Tes yang hanya mengejar persentase adalah kebisingan dan
ditolak.

### 12.3 Konvensi Commit & Branch

**Penamaan branch:**

| Tipe     | Pola                            | Contoh                           |
| -------- | ------------------------------- | -------------------------------- |
| Fitur    | `feat/{kebab-description}`      | `feat/add-internship-export`     |
| Perbaikan bug | `fix/{issue-short-description}` | `fix/login-redirect-loop`   |
| Hotfix   | `hotfix/{description}`          | `hotfix/critical-security-patch` |
| Refaktor | `refactor/{module}-{scope}`     | `refactor/setup-audit-cache`     |
| Dokumen  | `docs/{what}`                   | `docs/conventions-security`      |
| Tugas rutin | `chore/{task}`               | `chore/update-dependencies`      |

**Format pesan commit:**

```
type(scope): description

- Bullet points for details (optional)
- Reference issues: #123

```

| Tipe       | Kapan                                 |
| ---------- | ------------------------------------- |
| `feat`     | Fitur baru                            |
| `fix`      | Perbaikan bug                         |
| `refactor` | Perubahan kode tanpa perubahan perilaku |
| `docs`     | Hanya dokumentasi                     |
| `chore`    | Pemeliharaan, dependensi, perkakas    |
| `test`     | Menambah atau memperbaiki tes         |
| `perf`     | Peningkatan kinerja                   |
| `security` | Perbaikan keamanan                    |

Contoh:

```
feat(internship): add CSV export for approved registrations

fix(auth): prevent infinite redirect after setup completion

refactor(setup): move dispatchEvent inside transaction callback

```

### 12.4 Konvensi Versi & Rilis

Internara mengikuti Semantic Versioning dengan pemisahan ketat antara berkas repositori kode dan tag
rilis Git:

1. **`composer.json` & `package.json` hanya melacak versi stabil:**
   - Field `"version"` di `composer.json` dan `package.json` merepresentasikan rilis stabil saat ini
     (mis. `0.17.0`).
   - **Jangan pernah commit atau push sufiks pra-rilis atau pengembangan** (`-dev`, `-alpha`,
     `-beta`, `-rc`) ke `composer.json` atau `package.json`.
   - Mengubah versi di `composer.json` hanya dilakukan saat merilis versi stabil final (`production`).
2. **Versi Pengembangan & Pra-Rilis Hanya Berupa Tag Git:**
   - Build pengembangan menggunakan tag Git (`vX.Y.Z-dev.N`). Membuat rilis dev hanya memerlukan
     penandaan: `git tag vX.Y.Z-dev.N && git push origin vX.Y.Z-dev.N`.
   - Tag pra-rilis (`-alpha.N`, `-beta.N`, `-rc.N`) menandai tonggak pengujian tanpa mengubah berkas
     repositori.
   - Pipeline CI (`release.yml`) memvalidasi tahap dev dan pra-rilis hanya dari tag Git dan lolos tanpa
     menuntut perubahan `composer.json`.
3. **Penginderaan Versi Runtime:**
   - Resolusi versi runtime memprioritaskan injeksi lingkungan (`APP_VERSION` / `VERSION_TAG`), dengan
     fallback ke `composer.json`.
   - `AppInfo` menyimpan cache metadata secara ketat di memori per permintaan (`self::$metadata`);
     jangan pernah menyimpan metadata aplikasi secara permanen di cache disk/Redis.

### 10.4 Anotasi Utang Teknis

Gunakan anotasi berikut dalam komentar kode untuk melacak utang teknis:

| Anotasi                                | Arti                           | Konvensi                |
| -------------------------------------- | ------------------------------ | ----------------------- |
| `TODO(username, YYYY-MM-DD): message`  | Pekerjaan terencana            | Sertakan penulis dan tanggal |
| `FIXME(username, YYYY-MM-DD): message` | Bug yang diketahui             | Sertakan penulis dan tanggal |
| `HACK`                                 | Kode suboptimal yang berfungsi | Harus menjelaskan alasannya |
| `XXX`                                  | Bahaya — kode rapuh atau berisiko | Harus menjelaskan risikonya |

```php
// TODO(alice, 2026-07-01): Extract this inline query into a Read Action
// HACK: This works but bypasses the standard validation chain because...

```

---

## 13. Tematisasi & Konsistensi Visual

> 📖 Referensi pola: [Pola UI](guides/arch/ui-pattern.md) (hierarki visual, tematisasi, desain
> komponen).

### Penggunaan Variabel CSS

Semua komponen WAJIB menggunakan variabel CSS dari modul Settings/Theme untuk warna merek:

```blade
{{-- ✅ Benar — menggunakan variabel CSS --}}
<div class="bg-[var(--color-primary)]">

{{-- ❌ Salah — warna hardcode --}}
<div class="bg-blue-500">

```

- Warna primer, sekunder, aksen dari helper `brand()` atau variabel CSS
- Jangan pernah menuliskan warna hex secara hardcode di Blade atau CSS
- Hormati mode gelap/terang melalui pengaturan `theme.dark_mode`

### Varian Semantik & Hierarki Berorientasi Pengguna

Elemen UI WAJIB mematuhi varian semantik yang telah ditetapkan sesuai peran komunikatifnya (lihat
[Sistem Desain §3.1](guides/ui-ux/design-system.md#31-user-oriented-semantic-variant-architecture)):

- **`primary`**: Aksi utama / titik fokus (mis. Simpan, Kirim). **Maksimal 1 per viewport/kartu**.
  Jangan pernah menduplikasi tombol primary yang berdekatan.
- **`secondary`**: Aksi konstruktif pendukung (mis. Ekspor, Filter Lanjutan, Simpan Draf).
  Berpasangan dengan primary.
- **`tertiary`**: Aksi dengan penonjolan rendah (mis. Reset Filter, Lihat Log Audit). Gaya datar/minimal
  yang halus.
- **`accent`**: Sorotan fitur bernilai tinggi (mis. lencana Fitur Baru, rekomendasi). Penggunaan
  hemat (<5% permukaan visual).
- **`muted`**: Teks/keterangan pasif yang tidak ditekankan (mis. stempel waktu, teks bantuan). Harus
  memenuhi kontras WCAG AA (≥4,5:1).
- **`ghost`**: Keadaan istirahat transparan dengan friksi rendah (mis. ikon aksi baris tabel, Batal di
  modal, paginasi).
- **`neutral`**: Data/status objektif (mis. Draf, Diarsipkan, Offline).
- **`info`**: Panduan, tips, atau status sedang berlangsung (mis. Menunggu Verifikasi, Terjadwal).
- **`success`**: Konfirmasi positif / kondisi valid (mis. Terverifikasi, Selesai, Disetujui).
- **`warning`**: Peringatan yang dapat ditindaklanjuti / mendekati kuota atau tenggat (mis. Perlu
  Revisi). Harus menyertakan saran yang dapat ditindaklanjuti.
- **`error` / `danger`**: Kegagalan kritis, penolakan, atau tindakan destruktif permanen (mis. Hapus).
  Tindakan destruktif memerlukan konfirmasi dua langkah.

### Ikon Field Formulir

Setiap field formulir WAJIB menyertakan ikon untuk kejelasan visual:

| Tipe Field       | Contoh Ikon                            |
| ---------------- | -------------------------------------- |
| Input teks       | `o-user`, `o-envelope`, `o-phone`      |
| Kata sandi       | `o-key`, `o-lock-closed`               |
| Tanggal          | `o-calendar`                           |
| Waktu            | `o-clock`                              |
| Unggah berkas    | `o-cloud-arrow-up`                     |
| Pencarian        | `o-magnifying-glass`                   |
| Select/Dropdown  | `o-chevron-down`                       |

Ikon menggunakan Heroicons melalui `x-ts-icon` TallstackUI (atau SVG inline dengan `heroicon-*`).
Pasangkan ikon dengan label — jangan pernah menggunakan ikon sebagai satu-satunya indikator demi
aksesibilitas.

### Kelengkungan & Bentuk Lembut (Tanpa Sudut Tajam, Skala xs–l)

Sudut 90 derajat yang tajam (`rounded-none`) dan bentuk yang terlalu bulat (`rounded-2xl`,
`rounded-3xl`) dilarang keras pada semua elemen UI. Border radius dikalibrasi pada spektrum `xs` hingga
`l` (`rounded-xs` sampai `rounded-lg`) tergantung peran fungsionalnya ("tidak terlalu bulat, tidak
lancip"):

- **Kartu & permukaan struktural**: `rounded-lg` (0,5rem / 8px)
- **Input interaktif, select, textarea, tombol, dan alert**: `rounded-md` (0,375rem / 6px)
- **Badge, indikator status, tag, dan tooltip**: `rounded-sm` (0,25rem / 4px) hingga `rounded-md`
  (0,375rem / 6px)
- **Checkbox & mikro-kontrol**: `rounded-xs` (0,125rem / 2px) hingga `rounded-sm` (0,25rem / 4px)

### Hierarki Ringkas & Responsivitas Viewport

Antarmuka harus menghindari pembengkakan vertikal dekoratif dan padding berlebihan:

- **Formulir ringkas**: `space-y-3.5` (seluler) hingga `space-y-4` (desktop)
- **Padding kartu**: `p-5 sm:p-6` (kartu utama/auth) atau `p-4 sm:p-5` (kartu statistik/fitur)
- **Efisiensi viewport**: Layar auth dan alur kerja gambaran umum harus muat dengan nyaman di viewport
  standar tanpa memaksa pengguliran vertikal yang tidak perlu
- **Responsivitas tiga tingkat**: Kecil (<640px) menggunakan reflow satu kolom (`w-full`); sedang
  (640px–1024px) menggunakan dua kolom berimbang (`sm:grid-cols-2`); besar (≥1024px) menggunakan
  kepadatan multi-kolom (`lg:grid-cols-3` atau `lg:grid-cols-4`).

---

## 14. Presentasi Frontend & Blade

> 📖 Referensi pola: [Pola Livewire](guides/arch/livewire-pattern.md) (Aturan Komponen Tipis — tanpa
> logika bisnis di komponen atau Blade); [Pola UI](guides/arch/ui-pattern.md) (presentasi & desain
> komponen).

### 14.1 Blade Tidak Mengandung Logika Bisnis atau UI

Berkas Blade adalah **presentasi murni**. Tidak ada logika bisnis, tidak ada komputasi UI, sekecil apa
pun, yang boleh berada di berkas `.blade.php`. Semua state turunan, perhitungan, pemformatan, dan
percabangan kondisional yang bergantung pada aturan bisnis harus disiapkan sebelum render.

| Lokasi                 | Tanggung Jawab |
|------------------------|----------------|
| **Komponen Livewire**  | Properti terkomputasi, metrik turunan, peta status, nilai terformat. Ekspos data siap-render (`public array $pipelineStages`, `public string $throughputLabel`, metode seperti `getProgressPercent()`). |
| **Alpine.js (`x-data`)**| Interaktivitas sisi klien ringan (toggle, dropdown, filter lokal). Tanpa aturan bisnis sisi server. |
| **Blade**              | Ikat dan tampilkan: `{{ $value }}`, `@foreach ($items as $item)`, `@if ($isVisible)`. Tanpa blok `@php` berisi perhitungan, tanpa aritmetika inline melampaui tampilan trivial. |

**Aturan:**

- Blok `@php` di Blade **dilarang** kecuali untuk penetapan trivial non-bisnis yang murni presentasional
  dan tidak dapat dipindahkan (dan bahkan dalam kasus itu, utamakan properti terkomputasi Livewire).
  Jika Anda membutuhkan `@php` untuk menghitung persentase, rasio, atau status — pindahkan ke kelas
  Livewire.
- Perhitungan seperti `round(($a / $b) * 100)`, `max(...)`, penyusunan array untuk tahapan/funnel,
  pemformatan tanggal yang bermakna bisnis, dan visibilitas turunan izin harus dihitung di komponen
  Livewire dan diteruskan sebagai nilai biasa.
- `@if` / `@elseif` / `@else` dengan ekspresi PHP inline **sebaiknya dihindari**. Jangan menulis
  `@if ($user->role === 'admin' && $stats['x'] > 0)` atau `@if (auth()->user()->hasRole('super_admin'))`.
  Sebagai gantinya, ekspos boolean terkomputasi dari Livewire (`public bool $isSuperAdmin`,
  `public bool $showFunnel`) dan biarkan Blade hanya memeriksa flag, atau gunakan direktif Blade Spatie
  (`@hasrole`, `@role`, `@hasanyrole`) untuk visibilitas berbasis peran — mis.
  `@hasrole('super_admin')`. Untuk toggle sisi klien saja (modal, dropdown, filter), utamakan Alpine.js
  (`x-data`, `x-show`, `x-if`) di atas `@if` server untuk menghindari perjalanan bolak-balik.
- Sebagai panduan, **`@if` di Blade sebaiknya dihindari bila memungkinkan** — tidak dilarang, tetapi
  utamakan properti boolean Livewire (atau `@hasrole` Spatie untuk RBAC) untuk visibilitas yang
  digerakkan server dan state Alpine untuk visibilitas khusus UI. Jika `@if` tetap ada, ia harus berupa
  gerbang trivial atas boolean yang sudah dihitung (`@if ($isSuperAdmin)`), jangan pernah berupa
  ekspresi dengan logika bisnis.
- Saat meninjau PR, berkas Blade apa pun yang mengandung logika yang memengaruhi bisnis atau ekspresi
  kondisional di dalam `@if` adalah masalah yang memblokir.

**Contoh:**

```blade
{{-- ❌ Salah — logika bisnis di Blade --}}
@php $rate = $total > 0 ? round(($completed / $total) * 100) : 0; @endphp
<span>{{ $rate }}%</span>

{{-- ✅ Benar — Livewire menyiapkan nilainya --}}
// AdminDashboard.php: public int $completionRate; dihitung di mount()
<span>{{ $completionRate }}%</span>

```

```blade
{{-- ❌ Salah — penyusunan tahapan di Blade --}}
@php $stages = [['label'=>..., 'v'=>$totalSt], ...]; $maxV = max(array_column($stages,'v')); @endphp

{{-- ✅ Benar — Livewire mengekspos $pipelineStages dan $pipelineMaxV --}}
@foreach ($pipelineStages as $stage) ... @endforeach

```

Ditegakkan oleh: `docs/guides/arch/livewire-pattern.md` §1 (Komponen Tipis) dan skill agen
`livewire-development` + `tailwindcss-development`.

## 15. Lokalisasi

> 📖 Referensi pola: [Pola UX](guides/arch/ux-pattern.md) (lokalisasi & i18n);
> [Pola Modular §23 — Pola Lokalisasi](guides/arch/modular-pattern.md#23-localization-patterns).

### Struktur Berkas

Setiap modul dan submodul memiliki berkas terjemahan datarnya sendiri di bawah `lang/{locale}/`. Tanpa
subdirektori. Modul dapat memisahkan kunci submodul ke berkas terpisah menggunakan konvensi penamaan
`{submodule}.php` (tanpa awalan modul):

| Cakupan          | Berkas                                              | Contoh Kunci                                                                                                        |
| ---------------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Root modul       | `{module}.php`                                      | `auth.php` → `__('auth.login.*')`, `__('auth.forgot_password.*')`                                                   |
| Submodul         | `{submodule}.php`                                   | `login.php` → `__('login.failed')`, `__('login.throttle')`                                                         |
| Utilitas bersama | `common.php`                                        | Label global: `__('common.actions.save')`, `__('common.yes')`, `__('common.status.*')`                             |
| Spesifik domain  | `{domain}.php`                                      | `activity.php` → `__('activity.login_success')` (pencarian DB dinamis), `notifications.php` → `__('notifications.*')` |
| Framework        | `passwords.php`, `pagination.php`, `validation.php` | Berkas bawaan Laravel — biarkan seperti dikirimkan                                                                  |

Berkas submodul terkolokasi di direktori `lang/{locale}/` yang sama — tanpa subdirektori. Salinan `en`
dan `id` harus ada untuk setiap berkas submodul. Lihat `docs/guides/infra/localization.md`
§Berkas Terjemahan Submodul untuk detailnya.

### Aturan Penggunaan `__()`

1. **Setiap** string yang dilihat pengguna harus menggunakan `__()` — jangan pernah menuliskan teks
   tampilan secara hardcode.
2. Pengecualian: string layout di Blade yang murni label markup (mis. `<label>Name</label>` tanpa
   padanan dinamis) boleh tetap tidak diterjemahkan jika nilainya jelas dengan sendirinya dalam bahasa
   Inggris.
3. Parameter menggunakan sintaks `:param`: `__('user.welcome', ['name' => $user->name])`.

### Rujukan Silang

- `__('module.key')` diselesaikan ke kunci `key` di `lang/{locale}/module.php`.
- Locale `en` dan `id` harus selalu ada.
- Saat menambahkan kunci baru, tambahkan ke `lang/en/{file}.php` dan `lang/id/{file}.php`.

### Ketika Kunci Milik Beberapa Modul

Kunci yang digunakan lintas batas modul berada di salah satu berkas domain bersama:

| Berkas              | Isi                                                                 |
| ------------------- | ------------------------------------------------------------------- |
| `common.php`        | Label yang benar-benar global: `yes`, `no`, `actions`, `status`, label field |
| `notifications.php` | Pesan notifikasi — digunakan oleh beberapa modul                    |
| `activity.php`      | Deskripsi log aktivitas — diselesaikan secara dinamis dari DB       |
| `log.php`           | Nama event SmartLogger — jejak audit lintas sektor                  |

Jangan menduplikasi kunci antar berkas.

### Kunci Dinamis

Beberapa kunci diselesaikan secara dinamis dan tidak dapat diganti nama tanpa memengaruhi nilai basis
data:

```php
{{ __("activity.{$activity->description}") }}

```

Basis data menyimpan `login_success`, `user_created`, dll. sebagai deskripsi aktivitas. Kunci-kunci ini
harus tetap berada di `activity.php` dengan nama aslinya.

---

## Referensi Cepat

- [Indeks dokumentasi](index.md) — Telusuri semua dokumentasi
- [Issue spesifikasi fitur](https://github.com/reasvyn/internara/issues?q=label%3Aspec) — Semua spesifikasi fitur (#442–#509)
- [Catatan Keputusan Arsitektur](adr/index.md) — Keputusan kunci beserta alasannya
- [Indeks panduan](guides/index.md) — Panduan operasional
- [Indeks referensi modul](refs/modules/index.md) — Referensi API modul
