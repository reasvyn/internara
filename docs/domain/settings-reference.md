# Settings — API Reference
> Last updated: 2026-05-28
> Changes: refactor(settings): implement ideal design — fix locale, optimize cache, convert Read Action


Total: 21 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` | Sets multiple settings atomically in a transaction |
| `Settings/Actions/GetAcademicYearsAction.php` | `GetAcademicYearsAction` | — | Lists academic years for settings configuration (Read Action — no transaction/log needed) |
| `Settings/Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseAction` | Orchestrates complete system settings form (general + branding + mail) |
| `Settings/Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` | Creates or updates a single setting with type auto-detection |
| `Settings/Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` | Sends test email to verify SMTP configuration |
| `Settings/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` | Uploads brand assets (logo, favicon) via media library |

## Casts

| File | Class | Implements | Description |
|---|---|---|---|
| `Settings/Casts/SettingValueCast.php` | `SettingValueCast` | `CastsAttributes` | Custom Eloquent cast supporting 7 types: string, integer, float, boolean, json, encrypted, null |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Settings/Enums/SettingGroup.php` | `SettingGroup` | `LabelEnum` | Setting group classification: GENERAL, MAIL, SYSTEM, BRANDING, FEATURES, LOCALIZATION, NOTIFICATIONS |

## Middleware

| File | Class | Description |
|---|---|---|
| `Settings/Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Middleware that sets app locale from cookie/config via Locale::current() |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Livewire/AppSignature.php` | `AppSignature` | `Component` | Displays application name, version, author, license |
| `Settings/Livewire/SystemSetting.php` | `SystemSetting` | `Component` | Main system settings page with General/Branding/Mail tabs |

### Livewire Forms

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Livewire/Forms/BrandingForm.php` | `BrandingForm` | `Form` | Brand color, logo, favicon form state |
| `Settings/Livewire/Forms/GeneralSettingsForm.php` | `GeneralSettingsForm` | `Form` | App name, locale, academic year form state |
| `Settings/Livewire/Forms/MailSettingsForm.php` | `MailSettingsForm` | `Form` | SMTP configuration form state |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Models/Setting.php` | `Setting` | `BaseModel` | Key-value store with type enforcement, 7 validation scopes, media collections for logo/favicon |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Policies/SettingPolicy.php` | `SettingPolicy` | `BasePolicy` | viewAny/view: admin+; create/update/delete: super_admin only |

## Rules

| File | Class | Implements | Description |
|---|---|---|---|
| `Settings/Rules/ValidSettingKey.php` | `ValidSettingKey` | `ValidationRule` | Validates setting key format: `^[a-z][a-z0-9_.]*$` |

## Support

| File | Class | Description |
|---|---|---|
| `Settings/Support/AppInfo.php` | `AppInfo` | Reads composer.json metadata (name, version, author, license) with 24h cache |
| `Settings/Support/AppMetadata.php` | `AppMetadata` | Brand resolver combining Settings + AppInfo + Theme with fallback chains |
| `Settings/Support/Color.php` | `Color` | Pure color utility — hex conversion, luminance, contrast, lighten/darken, shade computation |
| `Settings/Support/Settings.php` | `Settings` | Central settings engine — 5-tier resolution (override → AppInfo → DB → config → default), cache invalidation, batch operations |

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
