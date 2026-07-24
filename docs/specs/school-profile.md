# School Profile — Settings-Based Entity Management

> **Last updated:** 2026-07-22 **Changes:** feat — split from institutional-and-academics.md;
> school profile via Settings, SchoolEntity, SaveSchoolProfileAction, logo upload, cache

## Description

Specification of Internara's school profile feature: a settings-based entity that stores school
identity information as individual `Setting` records under the `school.*` namespace. Covers
`SchoolEntity`, `SaveSchoolProfileAction`, logo upload, form object, and cache invalidation.
Departments and academic years are defined in [department-management.md](department-management.md) and [academic-year-management.md](academic-year-management.md).

---

## 1. Problem Statements

### PS-1 — School Profile Without a Dedicated Model

The school profile (name, institutional code, email, address, phone, website, principal name)
is conceptually a single entity, but has no dedicated database table. This is intentional: the
Settings module already provides caching, validation, type casting, and observer-based cache
invalidation. Creating a `schools` table for 7 string columns would duplicate this infrastructure
and fragment the Settings resolution chain — every place that reads `school.name` via the
`setting()` helper would break.

### PS-2 — Typed Access Without Eloquent

Because there is no Eloquent model, the school profile cannot use `Model::findOrFail()` or
relationship accessors. The system needs a typed value object (`SchoolEntity`) that reads from
the Settings store and provides named accessors — otherwise every consumer would manually call
`setting('school.name')` with string keys and cast results.

### PS-3 — Atomic Save Across Multiple Setting Keys

A school profile update touches 7+ keys. Without atomic writes, a failure mid-save could leave
the profile partially updated — name changed but email stale. The system must ensure all keys
are written or none are.

### PS-4 — Cache Consistency After Profile Updates

`SchoolEntity` resolves from cached Settings. If a profile update doesn't invalidate the
`school_entity` cache key, subsequent reads serve stale data. The system must synchronously
invalidate the entity cache after every save.

### PS-5 — Logo Upload as Brand Asset

The school logo is displayed on certificates, reports, and official documents. It must be
uploaded via Spatie Media Library and its URL persisted as a setting, independently of the
profile save flow.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide school profile management via Settings infrastructure (no dedicated model) |
| G2  | Deliver typed `SchoolEntity` value object with named accessors for all 7 properties |
| G3  | Atomic save of all school profile fields via `BatchSetSettingAction` transaction |
| G4  | Synchronous cache invalidation of `school_entity` key after profile save |
| G5  | Logo upload, storage, and removal via Spatie Media Library |
| G6  | Form object (`SchoolForm`) with validation rules that load from and serialize to `SchoolEntity` |
| G7  | Live logo upload via Livewire `updated*` hook without requiring a full form save |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-school / multi-tenant support (single-tenant only) |
| NG2  | School logo auto-generation, cropping, or resizing beyond Spatie conversions |
| NG3  | Dedicated `School` model, migration, or Eloquent relationships |
| NG4  | School profile versioning, audit trail, or rollback |
| NG5  | Settings infrastructure, resolution chain, or type system (see [settings-infrastructure.md](settings-infrastructure.md)) |
| NG6  | Branding, theme, or locale management (see [branding-theme-locale.md](branding-theme-locale.md)) |
| NG7  | Department management (see [department-management.md](department-management.md)) |
| NG8  | Academic year management (see [academic-year-management.md](academic-year-management.md)) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Updates School Profile

**Actor:** Admin / Super Admin
**Preconditions:** Admin is authenticated with `super_admin` or `admin` role; Settings permission granted
**Flow:**
1. Admin navigates to Settings → School Profile (`/admin/school`)
2. `SchoolEditor` Livewire component mounts, authorizes via `SettingPolicy`
3. `SchoolForm::loadFromEntity()` reads `SchoolEntity::get()` and populates form fields
4. Admin updates one or more fields: name, institutional code, email, address, phone, website, principal name
5. Admin clicks Save
6. `SchoolEditor::save()` validates form, calls `SaveSchoolProfileAction::execute(data: form.toPayload())`
7. `SaveSchoolProfileAction` executes within a transaction:
   - Maps each field to `SettingEntryData(key: "school.{key}", value: value)`
   - Calls `BatchSetSettingAction` for atomic upsert
   - Forgets `school_entity` cache key
8. Form reloads from fresh `SchoolEntity::get()`
9. Flash message confirms save
**Postconditions:** All school profile fields updated atomically, cache invalidated, UI refreshed

### UC-2 — Admin Uploads School Logo

**Actor:** Admin
**Preconditions:** Admin is on the School Profile editor page
**Flow:**
1. Admin selects a file in the logo upload field
2. Livewire `updatedLogoFile` hook fires immediately
3. Validates: `nullable|image|max:2048` (KB)
4. `UploadBrandAssetAction::execute()` stores file via Spatie Media Library
5. `SetSettingAction::execute()` persists the URL under `brand_logo` key
6. Component updates logo preview via `logoPreviewUrl()`
7. Flash message confirms logo saved
**Postconditions:** Logo uploaded, URL persisted, preview updated

### UC-3 — Admin Removes School Logo

**Actor:** Admin
**Preconditions:** A logo is currently set
**Flow:**
1. Admin clicks "Remove Logo" and confirms via `showConfirm` modal
2. `confirmAction()` authorizes, calls `RemoveBrandAssetAction::execute('logo')`
3. `Settings::forget('brand_logo')` clears the setting key
4. Flash message confirms removal
**Postconditions:** Logo removed from media collection and setting, preview cleared

---

## 4. Functional Requirements

### School Entity

| ID   | Requirement |
| ---- | ----------- |
| FR-SP1 | `SchoolEntity` must be a `final readonly class` extending `BaseEntity` with 7 typed `string` properties |
| FR-SP2 | `SchoolEntity` must define a `KEYS` constant mapping property names to `school.*` setting keys |
| FR-SP3 | `SchoolEntity::get()` must read all 7 keys from Settings store via `Settings::get()` and return a populated instance |
| FR-SP4 | `SchoolEntity::keys()` must return the `KEYS` constant array for iteration by setup and other consumers |
| FR-SP5 | `SchoolEntity::fromModel()` must delegate to `SchoolEntity::get()` (no Model dependency) |
| FR-SP6 | `SchoolEntity` must provide named accessors: `name()`, `institutionalCode()`, `email()`, `address()`, `phone()`, `website()`, `principalName()` |

### Save Action

| ID   | Requirement |
| ---- | ----------- |
| FR-SP7 | `SaveSchoolProfileAction` must extend `BaseCommandAction` and accept `array $data` and optional `?UploadedFile $logoFile` |
| FR-SP8 | Must execute within `BaseCommandAction::transaction()` for atomicity |
| FR-SP9 | Must map each `$data` key to `SettingEntryData(key: "school.{$key}", value: $value)` |
| FR-SP10 | Must call `BatchSetSettingAction::execute(...$entries)` for atomic batch upsert |
| FR-SP11 | Must call `UploadBrandAssetAction::execute()` when `$logoFile` is provided |
| FR-SP12 | Must forget `school_entity` cache key via `Cache::forget()` after write |
| FR-SP13 | Must log `school_profile_updated` event with affected keys |

### Form Object

| ID   | Requirement |
| ---- | ----------- |
| FR-SP14 | `SchoolForm` must extend Livewire `Form` with 8 properties: `name`, `institutional_code`, `email`, `phone`, `fax`, `address`, `website`, `principal_name` |
| FR-SP15 | `rules()` must validate: `name` required/max:255, others nullable with type-specific rules (email, url, max) |
| FR-SP16 | `loadFromEntity()` must read `SchoolEntity::get()` and populate all form properties |
| FR-SP17 | `toPayload()` must return associative array mapping form fields to setting key suffixes |

### Livewire Component

| ID   | Requirement |
| ---- | ----------- |
| FR-SP18 | `SchoolEditor` must extend `BaseFormView` with `WithFileUploads` trait |
| FR-SP19 | `mount()` must authorize via `Setting::class` policy and load form from entity |
| FR-SP20 | `save()` must authorize, validate, call `SaveSchoolProfileAction`, reload form, flash success |
| FR-SP21 | `updatedLogoFile()` must authorize, validate (`image|max:2048`), upload, persist URL, flash success |
| FR-SP22 | `confirmAction()` must authorize, remove logo via `RemoveBrandAssetAction`, forget setting, flash |
| FR-SP23 | `logoPreviewUrl()` must return temporary URL for pending upload or current logo URL |

### Cache Invalidation

| ID   | Requirement |
| ---- | ----------- |
| FR-SP24 | `school_entity` cache key must be registered in `config/cache-keys.php` as `academics.school.entity` |
| FR-SP25 | Must synchronously invalidate `school_entity` cache key after writing entries |
| FR-SP26 | `SchoolEntity::get()` resolution from cache must complete in < 50ms on hit |

### Routes

| ID   | Requirement |
| ---- | ----------- |
| FR-SP27 | Route must be `GET /admin/school`, name `sysadmin.school`, middleware `['auth', 'role:super_admin\|admin']` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | `SchoolEntity` resolution from cache must complete in < 50ms (cache hit) |
| NFR-P2 | `SaveSchoolProfileAction` with 7 fields must complete in < 2s including DB write |
| NFR-S1 | Setting keys must match `^[a-z][a-z0-9_.]*$` pattern to prevent injection |
| NFR-S2 | Logo upload must validate MIME type and file size server-side (`image|max:2048`) |
| NFR-S3 | `SchoolEditor` must authorize all mutations via `Setting::class` policy |
| NFR-S4 | Logo removal must delete from Spatie Media Library and clear setting key atomically |
| NFR-R1 | Profile save must be atomic — all 7 keys written or none (`BatchSetSettingAction` transaction) |
| NFR-R2 | Cache invalidation must be synchronous (not queued) to prevent stale reads |
| NFR-U1 | Form must display all 7 fields with appropriate input types (text, email, URL) |
| NFR-U2 | Logo upload must show live preview without page reload |
| NFR-U3 | Logo removal must require confirmation dialog before executing |
| NFR-U4 | Flash messages must confirm save, logo upload, and logo removal actions |
| NFR-A1 | All form inputs must have associated `<label>` elements (WCAG 2.1 Level AA) |
| NFR-A2 | Logo upload field must include alt text for screen readers |
| NFR-A3 | Flash messages must be announced via `aria-live` region |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-M1 | `SchoolEntity` must be accessed via `::get()`, not direct `setting()` calls in consumers |

---

## 6. API / Data Contracts

### 6.1 SchoolEntity

```php
// app/Academics/School/Entities/SchoolEntity.php
final readonly class SchoolEntity extends BaseEntity
{
    private const array KEYS = [
        'name'               => 'school.name',
        'institutional_code' => 'school.institutional_code',
        'email'              => 'school.email',
        'address'            => 'school.address',
        'phone'              => 'school.phone',
        'website'            => 'school.website',
        'principal_name'     => 'school.principal_name',
    ];

    public function __construct(
        private string $name,
        private string $institutionalCode,
        private string $email,
        private string $address = '',
        private string $phone = '',
        private string $website = '',
        private string $principalName = '',
    ) {}

    public static function keys(): array;
    public static function fromModel(Model $model): static;
    public static function get(): self;
    public function name(): string;
    public function institutionalCode(): string;
    public function email(): string;
    public function address(): string;
    public function phone(): string;
    public function website(): string;
    public function principalName(): string;
}
```

- `KEYS` maps property names to `school.*` setting keys
- `get()` reads via `Settings::get(array_values(self::KEYS))` — single batch query
- `fromModel()` delegates to `get()` — no Eloquent model dependency
- All accessors return `string`, defaulting to `''` when settings are absent

### 6.2 SaveSchoolProfileAction

```php
// app/Academics/School/Actions/SaveSchoolProfileAction.php
final class SaveSchoolProfileAction extends BaseCommandAction
{
    public function __construct(
        protected readonly BatchSetSettingAction $batchSetSetting,
        protected readonly UploadBrandAssetAction $uploadBrandAsset,
    ) {}

    public function execute(array $data, ?UploadedFile $logoFile = null): void;
}
```

Wraps in `transaction()`: maps `$data` → `SettingEntryData("school.{$key}")`, uploads logo if
provided, calls `BatchSetSettingAction`, forgets `school_entity` cache, logs update.

### 6.3 SchoolForm

```php
// app/Academics/School/Livewire/Forms/SchoolForm.php
class SchoolForm extends Form
{
    public string $name = '';
    public string $institutional_code = '';
    public string $email = '';
    public string $phone = '';
    public string $fax = '';
    public string $address = '';
    public string $website = '';
    public string $principal_name = '';

    public function rules(): array;
    public function loadFromEntity(): void;
    public function toPayload(): array;
}
```

| Field | Rules |
| ----- | ----- |
| `name` | `required\|string\|max:255` |
| `institutional_code` | `nullable\|string\|max:50` |
| `email` | `nullable\|email\|max:255` |
| `phone` | `nullable\|string\|max:50` |
| `fax` | `nullable\|string\|max:50` |
| `address` | `nullable\|string\|max:500` |
| `website` | `nullable\|url\|max:255` |
| `principal_name` | `nullable\|string\|max:255` |

### 6.4 SchoolEditor

```php
// app/Academics/School/Livewire/SchoolEditor.php
class SchoolEditor extends BaseFormView
{
    use WithFileUploads;
    public SchoolForm $form;
    public $logo_file = null;
    public bool $showConfirm = false;

    public function mount(): void;
    public function updatedLogoFile(UploadBrandAssetAction, SetSettingAction): void;
    public function save(SaveSchoolProfileAction $action): void;
    public function logoPreviewUrl(): ?string;
    public function confirmAction(): void;
    public function render(): View;
}
```

### 6.5 Setting Keys

| Key | Type | Default |
| --- | ---- | ------- |
| `school.name` | string | `''` |
| `school.institutional_code` | string | `''` |
| `school.email` | string | `''` |
| `school.address` | string | `''` |
| `school.phone` | string | `''` |
| `school.website` | string | `''` |
| `school.principal_name` | string | `''` |

### 6.6 Cache Key

| Config Key | Cache Value | TTL |
| ---------- | ----------- | --- |
| `school_entity` | `academics.school.entity` | forever |

### 6.7 Route

```
GET /admin/school → SchoolEditor (Livewire)
Name: sysadmin.school
Middleware: auth, role:super_admin|admin
```

---

## 7. Design Decisions

### DD-1 — School Profile via Settings, Not a Dedicated Model

**Decision:** School profile stored as individual `Setting` records under `school.*` namespace,
not a dedicated `School` model/table.
**Rationale:** The Settings module already provides caching, validation, type casting, and
observer-based invalidation. A dedicated School model would duplicate this infrastructure for
7 string columns. The `SchoolEntity` class provides typed access without Eloquent overhead.
The `setting()` helper and `Settings::get()` resolution chain work seamlessly with `school.*`
keys without any adapter layer.
**Trade-off:** No Eloquent relationships (school → departments). Acceptable because school is a
singleton entity with no relational queries needed. No `School::find()` or `School::where()`
patterns — all access goes through `SchoolEntity::get()`.

### DD-2 — SchoolEntity as Final Readonly Value Object

**Decision:** `SchoolEntity` is `final readonly` with a private `KEYS` constant and named
accessors.
**Rationale:** `final` prevents subclassing. `readonly` ensures immutability. The `KEYS` constant
provides a single source of truth for property-to-key mapping. Named accessors provide typed
access without magic methods.
**Trade-off:** No fluent setter. Acceptable — mutations go through `SaveSchoolProfileAction`.

### DD-3 — Batch Save via BatchSetSettingAction

**Decision:** `SaveSchoolProfileAction` maps fields to `SettingEntryData` and delegates to
`BatchSetSettingAction` for atomic upsert.
**Rationale:** `BatchSetSettingAction` handles transactions, type auto-detection, and
observer-triggered cache invalidation. Reusing it prevents duplicate transaction logic.
**Trade-off:** Extra indirection (Action → Action). Acceptable — the batch action is the canonical
way to write multiple settings atomically.

### DD-4 — Logo Upload Independent of Profile Save

**Decision:** Logo upload fires immediately via `updatedLogoFile` hook, separate from save.
**Rationale:** File uploads can fail. Immediate upload provides instant feedback and allows retry.
Bundling with profile save would block the entire save on upload failure.
**Trade-off:** Orphaned files if user uploads logo but never saves. Mitigated by < 2MB size and
local storage. `RemoveBrandAssetAction` provides cleanup.

### DD-5 — Cache Key Registered in Config

**Decision:** `school_entity` cache key declared in `config/cache-keys.php`.
**Rationale:** All cache keys must be registered in one place (NFR-M1 from settings spec).
Prevents ad-hoc key strings and enables bulk invalidation.
**Trade-off:** Extra config entry for a single key. Negligible overhead.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| School entity load (cache hit) | < 50ms |
| School entity load (cache miss) | < 200ms |
| Profile save (7 fields) | < 2s p95 |
| Cache invalidation | < 10ms |
| Atomic save | All fields saved or none |
| Cache invalidation coverage | 100% of saves invalidate cache |
| FR test coverage | ≥ 90% of FR-SP1–SP27 |
| SchoolEntity accessor coverage | 100% |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [settings-infrastructure.md](settings-infrastructure.md) | `school.*` settings keys, `SchoolEntity` cached via `SettingsStore` |

### Build Guide
After implementing this spec, the system has the school's identity (name, NPSN, address, contact info) stored as settings. This is the single-tenant school profile — one school per deployment. Departments and academic years are scoped to this school. The next step is to build department management, which creates the academic departments that companies reference in internship offerings.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [department-management.md](department-management.md) | Departments belong to this school; `school_profile` entity used in department context |
| 2 | [company-management.md](company-management.md) | Companies offer internships to departments from this school |

---

## Quick References

- `app/Academics/School/Entities/SchoolEntity.php` — School profile value object (7 typed properties)
- `app/Academics/School/Actions/SaveSchoolProfileAction.php` — Atomic save via BatchSetSettingAction
- `app/Academics/School/Livewire/SchoolEditor.php` — Livewire component (form, logo upload, removal)
- `app/Academics/School/Livewire/Forms/SchoolForm.php` — Form object with validation rules
- `app/Settings/Actions/BatchSetSettingAction.php` — Transaction-wrapped batch upsert
- `app/Settings/Actions/SetSettingAction.php` — Single key set with type auto-detection
- `app/Settings/Data/SettingEntryData.php` — Single setting entry DTO
- `app/Settings/Branding/Actions/UploadBrandAssetAction.php` — Spatie Media Library upload
- `app/Settings/Branding/Actions/RemoveBrandAssetAction.php` — Asset removal
- `app/Settings/Services/Settings.php` — Settings service (get, forget, resolution)
- `app/Core/Actions/BaseCommandAction.php` — Transaction-wrapped action base class
- `config/cache-keys.php:29` — `school_entity` cache key registration
- `routes/web/academics.php:13` — Route definition (`/admin/school`)
- `tests/Academics/School/SchoolEntityTest.php` — SchoolEntity unit tests
- `tests/Academics/School/Entities/SchoolEntityTest.php` — SchoolEntity entity tests
- **Related:** [settings-infrastructure.md](settings-infrastructure.md) — Settings store, type system & cache
- **Related:** [branding-theme-locale.md](branding-theme-locale.md) — Branding, theme & locale UI
- **Related:** [department-management.md](department-management.md) — Department CRUD
- **Related:** [academic-year-management.md](academic-year-management.md) — Academic year lifecycle
