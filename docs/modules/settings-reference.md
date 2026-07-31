# Settings — Technical Reference

> **Last updated:** 2026-07-31 **Changes:** sync — add RemoveBrandAssetAction + BrandData, fix SystemSetting extends + SettingUpdated dispatchers, route names, flat test paths

## Description

Detailed structural and implementation reference for the **Settings** module.

---

## Overview

Manages system-wide configuration: key-value settings store, brand identity (logo, colors),
localization (locale switching), mail configuration, theme management (dark/light), and global
feature toggles.

## Actions

| File                                          | Class                      | Extends             |
| --------------------------------------------- | -------------------------- | ------------------- |
| `Actions/SetSettingAction.php`                | `SetSettingAction`         | `BaseCommandAction` |
| `Actions/BatchSetSettingAction.php`           | `BatchSetSettingAction`    | `BaseCommandAction` |
| `Actions/DeleteSettingAction.php`             | `DeleteSettingAction`      | `BaseCommandAction` |
| `Actions/SaveSystemSettingsAction.php`        | `SaveSystemSettingsAction` | `BaseCommandAction` |
| `Actions/ReadAcademicYearAction.php`          | `ReadAcademicYearAction`   | `BaseReadAction`    |
| `Actions/TestMailSettingsAction.php`          | `TestMailSettingsAction`   | `BaseCommandAction` |
| `Branding/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction`   | `BaseCommandAction` |
| `Branding/Actions/RemoveBrandAssetAction.php` | `RemoveBrandAssetAction`   | `BaseCommandAction` |

---

## Models

| File                 | Class     | Extends                             |
| -------------------- | --------- | ----------------------------------- |
| `Models/Setting.php` | `Setting` | `BaseModel` (implements `HasMedia`) |

---

## Enums

| File                        | Enum              | Implements  | Values                                                                 |
| --------------------------- | ----------------- | ----------- | ---------------------------------------------------------------------- |
| `Enums/SettingGroup.php`    | `SettingGroup`    | `LabelEnum` | general, mail, system, branding, features, localization, notifications |
| `Enums/SettingType.php`     | `SettingType`     | `LabelEnum` | string, integer, float, boolean, json, encrypted, null                 |
| `Enums/MediaCollection.php` | `MediaCollection` | `LabelEnum` | brand_logo, brand_favicon                                              |

---

## Data / DTOs

| File                          | Class                | Extends    |
| ----------------------------- | -------------------- | ---------- |
| `Data/SettingData.php`        | `SettingData`        | `BaseData` |
| `Data/SettingEntryData.php`   | `SettingEntryData`   | `BaseData` |
| `Data/SettingGroupData.php`   | `SettingGroupData`   | `BaseData` |
| `Data/SystemSettingsData.php` | `SystemSettingsData` | `BaseData` |
| `Branding/Data/BrandData.php` | `BrandData`          | `BaseData` |

## Entities

| File                         | Class           | Extends      |
| ---------------------------- | --------------- | ------------ |
| `Entities/SettingEntity.php` | `SettingEntity` | `BaseEntity` |

## Policies

| File                         | Policy          | Extends      |
| ---------------------------- | --------------- | ------------ |
| `Policies/SettingPolicy.php` | `SettingPolicy` | `BasePolicy` |

---

## Livewire Components

| File                         | Component       | Extends     |
| ---------------------------- | --------------- | ----------- |
| `Livewire/SystemSetting.php` | `SystemSetting` | `BaseFormView` |
| `Livewire/LangSwitcher.php`  | `LangSwitcher`  | `Component` |
| `Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` |

## Livewire Forms

| File                                       | Form                  |
| ------------------------------------------ | --------------------- |
| `Livewire/Forms/GeneralSettingsForm.php`   | `GeneralSettingsForm` |
| `Livewire/Forms/MailSettingsForm.php`      | `MailSettingsForm`    |
| `Branding/Livewire/Forms/BrandingForm.php` | `BrandingForm`        |

## Casts

| File                         | Cast               | Purpose                                   |
| ---------------------------- | ------------------ | ----------------------------------------- |
| `Casts/SettingValueCast.php` | `SettingValueCast` | Casts setting values to appropriate types |

## Events

| File                        | Event            | Dispatched By                                                            | Notes                                              |
| --------------------------- | ---------------- | ------------------------------------------------------------------------ | -------------------------------------------------- |
| `Events/SettingUpdated.php` | `SettingUpdated` | `SetSettingAction`, `DeleteSettingAction` | Audit/logging only — no listener for cache invalidation |

## Middleware

| File                                             | Middleware            | Purpose                              |
| ------------------------------------------------ | --------------------- | ------------------------------------ |
| `Locale/Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Sets application locale from session |

## Support

| File                        | Class      | Purpose                                                                                      |
| --------------------------- | ---------- | -------------------------------------------------------------------------------------------- |
| `Services/Settings.php`     | `Settings` | Runtime settings manager with cached reads (note: includes write path that bypasses Actions) |
| `Support/Brand.php`         | `Brand`    | Dynamic branding values from database                                                        |
| `Support/helpers.php`       | —          | `setting()`, `brand()` global helpers                                                        |
| `Locale/Support/Locale.php` | `Locale`   | Locale management                                                                            |
| `Theme/Support/Theme.php`   | `Theme`    | Theme engine (CSS variables)                                                                 |

## Observers

| File                                    | Observer          | Handles                                                          |
| --------------------------------------- | ----------------- | ---------------------------------------------------------------- |
| `Observers/SettingObserver.php`         | `SettingObserver` | Eloquent `created`/`updated`/`deleted` — clears affected cache keys |

## Rules

| File                        | Rule              | Purpose                      |
| --------------------------- | ----------------- | ---------------------------- |
| `Rules/ValidSettingKey.php` | `ValidSettingKey` | Validates setting key format |

---

## Routes

File: `routes/web/settings.php` Named route: `admin.settings`

## Views

Views are located in `resources/views/settings/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Settings/`. See [Testing](../infrastructure/testing.md)
for the testing conventions.

| File                                                           | What It Tests                                                      |
| -------------------------------------------------------------- | ------------------------------------------------------------------ |
| `Settings/Enums/SettingGroupTest.php`                     | SettingGroup enum cases, labels, defaults                          |
| `Settings/Enums/SettingTypeTest.php`                      | SettingType detect, cast, values, labels                           |
| `Settings/Enums/MediaCollectionTest.php`                  | MediaCollection cases                                              |
| `Settings/Data/SettingDataTest.php`                       | SettingData DTO construction and serialization                     |
| `Settings/Data/SettingGroupDataTest.php`                  | SettingGroupData DTO                                               |
| `Settings/Entities/SettingEntityTest.php`                 | SettingEntity fromModel, type checks, boolean/json/int helpers     |
| `Settings/Models/SettingModelTest.php`                    | Setting model scopes, casts, media collections                     |
| `Settings/Casts/SettingValueCastTest.php`                 | SettingValueCast get/set for all types                             |
| `Settings/Policies/SettingPolicyTest.php`                 | Policy authorization gates                                         |
| `Settings/Rules/ValidSettingKeyTest.php`                  | Key validation rule                                                |
| `Settings/Support/SettingsTest.php`                       | Settings facade: get, set, has, groups, forget, cache invalidation |
| `Settings/Support/BrandTest.php`                          | Brand facade: name, logo, colors, get routing                      |
| `Settings/Support/ThemeTest.php`                          | Theme: defaults, presets, cssVariables, color computation          |
| `Settings/Support/LocaleTest.php`                         | Locale switching, supported locales, metadata                      |
| `Settings/Branding/Data/BrandDataTest.php`                | BrandData DTO, get(), immutability                                 |
| `Settings/Livewire/LangSwitcherTest.php`                  | LangSwitcher component                                             |
| `Settings/Livewire/ThemeSwitcherTest.php`                 | ThemeSwitcher component                                            |
| `Settings/Actions/SetSettingActionTest.php`            | SetSettingAction execute, type detection, validation               |
| `Settings/Actions/BatchSetSettingActionTest.php`       | BatchSetSettingAction, transactional, array config                 |
| `Settings/Actions/DeleteSettingActionTest.php`         | DeleteSettingAction, key deletion                                  |
| `Settings/Actions/SaveSystemSettingsActionTest.php`    | SaveSystemSettingsAction, combined form save                       |
| `Settings/Actions/ReadAcademicYearActionTest.php`      | `ReadAcademicYearAction`                                           |
| `Settings/Actions/TestMailSettingsActionTest.php`      | TestMailSettingsAction SMTP test                                   |
| `Settings/Actions/UploadBrandAssetActionTest.php`      | UploadBrandAssetAction, media upload                               |
| `Settings/Events/SettingUpdatedEventTest.php`          | SettingUpdated event dispatch                                      |
| `Settings/Observers/SettingObserverTest.php`           | SettingObserver: cache invalidation on create/update/delete        |
| `Settings/Http/Middleware/SetLocaleMiddlewareTest.php` | SetLocaleMiddleware locale resolution                              |
| `Settings/SettingsRouteTest.php`                       | Settings route accessibility                                       |

## Factories

| Factory          | Model     |
| ---------------- | --------- |
| `SettingFactory` | `Setting` |

## Migrations

| Migration               | Table      |
| ----------------------- | ---------- |
| `create_settings_table` | `settings` |

---

## Architectural Integration

- **Submodules**: `Branding`, `Locale`, `Theme`
- **Business Logic**: `app/Settings/`
- **Routing**: `routes/web/settings.php`
- **Views**: `resources/views/settings/`
- **Testing**: `tests/Settings/`
- **Dependencies**: Core, Academics
- **Used By**: All modules (via `setting()` and `brand()` helpers)

_For overview and business context, see [settings.md](settings.md)._
