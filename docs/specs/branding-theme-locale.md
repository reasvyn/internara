# Branding, Theme & Locale — Identity, Appearance & Language Switching

> **Last updated:** 2026-07-22 **Changes:** feat — split from system-settings.md; branding,
> theme & locale

## Description

Specification of Internara's branding, theme, and locale initiatives: brand identity management
with color presets and asset uploads, dark/light theme switching, and EN/ID locale switching.
Settings infrastructure and the admin page are a separate initiative — see
[settings-infrastructure.md](settings-infrastructure.md).

---

## 1. Problem Statements

### PS-1 — Branding Customization Without Redeployment

Schools need to customize their identity — site name, logo, favicon, color palette — without
touching code or environment files. The system must provide a live admin UI that persists brand
assets to storage and color choices to the database, with changes reflecting immediately.

### PS-2 — Theme Switching Without Database Overhead

Dark/light mode preference is per-browser, not per-user. Storing it in the database would add
write amplification for a preference that changes frequently. The system must use cookie-based
storage while generating dynamic CSS variables from the active color palette.

### PS-3 — Locale Management Across the Interface

Indonesian vocational schools need EN/ID switching. Locale preference is per-browser and must
apply on every request via middleware without database queries.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Live logo/favicon upload without requiring a full form save       |
| G2  | 6 color presets (sky, emerald, violet, rose, ocean, slate) with one-click apply |
| G3  | Store theme and locale preferences in cookies, not database       |
| G4  | EN/ID locale switching with immediate UI update                   |
| G5  | CSS variables generated from brand colors with dark mode support  |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Settings infrastructure (see [settings-infrastructure.md](settings-infrastructure.md)) |
| NG2  | Per-user theme or locale preferences                              |
| NG3  | Real-time settings sync across browser tabs                       |
| NG4  | Multi-language support beyond English and Indonesian              |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Uploads Logo or Favicon

**Actor:** Admin
**Preconditions:** User is on the System Settings page
**Flow:**
1. User selects a file in the logo upload field
2. Livewire `updatedBrandingFormBrandLogo` hook fires immediately
3. Validates: `nullable|image|max:1024` (KB) for logo, `nullable|image|max:512` for favicon
4. `UploadBrandAssetAction::execute()` stores file via Spatie Media Library
5. `SetSettingAction::execute()` persists the URL
6. Component updates preview
**Postconditions:** Asset is live immediately; logo URL persisted

### UC-2 — Admin Switches Theme

**Actor:** Any authenticated user
**Preconditions:** User is on any page with the `ThemeSwitcher` component
**Flow:**
1. User clicks light/dark/system toggle
2. `setTheme('dark')` queues a forever cookie: `theme=dark`
3. Dispatches `theme-changed` Livewire event
4. Alpine.js updates `data-theme` attribute on `<html>`
5. CSS variables from `Theme::cssVariables()` provide palettes
**Postconditions:** Theme applied immediately; cookie persists; no DB write

### UC-3 — Admin Changes Locale

**Actor:** Any authenticated user
**Preconditions:** User is on any page with the `LangSwitcher` component
**Flow:**
1. User selects "Bahasa Indonesia"
2. `setLocale('id')` calls `Locale::set('id')`
3. `Locale::set()` queues forever cookie and sets `App::setLocale('id')`
4. On next request, `SetLocaleMiddleware` reads cookie
**Postconditions:** UI renders in Indonesian on next page load; no DB write

---

## 4. Functional Requirements

### Branding

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-B1  | `Brand::resolve()` must return a `BrandData` DTO with name, title, logo, favicon, colors, version, author info |
| FR-B2  | `Brand` resolution uses dual-path: DB settings for branding, `AppInfo` for static metadata |
| FR-B3  | `Brand::colors()` must cache for 24h under `brand.colors` key                       |
| FR-B4  | 6 color presets defined in config: sky, emerald (default), violet, rose, ocean, slate |
| FR-B5  | `BrandingForm::detectPreset()` must compare current colors against presets and return matching key or null |
| FR-B6  | `BrandingForm::applyPreset()` must set all 4 color fields from selected preset       |
| FR-B7  | Logo upload: `image|max:1024` (KB), MIME: PNG, JPEG, WebP                           |
| FR-B8  | Favicon upload: `image|max:512` (KB), MIME: PNG, JPEG, WebP, ICO                    |
| FR-B9  | `UploadBrandAssetAction` must store via Spatie Media Library under `brand_logo` or `brand_favicon` collections |
| FR-B10 | `RemoveBrandAssetAction` must delete from media collection and clear setting key     |

### Theme

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-T1  | Theme preference stored in `theme` cookie (values: `light`, `dark`, `system`), not DB |
| FR-T2  | `ThemeSwitcher` Livewire component must dispatch `theme-changed` event               |
| FR-T3  | Alpine.js must listen for `theme-changed` and update `data-theme` attribute          |
| FR-T4  | `Theme::cssVariables()` must generate CSS variables for light/dark palettes, cached 1h |
| FR-T5  | CSS variables: `--color-primary`, `--color-secondary`, `--color-accent`, `--color-base-{100,200,300,content}`, `--brand-{primary,secondary,accent}` |
| FR-T6  | Dark mode must apply `Color::lighten()` (40%) and `Color::computeDarkShades()` for base tones |
| FR-T7  | `Color` helper: `hexToRgb()`, `relativeLuminance()`, `contrastColor()`, `lighten()`, `darken()`, `computeBaseShades()`, `computeDarkShades()` |

### Locale

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-L1  | Supported locales: EN and ID, defined in `Locale::SUPPORTED_LOCALES` constant       |
| FR-L2  | Locale preference stored in `locale` cookie (forever TTL), not DB                    |
| FR-L3  | `SetLocaleMiddleware` must read `locale` cookie on every request and call `App::setLocale()` |
| FR-L4  | `Locale::set()` must validate against `SUPPORTED_LOCALES`, queue cookie, set locale  |
| FR-L5  | `LangSwitcher` Livewire component must render EN/ID dropdown and dispatch `language-changed` event |
| FR-L6  | `Locale::metadata()` must return `['name' => '...', 'native' => '...']` for display  |

---

## 5. Non-Functional Requirements

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| NFR-S4 | Brand asset uploads must validate MIME type and file size server-side                |
| NFR-P3 | Theme CSS variable generation must complete in < 50ms and be cached for 1h          |
| NFR-U1 | Logo/favicon upload must show live preview immediately without page reload           |
| NFR-U2 | Color preset selection must show visual preview of all 4 colors before applying      |
| NFR-A2 | Logo/favicon upload must show alt text preview for screen readers                   |
| NFR-A3 | Color preset picker must indicate selection via non-color means (check icon, border) |
| NFR-A4 | Theme and locale switches must announce the change to screen readers via `aria-live` |
| NFR-A5 | All form inputs must have associated labels                                          |
| NFR-L1 | All UI labels must use `__()` translation helper                                     |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/`                       |
| NFR-L3 | Locale switcher must update `app()->setLocale()` and persist preference in cookie    |

---

## 6. API / Data Contracts

### BrandData

```php
// app/Settings/Branding/Data/BrandData.php
final readonly class BrandData extends BaseData
{
    public function __construct(
        public string $name,
        public string $title,
        public string $logo,
        public string $favicon,
        public array $colors,
        public string $version,
        public string $authorName,
        public string $authorEmail,
        public string $description,
        public string $license,
        public string $gitUrl,
    ) {}
}
```

### Brand Class

```php
// app/Settings/Support/Brand.php
final class Brand
{
    public static function name(): string;
    public static function title(): string;
    public static function logo(): string;
    public static function favicon(): string;
    public static function colors(): array;      // cached 24h
    public static function resolve(): BrandData;
    public static function get(string $key, mixed $default = null): mixed;
    public static function clearCache(): void;
}
```

### Theme Class

```php
// app/Settings/Theme/Support/Theme.php
final class Theme
{
    public static function defaults(): array;
    public static function presets(): array;
    public static function all(): array;
    public static function get(string $key): string;
    public static function base(): string;
    public static function cssVariables(): array;  // cached 1h
}
```

### Locale Class

```php
// app/Settings/Locale/Support/Locale.php
final class Locale
{
    public const DEFAULT_LOCALE = 'en';
    public const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
    ];

    public static function set(string $locale): bool;
    public static function current(): string;
    public static function all(): array;
    public static function keys(): array;
    public static function isSupported(string $locale): bool;
    public static function metadata(string $locale): ?array;
}
```

---

## 7. Design Decisions

### DD-1 — Cookie-Based Preferences (Not Database)

**Decision:** Theme and locale preferences stored in cookies.
**Rationale:** Per-browser, not per-user. Database storage adds write amplification for zero
cross-device benefit. Cookies read by middleware with zero DB overhead.
**Trade-off:** Preferences lost if cookies cleared. Acceptable — single-click re-select.

### DD-2 — Dual-Path Brand Resolution

**Decision:** `Brand` resolves branding from DB settings; static metadata from `AppInfo`.
**Rationale:** `AppInfo` is canonical for static metadata. `Brand` queries DB directly to
avoid key collision with `AppInfo::name()`.
**Trade-off:** Two resolution mechanisms. Acceptable — clear separation of concerns.

### DD-3 — Live Asset Upload (Not Deferred to Save)

**Decision:** Logo/favicon uploaded immediately via Livewire `updated*` hooks.
**Rationale:** File uploads can fail. Immediate upload provides instant feedback and allows
retry before investing more time in the form.
**Trade-off:** Orphaned files if user cancels. Acceptable — small files, local storage.

---

## 8. Success Metrics

### Usability

| Metric                         | Target |
| ------------------------------ | ------ |
| Logo upload → preview visible  | < 3s   |
| Color preset apply → UI update | < 500ms |
| Theme toggle → apply           | < 100ms (Alpine.js, no server roundtrip) |
| Locale toggle → apply          | < 1s (next page load) |

### Reliability

| Metric                                   | Target |
| ---------------------------------------- | ------ |
| Brand resolution failure → fallback      | 100%   |
| Cookie fallback for invalid theme/locale | 100%   |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [settings-infrastructure.md](settings-infrastructure.md) | `SettingsStore` interface, `brand.*` and `theme.*` settings keys, `SettingObserver` cache invalidation |

### Build Guide
After implementing this spec, the system has customizable branding (school name, logo, colors), dark mode theming, and bilingual locale (English/Indonesian). CSS variables are generated from settings and applied via middleware. The next step is to build authentication, which uses these locale preferences for localized error messages.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [authentication.md](authentication.md) | Locale preference from this spec used for login error messages; `SetLocaleMiddleware` resolves locale |

---

## Quick References

- `app/Settings/Support/Brand.php` — Dual-path brand resolution (DB + AppInfo)
- `app/Settings/Branding/Data/BrandData.php` — Brand identity DTO
- `app/Settings/Branding/Actions/UploadBrandAssetAction.php` — Spatie Media Library upload
- `app/Settings/Branding/Actions/RemoveBrandAssetAction.php` — Asset removal
- `app/Settings/Branding/Livewire/Forms/BrandingForm.php` — Color/logo/favicon form
- `app/Settings/Theme/Support/Theme.php` — Color resolution, CSS variables, presets
- `app/Settings/Locale/Support/Locale.php` — EN/ID locale management
- `app/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` — Per-request locale from cookie
- `app/Settings/Livewire/ThemeSwitcher.php` — Light/dark/system toggle (cookie-based)
- `app/Settings/Livewire/LangSwitcher.php` — EN/ID dropdown (cookie-based)
- `docs/modules/settings.md` — Module conceptual documentation
- **Related specs:** [settings-infrastructure.md](settings-infrastructure.md) — Settings store, type system & cache
