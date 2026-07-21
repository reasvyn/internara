# System Settings — Settings Store, Branding, Theme & Locale

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering settings resolution,
> system settings page, branding customization, theme switching, locale management, and
> type-aware storage

## Description

Complete specification of Internara's system settings infrastructure: a type-aware key-value store
with multi-layer resolution and automatic cache invalidation, the unified System Settings admin
page (general, branding, mail), brand identity management with color presets and asset uploads,
dark/light theme switching, and EN/ID locale switching. All user-facing preferences use
cookie-based storage; all system settings persist to database with `rememberForever` caching.

---

## 1. Problem Statements

### PS-1 — Settings Resolution Across Environments

A single-tenant self-hosted system must support environment-specific overrides (e.g., staging
SMTP config) while defaulting to sensible values for fresh installs. Without a resolution chain,
admins must manually configure every key on first deployment. The system must cascade: runtime
overrides → static app info → database cache → config file → hardcoded default, so zero-config
installs work out of the box while production can override any value.

### PS-2 — Branding Customization Without Redeployment

Schools need to customize their identity — site name, logo, favicon, color palette — without
touching code or environment files. If branding is hardcoded in templates or config, every
customization requires a developer and a redeploy. The system must provide a live admin UI that
persists brand assets to storage and color choices to the database, with changes reflecting
immediately on the next page load.

### PS-3 — Theme Switching Without Database Overhead

Dark/light mode preference is per-browser, not per-user — there is no reason to persist it in the
database. Storing it in the database would add write amplification (every toggle = DB write +
cache invalidation) for a preference that changes frequently and has no cross-device sync
requirement. The system must use cookie-based storage for theme preference while still generating
dynamic CSS variables from the active color palette.

### PS-4 — Locale Management Across the Interface

Indonesian vocational schools need EN/ID switching. Locale preference is similarly per-browser
(not per-user in a single-tenant context) and must apply on every request via middleware without
database queries. The system must read the locale cookie on each request, validate against the
supported locale list, and set the application locale before any controller or Livewire component
executes.

### PS-5 — Type-Aware Setting Storage and Retrieval

A generic key-value store that stores everything as strings forces callers to manually cast
values. With settings like `active_academic_year` (string), `mail_port` (integer),
`mail_password` (encrypted), and feature flags (boolean), the system must auto-detect and enforce
type at the storage layer. The `SettingType` enum and `SettingValueCast` must ensure that a
boolean stored as `"1"` is returned as `true`, a JSON array is returned as `array`, and
encrypted values are transparently decrypted on read.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a multi-layer resolution chain (runtime → AppInfo → DB → config → default) for all settings |
| G2  | Auto-detect and enforce setting types via `SettingType` enum with `SettingValueCast` |
| G3  | Invalidate caches synchronously via `SettingObserver` on model create/update/delete events |
| G4  | Deliver a unified System Settings admin page with general, branding, and mail sections |
| G5  | Support live logo/favicon upload without requiring a full form save |
| G6  | Provide 6 color presets (sky, emerald, violet, rose, ocean, slate) with one-click apply |
| G7  | Store theme (dark/light/system) and locale (EN/ID) preferences in cookies, not database |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Per-user theme or locale preferences (single-tenant, per-browser cookie is sufficient) |
| NG2  | Real-time settings sync across browser tabs (each tab reads cookie independently) |
| NG3  | Settings import/export or migration tooling |
| NG4  | UI-based settings versioning, rollback, or audit diff viewer |
| NG5  | Multi-language support beyond English and Indonesian |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Saves All System Settings

**Actor:** Admin or Super Admin
**Preconditions:** User is authenticated with `admin` or `super_admin` role
**Flow:**
1. User navigates to `/admin/settings`
2. `SystemSetting` Livewire component mounts, loads current values from `Settings::get()` into three forms: `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm`
3. User modifies brand name, site title, default locale, active academic year, support email (General)
4. User modifies 4 color values or applies a preset (Branding)
5. User modifies SMTP fields (Mail)
6. User clicks Save
7. All three forms validate independently
8. `SystemSettingsData` DTO is constructed from form values
9. `SaveSystemSettingsAction::execute()` runs inside a DB transaction:
   - Builds `SettingEntryData` array from non-empty fields
   - Calls `BatchSetSettingAction::execute()` to upsert all entries
   - If `brandLogo`/`siteFavicon` are `UploadedFile` instances, uploads via `UploadBrandAssetAction`
   - `SettingObserver` fires for each changed key, clearing affected cache keys
10. If `active_academic_year` changed and the year can be activated, `ActivateAcademicYearAction` runs
11. Flash success message
**Postconditions:** All settings persisted, caches invalidated, branding and theme reflect changes on next page load

### UC-2 — Admin Uploads Logo or Favicon

**Actor:** Admin or Super Admin
**Preconditions:** User is on the System Settings page
**Flow:**
1. User selects a file in the logo upload field
2. Livewire `updatedBrandingFormBrandLogo` hook fires immediately (not on save)
3. Validates: `nullable|image|max:1024` (KB) for logo, `nullable|image|max:512` for favicon
4. `UploadBrandAssetAction::execute()` stores file via Spatie Media Library collection (`brand_logo` or `brand_favicon`)
5. Returns the public URL
6. `SetSettingAction::execute()` persists the URL to `brand_logo` or `site_favicon` key
7. `SettingObserver` clears `settings.key.brand_logo`, `settings.all`, `settings.group.branding`
8. Component updates `current_logo_url` / `current_favicon_url` for preview
9. Flash success message
**Postconditions:** Asset is live immediately; logo URL persisted to DB; old asset remains in storage until explicitly removed

### UC-3 — Admin Switches Theme

**Actor:** Any authenticated user
**Preconditions:** User is on any page with the `ThemeSwitcher` component
**Flow:**
1. User clicks light/dark/system toggle in `ThemeSwitcher`
2. `setTheme('dark')` queues a forever cookie: `theme=dark`
3. Dispatches `theme-changed` Livewire event with `theme: 'dark'`
4. Alpine.js listener reads the event, updates `data-theme` attribute on `<html>`
5. CSS variables generated by `Theme::cssVariables()` (cached 1h) provide light/dark palettes
**Postconditions:** Theme applied immediately via Alpine.js; cookie persists across sessions; no DB write

### UC-4 — Admin Changes Locale

**Actor:** Any authenticated user
**Preconditions:** User is on any page with the `LangSwitcher` component
**Flow:**
1. User opens language dropdown, selects "Bahasa Indonesia"
2. `setLocale('id')` calls `Locale::set('id')`
3. `Locale::set()` queues a forever cookie: `locale=id`, calls `App::setLocale('id')`
4. Dispatches `language-changed` Livewire event
5. On next request, `SetLocaleMiddleware` reads `locale` cookie and sets `App::setLocale()`
**Postconditions:** UI renders in Indonesian on next page load; cookie persists; no DB write

### UC-5 — Admin Tests Email Settings

**Actor:** Admin or Super Admin
**Preconditions:** User is on System Settings page with mail fields filled
**Flow:**
1. User clicks "Send Test Email"
2. `testEmail()` method validates mail fields: `mail_host` (required), `mail_port` (required|numeric), `mail_username` (required), `mail_password` (required), `mail_from_address` (required|email)
3. `TestMailSettingsAction::execute()` temporarily swaps config values, sends a test email to the current user's address, then restores original config
4. Returns boolean success/failure
5. Flash success or error message
**Postconditions:** Test email delivered (or failure reported); system config restored to original values

---

## 4. Functional Requirements

### Settings Core

| ID     | Requirement |
| ------ | ----------- |
| FR-S1  | `Setting` model must use `key` string column as primary key (not UUID) |
| FR-S2  | `SettingEntity` must provide typed accessors: `booleanValue()`, `intValue()`, `floatValue()`, `jsonValue()`, `isEmpty()` |
| FR-S3  | `SetSettingAction` must validate key pattern `^[a-z][a-z0-9_.]*$` and auto-detect type via `SettingType::detect()` |
| FR-S4  | `BatchSetSettingAction` must execute all upserts within a single DB transaction |
| FR-S5  | `DeleteSettingAction` must remove a setting by key and trigger observer cache invalidation |
| FR-S6  | `SaveSystemSettingsAction` must accept `SystemSettingsData` and delegate to `BatchSetSettingAction` within a transaction |
| FR-S7  | `SettingType` must support 7 types: `STRING`, `INTEGER`, `FLOAT`, `BOOLEAN`, `JSON`, `ENCRYPTED`, `NULL` |
| FR-S8  | `SettingValueCast` must transparently encrypt/decrypt values of type `ENCRYPTED` using Laravel's `Crypt` facade |
| FR-S9  | Settings resolution chain must cascade: runtime overrides → `AppInfo` → database (cached) → config file fallback → provided default |
| FR-S10 | `setting($key, $default)` global helper must resolve through the full resolution chain |
| FR-S11 | `SettingObserver` must clear `settings.key.{key}`, `settings.all`, and `settings.group.{group}` on model created/updated/deleted events |
| FR-S12 | `SettingObserver` must additionally clear `theme.css_variables` and `brand.colors` when the changed key is in `config('settings.theme_cache_keys')` |

### System Settings Page

| ID     | Requirement |
| ------ | ----------- |
| FR-W1  | `SystemSetting` Livewire component must render a 3-column layout: general settings, color scheme, mail settings (main column); system info, logo/favicon (sidebar) |
| FR-W2  | Three form objects must validate independently: `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm` |
| FR-W3  | Save action must validate all three forms, build `SystemSettingsData`, and call `SaveSystemSettingsAction` |
| FR-W4  | After saving, if `active_academic_year` changed and the year `canBeActivated()`, `ActivateAcademicYearAction` must run automatically |
| FR-W5  | Logo and favicon uploads must trigger immediately on file selection via Livewire `updated*` hooks, not on form save |
| FR-W6  | `MailSettingsForm::toMailConfig()` must return an array suitable for `Config::set('mail')` |
| FR-W7  | A floating help button must provide a modal with setting descriptions |
| FR-W8  | Route must be `/admin/settings` with middleware `['auth', 'role:super_admin|admin']` |

### Branding

| ID     | Requirement |
| ------ | ----------- |
| FR-B1  | `Brand::resolve()` must return a `BrandData` DTO with name, title, logo, favicon, colors, version, author info, description, license, gitUrl |
| FR-B2  | `Brand` resolution must use dual-path: `Brand` resolves from DB settings (bypassing AppInfo's static keys); static metadata (version, author) delegates to `AppInfo` |
| FR-B3  | `Brand::colors()` must cache for 24h under `brand.colors` key |
| FR-B4  | 6 color presets must be defined in config: sky, emerald (default), violet, rose, ocean, slate |
| FR-B5  | `BrandingForm::detectPreset()` must compare current colors against preset palettes and return the matching key or `null` |
| FR-B6  | `BrandingForm::applyPreset()` must set all 4 color fields from the selected preset config |
| FR-B7  | Logo upload must validate: `image|max:1024` (KB), accepted MIME types: PNG, JPEG, WebP |
| FR-B8  | Favicon upload must validate: `image|max:512` (KB), accepted MIME types: PNG, JPEG, WebP, ICO |
| FR-B9  | `UploadBrandAssetAction` must store files via Spatie Media Library under `brand_logo` or `brand_favicon` collections |
| FR-B10 | `RemoveBrandAssetAction` must delete the current asset from media collection and clear the setting key |

### Theme

| ID     | Requirement |
| ------ | ----------- |
| FR-T1  | Theme preference must be stored in a `theme` cookie (values: `light`, `dark`, `system`), not in the database |
| FR-T2  | `ThemeSwitcher` Livewire component must dispatch `theme-changed` event with the selected theme value |
| FR-T3  | Alpine.js must listen for `theme-changed` and update the `data-theme` attribute on the root element |
| FR-T4  | `Theme::cssVariables()` must generate CSS variables for both light and dark palettes, cached 1h under `theme.css_variables` |
| FR-T5  | CSS variables must include: `--color-primary`, `--color-secondary`, `--color-accent`, `--color-base-{100,200,300,content}`, and `--brand-{primary,secondary,accent}` |
| FR-T6  | Dark mode must apply `Color::lighten()` (40%) to primary/secondary/accent and use `Color::computeDarkShades()` for base tones |
| FR-T7  | `Color` helper must provide: `hexToRgb()`, `relativeLuminance()`, `contrastColor()`, `lighten()`, `darken()`, `computeBaseShades()`, `computeDarkShades()` |

### Locale

| ID     | Requirement |
| ------ | ----------- |
| FR-L1  | Supported locales must be EN and ID, defined in `Locale::SUPPORTED_LOCALES` constant |
| FR-L2  | Locale preference must be stored in a `locale` cookie (forever TTL), not in the database |
| FR-L3  | `SetLocaleMiddleware` must read the `locale` cookie on every request and call `App::setLocale()` |
| FR-L4  | `Locale::set()` must validate the locale against `SUPPORTED_LOCALES`, queue the cookie, and set the app locale |
| FR-L5  | `LangSwitcher` Livewire component must render a dropdown with EN/ID options and dispatch `language-changed` event |
| FR-L6  | `Locale::metadata()` must return `['name' => '...', 'native' => '...']` for display in the switcher |

### Cache Invalidation

| ID     | Requirement |
| ------ | ----------- |
| FR-C1  | All setting reads must use `Cache::rememberForever()` with keys from `config('cache-keys')` |
| FR-C2  | Cache keys must be: `settings_all`, `settings_group.{group}`, `settings_key.{key}`, `theme_css_variables`, `brand_colors` |
| FR-C3  | `SettingObserver` must invalidate synchronously (not via queued events) to prevent stale reads |
| FR-C4  | `brand.colors` cache TTL must be 86400s (24h) |
| FR-C5  | `theme.css_variables` cache TTL must be 3600s (1h) |
| FR-C6  | `Brand::clearCache()` must forget the `brand.colors` cache key |

---

## 5. Non-Functional Requirements

| ID     | Category       | Requirement |
| ------ | -------------- | ----------- |
| NFR-S1 | Security       | SMTP passwords and other encrypted settings must use Laravel `Crypt` facade (AES-256-CBC), never stored as plaintext |
| NFR-S2 | Security       | Setting keys must match pattern `^[a-z][a-z0-9_.]*$` to prevent injection via key names |
| NFR-S3 | Security       | Only `super_admin` role may create or delete settings; `admin` may view and update (enforced by `SettingPolicy`) |
| NFR-S4 | Security       | Brand asset uploads must validate MIME type and file size server-side; never trust client-side validation alone |
| NFR-P1 | Performance    | Settings reads from cache must complete in < 5ms (memory hit) |
| NFR-P2 | Performance    | System Settings page load (mount + render) must complete in < 500ms |
| NFR-P3 | Performance    | Theme CSS variable generation must complete in < 50ms and be cached for 1h |
| NFR-P4 | Performance    | `SettingObserver` cache invalidation must complete in < 10ms per changed key |
| NFR-R1 | Reliability    | `SaveSystemSettingsAction` must execute all setting writes within a single DB transaction; partial writes must not persist |
| NFR-R2 | Reliability    | `Brand::resolve()` must catch exceptions and fall back to `AppInfo` defaults — brand resolution failure must never crash the page |
| NFR-R3 | Reliability    | Cookie-based theme and locale must degrade gracefully: invalid cookie values must fall back to defaults (`system` / `en`) |
| NFR-U1 | Usability      | Logo/favicon upload must show live preview immediately after selection, without page reload |
| NFR-U2 | Usability      | Color preset selection must show a visual preview of all 4 colors before applying |
| NFR-M1 | Maintainability| Every setting key must be declared in exactly one place (config or code) — no ad-hoc key strings scattered across modules |
| NFR-M2 | Maintainability| All setting reads must go through `setting()` helper or `Settings::get()` — no direct `Setting::find()` calls outside the Settings module |
| NFR-A1 | Accessibility | All settings UI (branding, theme, locale) must meet WCAG 2.1 Level AA |
| NFR-A2 | Accessibility | Logo/favicon upload must show alt text preview for screen readers |
| NFR-A3 | Accessibility | Color preset picker must indicate selection via non-color means (check icon, border) |
| NFR-A4 | Accessibility | Theme and locale switches must announce the change to screen readers via `aria-live` |
| NFR-A5 | Accessibility | All form inputs in settings pages must have associated labels |
| NFR-L1 | Localization | All settings UI labels, messages, and status text must use `__()` translation helper |
| NFR-L2 | Localization | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Localization | Locale switcher must update `app()->setLocale()` and persist preference in cookie |

---

## 6. API / Data Contract

### SettingEntity

```php
// app/Settings/Entities/SettingEntity.php
final readonly class SettingEntity extends BaseEntity
{
    public function __construct(
        private string $key,
        private mixed $value,
        private ?string $type,
        private ?string $group,
    ) {}

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
// app/Settings/Enums/SettingGroup.php
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
// app/Settings/Enums/SettingType.php
enum SettingType: string implements LabelEnum
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case ENCRYPTED = 'encrypted';
    case NULL = 'null';

    public static function detect(mixed $value): self; // auto-detect from value
    public function cast(mixed $value): mixed;         // delegate to SettingCaster
}
```

### BrandData

```php
// app/Settings/Branding/Data/BrandData.php
final readonly class BrandData extends BaseData
{
    public function __construct(
        public string $name,
        public string $title,
        public string $logo,
        public string $favicon,
        public array $colors,
        public string $version,
        public string $authorName,
        public string $authorEmail,
        public string $description,
        public string $license,
        public string $gitUrl,
    ) {}
}
```

### Brand Class

```php
// app/Settings/Support/Brand.php
final class Brand
{
    public static function name(): string;       // DB setting 'brand_name' → AppInfo::name()
    public static function title(): string;      // DB setting 'site_title' → self::name()
    public static function logo(): string;       // DB setting 'brand_logo' → config('app.logo')
    public static function favicon(): string;    // DB setting 'favicon' → config('app.favicon')
    public static function colors(): array;      // cached 24h via Theme::all()
    public static function resolve(): BrandData;
    public static function get(string $key, mixed $default = null): mixed;
    public static function clearCache(): void;   // forgets brand.colors
}
```

### Theme Class

```php
// app/Settings/Theme/Support/Theme.php
final class Theme
{
    public static function defaults(): array;               // ['primary' => '#059669', ...]
    public static function presets(): array;                // 6 presets from config
    public static function all(): array;                    // primary, secondary, accent, base, content
    public static function get(string $key): string;
    public static function base(): string;
    public static function cssVariables(): array;           // ['light' => [...], 'dark' => [...]], cached 1h
}
```

### Locale Class

```php
// app/Settings/Locale/Support/Locale.php
final class Locale
{
    public const DEFAULT_LOCALE = 'en';
    public const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
    ];

    public static function set(string $locale): bool;
    public static function current(): string;
    public static function all(): array;
    public static function keys(): array;
    public static function isSupported(string $locale): bool;
    public static function metadata(string $locale): ?array;
}
```

### Routes

```php
// routes/web/settings.php
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

### DD-1 — Cookie-Based Preferences (Not Database)

**Decision:** Theme and locale preferences are stored in cookies, not in the `settings` table.
**Rationale:** These are per-browser, not per-user. A single user may use different themes on
different devices. Database storage would add write amplification (every toggle = DB write +
cache invalidation) and migration complexity for zero cross-device benefit. Cookies are
read on every request by middleware with zero DB overhead.
**Trade-off:** Preferences don't sync across devices and are lost if cookies are cleared. This is
acceptable because re-selecting theme/locale is a single-click action.

### DD-2 — SettingObserver Over Event Listener for Cache Invalidation

**Decision:** Use an Eloquent Observer (`SettingObserver`) instead of event listeners for cache
invalidation.
**Rationale:** Observers fire synchronously within the same request as the model mutation,
guaranteeing that no subsequent code in the same request reads stale cache. Event listeners
could be dispatched to the queue, creating a race condition. Observers also automatically
receive the model instance without manual event construction.
**Trade-off:** Observers are coupled to the model and cannot be swapped without modifying the
model's `$observables`. This is acceptable because the Setting model has exactly one observer
with a single concern (cache invalidation).

### DD-3 — Dual-Path Brand Resolution

**Decision:** `Brand` resolves branding values from DB settings; static metadata (version, author)
delegates to `AppInfo`. These are two separate resolution paths in the same class.
**Rationale:** `AppInfo` is the canonical source for static metadata (composer.json). If `Brand`
also resolved name/title from `AppInfo`, user-customized `brand_name` would collide with
`AppInfo::name()`. By querying DB settings directly, `Brand` avoids this key collision while
still providing a unified `BrandData` DTO.
**Trade-off:** `Brand` has two resolution mechanisms (DB query for branding, `AppInfo` delegation
for metadata), making it slightly harder to reason about. The alternative (config-only branding)
would require environment variables for every brand field, which is not user-friendly.

### DD-4 — Live Asset Upload (Not Deferred to Save)

**Decision:** Logo and favicon are uploaded immediately on file selection via Livewire
`updated*` hooks, not when the user clicks Save.
**Rationale:** File uploads can be large (up to 1MB) and may fail. If upload is deferred to save,
the user discovers the failure only after filling out the entire form. Immediate upload provides
instant feedback and allows the user to retry before investing more time in the form.
**Trade-off:** If the user uploads a logo but then cancels without saving, the file is orphaned
in storage. This is acceptable because the file is small and storage is local (not cloud).

### DD-5 — Type-Aware Storage with Auto-Detection

**Decision:** `SetSettingAction` auto-detects value types via `SettingType::detect()` rather than
requiring the caller to specify the type.
**Rationale:** Most callers don't know or care about the storage type — they pass a PHP value
and expect it back unchanged. Auto-detection (`is_bool → BOOLEAN`, `is_int → INTEGER`, etc.)
reduces boilerplate and prevents type mismatches. The `SettingValueCast` ensures transparent
encryption/decryption for `ENCRYPTED` type and proper JSON encoding/decoding.
**Trade-off:** Auto-detection can be surprising — `"1"` (string) vs `1` (integer) vs `true`
(boolean) are all logically "truthy" but stored as different types. The key pattern validation
(`^[a-z][a-z0-9_.]*$`) and explicit `SettingType` enum make the system predictable.

### DD-6 — Resolution Chain Precedence

**Decision:** Settings resolve through a 5-layer chain: runtime overrides → AppInfo → DB
(cached) → config file → provided default.
**Rationale:** Fresh installs work without any database configuration (config + AppInfo
defaults). Production environments can override any value via runtime (e.g., testing mail
settings). DB values (set via admin UI) take precedence over config, so admins don't need
to edit `.env`. This layering supports zero-config development, environment-specific overrides,
and admin customization without conflict.
**Trade-off:** Debugging which layer provides a given value can be difficult. The `setting()`
helper's `$skipCache` parameter and the observer's cache clearing help, but a "resolution
trace" debug tool would be a useful future addition.

---

## 8. Success Metrics

### Functionality

| Metric                              | Target |
| ----------------------------------- | ------ |
| Settings read (cache hit)           | < 5ms p99 |
| System Settings page mount + render | < 500ms p95 |
| Save all settings (18 properties)   | < 2s p95 including file uploads |
| Cache invalidation per key          | < 10ms |

### Reliability

| Metric                                   | Target |
| ---------------------------------------- | ------ |
| Brand resolution failure → graceful fallback | 100% (no page crashes) |
| Partial write prevention (transaction)   | 100% (all-or-nothing) |
| Cookie fallback for invalid theme/locale | 100% (defaults applied) |

### Usability

| Metric                         | Target |
| ------------------------------ | ------ |
| Logo upload → preview visible  | < 3s from file selection |
| Color preset apply → UI update | < 500ms (no page reload) |
| Theme toggle → apply           | < 100ms (Alpine.js, no server roundtrip) |
| Locale toggle → apply          | < 1s (next page load) |

### Coverage

| Metric                          | Target |
| ------------------------------- | ------ |
| Setting keys declared in config | 100% (no ad-hoc keys) |
| Setting reads via helper/API    | 100% (no direct `Setting::find()` outside Settings module) |
| FR coverage in tests            | ≥ 90% of FR-* IDs have at least one corresponding test |

---

## Quick References

- `app/Settings/Models/Setting.php` — Eloquent model with string PK, media collections
- `app/Settings/Entities/SettingEntity.php` — Typed value accessors, group/type checks
- `app/Settings/Enums/SettingGroup.php` — 7 group cases (GENERAL, MAIL, SYSTEM, BRANDING, FEATURES, LOCALIZATION, NOTIFICATIONS)
- `app/Settings/Enums/SettingType.php` — 7 type cases with auto-detection
- `app/Settings/Casts/SettingValueCast.php` — Transparent type casting for model attributes
- `app/Settings/Support/SettingCaster.php` — Type casting logic delegated from `SettingType::cast()`
- `app/Settings/Support/Brand.php` — Dual-path brand resolution (DB + AppInfo)
- `app/Settings/Support/helpers.php` — `setting()` and `brand()` global helpers
- `app/Settings/Theme/Support/Theme.php` — Color resolution, CSS variable generation, presets
- `app/Settings/Locale/Support/Locale.php` — EN/ID locale management
- `app/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` — Per-request locale from cookie
- `app/Settings/Observers/SettingObserver.php` — Synchronous cache invalidation on model events
- `app/Settings/Policies/SettingPolicy.php` — RBAC: view/update for admin, create/delete for super_admin
- `app/Settings/Actions/SetSettingAction.php` — Single key set with type auto-detection
- `app/Settings/Actions/BatchSetSettingAction.php` — Transaction-wrapped batch upsert
- `app/Settings/Actions/DeleteSettingAction.php` — Key deletion with observer trigger
- `app/Settings/Actions/SaveSystemSettingsAction.php` — Orchestrator for SystemSettingsData
- `app/Settings/Actions/TestMailSettingsAction.php` — Temp config swap for mail testing
- `app/Settings/Actions/ReadAcademicYearAction.php` — Academic year data for settings page
- `app/Settings/Branding/Actions/UploadBrandAssetAction.php` — Spatie Media Library upload
- `app/Settings/Branding/Actions/RemoveBrandAssetAction.php` — Asset removal from media collection
- `app/Settings/Branding/Data/BrandData.php` — Brand identity DTO
- `app/Settings/Branding/Livewire/Forms/BrandingForm.php` — Color/logo/favicon form
- `app/Settings/Data/SystemSettingsData.php` — 18-property DTO for full settings save
- `app/Settings/Data/SettingEntryData.php` — Single setting entry for batch operations
- `app/Settings/Data/SettingData.php` — Individual setting DTO
- `app/Settings/Livewire/SystemSetting.php` — Main settings page component
- `app/Settings/Livewire/Forms/GeneralSettingsForm.php` — General settings form
- `app/Settings/Livewire/Forms/MailSettingsForm.php` — Mail settings form with `toMailConfig()`
- `app/Settings/Livewire/ThemeSwitcher.php` — Light/dark/system toggle (cookie-based)
- `app/Settings/Livewire/LangSwitcher.php` — EN/ID dropdown (cookie-based)
- `app/Settings/Events/SettingUpdated.php` — Domain event dispatched on setting change
- `routes/web/settings.php` — `/admin/settings` route definition
- `docs/modules/settings.md` — Module conceptual documentation
- `docs/modules/settings-reference.md` — Module reference documentation (if exists)
