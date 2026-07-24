# Settings Infrastructure — Type-Aware Store, Resolution & Cache Invalidation

> **Last updated:** 2026-07-22 **Changes:** feat — split from system-settings.md; settings store,
> type system, resolution chain, observer, admin page

## Description

Specification of Internara's settings infrastructure: a type-aware key-value store with
multi-layer resolution and automatic cache invalidation, the System Settings admin page, and
the settings CRUD pipeline. Branding, theme, and locale are separate initiatives — see
[branding-theme-locale.md](branding-theme-locale.md).

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
| NG1  | Branding/theme/locale UI (see [branding-theme-locale.md](branding-theme-locale.md)) |
| NG2  | Per-user preferences (single-tenant, per-browser cookie)        |
| NG3  | Settings import/export or migration tooling                       |
| NG4  | UI-based settings versioning or rollback                          |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Saves All System Settings

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

### UC-2 — Admin Tests Email Settings

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
| FR-S1  | `Setting` model must use `key` string column as primary key (not UUID)              |
| FR-S2  | `SettingEntity` must provide typed accessors: `booleanValue()`, `intValue()`, `floatValue()`, `jsonValue()`, `isEmpty()` |
| FR-S3  | `SetSettingAction` must validate key pattern `^[a-z][a-z0-9_.]*$` and auto-detect type via `SettingType::detect()` |
| FR-S4  | `BatchSetSettingAction` must execute all upserts within a single DB transaction      |
| FR-S5  | `DeleteSettingAction` must remove a setting by key and trigger observer cache invalidation |
| FR-S6  | `SaveSystemSettingsAction` must accept `SystemSettingsData` and delegate to `BatchSetSettingAction` within a transaction |
| FR-S7  | `SettingType` must support 7 types: `STRING`, `INTEGER`, `FLOAT`, `BOOLEAN`, `JSON`, `ENCRYPTED`, `NULL` |
| FR-S8  | `SettingValueCast` must transparently encrypt/decrypt `ENCRYPTED` values using Laravel's `Crypt` facade |
| FR-S9  | Settings resolution chain: runtime overrides → `AppInfo` → database (cached) → config → default |
| FR-S10 | `setting($key, $default)` global helper must resolve through the full resolution chain |
| FR-S11 | `SettingObserver` must clear `settings.key.{key}`, `settings.all`, and `settings.group.{group}` on model events |
| FR-S12 | `SettingObserver` must additionally clear `theme.css_variables` and `brand.colors` for theme-related keys |

### System Settings Page

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-W1  | `SystemSetting` Livewire component must render a 3-column layout: general/color/mail (main), system info/logo/favicon (sidebar) |
| FR-W2  | Three form objects must validate independently: `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm` |
| FR-W3  | Save action must validate all three forms, build `SystemSettingsData`, call `SaveSystemSettingsAction` |
| FR-W4  | After saving, if `active_academic_year` changed and can be activated, auto-activate    |
| FR-W5  | Logo and favicon uploads must trigger immediately via Livewire `updated*` hooks      |
| FR-W6  | `MailSettingsForm::toMailConfig()` must return array suitable for `Config::set('mail')` |
| FR-W7  | A floating help button must provide a modal with setting descriptions                 |
| FR-W8  | Route must be `/admin/settings` with middleware `['auth', 'role:super_admin|admin']`  |

### Cache Invalidation

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-C1  | All setting reads must use `Cache::rememberForever()` with keys from `config('cache-keys')` |
| FR-C2  | Cache keys: `settings_all`, `settings_group.{group}`, `settings_key.{key}`, `theme_css_variables`, `brand_colors` |
| FR-C3  | `SettingObserver` must invalidate synchronously (not queued) to prevent stale reads   |
| FR-C4  | `brand.colors` cache TTL must be 86400s (24h)                                       |
| FR-C5  | `theme.css_variables` cache TTL must be 3600s (1h)                                   |

---

## 5. Non-Functional Requirements

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| NFR-S1 | SMTP passwords and encrypted settings must use Laravel `Crypt` (AES-256-CBC)         |
| NFR-S2 | Setting keys must match `^[a-z][a-z0-9_.]*$` to prevent injection                   |
| NFR-S3 | Only `super_admin` may create/delete settings; `admin` may view/update               |
| NFR-P1 | Settings reads from cache must complete in < 5ms                                    |
| NFR-P2 | System Settings page load must complete in < 500ms                                  |
| NFR-P4 | `SettingObserver` cache invalidation must complete in < 10ms per key                 |
| NFR-R1 | `SaveSystemSettingsAction` must execute within a single DB transaction               |
| NFR-R2 | `Brand::resolve()` must catch exceptions and fall back to `AppInfo` defaults        |
| NFR-R3 | Cookie-based preferences must degrade gracefully: invalid values fall back to defaults |
| NFR-M1 | Every setting key must be declared in exactly one place — no ad-hoc key strings      |
| NFR-M2 | All setting reads must go through `setting()` helper or `Settings::get()`            |
| NFR-A1 | All settings UI must meet WCAG 2.1 Level AA                                         |
| NFR-A5 | All form inputs must have associated labels                                          |
| NFR-L1 | All UI labels must use `__()` translation helper                                     |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/`                       |

---

## 6. API / Data Contracts

### SettingEntity

```php
// app/Settings/Entities/SettingEntity.php
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
// app/Settings/Data/SystemSettingsData.php
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
| `default_locale`      | localization  | string     | `id`                   |
| `active_academic_year`| system        | string     | `YYYY/YYYY+1`          |
| `support_email`       | general       | string     | `''`                   |
| `mail_from_address`   | mail          | string     | `''`                   |
| `mail_from_name`      | mail          | string     | `''`                   |
| `mail_host`           | mail          | string     | `''`                   |
| `mail_port`           | mail          | string     | `587`                  |
| `mail_encryption`     | mail          | string     | `tls`                  |
| `mail_username`       | mail          | string     | `''`                   |
| `mail_password`       | mail          | encrypted  | `null`                 |

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
| Brand resolution failure → fallback      | 100%   |
| Partial write prevention (transaction)   | 100%   |
| Cookie fallback for invalid values       | 100%   |

### Coverage

| Metric                          | Target |
| ------------------------------- | ------ |
| Setting keys declared in config | 100%   |
| Setting reads via helper/API    | 100%   |
| FR coverage in tests            | ≥ 90%  |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [core-foundation.md](core-foundation.md) | `BaseCommandAction`, `BaseReadAction`, cache key registry (`config/cache-keys.php`) |

### Build Guide
After implementing this spec, the system has a key-value settings store with caching, type enforcement, group-based organization, and CRUD via Livewire UI. Every module reads configuration from this store. The next step is to build branding, theme, and locale, which reads CSS variables and locale preferences from settings.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [branding-theme-locale.md](branding-theme-locale.md) | Reads `brand.*`, `theme.*`, `locale.*` settings keys; `SettingObserver` triggers cache invalidation |
| 2 | [school-profile.md](school-profile.md) | Stores `school.*` settings keys for school identity |

---

## Quick References

- `app/Settings/Models/Setting.php` — Eloquent model with string PK, media collections
- `app/Settings/Entities/SettingEntity.php` — Typed value accessors, group/type checks
- `app/Settings/Enums/SettingGroup.php` — 7 group cases
- `app/Settings/Enums/SettingType.php` — 7 type cases with auto-detection
- `app/Settings/Casts/SettingValueCast.php` — Transparent type casting
- `app/Settings/Support/SettingCaster.php` — Type casting logic
- `app/Settings/Support/helpers.php` — `setting()` and `brand()` global helpers
- `app/Settings/Observers/SettingObserver.php` — Synchronous cache invalidation
- `app/Settings/Policies/SettingPolicy.php` — RBAC: view/update for admin, create/delete for super_admin
- `app/Settings/Actions/SetSettingAction.php` — Single key set with type auto-detection
- `app/Settings/Actions/BatchSetSettingAction.php` — Transaction-wrapped batch upsert
- `app/Settings/Actions/DeleteSettingAction.php` — Key deletion with observer trigger
- `app/Settings/Actions/SaveSystemSettingsAction.php` — Orchestrator for SystemSettingsData
- `app/Settings/Actions/TestMailSettingsAction.php` — Temp config swap for mail testing
- `app/Settings/Data/SystemSettingsData.php` — 18-property DTO
- `app/Settings/Data/SettingEntryData.php` — Single setting entry DTO
- `app/Settings/Livewire/SystemSetting.php` — Main settings page component
- `app/Settings/Livewire/Forms/GeneralSettingsForm.php` — General settings form
- `app/Settings/Livewire/Forms/MailSettingsForm.php` — Mail settings form
- `routes/web/settings.php` — `/admin/settings` route definition
- `docs/modules/settings.md` — Module conceptual documentation
- **Related specs:** [branding-theme-locale.md](branding-theme-locale.md) — Branding, theme & locale UI
