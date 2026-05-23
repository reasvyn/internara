# Settings — API Reference

Total: 14 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` | Sets multiple settings at once |
| `Settings/Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` | Sets a single setting with validation |
| `Settings/Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` | Sends a test email to verify mail settings |
| `Settings/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` | Uploads brand assets (logo, favicon) |

## Casts

| File | Class | Implements | Description |
|---|---|---|---|
| `Settings/Casts/SettingValueCast.php` | `SettingValueCast` | `CastsAttributes` | Custom Eloquent cast for encrypted setting values |

## Middleware

| File | Class | Description |
|---|---|---|
| `Settings/Http/Middleware/SetLocaleMiddleware.php` | `SetLocaleMiddleware` | Middleware that sets app locale from session/database |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Livewire/AppSignature.php` | `AppSignature` | `Component` | Displays application signature/metadata |
| `Settings/Livewire/SystemSetting.php` | `SystemSetting` | `Component` | Main system settings page (general, brand, mail) |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Settings/Models/Setting.php` | `Setting` | `BaseModel` | Eloquent model for key-value settings |

## Rules

| File | Class | Implements | Description |
|---|---|---|---|
| `Settings/Rules/ValidSettingKey.php` | `ValidSettingKey` | `ValidationRule` | Validation rule for setting keys |

## Support

| File | Class | Description |
|---|---|---|
| `Settings/Support/AppInfo.php` | `AppInfo` | Reads application metadata from files/environment |
| `Settings/Support/AppMetadata.php` | `AppMetadata` | Provides app metadata for frontend |
| `Settings/Support/Color.php` | `Color` | Color utility class |
| `Settings/Support/Settings.php` | `Settings` | Settings service with multi-tier resolution (runtime > AppInfo > DB > config > default) |
