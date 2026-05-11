# Dynamic Branding

Internara supports fully dynamic branding that can be customized through the admin panel without touching code or configuration files.

## Overview

Branding values flow through a five-tier resolution chain:

```
Runtime Overrides → AppInfo (composer.json) → Settings DB (cached) → Laravel Config → Default
```

The `brand()` helper function is the primary API for reading brand values in any context.

---

## Quick Reference

### Global Helper Functions

```php
// Branding facade — the main API
brand('name');           // Institution display name
brand('logo');           // Logo image URL
brand('favicon');        // Favicon URL
brand('site_title');     // Browser tab title
brand('colors');         // [primary, secondary, accent] hex colors
brand('description');    // App description from composer.json
brand('version');        // Semantic version
brand('author_name');    // Author name
brand('author_email');   // Author email
brand('license');        // License name

// Raw settings access
setting('brand_name');
setting('site_title');
setting('primary_color');

// Composer.json metadata (SSoT)
app_info('name');
app_info('version');
app_info('author.name');
app_info('author.email');
app_info('license');
app_info('description');
```

### In Blade Templates

```blade
<!-- Browser tab title -->
<title>{{ brand('site_title') }}</title>

<!-- Logo -->
<img src="{{ brand('logo') }}" alt="{{ brand('name') }}">

<!-- Colors injected as CSS custom properties -->
@php $colors = brand('colors'); @endphp
<style>
    :root {
        --brand-primary: {{ $colors['primary'] }};
        --brand-secondary: {{ $colors['secondary'] }};
        --brand-accent: {{ $colors['accent'] }};
    }
</style>

<!-- Reusable brand component -->
<x-ui::brand
    :size="'md'"
    :with-name="true"
    :with-tagline="false"
/>
```

---

## Brand Values

### What Can Be Branded

| Key | Source | Description |
|---|---|---|---|
| `name` | Settings → composer.json | Institution display name |
| `logo` | Settings → static fallback | Logo image URL |
| `favicon` | Settings → logo → fallback | Favicon URL |
| `site_title` | Settings → brand name | Browser tab title |
| `colors` | Settings → defaults | `primary`, `secondary`, `accent` hex |
| `description` | composer.json | App description |
| `version` | composer.json | App version |
| `author_name` | composer.json | Author name |
| `author_email` | composer.json | Author email |
| `license` | composer.json | License |

### Resolution Logic

Each `brand('key')` call follows a fallback chain:

**`brand('name')`** — if not installed → composer.json name → fallback to `'Laravel'`

**`brand('logo')`** — if not installed → fallback to `/brand/logo.png`
- If installed → setting `brand_logo` (URL string) → fallback to `/brand/logo.png`

**`brand('favicon')`** — if not installed → fallback to `/brand/favicon.ico`
- If installed → setting `site_favicon` → if empty, falls back to `brand_logo` → if empty, falls back to `/brand/favicon.ico`

**`brand('colors')`** — returns array `['primary' => '#hex', 'secondary' => '#hex', 'accent' => '#hex']`
- Each color reads from its respective setting key → falls back to defaults defined in the settings configuration

**`brand('description')`, `brand('version')`, `brand('author_name')`, `brand('author_email')`, `brand('license')`** — all delegate directly to `app_info()` (composer.json), no database settings.

---

## Storage

### Settings Table

Dynamic brand values are stored in the `settings` table:

| Column | Type | Description |
|---|---|---|
| `id` | UUID | Primary key |
| `key` | string (unique) | Setting identifier (lowercase snake_case) |
| `value` | text (nullable) | Stored value |
| `type` | string | Storage type: `string`, `integer`, `float`, `boolean`, `json`, `encrypted`, `null` |
| `group` | string (nullable, indexed) | Logical grouping |
| `description` | text (nullable) | Human-readable description |

### Setting Groups

| Group | Purpose | Example Keys |
|---|---|---|
| `general` | Institution branding | `brand_name`, `brand_logo`, `site_title`, `default_locale` |
| `system` | App metadata & mail | `app_name`, `app_version`, `mail_host`, `mail_password` |
| `operational` | Business rules | `active_academic_year`, `attendance_check_in_start`, `attendance_late_threshold` |

See the [System Lifecycle](system-lifecycle.md#settings-resolution-chain) for the full settings resolution chain and cache invalidation strategy.

### Key Constraints

Setting keys must:
- Start with a lowercase letter
- Contain only lowercase alphanumeric characters, underscores, and dots
- Not be empty

Enforced by a `saving` event on the `Setting` model.

### Value Types

The `SettingValueCast` handles automatic type coercion:

| DB Type | Getter | Setter (auto-detection) |
|---|---|---|
| `string` | Returned as-is | Detected for strings |
| `integer` | Cast to `(int)` | Detected for ints |
| `float` | Cast to `(float)` | Detected for floats |
| `boolean` | Cast to `(bool)` | Detected for bools |
| `json` | `json_decode()` to array | Detected for arrays/objects |
| `encrypted` | `Crypt::decryptString()` | Must be explicitly specified |
| `null` | Returns `null` | Detected for null values |

### Composer.json (SSoT)

The `composer.json` file serves as the single source of truth for static metadata. Fields and their mapped keys:

| composer.json Field | `app_info()` Key | Example Value |
|---|---|---|
| `display_name` | `name` | `Internara` |
| `version` | `version` | `0.1.0` |
| `description` | `description` | `The practical field work management...` |
| `license` | `license` | `MIT` |
| `authors[0].name` | `author.name` | `Reas Vyn` |
| `authors[0].email` | `author.email` | `reasvyn@gmail.com` |
| `authors[0].homepage` | `author.github` | `https://github.com/reasvyn` |

Metadata is parsed once and cached in-memory. The `Integrity::verify()` guard checks the author attribution on every access — if `authors[0].name` has been altered from the original, the application exits with a 403 error.

---

## Caching

### Behavior

- Brand values from the `settings` table are cached forever using `Cache::rememberForever()`
- Individual keys are cached as `settings.{key}`
- All settings are cached as `settings.all`
- Group queries are cached as `settings.group.{name}`
- Composer.json metadata is cached in-memory within the `AppInfo` class (per request)

### Invalidation

Cache is automatically invalidated when settings are updated:
- **Single setting update** — clears `settings.{key}`, its group cache, and `settings.all`
- **Group update** — clears all keys in the group, group cache, and `settings.all`
- **Full flush** — `php artisan cache:clear`

You can bypass cache for reading:
```php
setting('brand_name', skipCache: true);
```

---

## Setting Values

### Through the Admin Panel

Navigate to **Admin → Settings** (`/admin/settings`). The `SystemSetting` Livewire component provides a form for managing:

- **General** — brand name, site title, default locale
- **Colors** — primary, secondary, accent (hex validation with regex)
- **Assets** — brand logo (image upload, max 1MB, stored to `public/brand/`), favicon (max 512KB)
- **Operational** — active academic year
- **Mail** — SMTP host, port, encryption, username, password (stored encrypted), with a test email button

### Programmatically

```php
use App\Actions\Admin\SetSettingAction;

// Single setting
app(SetSettingAction::class)->execute(
    key: 'brand_name',
    value: 'My Institution',
    group: 'general',
);

// Batch update (preferred for multiple changes)
app(SetSettingAction::class)->executeBatch([
    'brand_name' => 'My Institution',
    'site_title' => 'My Institution - Portal',
    'primary_color' => '#06b6d4',
    'secondary_color' => '#8b5cf6',
]);

// With explicit type (for encrypted values)
app(SetSettingAction::class)->executeBatch([
    'mail_password' => [
        'value' => 's3cret',
        'type' => 'encrypted',
    ],
]);

// Direct model manipulation (bypasses cache invalidation helpers)
use App\Models\Setting;

Setting::updateOrCreate(
    ['key' => 'brand_name'],
    ['value' => 'My Institution', 'type' => 'string', 'group' => 'general'],
);
```

### Seeding Defaults

When the application is installed, `AppSettingSeeder` populates the settings table with sensible defaults drawn from composer.json metadata.

To reset settings to defaults, re-run the seeder:
```bash
php artisan db:seed --class=AppSettingSeeder
```

---

## School Profile

In addition to the system-wide brand settings, each **School** record holds the institution's detailed profile — name, code, address, principal, contact details, and a logo managed through the media library.

### School Logo vs Brand Logo

| Aspect | School Logo | Brand Logo |
|---|---|---|
| Source | Spatie Media Library collection `'logo'` | Settings `brand_logo` key (URL string) |
| Model | `School` model | `Setting` model |
| Managed via | School Editor (`admin/school`) | System Settings (`admin/settings`) |
| URL | `$school->logo_url` (appended attribute) | `brand('logo')` |
| Use | Institutional profile display | System-wide brand display |

### Managing School Profile

Navigate to **Admin → School** (`/admin/school`). The `SchoolEditor` Livewire component manages:
- Institution name, institutional code
- Address, email, phone, fax, website
- Principal name
- Logo (uploaded and managed via Spatie Media Library)

```php
use App\Actions\School\UpdateSchoolAction;

app(UpdateSchoolAction::class)->execute(
    school: $school,
    data: [
        'name' => 'My School',
        'address' => '123 Main St',
        'logo_file' => $uploadedFile, // optional
    ],
);
```

---

## Architecture

### Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Blade Views                               │
│  brand('name'), brand('logo'), brand('colors'), brand(...)  │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│              AppMetadata (branding facade)                   │
│  brandName(), brandLogo(), favicon(), colors(), siteTitle()  │
│  -> resolves by installation state, delegates to Settings    │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│         Settings (cached multi-tier resolver)                │
│  get(key) -> runtime overrides → AppInfo → DB → config → def │
│  rememberForever('settings.{key}')                           │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────┐ ┌──────────────┐
│        Setting Model (Eloquent)              │ │   AppInfo    │
│  #[Fillable], SettingValueCast,              │ │ composer.json│
│  scopes: group(), byKey(), etc.              │ │ metadata     │
└───────────────────────┬─────────────────────┘ └──────┬───────┘
                        │                             │
┌───────────────────────▼─────────────────────┐        │
│     settings table (database)                │        │
│  key | value | type | group | description    │        │
└─────────────────────────────────────────────┘        │
                                                       │
                                              ┌───────▼───────┐
                                              │ composer.json  │
                                              │ (SSoT)         │
                                              └───────────────┘
```

### Author Attribution Guard

`App\Support\Integrity::verify()` is called every time `AppInfo::all()` is accessed (which happens on every `brand()` and `app_info()` call). It checks that `composer.json`'s `authors[0].name` matches the original author. If the check fails, the application terminates with a 403 HTTP response (or CLI error message).

The check is bypassed during testing environments.

### Error Resilience

All `AppMetadata` methods are wrapped in try-catch with `withFallback()`. If the database query fails (e.g., before installation or during maintenance), it logs a warning and returns the fallback value — the application continues running.

---

## File Reference

| File | Role |
|---|---|
| `app/Support/helpers.php` | Global `brand()`, `setting()`, `app_info()` functions |
| `app/Support/AppMetadata.php` | Branding facade with all brand resolution logic |
| `app/Support/Settings.php` | Multi-tier cached settings resolver |
| `app/Support/AppInfo.php` | Composer.json metadata parser (in-memory cache) |
| `app/Support/Integrity.php` | Author attribution tamper guard |
| `app/Models/Setting.php` | Setting model with typed value cast and key validation |
| `app/Casts/SettingValueCast.php` | Value serialization/deserialization by type |
| `app/Actions/Admin/SetSettingAction.php` | Write action for single and batch setting updates |
| `app/Actions/School/UpdateSchoolAction.php` | Write action for school profile + media logo |
| `app/Livewire/Admin/SystemSetting.php` | Admin UI for all system/brand settings |
| `app/Livewire/School/SchoolEditor.php` | Admin UI for school profile editing |
| `app/Livewire/Core/AppSignature.php` | Footer component with author credits |
| `app/Livewire/Core/ThemeSwitcher.php` | Light/dark/system theme toggle |
| `database/seeders/AppSettingSeeder.php` | Default settings seed data |
| `resources/views/layouts/base.blade.php` | Base shell — injects brand colors as CSS variables |
| `resources/views/layouts/base/head.blade.php` | Head partial — title + favicon |
| `resources/views/layouts/base/footer.blade.php` | Footer — logo, name, description, author, version |
| `resources/views/components/ui/brand.blade.php` | Reusable brand logo + name component with size variants |
