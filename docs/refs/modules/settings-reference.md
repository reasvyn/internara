# Settings — Technical Reference

## Description

Detailed structural and implementation reference for the **Settings** module.

---

## Overview

Manages system-wide configuration: key-value settings store, brand identity (logo, colors),
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

## Policies

| File | Policy | Extends |
|---|---|---|
| `Policies/SettingPolicy.php` | `SettingPolicy` | `BasePolicy` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Branding/Livewire/Forms/BrandingForm.php` | `BrandingForm` | `BaseFormView` |
| `Livewire/Forms/GeneralSettingsForm.php` | `GeneralSettingsForm` | `BaseFormView` |
| `Livewire/Forms/MailSettingsForm.php` | `MailSettingsForm` | `BaseFormView` |
| `Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` |
| `Livewire/SystemSetting.php` | `SystemSetting` | `Component` |

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

| File                        | Class      | Purpose                                                                                      |
| --------------------------- | ---------- | -------------------------------------------------------------------------------------------- |
| `Services/Settings.php`     | `Settings` | Runtime settings manager with cached reads (note: includes write path that bypasses Actions) |
| `Support/Brand.php`         | `Brand`    | Dynamic branding values from database                                                        |
| `Support/SettingCaster.php` | `SettingCaster` | Type casting logic for setting values                                                   |
| `Support/helpers.php`       | —          | `setting()`, `brand()` global helpers                                                        |
| `Locale/Support/Locale.php` | `Locale`   | Locale management                                                                            |
| `Theme/Support/Theme.php`   | `Theme`    | Theme engine (CSS variables)                                                                 |

## Observers

| File                                    | Observer          | Handles                                                          |
| --------------------------------------- | ----------------- | ---------------------------------------------------------------- |
| `Observers/SettingObserver.php`         | `SettingObserver` | Eloquent `created`/`updated`/`deleted` — clears affected cache keys |

## Rules

| File | Rule | Purpose |
|---|---|---|
| `Rules/ValidSettingKey.php` | `ValidSettingKey` | — |

## Routes

File: `routes/web/settings.php` Named route: `admin.settings`

## Views

Views are located in `resources/views/settings/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the design
system.

## Tests

Tests are located in `tests/Settings/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions. Tests are spec-driven: each test traces to a spec requirement ID
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

## Architectural Integration

- **Submodules**: `Branding`, `Locale`, `Theme`
- **Business Logic**: `app/Modules/Settings/`
- **Routing**: `routes/web/settings.php`
- **Views**: `resources/views/settings/`
- **Testing**: `tests/Settings/`
- **Dependencies**: Core, Academics
- **Used By**: All modules (via `setting()` and `brand()` helpers)

_For overview and business context, see [settings.md](settings.md)._
