# Branding & Theming

> **Last updated:** 2026-06-08

---

## 1. Dynamic Theming

Branding is fully dynamic — colors, logos, and site metadata are configurable at runtime through
the admin panel. No code changes or build steps required.

### Resolution Chain

Values resolve through a fallback chain at runtime:

```
Runtime override → Database setting (cached forever) → Config file default → Hardcoded default
```

### The `brand()` Helper

The `brand()` function is the primary API for accessing brand values:

```php
brand('name')        // Institution name
brand('logo')        // Logo URL (supports light/dark via CSS invert filter)
brand('favicon')     // Favicon URL
brand('title')       // Site title
brand('primary')     // Primary brand color (hex)
brand('secondary')   // Secondary brand color (hex)
brand('accent')      // Accent brand color (hex)
```

The `app_info()` helper returns metadata from `composer.json`: version, author, license.

---

## 2. Color System

Four brand colors are configurable:

| Color | Usage | Dark Mode |
|-------|-------|-----------|
| **Primary** | Main actions, links, active navigation | Lightened by 40% |
| **Secondary** | Supporting elements | Lightened |
| **Accent** | Highlights, call-to-action | Lightened |
| **Base** | Page background | Darkened |

The system automatically computes:

- Contrast text colors (readability on each brand color background)
- Base surface hierarchy (base-100 for cards, base-200 for sidebar, base-300 for page background)
- Dark mode equivalents

### Presets

Six preset palettes are available:

1. Emerald (default)
2. Sky
3. Violet
4. Rose
5. Ocean
6. Slate

Selecting a preset fills the color pickers. Manual color changes clear the preset selection. The
system auto-detects when manual colors match a preset and re-selects it.

---

## 3. Dynamic Configuration vs Compile-Time CSS

Brand colors are **not hardcoded in CSS**. The `Theme::cssVariables()` method generates CSS custom
properties for both light and dark themes, injected as an inline `<style>` block on every page
load. A color change in the admin panel is reflected on the next page refresh — no CSS rebuild,
no deployment, no cache clear.

Only color values are dynamic. Component styles, layout grid, spacing, and typography are compiled
at build time. The inline style block is approximately 50 CSS custom properties — negligible
impact on page size.

---

## 4. Logo Management

Brand logo and favicon are uploaded through the admin settings panel. Files stored in
`public/brand/`. The URL is saved as a setting value. The `brand('logo')` helper returns this
URL, falling back to a default file if none uploaded.

Light/dark mode display uses an `invert` CSS filter — the image renders as a solid white
silhouette on dark backgrounds.

A separate **school logo** is managed through the media library and displayed on the school
profile page.

---

## 5. Font Strategy

| Context | Typeface | Weights | Strategy |
|---------|----------|---------|----------|
| UI | Instrument Sans | 400, 500, 600 | Self-hosted WOFF2, `font-display: swap` |
| Headings | Instrument Sans | 900 (Heavy) | Tight letter-spacing |
| Email | Arial | — | Maximum email client compatibility |
| PDF - recovery codes | Courier New | — | Monospace for readability |
| PDF - body | System fonts | — | Broad compatibility |

Self-hosting eliminates external requests and ensures reliable loading. The `font-display: swap`
strategy prevents invisible text (FOIT) — browser renders with fallback first, swaps when
Instrument Sans finishes loading.

---

## 6. Key Locations

| Component | Path |
|-----------|------|
| `brand()` / `app_info()` helpers | `app/Support/helpers.php` |
| AppMetadata resolver | `app/Settings/Support/AppMetadata.php` |
| Theme (color computation) | `app/Settings/Support/Theme.php` |
| Color utility | `app/Settings/Support/Color.php` |
| Admin branding form | `app/Settings/Livewire/SystemSetting.php` |
| Color presets config | `config/settings.php` |
| Font files | `resources/fonts/` |
| CSS entry point | `resources/css/app.css` |
