# Settings Domain
> Last updated: 2026-05-28
> Changes: docs: update shared-reference, add ideal settings domain design


## Purpose

Settings manages the application's runtime configuration — the knobs and dials that can be
adjusted without a code deployment or server restart. Unlike environment variables (deployment-
specific) and config files (code-managed), settings are stored in the database and modified at
runtime through the admin interface.

Settings controls:
- **Appearance** — name, logo, colors, footer text
- **Behavior** — feature flags, default values, operational thresholds
- **Regional presentation** — language, timezone, date/number formats
- **Operational defaults** — pagination size, session lifetime, notification retention
- **Mail configuration** — SMTP host, credentials, encryption

---

## Design Principles

### 1. Multi-Tier Resolution with Caching

Every setting value resolves through a five-tier chain, ensuring the system always returns
the most specific value available:

```
1. Runtime overrides (ephemeral — for testing only)
2. AppInfo (composer.json — SSOT for app name, version, author)
3. Database (cached forever, invalidated on write)
4. Laravel config file (code defaults)
5. Default parameter (caller-provided fallback)
```

Tiers 1–2 are bypassed when not applicable. The database tier is cached with
`Cache::rememberForever()` and invalidated atomically on every write — no manual cache
clearing required.

### 2. Single Source of Truth for App Metadata

`AppInfo` reads from `composer.json` (cached 24h) and is the authoritative source for
application name, version, author, license, and support URLs. The `setting()` helper's
resolution chain maps several setting keys directly to `AppInfo` fields — so `app_name`
always returns the canonical name regardless of whether it exists in the database.

### 3. Type-Enforced Storage

Every setting has an explicit type. The `SettingValueCast` handles serialization and
deserialization automatically:

| Type | Stored As | Retrieved As |
|---|---|---|
| `string` | Plain string | `string` |
| `integer` | String representation | `int` |
| `float` | String representation | `float` |
| `boolean` | `0` or `1` | `bool` |
| `json` / `array` | JSON-encoded string | `array` |
| `encrypted` | Encrypted string | Decrypted `string` |
| `null` | `NULL` | `null` |

Type mismatches on write are rejected with validation errors. Encrypted values are
transparently decrypted on read.

### 4. Immediate Propagation via Cache Invalidation

Every setting change triggers atomic cache invalidation:
1. The specific key's cache entry is forgotten
2. The group cache is forgotten
3. The "all settings" cache is forgotten
4. If the key relates to colors or branding, the theme CSS variables cache is also forgotten

This guarantees that configuration changes take effect on the next page load — no restart,
no deployment, no manual cache clear.

### 5. Audit Trail on Every Write

All setting changes (create, update, delete) are logged via SmartLogger with before/after
values, acting user, timestamp, and IP address. This provides a complete change history for
compliance and troubleshooting.

---

## Layer Structure

```
app/Domain/Settings/
├── Actions/         → Command and Read Actions
├── Casts/           → Custom Eloquent casts
├── Enums/           → Setting group classification
├── Http/            → Middleware
├── Livewire/        → Admin UI components
│   └── Forms/       → Form Objects for settings forms
├── Models/          → Eloquent model
├── Policies/        → Authorization
├── Rules/           → Validation rules
└── Support/         → Resolution, metadata, color utilities
```

---

## Actions

### Command Actions

| Action | Input | Side Effects | Description |
|---|---|---|---|
| `SetSettingAction` | key, value, group, description, type | Cache invalidation, audit log | Create or update a single setting. Auto-detects type from PHP value |
| `BatchSetSettingAction` | `array<key, value>` | Transaction, cache invalidation, audit log | Sets multiple settings atomically via `SetSettingAction` |
| `SaveSystemSettingsAction` | general, branding, mail arrays | Delegates to BatchSetSetting + UploadBrandAsset | Orchestrates the complete system settings form submission |
| `UploadBrandAssetAction` | UploadedFile, type (logo/favicon) | Media library attachment | Uploads and stores brand assets via Spatie Media Library |
| `TestMailSettingsAction` | recipientEmail, config | Temporary config override, test notification | Sends a test email to verify SMTP configuration |

### Read Action

| Action | Purpose |
|---|---|
| `GetAcademicYearsAction` | Retrieves academic years for the settings configuration UI |

**Note:** `GetAcademicYearsAction` is a Read Action — it performs a query without mutation.
It should NOT extend `BaseAction` (no transaction or logging needed). Its current usage of
`BaseAction` is a heritage from before the Action Triad was formalized.

---

## Models

### Setting

The single model in this domain — a key-value store with type enforcement.

| Column | Type | Purpose |
|---|---|---|
| `key` | string (unique) | Setting identifier — lowercase snake_case with dots |
| `value` | text | Stored value (cast by `SettingValueCast`) |
| `type` | string | Data type: string, integer, float, boolean, json, encrypted, null |
| `description` | text, nullable | Human-readable explanation |
| `group` | string, nullable | Classification group for UI organization |

**Key validation:** Keys must match `^[a-z][a-z0-9_.]*$` — enforced by `ValidSettingKey` rule.

**Media collections:** Logo (`brand_logo`) and favicon (`brand_favicon`), both single-file with
200px WebP thumb conversion.

**Scopes:** `group()`, `byKey()`, `inGroup()`, `ofType()`, `searchable()`.

---

## Enums

### SettingGroup

Classifies settings into 7 groups for admin UI organization:

| Case | Key | Controls |
|---|---|---|
| `GENERAL` | `general` | App name, site title, default locale, active academic year |
| `MAIL` | `mail` | SMTP host, port, encryption, credentials, from address |
| `SYSTEM` | `system` | Pagination defaults, session lifetime, operational thresholds |
| `BRANDING` | `branding` | Logo, favicon, primary/secondary/accent colors, footer, custom CSS |
| `FEATURES` | `features` | Feature flags (boolean toggles enabled/disabled) |
| `LOCALIZATION` | `localization` | Default language, timezone, date/time/number formats |
| `NOTIFICATIONS` | `notifications` | Default delivery preferences, digest timing, retention |

---

## Support

### Settings (Resolution Engine)

The central settings service with multi-tier resolution. 394 lines covering:

| Method | Purpose |
|---|---|
| `get(key)` | Resolve single value through 5-tier chain |
| `all()` | Return all settings as collection (cached forever) |
| `group(name)` | Return settings in a group (cached forever) |
| `has(key)` | Check if a setting has a non-null value |
| `set(settings)` | Batch upsert with cache invalidation |
| `forget(key, group)` | Invalidate cache for a specific key and related caches |
| `forgetGroup(name)` | Invalidate all cache entries for a group |
| `keys()` | Return all distinct setting keys (cached forever) |
| `groups()` | Return all distinct group names |
| `countByGroup()` | Return setting counts per group |
| `override(overrides)` | Set runtime overrides (testing only) |
| `clearOverrides()` | Clear all runtime overrides |

Resolution chain in `resolveSingle()`:

```
1. Runtime overrides → 2. AppInfo mapping → 3. Database (cached) → 4. Config file → 5. Default
```

### AppInfo (Composer Metadata)

Reads application metadata from `composer.json` (cached 24h). Provides:

| Method | Returns |
|---|---|
| `all()` | Full parsed array: name, version, description, license, author, support |
| `get(key)` | Single value by dot-notation key |
| `version()` | Version string |
| `author()` | Author array |
| `logo()` | Default logo URL |
| `clearCache()` | Flush static + cache store |

Integrity verification: calls `Integrity::verify()` on every `all()` call to detect
unauthorized composer.json modifications (exits in production, warns in dev).

### AppMetadata (Brand Resolver)

Facade that combines Settings + AppInfo + Theme into a unified brand API. Used by the
`brand()` helper function.

| Method | Description |
|---|---|
| `brandName()` | Settings `brand_name` or AppInfo name fallback |
| `brandLogo()` | Settings `brand_logo` or default asset |
| `siteTitle()` | Settings `site_title` or brand name |
| `favicon()` | Settings `site_favicon` or logo fallback |
| `colors()` | Theme::all() with defaults |
| `version()` | AppInfo version |
| `get(key)` | Generic getter with key mapping |

Every method follows the same pattern: check if installed → try Settings → fallback to
AppInfo → return default. This pattern is intentionally repetitive for clarity — each
method has a unique fallback chain.

### Color (Color Computation)

Pure utility class with zero framework dependencies. Handles hex color manipulation:

| Method | Description |
|---|---|
| `hexToRgb(hex)` | Convert hex to RGB array |
| `relativeLuminance(hex)` | Calculate WCAG relative luminance |
| `contrastColor(hex)` | Return black or white for readability |
| `lighten(hex, percent)` | Lighten by percentage |
| `darken(hex, percent)` | Darken by percentage |
| `isValid(hex)` | Validate hex color format |
| `computeBaseShades(hex)` | Generate 3 base surface shades + content color |
| `computeDarkShades(hex)` | Generate dark mode equivalents |

---

## Casts

### SettingValueCast

Custom Eloquent cast that handles 7 data types with proper serialization. Encrypted values
use Laravel's `Crypt::encryptString()`/`decryptString()`. All encoding/decoding errors are
logged via SmartLogger with context (model class, key, setting ID).

---

## Middleware

### SetLocaleMiddleware

Reads the current locale from `Locale::current()` (which checks cookie → config → default)
and sets it on the application via `App::setLocale()`.

---

## Livewire Components

| Component | Form Object | Purpose |
|---|---|---|
| `SystemSetting` | `BrandingForm`, `GeneralSettingsForm`, `MailSettingsForm` | Main system settings page with 3 tabs: General, Branding, Mail |
| `AppSignature` | — | Displays application name, version, author, license in the admin footer |

---

## Policies

### SettingPolicy

Grants full access to super_admin only:
- `viewAny`, `view`: admin or super_admin
- `create`, `update`, `delete`: super_admin only

---

## Dependency Graph

```
Settings Domain
├── Core          → BaseModel, BaseAction, SmartLogger, CacheKeys, Integrity
├── User          → TestMailNotification (email testing)
├── Shared        → Theme::all(), Theme::defaults() (consumed by AppMetadata)
├── School        → AcademicYear (GetAcademicYearsAction, cross-domain violation)
└── Laravel       → Crypt, Cache, Config, Validator
```

**Cross-domain note:** `GetAcademicYearsAction` imports from `School\Models\AcademicYear`,
which violates the no-sibling-import rule. Long-term fix: move academic year queries to the
School domain and call via Action delegation or a Core contract.

---

## Requirements

### Rules

- Settings keys must be lowercase snake_case with dots — enforced by `ValidSettingKey`
- All setting changes MUST invalidate the relevant cache entries
- All setting changes MUST be audited via SmartLogger
- Encrypted settings use `Crypt::encryptString()` — transparently decrypted on read
- The `setting()` helper (defined in `app/Support/helpers.php`) delegates to `Settings::get()`
- The `brand()` helper delegates to `AppMetadata::get()`
- Read Actions should NOT extend `BaseAction` — use plain classes instead

### Key Operations

| Action | Description |
|---|---|
| `SetSettingAction` | Set a single setting with type auto-detection |
| `BatchSetSettingAction` | Set multiple settings in one transaction |
| `SaveSystemSettingsAction` | Orchestrate full system settings save (general + branding + mail) |
| `UploadBrandAssetAction` | Upload logo or favicon via media library |
| `TestMailSettingsAction` | Send test email with temporary SMTP override |
| `GetAcademicYearsAction` | List academic years for settings UI |

## Where to Find It

- `app/Domain/Settings/Support/Settings.php` — settings resolution engine
- `app/Domain/Settings/Support/AppInfo.php` — composer.json metadata
- `app/Domain/Settings/Support/AppMetadata.php` — brand resolver
- `app/Domain/Settings/Support/Color.php` — color utility
- `app/Domain/Settings/Models/Setting.php` — key-value model
- `app/Domain/Settings/Casts/SettingValueCast.php` — type casting
- `app/Domain/Settings/Enums/SettingGroup.php` — group classification
- `app/Domain/Settings/Rules/ValidSettingKey.php` — key validation
- `app/Domain/Settings/Policies/SettingPolicy.php` — authorization
- `app/Support/helpers.php` — `setting()` and `brand()` helper functions
- `config/settings.php` — color presets and defaults
