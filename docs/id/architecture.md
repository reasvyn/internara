# Arsitektur MVC Berbasis Action — Arsitektur 4 Lapisan, Alur Data & Aturan Dependensi

## Deskripsi

> **Spesifikasi otoritatif:** Kontrak arsitektur yang berlaku adalah
> spesifikasi `[D2FT3] — Architecture Design` (GitHub Issue `label:spec`, #443;
> `gh issue list --label spec --search "[D2FT3]"`). Dokumen ini adalah referensi
> hidup (pola, alur data, penamaan) untuk spesifikasi tersebut; bila keduanya
> berbeda, spesifikasi yang menang.

> **Telaah mendalam pola:** Untuk dokumentasi terfokus pada domain tertentu, lihat referensi pola
> khusus yang tercantum di setiap bagian di bawah.

---

## Filosofi

Internara menyusun kode berdasarkan **modul bisnis**, bukan berdasarkan lapisan teknis. Setiap
konsep bisnis — User, Academics, Program, Assessment — memiliki irisan vertikal lengkapnya sendiri:
persistensi, aturan bisnis, komponen UI, otorisasi, dan antarmuka HTTP.

Pelapisan datar (`app/Models/`, `app/Livewire/`, `app/Actions/`) menyebarkan satu fitur ke berbagai
direktori, membuat batas sulit dinalar, enkapsulasi mustahil ditegakkan, dan refaktor menjadi mahal.
Kolokasi modul menyelesaikan ini dengan memastikan semua yang berkaitan dengan "Enrollment" berada di
bawah `app/Modules/Enrollment/`.

Arsitektur ini mengambil inspirasi dari Domain-Driven Design (desain strategis, bounded context) dan
CQRS (pemisahan command/query) tanpa beban operasional basis data terpisah atau event sourcing.
Action menggantikan kelas Service tradisional untuk menegakkan tanggung jawab tunggal secara
struktural. DTO berperan sebagai objek batas yang tidak dapat diubah untuk mencegah kopling lapisan
dan dependensi melingkar.

---

## Arsitektur 4 Lapisan

### Empat Lapisan

Sistem ini disusun menjadi empat lapisan dengan dependensi ketat hanya ke bawah. Setiap lapisan hanya
bergantung pada lapisan di bawahnya — tidak pernah sebaliknya.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Lapisan 4: PRESENTASI / UI                                                  │
│                                                                             │
│  Komponen Livewire  Template Blade  Controller  Perintah Console            │
│  Policy (gerbang RBAC / otorisasi)  Rute (17 berkas rute web incl. web.php) │
│  TallstackUI v4 + Alpine.js + Tailwind CSS v4 (+ shim palet self-hosted)    │
│                                                                             │
│  app/{Module}/**/Livewire/  app/{Module}/**/Policies/  app/{Module}/Http/  │
│  resources/views/{module}/  routes/web/{module}.php (+ {submodule}.php)    │
│  Lihat: docs/guides/arch/livewire-pattern.md                               │
│  Lihat: docs/guides/arch/policy-pattern.md                                 │
│  Lihat: docs/guides/arch/ui-pattern.md                                     │
│  Lihat: docs/guides/arch/ux-pattern.md                                     │
└──────────────────────────────────────────────────────────────────────────▲──┘
                                                                           │
┌──────────────────────────────────────────────────────────────────────────┴──┐
│  Lapisan 3: OPERASI BISNIS / DOMAIN                                          │
│                                                                             │
│  Command Action (mutasi — transaksi + log + event)                          │
│  Read Action (kueri kompleks — tanpa transaksi)                             │
│  Process Action (orkestrasi multi-langkah)                                  │
│  Event + Listener + Notifikasi                                              │
│  Middleware                                                                 │
│                                                                             │
│  app/{Module}/**/Actions/  →  1 kelas = 1 use case                         │
│  app/{Module}/**/Events/  app/{Module}/**/Listeners/                        │
│  Lihat: docs/guides/arch/action-pattern.md                                 │
│  Lihat: docs/guides/arch/event-pattern.md                                  │
└──────────────────────────────────────────────────────────────────────────▲──┘
                                                                           │
┌──────────────────────────────────────────────────────────────────────────┴──┐
│  Lapisan 2: DATA / PERSISTEN                                                 │
│                                                                             │
│  Model Eloquent (mewarisi BaseModel, PK UUID, #[Fillable], HasFactory)     │
│  Entity (final readonly — aturan bisnis murni)                              │
│  DTO (BaseData — objek batas yang tidak dapat diubah)                       │
│  Enum (LabelEnum, StatusEnum, ColorableEnum)                                │
│  Basis data: SQLite (dev/test + Docker) / MySQL (hosting / tingkatan VPS)   │
│  Konfigurasi: .env, config/*.php, tabel pengaturan Runtime                  │
│  Berkas: Spatie Media Library  Cache  Antrean  Sesi                         │
│                                                                             │
│  app/{Module}/**/Models/  app/{Module}/**/Entities/                         │
│  app/{Module}/**/Enums/  app/{Module}/**/Data/                             │
│  app/Modules/Core/Data/  app/Modules/Core/Exceptions/                                       │
│  database/migrations/  config/  storage/                                    │
│  Lihat: docs/guides/arch/model-pattern.md                                  │
│  Lihat: docs/guides/arch/entity-pattern.md                                 │
│  Lihat: docs/guides/arch/enum-pattern.md                                   │
│  Lihat: docs/guides/arch/data-pattern.md                                   │
│  Lihat: docs/guides/infra/database.md                                      │
└──────────────────────────────────────────────────────────────────────────▲──┘
                                                                           │
┌──────────────────────────────────────────────────────────────────────────┴──┐
│  Lapisan 1: FRAMEWORK / INFRASTRUKTUR / UTILITAS                             │
│                                                                             │
│  Kelas Dasar Core: BaseModel, BaseAction, BaseEntity, BasePolicy,           │
│                    BaseRecordManager, BaseData, BaseEvent, dll.             │
│  Kontrak Core: LabelEnum, StatusEnum, ColorableEnum                         │
│                SendsNotifications, SettingsStore                            │
│  Hierarki Exception: AppException + ModuleException (dua pohon)             │
│  Service (logika infrastruktur — metode instance, injeksi konstruktor)      │
│  Support (utilitas statis — tanpa efek samping, tanpa DI)                   │
│  PHP 8.5 + Laravel + paket Composer/Spatie                                  │
│                                                                             │
│  app/Modules/Core/{Actions,Models,Policies,Livewire,Data,Events,Exceptions,...}    │
│  app/Modules/Core/Services/  app/Modules/Core/Support/                                     │
│  app/{Module}/Services/  app/{Module}/Support/                             │
│  Lihat: docs/guides/arch/action-pattern.md                                 │
│  Lihat: docs/guides/arch/service-pattern.md                                │
│  Lihat: docs/guides/arch/support-pattern.md                                │
│  Lihat: docs/guides/arch/exception-pattern.md                              │
└─────────────────────────────────────────────────────────────────────────────┘

```

### Bagaimana Direktori Modul Dipetakan ke Lapisan

| Lapisan                        | Direktori / Lokasi                                                                                                            |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------- |
| **4 — Presentasi/UI**          | `{SubModule}/Livewire/`, `resources/views/{module}/`, `routes/web/{module}.php` (+ `{submodule}.php`), `{SubModule}/Policies/`, `{SubModule}/Http/` |
| **3 — Operasi Bisnis/Domain**  | `{SubModule}/Actions/`, `{SubModule}/Events/`, `{SubModule}/Listeners/`, `{SubModule}/Notifications/`, `Console/`             |
| **2 — Data/Persisten**         | `{SubModule}/Models/`, `{SubModule}/Entities/`, `{SubModule}/Enums/`, `{SubModule}/Data/`, `Types/`, basis data, konfigurasi   |
| **1 — Framework/Infra**        | `app/Modules/Core/`, `{SubModule}/Services/`, `{SubModule}/Support/`, PHP, Laravel, paket                                          |

Berkas lintas submodul (Action bersama, Http, Console) berada di root modul langsung di bawah
`app/{Module}/`.

Untuk pohon direktori lengkap dan konvensi jalur, lihat
[Pola Modular](guides/arch/modular-pattern.md).

### Aturan Dependensi Lapisan

1. **Hanya ke bawah**: Sebuah lapisan hanya boleh bergantung pada lapisan **di bawahnya**.
2. **Kemandirian Core**: Core (Lapisan 1) tidak bergantung pada apa pun kecuali paket Laravel dan
   Spatie. Tidak ada modul bisnis yang boleh diimpor oleh Core.
3. **Impor sesama lapisan diizinkan**: Modul di Lapisan 4 boleh mengimpor modul lain secara langsung.
   Utamakan event ketika ada efek samping.
4. **Isolasi persistensi**: Action (Lapisan 3) mendelegasikan ke Model (Lapisan 2) melalui dependensi
   yang diinjeksi — jangan pernah memanggil Eloquent secara langsung.
5. **Isolasi UI**: Komponen Livewire (Lapisan 4) tidak boleh mengimpor komponen Livewire modul lain
   secara langsung. Gunakan event atau pengalihan.
6. **Kemurnian Entity**: Entity (Lapisan 2) tidak boleh mengimpor Action (Lapisan 3) atau Livewire
   (Lapisan 4).
7. **Kepemilikan DTO**: DTO (Lapisan 2) dimiliki oleh Action yang mengonsumsinya — didefinisikan di
   modul tempat Action itu berada, bukan di lokasi bersama.

---

## Alur Data 4 Lapisan

```
┌──────────────────────────────────────────────────────────────────────────┐
│                       ALUR DATA 4 LAPISAN                                 │
│                                                                           │
│  ┌──────────────┐     ┌──────────────────┐     ┌──────────────────┐      │
│  │  PRESENTASI  │     │   BISNIS /       │     │   DATA /         │      │
│  │     / UI     │ ──► │  OPERASI DOMAIN  │ ──► │  PERSISTEN       │      │
│  │              │     │                  │     │                  │      │
│  │ Livewire     │     │ Action*          │     │ Model            │      │
│  │ Controller   │     │ Event            │     │ Entity           │      │
│  │ Console      │     │ Middleware       │     │ DTO              │      │
│  │ Policy       │     │                  │     │ Enum             │      │
│  │              │     │ * logika domain  │     │ Basis data       │      │
│  │              │     │                  │     │ Konfigurasi      │      │
│  └──────┬───────┘     └──────┬───────────┘     └────────┬─────────┘      │
│         │                    │                          │                │
│         │  DTO/FormReq      │      DTO                 │  Rekaman       │
│         │  /LivewireForm    │      (input)             │  Model         │
│         └──────────────────►└──────────────────────────►┘                │
│                                                                           │
│  FRAMEWORK / INFRASTRUKTUR / UTILITAS (Lapisan 1)                         │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │  Kelas dasar Core, kontrak, exception, Service (logika infra),    │    │
│  │  Support (util statis), paket PHP/Laravel/Spatie                  │    │
│  └──────────────────────────────────────────────────────────────────┘    │
│                                                                           │
│  KONTRAK BATAS:                                                            │
│  UI → Bisnis:   DTO (BaseData) atau skalar bertipe — jangan Request mentah │
│  Bisnis → Domain: Rekaman Model untuk konstruksi Entity                   │
│  Domain → Data:   Atribut Model dari nilai DTO                            │
│                                                                           │
│  🚫  JANGAN PERNAH: UI → Model::create() secara langsung                  │
│  🚫  JANGAN PERNAH: Bisnis → kelas UI                                     │
│  🚫  JANGAN PERNAH: Entity → Action atau Service                          │
└──────────────────────────────────────────────────────────────────────────┘

```

### Dua Jenis Logika: Domain vs Infrastruktur

Proyek ini membedakan **tiga** tipe kelas — Action (Lapisan 3), Service (Lapisan 1), Support
(Lapisan 1) — tetapi hanya Action yang memuat **logika bisnis domain**. Pembedaan ini krusial dan
mencegah perluasan cakupan Service yang justru memicu lahirnya Action Triad.

| Jenis Logika               | Apa Itu                                                                                                                                          | Contoh                                                          | Berada Di                                          |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------- | -------------------------------------------------- |
| **Logika bisnis domain**   | Aturan tentang _domain produk Anda_ — PKL, siswa, nilai, pendaftaran, sertifikat. Ini berubah ketika **kebutuhan bisnis** berubah.              | "Siswa harus punya penempatan aktif sebelum melakukan clock-in." | Action (Command/Read/Process) + Entity             |
| **Logika infrastruktur**   | Aturan tentang _framework atau sistem_ — pemeriksaan lingkungan, perutean UI, penemuan modul. Ini berubah ketika **tumpukan teknologi** berubah. | "Tentukan rute dasbor mana yang dituju berdasarkan peran pengguna." | Service (metode instance, injeksi konstruktor)     |
| **Utilitas statis**        | Transformasi murni tanpa efek samping — tanpa DB, tanpa event, tanpa keputusan bisnis.                                                          | "Samarkan alamat email pada keluaran log."                      | Support (hanya metode `public static`)             |

**Alur keputusan cepat:**

```
Apakah kelas tersebut memuat aturan bisnis tentang PKL, siswa, nilai, dll.?
├─ Ya → Action + Entity (wajib punya transaksi, logging, dukungan event)
└─ Tidak → Apakah butuh injeksi konstruktor / metode instance?
    ├─ Ya → Service (hanya logika infrastruktur)
    └─ Tidak → Support (hanya utilitas statis)

```

Untuk panduan menyeluruh tentang setiap tipe, lihat:

- [Pola Action](guides/arch/action-pattern.md) — logika bisnis domain
- [Pola Service](guides/arch/service-pattern.md) — logika infrastruktur
- [Pola Support](guides/arch/support-pattern.md) — utilitas statis

Untuk referensi alur data lengkap (alur mutasi, alur baca, alur event, siklus hidup DTO, tabel
penyeberangan batas), lihat [Pola Data](guides/arch/data-pattern.md).

---

## Action Triad

Action Triad adalah keputusan arsitektur terpenting di Internara. Action terbagi menjadi tiga
kategori berbeda, masing-masing dengan kontrak spesifik. Ketiganya berada di
`app/{Module}/{SubModule}/Actions/` dan mengikuti konvensi satu metode `execute()`.

| Tipe        | Tujuan                                                  | Kelas Dasar         | Transaksi | Logging  | Event       |
| ----------- | ------------------------------------------------------- | ------------------- | --------- | -------- | ----------- |
| **Command** | Semua penulisan — create, update, delete, transisi status | `BaseCommandAction` | Wajib     | Wajib    | Dianjurkan  |
| **Read**    | Kueri kompleks, agregasi, dasbor                         | `BaseReadAction`    | Tidak pernah | Tidak pernah | Tidak pernah |
| **Process** | Orkestrasi multi-langkah Action Command/Read             | `BaseProcessAction` | Wajib     | Wajib    | Wajib       |

**Aturan kunci:**

- Setiap Action memiliki tepat satu metode publik: `execute()`
- Action adalah **satu-satunya** titik masuk untuk mutasi — Livewire tidak pernah memanggil
  `Model::create()` secara langsung
- Action Command/Process SEBAIKNYA menerima DTO `BaseData` untuk 3+ parameter, gunakan skalar
  bertipe untuk 1-2 parameter
- Action Command/Process SEBAIKNYA mengembalikan `ActionResponse` untuk umpan balik terstruktur
- Action WAJIB mendelegasikan aturan bisnis ke Entity — lemparkan `RejectedException` saat dilanggar

Untuk referensi lengkap (kontrak, penamaan, contoh kode, ActionResponse, jalur migrasi), lihat
[Pola Action](guides/arch/action-pattern.md).

---

## Pencegahan Dependensi Melingkar

Arsitektur ini mencegah dependensi melingkar melalui empat mekanisme struktural:

1. **Arah lapisan yang ketat** — Dependensi mengalir satu arah: UI → Bisnis → Domain → Data
2. **DTO sebagai objek batas** — Hanya membawa skalar, enum, Carbon — jangan pernah Model atau Action
3. **Pemeriksaan bisnis hanya di Entity** — Entity bersifat `final readonly` tanpa dependensi framework
4. **Pemisahan berbasis event** — Efek samping lintas modul melalui event, bukan panggilan langsung

### Aturan Keamanan Dependensi

| #   | Aturan                                                                                                                  | Contoh Pelanggaran                                         |
| --- | ----------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| R1  | **DTO adalah kelas daun.** Hanya membawa skalar, enum, Carbon. Jangan pernah Model, Action, Entity.                     | `CompanyData` dengan properti `Company`                    |
| R2  | **Entity (Lapisan 2) tidak boleh mengimpor lapisan Bisnis/UI (Lapisan 3/4).**                                          | `RegistrationState` mengimpor `ApproveRegistrationAction`  |
| R3  | **Lapisan 4 (UI) tidak boleh menulis langsung ke Model Lapisan 2.** Semua persistensi melalui Action Command (Lapisan 3). | `Company::create(...)` di Livewire                         |
| R4  | **UI boleh mengakses Entity hanya untuk pemeriksaan BACA-SAJA.** Keputusan TULIS melalui Action.                       | `$reg->asState()->canBeApproved()` sebelum memanggil Action |
| R5  | **Action mengutamakan DTO untuk input kompleks.** 3+ parameter → DTO. 1-2 skalar bertipe → boleh. Jangan pernah `array` mentah. | `execute(array $data)` untuk operasi 5 parameter           |
| R6  | **Service (Lapisan 1) tidak boleh memanggil Action (Lapisan 3).** Service memiliki logika infrastruktur (bukan logika bisnis domain). | Service memanggil `CreateCompanyAction`                    |
| R7  | **Entity (Lapisan 2) tidak boleh melakukan I/O.** Tanpa kueri DB, HTTP, cache, event, facade.                          | Entity memanggil `Cache::get()`                            |

Untuk skenario dependensi melingkar yang rinci, deteksi, dan perbaikannya, lihat
[Pola Modular](guides/arch/modular-pattern.md).

---

## Komunikasi Lintas Modul

Impor lintas modul **diizinkan** — impor Model, Action, atau Policy dari modul saudara secara
langsung. Empat pola tersedia:

| Pola                | Kapan Digunakan                                                                |
| ------------------- | ------------------------------------------------------------------------------ |
| **Impor langsung**  | Akses lugas tanpa efek samping                                                 |
| **Panggilan Action**| Operasi bisnis lintas modul                                                    |
| **Event modul**     | Efek samping fire-and-forget (notifikasi, invalidasi cache)                    |
| **Kontrak Core**    | Abstraksi yang dipakai luas antar modul (`LabelEnum`, `SendsNotifications`)    |

Lihat [Disiplin Komunikasi Lintas Modul](adr/adr-cross-module-communication.md).

---

## Peta Referensi

| Topik                                                                                | Dokumen                                                                                                |
| ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------ |
| **Mandat kelas dasar**                                                               | [Konvensi Pengodean](conventions.md) §1                                                                |
| **Katalog pola lengkap**                                                             | [Referensi Pola Modular](guides/arch/modular-pattern.md)                                               |
| **Kontrak & contoh Action**                                                          | [Pola Action](guides/arch/action-pattern.md)                                                           |
| **Pemisahan Entity-Model**                                                           | [Pola Entity](guides/arch/entity-pattern.md)                                                           |
| **Konvensi Model**                                                                   | [Pola Model](guides/arch/model-pattern.md)                                                             |
| **DTO & ActionResponse**                                                             | [Pola Data](guides/arch/data-pattern.md)                                                               |
| **Enum & state machine**                                                             | [Pola Enum](guides/arch/enum-pattern.md)                                                               |
| **Dispatch event & listener**                                                        | [Pola Event](guides/arch/event-pattern.md)                                                             |
| **Aturan komponen Livewire**                                                         | [Pola Livewire](guides/arch/livewire-pattern.md)                                                       |
| **Konsistensi UI & visual**                                                          | [Pola UI](guides/arch/ui-pattern.md)                                                                   |
| **UX & aksesibilitas**                                                               | [Pola UX](guides/arch/ux-pattern.md)                                                                   |
| **Policy & RBAC**                                                                    | [Pola Policy](guides/arch/policy-pattern.md)                                                           |
| **Hierarki exception**                                                               | [Pola Exception](guides/arch/exception-pattern.md)                                                     |
| **Logging & penyamaran PII**                                                         | [Pola Logging](guides/arch/logging-pattern.md)                                                         |
| **Strategi caching**                                                                 | [Pola Cache](guides/arch/cache-pattern.md)                                                             |
| **Service vs Support vs Action** (logika domain vs logika infra vs utilitas statis)  | [Pola Service](guides/arch/service-pattern.md), [Pola Support](guides/arch/support-pattern.md)         |
| **Mengapa tanpa Repository**                                                         | [Pola Repository](guides/arch/repository-pattern.md)                                                   |
| **Pola pengujian**                                                                   | [Pola Pengujian](guides/arch/testing-pattern.md)                                                       |
| **Strategi validasi**                                                                | [Pola Modular](guides/arch/modular-pattern.md) §4                                                      |
| **Struktur & penamaan modul**                                                        | [Pola Modular](guides/arch/modular-pattern.md)                                                         |
| **Gambaran 19 modul (17 bisnis + UI + Core)**                                        | [Indeks Modul](refs/modules/index.md)                                                                  |
| **Invarian modul**                                                                   | [Konvensi Pengodean](conventions.md), AGENTS.md                                                        |

---

## Referensi Cepat

- [Indeks dokumentasi](index.md) — Telusuri semua dokumentasi
- [Issue spesifikasi fitur](https://github.com/reasvyn/internara/issues?q=label%3Aspec) — Semua spesifikasi fitur (#442–#509)
- [Catatan Keputusan Arsitektur](adr/index.md) — Keputusan kunci beserta alasannya
- [Indeks panduan](guides/index.md) — Panduan operasional
- [Indeks referensi modul](refs/modules/index.md) — Referensi API modul
