# Branding, Theme & Locale — Identity, Appearance & Language Switching

> **Spec ID:** 52O1I

## Description

Specification of Internara's branding, theme, and locale initiatives: brand identity management
with color presets and asset uploads, dark/light theme switching, and EN/ID locale switching.
Settings infrastructure and the admin page are a separate initiative — see
[settings-infrastructure.md](YB22J-settings-infrastructure.md).

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
| NG1  | Settings infrastructure (see [settings-infrastructure.md](YB22J-settings-infrastructure.md)) |
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

### UC-2 — User Switches Theme

**Actor:** Any authenticated user
**Preconditions:** User is on any page rendering `core::ui.theme-switch` (`<x-ts-theme-switch>`)
**Flow:**
1. User clicks the TallstackUI theme toggle (light/dark/system)
2. `<x-ts-theme-switch>` persists the mode to localStorage key `dark-theme`
3. TallstackUI fires its `theme` CustomEvent; `applyTheme()` in `resources/js/app.js` applies BOTH `data-theme` (semantic palette variables) and the `.dark` class on `<html>`
4. CSS variables from `Theme::cssVariables()` provide brand palettes
**Postconditions:** Theme applied immediately without reload; preference persists across sessions; no DB write

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
| FR-B11 | Custom CSS stored in `brand.custom_css` setting key, rendered after theme stylesheet in a dedicated `<style>` block |
| FR-B12 | Custom CSS is a free-text `STRING` setting; only `super_admin` may write it (SettingPolicy) |
| FR-B13 | Custom CSS must be escaped via `{!! $css !!}` only after the value passes a CSS safety scan (no `<script>`, `url(` to external hosts, `@import`, `expression()`) |

### Theme

| ID     | Requirement                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| FR-T1  | Theme preference mirrored in the `theme` cookie (values: `light`, `dark`, `system`) for SSR (`base.blade.php` reads it to set `data-theme` + `.dark` pre-hydration); the authoritative client store is localStorage `dark-theme` via `<x-ts-theme-switch>`, not DB |
| FR-T2  | Theme switching must render via TallstackUI `<x-ts-theme-switch>` (wrapped by `core::ui.theme-switch`); persists mode to localStorage `dark-theme` and listens for the TallstackUI `theme` CustomEvent — no custom Livewire ThemeSwitcher, no DaisyUI/Alpine fallback (coexistence removed in 0.15.0) |
| FR-T3  | `applyTheme()` in `resources/js/app.js` must listen for the TallstackUI `theme` CustomEvent and apply BOTH the `data-theme` attribute and the `.dark` class on `<html>` (Tailwind/TallstackUI `dark:` variant) |
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
| FR-L5  | `LangSwitcher` Livewire component must render EN/ID dropdown using TallstackUI `x-ts-dropdown`/`select.styled` (DaisyUI fallback removed with coexistence in 0.15.0); must dispatch `language-changed` event |
| FR-L6  | `Locale::metadata()` must return `['name' => '...', 'native' => '...']` for display  |
| FR-L7  | `Locale::current()` must resolve via chain: cookie → stored `default_locale` setting → config `app.locale` → `DEFAULT_LOCALE` constant; first valid (supported) value wins |

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
// app/Modules/Settings/Branding/Data/BrandData.php
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
// app/Modules/Settings/Support/Brand.php
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
// app/Modules/Settings/Theme/Support/Theme.php
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
// app/Modules/Settings/Locale/Support/Locale.php
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

`current()` resolution chain (FR-L7): `Cookie::get('locale')` → `setting('default_locale')` →
`config('app.locale')` → `self::DEFAULT_LOCALE`. The `default_locale` setting (default `id`,
declared in settings-infrastructure.md) is the admin-configured default; `DEFAULT_LOCALE = 'en'`
is only the code-level last resort. `SetLocaleMiddleware` and `Locale::current()` share this
chain so the stored setting drives the default on fresh installs before any cookie exists.

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

### DD-4 — Custom CSS as a Sandboxed STRING Setting

**Decision:** Custom CSS is stored as plain text in `brand.custom_css` (STRING setting) and
rendered in a dedicated `<style>` block after the theme stylesheet.
**Rationale:** A single school-admin-facing textarea satisfies the "custom CSS" requirement
without a file-upload pipeline or a build-time integration. Storing it as a setting means the
`SettingObserver` invalidates caches on change, and `SettingPolicy` restricts writes to
`super_admin`.
**Trade-off:** Free-text CSS is powerful and risky. Mitigated by FR-B13 — a CSS safety scan
rejects `<script>`, external `url()`, `@import`, and `expression()`, so the value cannot escalate
to script execution. Acceptable because the writer is already `super_admin` (highest trust).

### DD-5 — Locale Resolved From Stored Setting, Overridden by Cookie

**Decision:** `Locale::current()` resolves cookie → stored `default_locale` setting → config →
constant (FR-L7).
**Rationale:** The project requirement ("Locale Management … resolved from stored setting",
internara-project §6.1 Settings) demands an admin-configurable default. The per-browser cookie
preserves the "preference, not account setting" property (DD-1) while the stored `default_locale`
drives fresh browsers and unauthenticated visitors. `DEFAULT_LOCALE = 'en'` remains the last
resort only.
**Trade-off:** Two sources of truth (cookie vs setting) for one value. Mitigated by a strict
priority chain and a single `SUPPORTED_LOCALES` whitelist — invalid values at any layer fall
through to the next.

### DD-6 — TallstackUI Theme/Locale Switchers (migration complete)

**Decision:** `ThemeSwitcher` Livewire component deleted — theme switching renders via `<x-ts-theme-switch>` (wrapped by `core::ui.theme-switch`), persisting to localStorage and syncing `data-theme` + `.dark` via `applyTheme()` in `resources/js/app.js`. LangSwitcher uses TallstackUI dropdown. The DaisyUI/Alpine dual stack was removed with the 0.15.0 package removal.
**Rationale:** TallstackUI provides TALL-native `ThemeSwitch` with built-in WCAG focus/ARIA, removing custom Alpine/JS while preserving no-break behavior for 18 modules.
**Trade-off:** Resolved — coexistence ended; a single theming stack (`data-theme` + `.dark`, self-hosted palette) remains.

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
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | `SettingsStore` interface, `brand.*` and `theme.*` settings keys, `SettingObserver` cache invalidation |

### Build Guide
After implementing this spec, the system has customizable branding (school name, logo, colors), dark mode theming, and bilingual locale (English/Indonesian). CSS variables are generated from settings and applied via middleware. The next step is to build authentication, which uses these locale preferences for localized error messages.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [authentication.md](YB7RG-authentication.md) | Locale preference from this spec used for login error messages; `SetLocaleMiddleware` resolves locale |

---

## Quick References

- `app/Modules/Settings/Support/Brand.php` — Dual-path brand resolution (DB + AppInfo)
- `app/Modules/Settings/Branding/Data/BrandData.php` — Brand identity DTO
- `app/Modules/Settings/Branding/Actions/UploadBrandAssetAction.php` — Spatie Media Library upload
- `app/Modules/Settings/Branding/Actions/RemoveBrandAssetAction.php` — Asset removal
- `app/Modules/Settings/Branding/Livewire/Forms/BrandingForm.php` — Color/logo/favicon form
- `app/Modules/Settings/Theme/Support/Theme.php` — Color resolution, CSS variables, presets
- `app/Modules/Settings/Locale/Support/Locale.php` — EN/ID locale management
- `app/Modules/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` — Per-request locale from cookie
- `resources/views/ui/components/theme-switch.blade.php` — `<x-ts-theme-switch>` wrapper (light/dark/system, localStorage-based)
- `app/Modules/Settings/Livewire/LangSwitcher.php` — EN/ID dropdown (cookie-based)
- `docs/refs/modules/settings.md` — Module conceptual documentation
- **Related specs:** [settings-infrastructure.md](YB22J-settings-infrastructure.md) — Settings store, type system & cache
