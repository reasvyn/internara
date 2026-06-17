# Settings — Technical Reference

> **Last updated:** 2026-06-17
> **Changes:** sync — add Data/DTOs section (SettingData, SettingGroupData)

Detailed structural and implementation reference for the **Settings** module.

---

## Overview

Manages system-wide configuration: key-value settings store, brand identity (logo, colors), localization (locale switching), mail configuration, theme management (dark/light), and global feature toggles.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/SetSettingAction.php` | `SetSettingAction` | `BaseCommandAction` |
| `Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseCommandAction` |
| `Actions/DeleteSettingAction.php` | `DeleteSettingAction` | `BaseCommandAction` |
| `Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseCommandAction` |
| `Actions/ReadAcademicYearAction.php` | `ReadAcademicYearAction` | `BaseReadAction` |
| `Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseCommandAction` |
| `Branding/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseCommandAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/Setting.php` | `Setting` | `BaseModel` (implements `SettingsStore`) |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Enums/SettingGroup.php` | `SettingGroup` | `LabelEnum` | general, mail, system, branding, features, localization, notifications |
| `Enums/SettingType.php` | `SettingType` | `LabelEnum` | string, integer, float, boolean, json, encrypted, null |
| `Enums/MediaCollection.php` | `MediaCollection` | `LabelEnum` | brand_logo, brand_favicon |

---

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Data/SettingData.php` | `SettingData` | `BaseData` |
| `Data/SettingGroupData.php` | `SettingGroupData` | `BaseData` |

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/SettingEntity.php` | `SettingEntity` | `BaseEntity` |

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Policies/SettingPolicy.php` | `SettingPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Livewire/SystemSetting.php` | `SystemSetting` | `Component` |
| `Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` |
| `Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Livewire/Forms/GeneralSettingsForm.php` | `GeneralSettingsForm` |
| `Livewire/Forms/MailSettingsForm.php` | `MailSettingsForm` |
| `Branding/Livewire/Forms/BrandingForm.php` | `BrandingForm` |

## Casts

| File | Cast | Purpose |
| ---- | ---- | ------- |
| `Casts/SettingValueCast.php` | `SettingValueCast` | Casts setting values to appropriate types |

## Events

| File | Event | Dispatched By |
| ---- | ----- | ------------- |
| `Events/SettingUpdated.php` | `SettingUpdated` | `SetSettingAction`, `BatchSetSettingAction` |

## Middleware

| File | Middleware | Purpose |
| ---- | ---------- | ------- |
| `Locale/Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Sets application locale from session |

## Support

| File | Class | Purpose |
| ---- | ----- | ------- |
| `Support/Settings.php` | `Settings` | Runtime settings manager with cached reads |
| `Support/Brand.php` | `Brand` | Dynamic branding values from database |
| `Support/helpers.php` | — | `setting()`, `brand()` global helpers |
| `Locale/Support/Locale.php` | `Locale` | Locale management |
| `Theme/Support/Theme.php` | `Theme` | Theme engine (CSS variables) |

## Listeners

| File | Listener | Handles |
| ---- | -------- | ------- |
| `Listeners/InvalidateSettingsCache.php` | `InvalidateSettingsCache` | `SettingUpdated` — clears affected cache keys |

## Rules

| File | Rule | Purpose |
| ---- | ---- | ------- |
| `Rules/ValidSettingKey.php` | `ValidSettingKey` | Validates setting key format |

---

## Routes

File: `routes/web/settings.php`
Naming pattern: `settings.{resource}.{action}`

## Views

Views are located in `resources/views/settings/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Settings/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

| File | What It Tests |
| ---- | ------------- |
| `Unit/Settings/Enums/SettingGroupTest.php` | SettingGroup enum cases, labels, defaults |
| `Unit/Settings/Enums/SettingTypeTest.php` | SettingType detect, cast, values, labels |
| `Unit/Settings/Enums/MediaCollectionTest.php` | MediaCollection cases |
| `Unit/Settings/Data/SettingDataTest.php` | SettingData DTO construction and serialization |
| `Unit/Settings/Data/SettingGroupDataTest.php` | SettingGroupData DTO |
| `Unit/Settings/Entities/SettingEntityTest.php` | SettingEntity fromModel, type checks, boolean/json/int helpers |
| `Unit/Settings/Models/SettingModelTest.php` | Setting model scopes, casts, media collections |
| `Unit/Settings/Casts/SettingValueCastTest.php` | SettingValueCast get/set for all types |
| `Unit/Settings/Policies/SettingPolicyTest.php` | Policy authorization gates |
| `Unit/Settings/Rules/ValidSettingKeyTest.php` | Key validation rule |
| `Unit/Settings/Support/SettingsTest.php` | Settings facade: get, set, has, groups, forget, cache invalidation |
| `Unit/Settings/Support/BrandTest.php` | Brand facade: name, logo, colors, get routing |
| `Unit/Settings/Support/ThemeTest.php` | Theme: defaults, presets, cssVariables, color computation |
| `Unit/Settings/Support/LocaleTest.php` | Locale switching, supported locales, metadata |
| `Unit/Settings/Branding/Data/BrandDataTest.php` | BrandData DTO, get(), immutability |
| `Unit/Settings/Livewire/LangSwitcherTest.php` | LangSwitcher component |
| `Unit/Settings/Livewire/ThemeSwitcherTest.php` | ThemeSwitcher component |
| `Feature/Settings/Actions/SetSettingActionTest.php` | SetSettingAction execute, type detection, validation |
| `Feature/Settings/Actions/BatchSetSettingActionTest.php` | BatchSetSettingAction, transactional, array config |
| `Feature/Settings/Actions/DeleteSettingActionTest.php` | DeleteSettingAction, key deletion |
| `Feature/Settings/Actions/SaveSystemSettingsActionTest.php` | SaveSystemSettingsAction, combined form save |
| `Feature/Settings/Actions/ReadAcademicYearActionTest.php` | `ReadAcademicYearAction` |
| `Feature/Settings/Actions/TestMailSettingsActionTest.php` | TestMailSettingsAction SMTP test |
| `Feature/Settings/Actions/UploadBrandAssetActionTest.php` | UploadBrandAssetAction, media upload |
| `Feature/Settings/Events/SettingUpdatedEventTest.php` | SettingUpdated event dispatch and listener |
| `Feature/Settings/Listeners/InvalidateSettingsCacheTest.php` | InvalidateSettingsCache: single key, group, theme cache |
| `Feature/Settings/Http/Middleware/SetLocaleMiddlewareTest.php` | SetLocaleMiddleware locale resolution |
| `Feature/Settings/SettingsRouteTest.php` | Settings route accessibility |

## Factories

| Factory | Model |
| ------- | ----- |
| `SettingFactory` | `Setting` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_settings_table` | `settings` |

---


---

## Architectural Integration

- **Submodules**: `Branding`, `Locale`, `Theme`
- **Business Logic**: `app/Settings/`
- **Routing**: `routes/web/settings.php`
- **Views**: `resources/views/settings/`
- **Testing**: `tests/Feature/Settings/`, `tests/Unit/Settings/`
- **Dependencies**: Core, Academics
- **Used By**: All modules (via `setting()` and `brand()` helpers)

*For overview and business context, see [settings.md](settings.md).*