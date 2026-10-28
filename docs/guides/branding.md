# Branding — Visual Identity & Brand Assets

## Description

Brand assets, color palette, typography, logo usage, and visual identity guidelines for Internara.


## Prerequisites

See [Installation](installation.md#prerequisites) for full server requirements and verification commands.

## 1. Dynamic Theming

Branding is fully dynamic — colors, logos, and site metadata are configurable at runtime through the
admin panel. No code changes or build steps required.

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

| Color         | Usage                                  | Dark Mode        |
| ------------- | -------------------------------------- | ---------------- |
| **Primary**   | Main actions, links, active navigation | Lightened by 40% |
| **Secondary** | Supporting elements                    | Lightened        |
| **Accent**    | Highlights, call-to-action             | Lightened        |
| **Base**      | Page background                        | Darkened         |

The system automatically computes:

- Contrast text colors (readability on each brand color background)
- Base surface hierarchy — `base-200` is the page background, `base-100` the elevated surfaces
  (cards, modals, inputs, sidebar), `base-300` borders and dividers. The mapping is normative in
  [design-system.md §3.6](ui-ux/design-system.md#36-color-computation-appmodulescoresupportcolorphp);
  do not restate a different order here.
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
properties for both light and dark themes, injected as an inline `<style>` block on every page load.
A color change in the admin panel is reflected on the next page refresh — no CSS rebuild, no
deployment, no cache clear.

Only color values are dynamic. Component styles, layout grid, spacing, and typography are compiled
at build time. The inline style block is approximately 50 CSS custom properties — negligible impact
on page size.

---

## 4. Logo Management

Brand logo and favicon are uploaded through the admin settings panel. Files stored in
`public/brand/`. The URL is saved as a setting value. The `brand('logo')` helper returns this URL,
falling back to a default file if none uploaded.

Light/dark mode display uses an `invert` CSS filter — the image renders as a solid white silhouette
on dark backgrounds.

A separate **school logo** is managed through the media library and displayed on the school profile
page.

---

## 5. Font Strategy

| Context              | Typeface        | Weights       | Strategy                                |
| -------------------- | --------------- | ------------- | --------------------------------------- |
| UI                   | Instrument Sans | 400, 500, 600, 700 | Self-hosted WOFF2, `font-display: swap` |
| Headings             | Instrument Sans | 700 (Bold)    | Heaviest cut shipped; tight letter-spacing |
| Email                | Arial           | —             | Maximum email client compatibility      |
| PDF - recovery codes | Courier New     | —             | Monospace for readability               |
| PDF - body           | System fonts    | —             | Broad compatibility                     |

Self-hosting eliminates external requests and ensures reliable loading. The `font-display: swap`
strategy prevents invisible text (FOIT) — browser renders with fallback first, swaps when Instrument
Sans finishes loading.

This table records the **brand** decision (which family per medium). The weight range and the per-level
sizes are normative in
[Design System §5](ui-ux/design-system.md#5-typography--visual-hierarchy).

---

## 6. Key Locations

| Component                        | Path                                      |
| -------------------------------- | ----------------------------------------- |
| `brand()` / `app_info()` helpers | `app/Modules/Setting/Support/helpers.php`        |
| Brand resolver                   | `app/Modules/Setting/Support/Brand.php`          |
| Theme (color computation)        | `app/Modules/Setting/Domain/Theme/Support/Theme.php`    |
| Color utility                    | `app/Modules/Core/Support/Color.php`              |
| Admin branding form              | `app/Modules/Setting/Livewire/SystemSetting.php` |
| Color presets config             | `config/settings.php`                     |
| Font files                       | `resources/fonts/`                        |
| CSS entry point                  | `resources/css/app.css`                   |
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| A newly uploaded logo does not appear | `logo` and `favicon` are theme cache keys; a write that bypasses the settings service leaves `theme_css_variables` holding the old URL for up to an hour. | Save through the admin branding form so `Settings::forget()` fires, or run `php artisan cache:clear`. See [design-system.md §3.9](ui-ux/design-system.md#39-cache-invalidation). |
| Brand colour reverts by itself | The colour was never persisted, so the cached variables are rebuilt from the previous value once the hour expires. | Confirm the value lands in settings, then re-save; `Theme::all()` always reads settings, never the cache, as its source. |
| Headings look heavier or blurrier than designed | A weight of 800/900 was requested. Only `instrument-sans-400/500/600/700.woff2` ship, so the browser synthesises the face. | Use `font-bold` (700) — the heaviest real cut. See §5. |
| Font falls back to the system stack | `@font-face` `src` no longer resolves after the font files moved. | Keep the `@font-face` block and files together under `resources/css/app.css` and `resources/fonts/`. |


## Verification

Confirm the guide's procedures succeeded by:

- Re-running any commands in [Description](#description) and comparing expected vs. actual output
- Verifying the documented post-conditions hold (file presence, user state, log entries, dashboard status)
- Cross-checking against the [Troubleshooting](#troubleshooting) section if any step fails

For system-level verification, run `php artisan system:health` and confirm all checks pass.
