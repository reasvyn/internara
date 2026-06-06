# Settings — Technical Reference

> Last updated: 2026-06-06
> Changes: Extracted from SysAdmin into standalone module; created dedicated documentation.

Detailed structural and implementation reference for the **Settings** module.

---

## Overview

Manages system-wide configuration as a key-value store with type enforcement, caching, and dynamic branding support. Provides the global `setting()`, `brand()`, and `app_info()` helper functions used across all modules.

### Module Statistics
- **Actions**: 6 business logic operations
- **Models**: 1 data entity (key-value store)
- **Livewire Components**: 1 UI page (with 3 Form Objects)
- **Policies**: 1 authorization rule
- **Enums**: 1 setting group enum
- **Casts**: 1 value cast (7 types)
- **Middleware**: 1 locale middleware
- **Support Classes**: 5 (`Settings`, `AppMetadata`, `AppInfo`, `Locale`, `Theme`)

---

## Dependency Graph

This module depends on:
- **Core** — BaseAction, BaseModel, SmartLogger, SettingsStore contract
- **Academics** — AcademicYear model (for active academic year selection)
- **User** — TestMailNotification (for SMTP test)

**Used By**: All modules (via global helpers)

---

## Directory Structure

```
app/Settings/
├── Actions/
│   ├── BatchSetSettingAction.php       ← transactional batch upsert
│   ├── GetAcademicYearsAction.php       ← read academic years for dropdown
│   ├── SaveSystemSettingsAction.php     ← orchestrator: general + branding + mail
│   ├── SetSettingAction.php             ← single key upsert (validates, detects type)
│   ├── TestMailSettingsAction.php       ← dynamic SMTP config, test notification
│   └── UploadBrandAssetAction.php       ← upload logo/favicon to media library
├── Casts/
│   └── SettingValueCast.php            ← get/set with 7 type handlers
├── Enums/
│   └── SettingGroup.php                ← 7 groups: GENERAL, MAIL, SYSTEM, BRANDING, FEATURES, LOCALIZATION, NOTIFICATIONS
├── Http/
│   └── Middleware/
│       └── SetLocaleMiddleware.php     ← sets app locale from stored preference
├── Livewire/
│   ├── Forms/
│   │   ├── GeneralSettingsForm.php     ← brand_name, site_title, default_locale, active_academic_year
│   │   ├── BrandingForm.php            ← colors, presets, logo, favicon
│   │   └── MailSettingsForm.php        ← SMTP: host, port, encryption, credentials
│   └── SystemSetting.php               ← main settings page component
├── Models/
│   └── Setting.php                     ← key-value model with media support
├── Policies/
│   └── SettingPolicy.php               ← admin view, superadmin mutate
├── Rules/
│   └── ValidSettingKey.php             ← lowercase alphanumeric + underscore/dot
└── Support/
    ├── AppInfo.php                     ← composer.json metadata (cached 24h)
    ├── AppMetadata.php                 ← dynamic branding aggregator
    ├── Locale.php                      ← bilingual locale resolution (id/en)
    ├── Settings.php                    ← static facade with caching (forever TTL)
    └── Theme.php                       ← color resolution into CSS custom properties (light/dark)
```

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` |
| `Actions/GetAcademicYearsAction.php` | `GetAcademicYearsAction` | — (no base) |
| `Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseAction` |
| `Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` |
| `Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` |
| `Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` |

### Action Details

| Action | `execute()` Parameters | Description |
|---|---|---|
| `BatchSetSettingAction` | `array $settings` | Transactional batch upsert via `SetSettingAction`. Each value can be a scalar (auto-grouped GENERAL) or an array with `value`, `group`, `description`, `type` keys. |
| `GetAcademicYearsAction` | — | Returns ordered `AcademicYear` collection (name, start_date, end_date) for the active year dropdown. |
| `SaveSystemSettingsAction` | `array $general, array $branding, array $mail` | Orchestrator: maps form inputs to setting keys, handles logo/favicon uploads, encrypts mail password, logs via SmartLogger. |
| `SetSettingAction` | `string $key, mixed $value, ?string $group, ?string $description, ?string $type` | Validates key format, auto-detects PHP type, upserts model, invalidates cache. |
| `TestMailSettingsAction` | `string $recipientEmail, array $config` | Dynamically overrides mail config, sends `TestMailNotification`, logs success/failure. |
| `UploadBrandAssetAction` | `UploadedFile $file, string $type = 'logo'` | Adds file to media library (`brand_logo` or `brand_favicon` collection), returns thumbnail URL. |

---

## Models

| File | Class | PK | Traits |
|---|---|---|---|
| `Models/Setting.php` | `Setting` | `key` (string) | `HasFactory`, `InteractsWithMedia` |

### Setting Model

- **Primary Key**: `key` (string) — not UUID, not auto-incrementing
- **Fillable**: `key`, `value`, `type`, `description`, `group`
- **Casts**: `value` → `SettingValueCast`
- **Media Collections**:
  - `brand_logo` — single file, logo uploads
  - `brand_favicon` — single file, favicon uploads
- **Media Conversions**: `thumb` (200px width, WebP format)
- **Scopes**: `group()`, `byKey()`, `inGroup()`, `ofType()`, `searchable()`
- **Constants**: `COLLECTION_LOGO`, `COLLECTION_FAVICON`, `VALID_TYPES`

### Database Table: `settings`

| Column | Type | Constraints |
|---|---|---|
| `key` | string | Primary Key |
| `value` | text | nullable |
| `type` | string | default `'string'`, one of: `string`, `integer`, `float`, `boolean`, `json`, `encrypted`, `null` |
| `description` | text | nullable |
| `group` | string | nullable, indexed |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Enums

| File | Enum | Values | Implements |
|---|---|---|---|
| `Enums/SettingGroup.php` | `SettingGroup` | `GENERAL`, `MAIL`, `SYSTEM`, `BRANDING`, `FEATURES`, `LOCALIZATION`, `NOTIFICATIONS` | `LabelEnum` |

- `SettingGroup::default()` returns `GENERAL`
- Each case's `label()` delegates to `__('setting.groups.{value}')`

---

## Casts

| File | Class | Implements | Description |
|---|---|---|---|
| `Casts/SettingValueCast.php` | `SettingValueCast` | `CastsAttributes` | Handles 7 types: `string`, `integer`, `float`, `boolean`, `json`, `encrypted`, `null` |

---

## Support Classes

| File | Class | Description |
|---|---|---|
| `Support/Locale.php` | `Locale` (final) | Bilingual locale management: `current()`, `set()`, `available()`, `isRTL()`. Resolves locale from session, user preference, or stored setting. |
| `Support/Settings.php` | `Settings` (final) | Static facade — core API: `get()`, `set()`, `all()`, `has()`, `group()`, `keys()`, `groups()`, `countByGroup()`, `forget()`, `forgetGroup()`, `override()`, `clearOverrides()`. Resolution chain: overrides → AppInfo → DB (cached forever) → config → default. |
| `Support/AppMetadata.php` | `AppMetadata` (final) | Dynamic branding: `brandName()`, `siteTitle()`, `brandLogo()`, `favicon()`, `colors()`, `version()`, `authorName()`, `authorEmail()`, `get()`. All reads guarded by `isInstalled()` check. |
| `Support/AppInfo.php` | `AppInfo` (final) | Composer.json metadata (cached 24h): `all()`, `get()`, `version()`, `author()`, `logo()`, `clearCache()`. Extracts `display_name`, `version`, `description`, `license`, `author[0]`, `support`. |
| `Support/Theme.php` | `Theme` (final) | Theme resolution: resolves color settings and preset configurations into CSS custom properties for light/dark mode support. |

---

## Rules

| File | Class | Implements | Validation |
|---|---|---|---|
| `Rules/ValidSettingKey.php` | `ValidSettingKey` (final) | `ValidationRule` | Must match `/^[a-z][a-z0-9_.]*$/` |

---

## Livewire Components

| File | Component | Extends | View |
|---|---|---|---|
| `Livewire/SystemSetting.php` | `SystemSetting` | `Component` | `settings.system-setting` |

### SystemSetting Component

- **Traits**: `WithFileUploads`
- **Layout**: `core::layouts.app`
- **Route**: `GET /admin/settings` (name: `admin.settings`, middleware: `auth`, `role:superadmin`)
- **Form Objects**:
  - `GeneralSettingsForm` — brand name, site title, default locale (id/en), active academic year
  - `BrandingForm` — 4 color inputs with hex validation, 6 preset swatches, logo/favicon upload with preview, remove confirmation
  - `MailSettingsForm` — SMTP host/port/encryption/username/password, `toMailConfig()` for test
- **Public Methods**:
  - `mount()` — loads all settings from DB
  - `save()` — validates 3 forms, orchestrates `SaveSystemSettingsAction`, activates academic year
  - `testEmail()` — validates mail fields, executes `TestMailSettingsAction`
  - `applyPreset()` — applies a color preset to the branding form
  - `confirmRemoveBrandLogo()` / `confirmRemoveFavicon()` — media deletion
- **Computed Properties**: `academicYears()`, `academicYearOptions()` — for the active year dropdown

---

## Middleware

| File | Class | Description |
|---|---|---|
| `Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Sets app locale via `Locale::current()` at the start of each request. |

---

## Authorization Policies

| File | Policy | Extends | Rules |
|---|---|---|---|
| `Policies/SettingPolicy.php` | `SettingPolicy` | `BasePolicy` | `viewAny`/`view` → admin; `create`/`update`/`delete` → superadmin |

---

## Routes

| Method | Path | Name | Middleware | Component |
|---|---|---|---|---|
| GET | `/admin/settings` | `admin.settings` | `auth`, `role:superadmin` | `SystemSetting` |

Defined in `routes/web/settings.php`, loaded from `routes/web.php`.

---

## Views

| View | File | Description |
|---|---|---|
| `settings.system-setting` | `resources/views/settings/system-setting.blade.php` | 3-column settings page: general card, color scheme card with presets, mail card; sidebar with system info and identity uploads |
| `settings.components.settings-guide` | `resources/views/settings/components/settings-guide.blade.php` | Floating help button (bottom-right) with modal explaining all setting groups |

---

## Console Commands (Indirect)

Settings cache is pre-warmed by the `system:cache-warm` command (scheduled hourly in `routes/console.php`), which calls `Settings::all()` and `Settings::group(...)` for each group to populate the cache.

---

## Tests

| Test | Type | Path |
|---|---|---|
| `SettingsRouteTest` | Feature | `tests/Feature/Settings/SettingsRouteTest.php` |
| `SetSettingActionTest` | Feature | `tests/Feature/Settings/SetSettingActionTest.php` |
| `SettingsTest` | Unit | `tests/Unit/Settings/SettingsTest.php` |
| `SettingsStoreTest` | Unit | `tests/Unit/Core/Contracts/SettingsStoreTest.php` |

---

## Factories

| Factory | Model | States |
|---|---|---|
| `Database\Factories\SettingFactory` | `Setting` | `string()`, `integer()`, `float()`, `boolean()`, `json()`, `nullType()`, `encrypted()` — each generates appropriate test data |

---

## Config

Defined in `config/settings.php`:

- `valid_types` — array of 7 valid type strings
- `colors.defaults` — default hex values for primary, secondary, accent, base, content
- `colors.presets` — 6 named presets: Sky, Emerald, Violet, Rose, Ocean, Slate (each with label + 5 color values)

---

## Cache Keys

| Constant | Key Pattern | TTL | Invalidation |
|---|---|---|---|
| `CacheKeys::SETTINGS_ALL` | `settings.all` | forever | `Settings::set()`, `Settings::forget()`, `Settings::forgetGroup()` |
| `CacheKeys::SETTINGS_GROUP` | `settings.group.{name}` | forever | `Settings::set()`, `Settings::forget()`, `Settings::forgetGroup()` |
| `CacheKeys::SETTINGS_KEYS` | `settings.keys` | forever | `Settings::set()`, `Settings::forget()` |
| `CacheKeys::SETTINGS_KEY` | `settings.{key}` | forever | `Settings::set()`, `Settings::forget()` |
| `CacheKeys::THEME_CSS_VARIABLES` | `theme.css_variables` | 1 hour | `Settings::forget()` when key contains `color` or `brand` |
| `CacheKeys::APPINFO_METADATA` | `appinfo.metadata` | 24 hours | `AppInfo::clearCache()` |

---

## Contract

| File | Interface | Method |
|---|---|---|
| `Core/Contracts/SettingsStore.php` | `SettingsStore` | `get(string $key, mixed $default = null): mixed` |

---

## Global Helpers

All defined in `app/Support/helpers.php`:

| Function | Signature | Delegates To |
|---|---|---|
| `setting()` | `setting(string\|array\|null $key, mixed $default = null, bool $skipCache = false)` | `Settings::get()` |
| `brand()` | `brand(string $key, mixed $default = null)` | `AppMetadata::get()` |
| `app_info()` | `app_info(?string $key, mixed $default = null)` | `AppInfo::get()` |

---

*For overview and business context, see [settings.md](settings.md)*
