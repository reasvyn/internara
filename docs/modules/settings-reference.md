# Settings — Technical Reference

> **Last updated:** 2026-06-10

Detailed structural and implementation reference for the **Settings** module.

---

## Overview

Manages system-wide configuration: key-value settings store, brand identity (logo, colors), localization (locale switching), mail configuration, theme management (dark/light), and global feature toggles.

### Submodules

- `Branding` — Brand assets, logo, color presets
- `Locale` — Language switching (EN/ID)
- `Theme` — Dark/light mode and CSS variable generation

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` |
| `Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` |
| `Actions/DeleteSettingAction.php` | `DeleteSettingAction` | `BaseAction` |
| `Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseAction` |
| `Actions/GetAcademicYearsAction.php` | `GetAcademicYearsAction` | Read |
| `Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` |
| `Branding/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/Setting.php` | `Setting` | `BaseModel` (implements `SettingsStore`) |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Enums/SettingGroup.php` | `SettingGroup` | `LabelEnum` | general, branding, mail, locale, theme, system |

---

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

## Middleware

| File | Middleware | Purpose |
| ---- | ---------- | ------- |
| `Locale/Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Sets application locale from session |

## Support

| File | Class | Purpose |
| ---- | ----- | ------- |
| `Support/Settings.php` | `Settings` | Runtime settings manager with cached reads |
| `Support/Brand.php` | `Brand` | Dynamic branding values from database |
| `Support/AppInfo.php` | `AppInfo` | Delegates to `Core\AppInfo` |
| `Support/helpers.php` | — | `setting()`, `brand()` global helpers |
| `Locale/Support/Locale.php` | `Locale` | Locale management |
| `Theme/Support/Theme.php` | `Theme` | Theme engine (CSS variables) |

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

## Factories

| Factory | Model |
| ------- | ----- |
| `SettingFactory` | `Setting` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_settings_table` | `settings` |

---

## File Organization

```
app/Settings/
├── Actions/
│   ├── BatchSetSettingAction.php
│   ├── DeleteSettingAction.php
│   ├── GetAcademicYearsAction.php
│   ├── SaveSystemSettingsAction.php
│   ├── SetSettingAction.php
│   └── TestMailSettingsAction.php
├── Branding/
│   ├── Actions/UploadBrandAssetAction.php
│   └── Livewire/Forms/BrandingForm.php
├── Casts/SettingValueCast.php
├── Enums/SettingGroup.php
├── Locale/
│   ├── Http/Middleware/SetLocaleMiddleware.php
│   └── Support/Locale.php
├── Livewire/
│   ├── Forms/
│   │   ├── GeneralSettingsForm.php
│   │   └── MailSettingsForm.php
│   ├── LangSwitcher.php
│   ├── SystemSetting.php
│   └── ThemeSwitcher.php
├── Models/Setting.php
├── Policies/SettingPolicy.php
├── Rules/ValidSettingKey.php
├── Support/
│   ├── AppInfo.php
│   ├── Brand.php
│   ├── Settings.php
│   └── helpers.php
└── Theme/
    └── Support/Theme.php
```

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