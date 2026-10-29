# Setting — Technical Reference

## Description

Detailed structural and implementation reference for the **Setting** module.

---

## Overview

Manages system-wide configuration: key-value setting store, brand identity (logo, colors),
localization (locale switching), mail configuration, theme management (dark/light), and global
feature toggles.

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseCommandAction` |
| `Actions/DeleteSettingAction.php` | `DeleteSettingAction` | `BaseCommandAction` |
| `Actions/ReadAcademicYearAction.php` | `ReadAcademicYearAction` | `BaseReadAction` |
| `Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseCommandAction` |
| `Actions/SetSettingAction.php` | `SetSettingAction` | `BaseCommandAction` |
| `Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseCommandAction` |
| `Domain/Branding/Actions/RemoveBrandAssetAction.php` | `RemoveBrandAssetAction` | `BaseCommandAction` |
| `Domain/Branding/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Models/Setting.php` | `Setting` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Enums/MediaCollection.php` | `MediaCollection` | `LabelEnum` | — |
| `Enums/SettingGroup.php` | `SettingGroup` | `LabelEnum` | — |
| `Enums/SettingType.php` | `SettingType` | `LabelEnum` | — |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Data/SettingData.php` | `SettingData` | `BaseData` |
| `Data/SettingEntryData.php` | `SettingEntryData` | `BaseData` |
| `Data/SettingGroupData.php` | `SettingGroupData` | `BaseData` |
| `Data/SystemSettingsData.php` | `SystemSettingsData` | `BaseData` |
| `Domain/Branding/Data/BrandData.php` | `BrandData` | `BaseData` |

## Entities

| File | Class | Extends |
|---|---|---|
| `Entities/SettingEntity.php` | `SettingEntity` | `BaseEntity` |

## Policies & Permissions

| File | Policy | Extends |
|---|---|---|
| `Policies/SettingPolicy.php` | `SettingPolicy` | `BasePolicy` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Branding/Livewire/Forms/BrandingForm.php` | `BrandingForm` | `Form` |
| `Livewire/Forms/GeneralSettingsForm.php` | `GeneralSettingsForm` | `Form` |
| `Livewire/Forms/MailSettingsForm.php` | `MailSettingsForm` | `Form` |
| `Domain/Locale/Livewire/LangSwitch.php` | `LangSwitch` | `Component` |
| `Livewire/SystemSetting.php` | `SystemSetting` | `BaseFormView` |

## Casts

| File                         | Cast               | Purpose                                   |
| ---------------------------- | ------------------ | ----------------------------------------- |
| `Casts/SettingValueCast.php` | `SettingValueCast` | Casts setting values to appropriate types |

## Events

| File | Event | Extends |
|---|---|---|
| `Events/SettingUpdated.php` | `SettingUpdated` | `BaseEvent` |

## Middleware

| File                                             | Middleware            | Purpose                              |
| ------------------------------------------------ | --------------------- | ------------------------------------ |
| `Locale/Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Sets application locale from session |

## Support

| File                        | Class      | Purpose                                                         |
| --------------------------- | ---------- | --------------------------------------------------------------- |
| `Support/Brand.php`         | `Brand`    | Dynamic branding values from database                           |
| `Support/SettingCaster.php` | `SettingCaster` | Type casting logic for setting values                        |
| `Support/helpers.php`       | —          | `setting()`, `brand()` global helpers                           |
| `Locale/Support/Locale.php` | `Locale`   | Locale management                                               |
| `Theme/Support/Theme.php`   | `Theme`    | Theme engine (CSS variables)                                    |

## Observers

| File                                    | Observer          | Handles                                                          |
| --------------------------------------- | ----------------- | ---------------------------------------------------------------- |
| `Observers/SettingObserver.php`         | `SettingObserver` | Eloquent `created`/`updated`/`deleted` — clears affected cache keys |

## Rules

| File | Rule | Purpose |
|---|---|---|
| `Rules/ValidSettingKey.php` | `ValidSettingKey` | — |

## Routes

File: `routes/web/setting.php` Named route: `admin.settings`

## Views

Views are located in `resources/views/setting/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the design
system.

## Tests

Tests are located in `tests/{Type}/Setting/` (Arch, Unit, Feature, Browser). See [Testing](../../guides/infra/testing.md)
for the testing conventions. Tests are requirement-traceable: each test traces to a spec requirement ID
(`FR-*` / `NFR-*` / `UC-*`) using the `test("{SpecID}-{ReqID}: Test description...")` convention
(grouped under `describe("{SpecID}: Test description...")`); there is no one-test-per-class mandate.

## Factories

| Factory          | Model     |
| ---------------- | --------- |
| `SettingFactory` | `Setting` |

## Migrations

| Migration               | Table      |
| ----------------------- | ---------- |
| `create_settings_table` | `settings` |

---

## Services

| File | Service | Purpose |
|---|---|---|
| `Services/Settings.php` | `Settings` | Static runtime setting reader backed by a shared registry cache (`get`, `forget`, `keys`); the write path bypasses Actions by design |

## Caching

Every setting read is served from one cached snapshot of the `settings` table, so a cold request
costs a single query instead of one query per key.

| Cache key | Shape | Invalidated by |
|---|---|---|
| `cache-keys.settings_all` | `array<key, casted value>` hydrated through `Setting` | `SettingObserver` (`created`/`updated`/`deleted`), `Settings::forget()`, `skipCache` reads |
| `cache-keys.settings_key` + key | single casted value derived from the registry | same as above |
| `cache-keys.settings_group` + group | collection of `Setting` models | same as above |
| `cache-keys.theme_css_variables` | CSS variable map built by `Theme::cssVariables()` | observer when the key is listed in `settings.theme_cache_keys` |
| `cache-keys.brand_colors` | colour palette from `Theme::all()` | `Brand::clearCache()`, observer |

- The registry map is hydrated through the model (`get(['key', 'type', 'value'])`), so value casts
  apply: `encrypted` settings are cached decrypted, `json` settings decoded, `boolean`/`integer`
  coerced. Never cache raw `pluck()`ed values — ciphertext would leak into every read.
- `Brand` memoises resolved values in a per-request static map; `Brand::clearCache()` resets both
  the memo and `brand_colors`, and is called by `SettingObserver` and `Settings::forget()`.
- `setting(..., skipCache: true)` drops `settings_all` before reading, so a database write that
  bypassed the observer is still observed by the bypassing reader.

---

## Architectural Integration

- **Submodules**: `Branding`, `Locale`, `Theme`
- **Business Logic**: `app/Modules/Setting/`
- **Routing**: `routes/web/setting.php`
- **Views**: `resources/views/setting/`
- **Testing**: `tests/{Type}/Setting/`
- **Dependencies**: Core, Academic
- **Used By**: All modules (via `setting()` and `brand()` helpers)

_For overview and business context, see [setting.md](setting.md)._
