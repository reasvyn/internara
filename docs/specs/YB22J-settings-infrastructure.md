# Settings Infrastructure — Type-Aware Store, Resolution & Cache Invalidation

> **Spec ID:** YB22J

## Description

Specification of Internara's settings infrastructure: a type-aware key-value store with
multi-layer resolution and automatic cache invalidation, the System Settings admin page, and
the settings CRUD pipeline. Branding, theme, and locale are separate initiatives — see
[branding-theme-locale.md](52O1I-branding-theme-locale.md).

---

## 1. Problem Statements

### PS-1 — Settings Resolution Across Environments

A single-tenant self-hosted system must support environment-specific overrides (e.g., staging
SMTP config) while defaulting to sensible values for fresh installs. Without a resolution chain,
admins must manually configure every key on first deployment. The system must cascade: runtime
overrides → static app info → database cache → config file → hardcoded default.

### PS-2 — Type-Aware Setting Storage and Retrieval

A generic key-value store that stores everything as strings forces callers to manually cast
values. With settings like `active_academic_year` (string), `mail_port` (integer),
`mail_password` (encrypted), and feature flags (boolean), the system must auto-detect and enforce
type at the storage layer.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Multi-layer resolution chain for all settings                     |
| G2  | Auto-detect and enforce setting types via `SettingType` enum       |
| G3  | Invalidate caches synchronously via `SettingObserver`              |
| G4  | Unified System Settings admin page with general, branding, and mail sections |
| G5  | Type-aware storage with encrypted support                         |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Branding/theme/locale UI (see [branding-theme-locale.md](52O1I-branding-theme-locale.md)) |
| NG2  | Per-user preferences (single-tenant, per-browser cookie)        |
| NG3  | Settings import/export or migration tooling                       |
| NG4  | UI-based settings versioning or rollback                          |

---

## 3. User Stories / Use Cases

### UC-YB22J-1 — Admin Saves All System Settings

**Actor:** Admin or Super Admin
**Preconditions:** User authenticated with admin role
**Flow:**
1. User navigates to `/admin/settings`
2. `SystemSetting` Livewire component mounts, loads values from `Settings::get()` into three forms
3. User modifies fields across General, Branding, and Mail sections
4. All three forms validate independently
5. `SaveSystemSettingsAction::execute()` runs inside DB transaction: upserts all entries via `BatchSetSettingAction`
6. `SettingObserver` fires for each changed key, clearing affected cache keys
7. If `active_academic_year` changed and can be activated, `ActivateAcademicYearAction` runs
**Postconditions:** All settings persisted, caches invalidated

### UC-YB22J-2 — Admin Tests Email Settings

**Actor:** Admin
**Preconditions:** Mail fields filled on System Settings page
**Flow:**
1. User clicks "Send Test Email"
2. `TestMailSettingsAction::execute()` temporarily swaps config, sends test email, restores config
**Postconditions:** Test email delivered; config restored

---

## 4. Functional Requirements

### Settings Core

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-YB22J-S1  | `Setting` model must use `key` string column as primary key (not UUID)              |
| FR-YB22J-S2  | `SettingEntity` must provide typed accessors: `booleanValue()`, `intValue()`, `floatValue()`, `jsonValue()`, `isEmpty()` |
| FR-YB22J-S3  | `SetSettingAction` must validate key pattern `^[a-z][a-z0-9_.]*$` and auto-detect type via `SettingType::detect()` |
| FR-YB22J-S4  | `BatchSetSettingAction` must execute all upserts within a single DB transaction      |
| FR-YB22J-S5  | `DeleteSettingAction` must remove a setting by key and trigger observer cache invalidation |
| FR-YB22J-S6  | `SaveSystemSettingsAction` must accept `SystemSettingsData` and delegate to `BatchSetSettingAction` within a transaction |
| FR-YB22J-S7  | `SettingType` must support 7 types: `STRING`, `INTEGER`, `FLOAT`, `BOOLEAN`, `JSON`, `ENCRYPTED`, `NULL` |
| FR-YB22J-S8  | `SettingValueCast` must transparently encrypt/decrypt `ENCRYPTED` values using Laravel's `Crypt` facade |
| FR-YB22J-S9  | Settings resolution chain: runtime overrides → `AppInfo` → database (cached) → config → default |
| FR-YB22J-S10 | `setting($key, $default)` global helper must resolve through the full resolution chain |
| FR-YB22J-S11 | `SettingObserver` must clear `settings.key.{key}`, `settings.all`, and `settings.group.{group}` on model events |
| FR-YB22J-S12 | `SettingObserver` must additionally clear `theme.css_variables` and `brand.colors` for theme-related keys |

### System Settings Page

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-YB22J-W1  | `SystemSetting` Livewire component must render a 3-column layout: general/color/mail (main), system info/logo/favicon (sidebar) |
| FR-YB22J-W2  | Three form objects must validate independently: `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm` |
| FR-YB22J-W3  | Save action must validate all three forms, build `SystemSettingsData`, call `SaveSystemSettingsAction` |
| FR-YB22J-W4  | After saving, if `active_academic_year` changed and can be activated, auto-activate    |
| FR-YB22J-W5  | Logo and favicon uploads must trigger immediately via Livewire `updated*` hooks      |
| FR-YB22J-W6  | `MailSettingsForm::toMailConfig()` must return array suitable for `Config::set('mail')` |
| FR-YB22J-W7  | A floating help button must provide a modal with setting descriptions                 |
| FR-YB22J-W8  | Route must be `/admin/settings` with middleware `['auth', 'role:super_admin|admin']`  |

### Cache Invalidation

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-YB22J-C1  | All setting reads must use `Cache::rememberForever()` with keys from `config('cache-keys')` |
| FR-YB22J-C2  | Cache keys: `settings_all`, `settings_group.{group}`, `settings_key.{key}`, `theme_css_variables`, `brand_colors` |
| FR-YB22J-C3  | `SettingObserver` must invalidate synchronously (not queued) to prevent stale reads   |
| FR-YB22J-C4  | `brand.colors` cache TTL must be 86400s (24h)                                       |
| FR-YB22J-C5  | `theme.css_variables` cache TTL must be 3600s (1h)                                   |

### Feature Flags

Feature flags are runtime toggles for module behavior (internara-project §6.1 Settings).
They reuse the standard settings store under the `features.*` namespace with `BOOLEAN` type —
no separate table, no separate cache. A dedicated `feature()` helper hides the key namespace.

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-YB22J-FF1 | Feature flag keys must live under the `features.*` namespace (group `features`) and be stored via the standard `Setting` store with `BOOLEAN` type |
| FR-YB22J-FF2 | `feature($key, $default = false)` global helper must resolve `features.{key}` through the full settings resolution chain and cast to `bool` |
| FR-YB22J-FF3 | Feature flags must be immutable-only via `SetSettingAction`/`BatchSetSettingAction` — no bypass of type detection |
| FR-YB22J-FF4 | Toggling a feature flag must invalidate the same cache keys as any setting (`settings_key.{key}`, `settings_group.features`) via `SettingObserver` |
| FR-YB22J-FF5 | Only `super_admin` may create/update/delete feature flags (matches NFR-YB22J-S3); `admin` may read   |
| FR-YB22J-FF6 | Feature flags must be documented in `config/settings.php` under a `features` key listing each flag key, default, and owning module |

### Image & Color Settings

The high-level requirement (internara-project §6.1 Settings) lists "image" and "color" among
enforced setting types. These are **not** dedicated `SettingType` cases — they are `STRING`/
`JSON` values with domain-level validation (see DD-5). The requirements below make that contract
explicit.

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-YB22J-S13 | Color settings (e.g. `brand.*_color`, `theme.*`) must be stored as `STRING` and validated against hex pattern `^#[0-9a-fA-F]{6}$` at the form layer |
| FR-YB22J-S14 | Image settings (e.g. `brand_logo`, `site_favicon`) must store the media URL string (Spatie Media Library `getUrl()`), never raw binary, in the `Setting` store |

---

## 5. Non-Functional Requirements

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| NFR-YB22J-S1 | SMTP passwords and encrypted settings must use Laravel `Crypt` (AES-256-CBC)         |
| NFR-YB22J-S2 | Setting keys must match `^[a-z][a-z0-9_.]*$` to prevent injection                   |
| NFR-YB22J-S3 | Only `super_admin` may create/delete settings; `admin` may view/update               |
| NFR-YB22J-R1 | `SaveSystemSettingsAction` must execute within a single DB transaction               |
| NFR-YB22J-R2 | `Brand::resolve()` must catch exceptions and fall back to `AppInfo` defaults        |
| NFR-YB22J-R3 | Cookie-based preferences must degrade gracefully: invalid values fall back to defaults |
| NFR-YB22J-M1 | Every setting key must be declared in exactly one place — no ad-hoc key strings      |
| NFR-YB22J-M2 | All setting reads must go through `setting()` helper or `Settings::get()`            |
| NFR-YB22J-A1 | All settings UI must meet WCAG 2.1 Level AA                                         |
| NFR-YB22J-A5 | All form inputs must have associated labels                                          |
| NFR-YB22J-L1 | All UI labels must use `__()` translation helper                                     |
| NFR-YB22J-L2 | Translation keys must exist in both `lang/en/` and `lang/id/`                       |

---

## 6. API / Data Contracts

### SettingEntity

```php
// app/Modules/Settings/Entities/SettingEntity.php
final readonly class SettingEntity extends BaseEntity
{
    public function key(): string;
    public function value(): mixed;
    public function type(): ?string;
    public function group(): ?string;
    public static function fromModel(Model $model): static;
    public function settingType(): ?SettingType;
    public function booleanValue(): bool;
    public function intValue(): int;
    public function floatValue(): float;
    public function jsonValue(): array;
    public function isEmpty(): bool;
    public function isThemeColor(array $themeCacheKeys = []): bool;
    public function belongsToGroup(string $group): bool;
}

```

### SystemSettingsData

```php
// app/Modules/Settings/Data/SystemSettingsData.php
final readonly class SystemSettingsData extends BaseData
{
    public function __construct(
        public string $brandName = '',
        public string $siteTitle = '',
        public string $defaultLocale = 'id',
        public string $activeAcademicYear = '',
        public string $primaryColor = '',
        public string $secondaryColor = '',
        public string $accentColor = '',
        public string $baseColor = '',
        public ?UploadedFile $brandLogo = null,
        public ?UploadedFile $siteFavicon = null,
        public string $supportEmail = '',
        public string $mailFromAddress = '',
        public string $mailFromName = '',
        public string $mailHost = '',
        public string $mailPort = '587',
        public string $mailEncryption = 'tls',
        public string $mailUsername = '',
        public ?string $mailPassword = null,
    ) {}
}

```

### SettingGroup Enum

```php
enum SettingGroup: string implements LabelEnum
{
    case GENERAL = 'general';
    case MAIL = 'mail';
    case SYSTEM = 'system';
    case BRANDING = 'branding';
    case FEATURES = 'features';
    case LOCALIZATION = 'localization';
    case NOTIFICATIONS = 'notifications';
}

```

### SettingType Enum

```php
enum SettingType: string implements LabelEnum
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case ENCRYPTED = 'encrypted';
    case NULL = 'null';

    public static function detect(mixed $value): self;
    public function cast(mixed $value): mixed;
}

```

### Routes

```php
Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settings')
    ->middleware(['auth', 'role:super_admin|admin']);

```

### Key Setting Keys

| Key                   | Group         | Type       | Default                |
| --------------------- | ------------- | ---------- | ---------------------- |
| `brand_name`          | branding      | string     | `AppInfo::name()`      |
| `site_title`          | branding      | string     | `brand('name')`        |
| `brand_logo`          | branding      | string     | `asset('/brand/logo.png')` |
| `site_favicon`        | branding      | string     | `asset('/brand/favicon.ico')` |
| `primary_color`       | branding      | string     | `#059669`              |
| `secondary_color`     | branding      | string     | `#6b7280`              |
| `accent_color`        | branding      | string     | `#f97316`              |
| `base_color`          | branding      | string     | `#ffffff`              |
| `brand.custom_css`    | branding      | string     | `''`                   |
| `default_locale`      | localization  | string     | `id`                   |
| `active_academic_year`| system        | string     | School year containing today (July–June, FR-YB22J-AY40): `YYYY-1/YYYY` in Jan–Jun, `YYYY/YYYY+1` in Jul–Dec |
| `support_email`       | general       | string     | `''`                   |
| `mail_from_address`   | mail          | string     | `''`                   |
| `mail_from_name`      | mail          | string     | `''`                   |
| `mail_host`           | mail          | string     | `''`                   |
| `mail_port`           | mail          | string     | `587`                  |
| `mail_encryption`     | mail          | string     | `tls`                  |
| `mail_username`       | mail          | string     | `''`                   |
| `mail_password`       | mail          | encrypted  | `null`                 |

### Feature Flag Helper

```php
// app/Modules/Settings/Support/helpers.php
function feature(string $key, bool $default = false): bool;

```

Resolves `features.{key}` through the settings resolution chain and casts to `bool`. Returns
`$default` when the flag is absent. Consumers must call `feature('key')` instead of
`setting('features.key', false)` — the helper enforces the namespace and boolean contract (FR-YB22J-FF2).
Feature flag keys are declared in `config/settings.php` under the `features` key (FR-YB22J-FF6).

---

## 7. Design Decisions

### DD-1 — SettingObserver Over Event Listener

**Decision:** Use Eloquent Observer for cache invalidation instead of event listeners.
**Rationale:** Observers fire synchronously, guaranteeing no stale reads within the same request.
Event listeners could be dispatched to queue, creating race conditions.
**Trade-off:** Observer coupled to model. Acceptable — single observer, single concern.

### DD-2 — Type-Aware Storage with Auto-Detection

**Decision:** `SetSettingAction` auto-detects types via `SettingType::detect()`.
**Rationale:** Most callers pass PHP values without knowing storage type. Auto-detection
reduces boilerplate and prevents type mismatches.
**Trade-off:** `"1"` (string) vs `1` (int) vs `true` (bool) are different types. Mitigated by
key pattern validation and explicit `SettingType` enum.

### DD-3 — Resolution Chain Precedence

**Decision:** 5-layer cascade: runtime → AppInfo → DB (cached) → config → default.
**Rationale:** Fresh installs work without DB config. Production overrides via runtime. DB
values take precedence over config. Supports zero-config development and admin customization.
**Trade-off:** Debugging which layer provides a value can be difficult. Mitigated by
`setting()` helper's `$skipCache` parameter.

### DD-4 — Synchronous Cache Invalidation

**Decision:** `SettingObserver` clears caches synchronously on model events.
**Rationale:** Prevents stale reads in the same request. The `setting()` helper reads from
cache, so the observer must clear before any subsequent read.
**Trade-off:** Slight overhead on every setting write. Negligible for admin-triggered operations.

### DD-5 — Image & Color Are Validated STRINGs, Not Enum Cases

**Decision:** `SettingType` keeps 7 storage cases (`STRING`, `INTEGER`, `FLOAT`, `BOOLEAN`,
`JSON`, `ENCRYPTED`, `NULL`). "Image" and "color" from the high-level requirement are enforced
at the domain/form layer, not as new enum cases.
**Rationale:** Colors are single 6-digit hex strings — a dedicated case would add a cast layer
without new storage semantics. Images are stored as media URLs (Spatie `getUrl()`), not raw
binary, so the `Setting` column stays a string; the binary lives in the media library.
Adding `COLOR`/`IMAGE` cases would complicate `detect()` (which sees only the string) and force
cast plumbing for zero storage benefit.
**Trade-off:** A caller could theoretically store a non-hex string under a color key. Mitigated
by FR-YB22J-S13 (form-layer hex validation) and FR-YB22J-S14 (image URL contract); the `SettingPolicy`
restricts writes to `super_admin`.

### DD-6 — Feature Flags Reuse the Settings Store

**Decision:** Feature flags are ordinary `features.*` boolean settings, exposed via the
`feature()` helper, rather than a separate feature-flags package/table.
**Rationale:** The settings store already provides caching, type enforcement, observer-driven
invalidation, and RBAC. A separate table would duplicate that infrastructure for boolean toggles
(DRY — single source of truth for runtime config). Declaring flags in `config/settings.php`
(FR-YB22J-FF6) keeps them discoverable and prevents ad-hoc toggles.
**Trade-off:** Flags are visible in the settings key-value UI rather than a dedicated toggles UI.
Acceptable — `admin` can read, only `super_admin` mutates (NFR-YB22J-S3), and the group filter
(`SettingGroup::FEATURES`) keeps them grouped.

---

## 8. Success Metrics

### Functionality

| Metric                              | Target |
| ----------------------------------- | ------ |
| Settings read (cache hit)           | < 5ms p99 |
| System Settings page mount + render | < 500ms p95 |
| Save all settings (18 properties)   | < 2s p95 |
| Cache invalidation per key          | < 10ms |

### Reliability

| Metric                                   | Target |
| ---------------------------------------- | ------ |

### Coverage

| Metric                          | Target |
| ------------------------------- | ------ |
| FR coverage in tests            | ≥ 90%  |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `BaseReadAction`, cache key registry (`config/cache-keys.php`) |

### Build Guide
After implementing this spec, the system has a key-value settings store with caching, type enforcement, group-based organization, and CRUD via Livewire UI. Every module reads configuration from this store. The next step is to build branding, theme, and locale, which reads CSS variables and locale preferences from settings.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [branding-theme-locale.md](52O1I-branding-theme-locale.md) | Reads `brand.*`, `theme.*`, `locale.*` settings keys; `SettingObserver` triggers cache invalidation |
| 2 | [school-profile.md](81SMS-school-profile.md) | Stores `school.*` settings keys for school identity |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
