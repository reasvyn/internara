# UI/UX Design — Principles & Guidelines

## Description

UI/UX design principles, component library usage (TallstackUI v4 + Tailwind CSS v4), layout patterns,
and accessibility guidelines.

## 1. Design System Philosophy

> 📖 Authoritative references: [UI Pattern](../arch/ui-pattern.md) — visual hierarchy, Tailwind v4 tokens, component design, performance; [UX Pattern](../arch/ux-pattern.md) — theming, accessibility, localization, user flow.

Internara's interface is built on three layers:

| Layer                  | Purpose                    | Provides                                                                 |
| ---------------------- | -------------------------- | ------------------------------------------------------------------------ |
| **Tailwind CSS v4**    | Utility foundation         | Spacing, typography, responsive grid, colors (`dark:` via `.dark` class) |
| **TallstackUI v4**     | Component library          | Buttons, cards, tables, forms, selects, modals, dialogs, badges, alerts, layout, dropdowns, toast |
| **Self-hosted palette**| Semantic color bridge      | `--color-base-100/200/300`, `--color-primary/secondary/accent`, `info/success/warning/error` — mirrors legacy DaisyUI tokens via `@theme` + `@layer components` shims in `resources/css/app.css` (no DaisyUI plugin) |

Livewire manages server-side state; Alpine.js handles client-side behavior. The visual language is
clean and professional: neutral monochrome base, single accent brand color, low-saturation
backgrounds, subtle borders, bold typography, minimal shadows.

---

## 2. Layout Structure

### Cross-Cutting Layouts

Located at `resources/views/ui/layouts/`:

| Layout  | File                    | Purpose                                                  |
| ------- | ----------------------- | -------------------------------------------------------- |
| Base    | `base.blade.php`        | Root HTML shell with theme, branding CSS, Alpine.js      |
| Head    | `base/head.blade.php`   | `<head>` element with meta tags and assets               |
| Footer  | `base/footer.blade.php` | Page footer with credits                                 |
| App     | `app.blade.php`         | Authenticated layout (drawer sidebar + header + content) |
| Guest   | `guest.blade.php`       | Public/guest layout (centered card)                      |
| Sidebar | `sidebar.blade.php`     | Drawer sidebar with role-filtered navigation             |
| Header  | `header.blade.php`      | Sticky top header with search and actions                |

### Module-Specific Layouts

Located at `resources/views/{module}/layouts/`:

| Layout | Namespace              | Used By                     |
| ------ | ---------------------- | --------------------------- |
| Auth   | `auth::layouts.auth`   | Login, password reset pages |
| Setup  | `setup::layouts.setup` | Multi-step setup wizard     |

### Convention

- Layout shared by multiple modules → `resources/views/ui/layouts/`
- Layout specific to one module → `resources/views/{module}/layouts/`

---

## 3. Theme System & Color Schema

> 📖 Authoritative references: [UX Pattern](../arch/ux-pattern.md) §1 — dual-signal theming, token architecture, dark-mode dual-signal contract; [Branding](../branding.md) — brand assets, resolution chain, presets.

Theming is **fully dynamic**: brand and surface colors are stored as database settings, resolved
through a runtime chain, computed into CSS custom properties, and injected as an inline `<style>`
block on every page — no CSS rebuild or redeploy. This section documents the complete color schema,
the computation algorithm, and the injection + caching pipeline.

### 3.1 The Two Signals — Light / Dark

Each page carries **two** attributes on `<html>` (set in `resources/views/ui/layouts/base.blade.php`):

| Signal | Source | Purpose |
| ------ | ------ | ------- |
| `data-theme` (`light`/`dark`/`system`) | `theme` cookie (`request()->cookie('theme', 'system')`) | Selects the injected CSS-property block (`html[data-theme='light']` / `html[data-theme='dark']`) |
| `.dark` class | present when the cookie equals `dark` | Drives Tailwind/TallstackUI `dark:` variant (`@custom-variant dark`) and static status-token overrides |

The `@custom-variant dark (&:where(.dark, .dark *))` in `resources/css/app.css` means the
`dark:` variant keys off the `.dark` class only, while the semantic brand/surface properties key off
`data-theme`. Both must stay in sync — `resources/js/app.js` `applyTheme()` sets them together and
mirrors to the `theme` cookie for SSR accuracy, preventing flash-of-unstyled-content (FOUC).

### 3.2 Resolution Chain

Theme values never hardcode a color. Each slot resolves at runtime:

```
DB setting (cached forever) → Theme::defaults() from config → hardcoded fallback
```

`app/Modules/Settings/Domain/Theme/Support/Theme.php` exposes the chain:

- `defaults()` → `config('settings.colors.defaults')`
- `get($key)` → `all()[key] ?? defaults()[key] ?? '#000000'`
- `base()` → `getSetting('base_color', defaults()['base'])`
- `all()` → primary/secondary/accent/accent from DB, `base` via `base()`, `content` via `Color::computeBaseShades(base())['content']`

### 3.3 Brand Color Schema (Configurable)

Four brand colors are configurable in the admin panel (`SystemSetting` → Branding form;
`app/Modules/Settings/Livewire/SystemSetting.php`), stored as **`*_color`** settings, validated as
`#RRGGBB` hex in `BrandingForm`, and written via `SaveSystemSettingsAction`.

**Default palette is green (Emerald) — `primary = #059669`:**

| Slot | Setting key | Default (`config/settings.php`) | Usage |
| ---- | ----------- | ------------------------------- | ----- |
| Primary | `primary_color` | `#059669` (Emerald green) | Main actions, links, active navigation |
| Secondary | `secondary_color` | `#6b7280` | Supporting elements |
| Accent | `accent_color` | `#f97316` | Highlights, call-to-action |
| Base | `base_color` | `#ffffff` | Page/surface background anchor |

`content_color` is declared in `config('settings.theme_cache_keys')` for invalidation but the resolved
content color is **computed**, not read from a setting — see §3.5.

### 3.4 Presets

Six presets live in `config/settings.php` `colors.presets` (checkbox swatches in the admin panel):

| Key | Label | Primary | Secondary | Accent | Base |
| --- | ----- | ------- | --------- | ------ | ---- |
| `emerald` | Emerald (default) | `#059669` | `#6b7280` | `#f97316` | `#ffffff` |
| `sky` | Sky | `#0ea5e9` | `#64748b` | `#f59e0b` | `#ffffff` |
| `violet` | Violet | `#7c3aed` | `#71717a` | `#ec4899` | `#ffffff` |
| `rose` | Rose | `#e11d48` | `#78716c` | `#f59e0b` | `#ffffff` |
| `ocean` | Ocean | `#0891b2` | `#64748b` | `#7c3aed` | `#ffffff` |
| `slate` | Slate | `#475569` | `#57534e` | `#d97706` | `#ffffff` |

Applying a preset fills all four color pickers (`BrandingForm::applyPreset()`). `detectPreset()`
compares the current four values against every preset and re-selects the matching swatch when the
user's manual colors happen to match one — otherwise `selected_preset` is `null` (manual mode).

### 3.5 Color Computation (`app/Modules/Core/Support/Color.php`)

`Theme::cssVariables()` computes these values using `Color`:

**Base surface hierarchy from `base_color`:**
- `computeBaseShades($hex)` (light theme) — if `relativeLuminance > 0.5` (light base): `base-100 = base`, `base-200 = darken 3%`, `base-300 = darken 6%`, `content = #1a1a1a`. Dark base inverts the logic: lighten by 10%/20%, `content = #f0f0f0`.
- `computeDarkShades($hex)` (dark theme) — returns a fixed **black** surface scale (`base-100` = `#262626` neutral-800, `base-200` = `#171717` neutral-900, `base-300` = `#0a0a0a` neutral-950, `content` = `#e5e5e5`) so the dark background reads as **black, not gray**. `base-200` is the main page background (`bg-base-200` across the app/guest/auth/home layouts), `base-100` are elevated surfaces (cards/modals), `base-300` borders/dividers.

**Brand contrast + dark mode for primary/secondary/accent:**
- `--color-{key}-content` / `--{c}` = `contrastColor($hex)` — dark `#1a1a1a` when luminance > 0.5, else light `#f0f0f0`.
- Dark theme lightens each brand color by **40%** (`Color::lighten($hex, 40)`) for visibility on dark backgrounds; dark brand content is fixed `#ffffff`.

### 3.6 Injected CSS Variables

`Theme::cssVariables()` builds a `light` and a `dark` map and caches it for **1 hour** under
`config('cache-keys.theme_css_variables')` (`theme.css_variables`). `base.blade.php` emits each map
into an inline `<style>` under its scope selector:

```blade
html[data-theme='light'], html:not([data-theme='dark']) { … light vars … }
html[data-theme='dark'] { … dark vars … }
```

Variables per brand key (both themes): the modern `--color-{key}`, the legacy DaisyUI `--{initial}`
(`--p`/`--s`/`--a`), the content tokens `--color-{key}-content` + `--{initial}c`, and a brand alias
`--brand-{key}`. Base variables: `--color-base-100/200/300`, `--color-base-content`.

#### Static tokens in `resources/css/app.css`

Non-branded status/neutral colors are **statically defined** in `@theme` and overridden for dark in
`[data-theme='dark']` (in `oklch`):

| Token | Light | Dark |
| ----- | ----- | ---- |
| `--color-neutral` | `oklch(50% 0 0)` | `oklch(30% 0 0)` |
| `--color-info` | `oklch(60% 0.55 250)` | `oklch(70% 0.38 250)` |
| `--color-success` | `oklch(55% 0.58 150)` | `oklch(65% 0.42 150)` |
| `--color-warning` | `oklch(70% 0.6 80)` | `oklch(75% 0.45 80)` |
| `--color-error` | `oklch(55% 0.5 25)` | `oklch(65% 0.38 25)` |

Each status token also ships a `-content` counterpart (e.g. `--color-success-content`) that flips
between white and near-black in dark mode. These are pre-validated for WCAG contrast — never override
them with arbitrary utilities (see §6.1).

### 3.7 DaisyUI Shim Bridge

`app.css` `@layer components` re-declares the legacy DaisyUI component classes (`.btn`,
`.badge`, `.table`, `.card`, `.alert`, `.input`, `.menu`, …) that still appear across committed views,
mapping them to the CSS variables above (`background: var(--color-primary)`, etc.). This keeps old
class-based markup styled while the codebase migrates to `x-ts-*` components. There is **no DaisyUI
npm package**.

### 3.8 Cache Invalidation

`Settings::forget()` (and `forgetGroup()`) invalidates the theme variable cache. `Settings::forget()`
checks `in_array($key, config('settings.theme_cache_keys'))` — the keys `primary_color`,
`secondary_color`, `accent_color`, `base_color`, `content_color`, `logo`, `favicon`, `name`,
`title` — and calls `Cache::forget(theme_css_variables)` when a matching key changes. So saving a new
brand color invalidates the 1-hour CSS-variable cache and the change appears on the next page render
without `php artisan cache:clear`.

### 3.9 Theme Switcher & Dark Mode

Dual-signal dark mode with a three-state switcher (light / dark / system):

- **Component:** `resources/views/ui/components/theme-switch.blade.php` wraps TallstackUI's
  `<x-ts-dropdown>` (Alpine scope drives the button icon/label); it is **not** a Livewire component.
- **Persistence:** the three dropdown items set `mode` and `$dispatch('theme', { mode })`; `app.js`
  `applyTheme()` stores the preference in `localStorage` `dark-theme` (`light`/`dark`/`system` plus
  legacy `true`/`false`) and sets `data-theme` + `.dark` + the `theme` cookie.
- **Legend:** the `theme` cookie → server sets `class="dark"` in `base.blade.php` already at first
  render, so the correct theme is applied before hydration.

---

## 4. Responsive Strategy

Mobile-first layout:

- Small screens: sidebar hidden, accessed via hamburger toggle
- Desktop (≥1024px): sidebar always visible
- Tables: responsive classes hide secondary columns on mobile
- Stat grids: single column → multi-column as viewport increases
- Container: `max-w-7xl` for normal pages, `max-w-5xl` for setup/guest pages

---

## 5. View Namespaces

Each module's view directory (`resources/views/{module}/`) is registered as a Blade namespace by
`AppServiceProvider::registerBladeNamespaces()`.

| Pattern             | Syntax                | Example                |
| ------------------- | --------------------- | ---------------------- |
| Anonymous component | `x-{module}::name`    | `x-setup::brand`       |
| View include        | `{module}::view.name` | `setup::layouts.setup` |

Excluded directories: `components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor`.

---

## 6. Accessibility (WCAG 2.1 AA)

> 📖 Authoritative references: [UX Pattern](../arch/ux-pattern.md) §2 — WCAG 2.2 AA (Perceivable/Operable/Understandable/Robust), focus, ARIA, `aria-live`; [UI Pattern](../arch/ui-pattern.md) §7 — forms/tables/modals/navigation a11y. This section is the UI-layer checklist.

All user-facing interfaces MUST meet WCAG 2.1 Level AA. This section defines UI-layer
requirements. See `docs/guides/arch/modular-pattern.md` §22 for architectural rules and
`docs/guides/arch/livewire-pattern.md` §13 for component-specific patterns.

### 6.1 Color & Contrast

- **Minimum contrast ratios:** 4.5:1 for normal text, 3:1 for large text (≥18pt or ≥14pt bold),
  3:1 for UI components and graphical objects.
- **Semantic palette colors** (`--color-success`, `--color-warning`, etc. in `resources/css/app.css`) are pre-validated for contrast. Never override with arbitrary Tailwind color utilities that fail contrast checks.
- **Color is never the sole indicator:** Status badges (success/warning/error), capacity gauges,
  and validation states must include text labels, icons, or patterns alongside color (see UX Pattern §2.1).

### 6.2 Keyboard Navigation

- **Tab order:** Must follow logical reading order (top-to-bottom, left-to-right for LTR). No
  positive `tabindex` values.
- **Focus indicators:** Every focusable element must have a visible focus ring. TallstackUI provides
  `focus:ring` by default — do not suppress with `outline-none` without a visible replacement.
- **Skip links:** Every page with navigation must provide a "Skip to main content" link as the
  first focusable element in the DOM.
- **Interactive elements:** All buttons, links, form fields, modals, and dropdowns must be
  reachable and operable via keyboard alone (Enter, Space, Arrow keys, Escape).

### 6.3 Modal & Dialog Focus

- **Focus trap:** Modals (`x-ts-modal`, `x-ts-dialog`) must trap focus within the modal when open.
- **Focus return:** On modal close, focus must return to the trigger element.
- **Escape key:** All modals and dropdowns must close on Escape key press (TallstackUI default).

### 6.4 Screen Reader Support

- **ARIA landmarks:** Layout must use semantic HTML5 elements: `<nav>` (sidebar), `<main>`
  (content), `<header>` (top bar), `<footer>`. The app layout provides these by default.
- **aria-live for dynamic content:** Flash messages, Livewire partial updates, and real-time
  validation feedback must be wrapped in `aria-live="polite"` (or `"assertive"` for errors)
  containers.
- **Icon-only buttons:** Any button or link with only an icon must include `aria-label`:
  `<button aria-label="Close">`.
- **Image alt text:** All `<img>` tags require `alt`. Decorative images use `alt=""`.

### 6.5 Form Accessibility

- **Labels:** Every form input must have an associated `<label>` (via `for`/`id` or wrapping
  TallstackUI `label` prop). Placeholder text is not a label substitute.
- **Required indicators:** Use the `required` HTML attribute (TallstackUI `required` prop), not just
  visual asterisks.
- **Error messaging:** Validation errors must be associated with their field via `aria-describedby`
  and announced to screen readers via `aria-live` regions. TallstackUI handles this automatically.
- **Error focus:** After failed validation, focus must move to the first invalid field or an error
  summary.

### 6.6 Content Reflow

- No horizontal scrolling at 320px viewport width (WCAG 1.4.10).
- Responsive breakpoints must prevent content clipping or overlap.
- Tables must reflow to card layout or horizontal scroll with visible scroll indicators on mobile.

---

## 7. SPA Navigation

> 📖 Authoritative reference: [UX Pattern](../arch/ux-pattern.md) §4.1 — `wire:navigate` SPA, focus reset, information architecture.

Internal links use `wire:navigate` for AJAX page transitions. Content area swaps without full page
reload. Browser history and URL update normally — bookmarking and back button work as expected. No
JavaScript framework needed.

### wire:navigate Accessibility

- After `wire:navigate` page transition, focus must reset to the page heading (`<h1>`) or the
  first interactive element. Use:
  ```blade
  <div wire:navigate x-init="$nextTick(() => $el.querySelector('h1, [autofocus]')?.focus())">
  ```
- Loading indicators during transition must include `aria-busy="true"` and `role="status"` to
  announce the loading state to screen readers.

---

## 8. Routing

### URL Structure

Routes follow a predictable, human-readable URL pattern:

| Scope          | Pattern                         | Example                                  |
| -------------- | ------------------------------- | ---------------------------------------- |
| Guest          | `/{resource}`                   | `/apply`, `/login`                       |
| Authenticated  | `/{resource}`                   | `/registration`, `/dashboard`            |
| Student        | `/student/{module}/{resource}`  | `/student/internships/placement-change`  |
| Teacher        | `/teacher/{module}/{resource}`  | `/teacher/journals/logbook`              |
| Supervisor     | `/supervisor/{module}/{resource}` | `/supervisor/journals/attendance`      |
| Admin          | `/admin/{module}/{resource}`    | `/admin/internships/placements`          |
| Super Admin    | `/admin/{module}/{resource}`    | `/admin/users` (shared with admin)       |

### Route Naming

Route names are flexible and describe the URL path — no rigid convention. Examples:

```php
Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
Route::livewire('/apply', ApplyPage::class)->name('apply');
Route::get('/dashboard', ...)->name('dashboard');
```

### Route Files

Module-level routes: `routes/web/{module}.php`. Submodule-level routes:
`routes/web/{submodule}.php` (no module prefix). See `docs/guides/infra/routes.md`.

### Livewire Routes

Livewire components are registered directly in route files:

```php
Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
```

Route middleware applies at the route level — `auth`, `guest`, `role:{roles}`, `auth.throttle`.
See `docs/guides/arch/modular-pattern.md` §13 for full route patterns.

---

## 9. Localization

> 📖 Authoritative reference: [UX Pattern](../arch/ux-pattern.md) §3 — localization (ICU/CLDR, key conventions, authoring rules, dual locale).

### Translation Key Convention

All user-facing strings use `__()` for EN/ID bilingual support. See `docs/conventions.md` §14 for
full rules.

| Scope           | Pattern                     | Example                                  |
| --------------- | --------------------------- | ---------------------------------------- |
| Module-level    | `{module}.key`              | `__('enrollment.register')`              |
| Submodule-level | `{submodule}.key`           | `__('internship.create_success')`        |
| Shared          | `common.key`                | `__('common.actions.save')`              |
| Validation      | `validation.*`              | `__('validation.required')`              |

### Language Switcher

The `LangSwitcher` Livewire component (`app/Modules/Settings/Livewire/LangSwitcher.php`) toggles between
EN and ID (plain Tailwind trigger with globe icons; no TallstackUI built-in). Locale preference is stored in a cookie (`locale`) and applied via `SetLocale` middleware
on every request.

### Theme Switcher

The theme switcher is **not** a Livewire component. It is the Blade partial `core::ui.theme-switch` wrapping TallstackUI's `<x-theme-switch>` (`tallstackui_darkTheme` Alpine scope). See §3 for persistence and JS wiring. Legacy `ThemeSwitcher.php` Livewire component and its test were removed in `0.15.0`.

### Date & Number Formatting

```php
// Locale-aware date
Carbon::locale(app()->getLocale())->isoFormat('D MMMM YYYY');

// Locale-aware number
Number::locale(app()->getLocale())->format(1234567.89);
```

### HTML Language Attribute

`<html lang="{{ app()->getLocale() }}">` is set in `resources/views/ui/layouts/base.blade.php`.
Screen readers use this to select the correct pronunciation engine.

### Dual Locale Requirement

Every translation key must exist in both `lang/en/{file}.php` and `lang/id/{file}.php`. Adding a
key to one locale without the other is a bug.

---

## 10. Component Library Patterns

### TallstackUI Components (`x-ts-*`)

All UI is built with TallstackUI v4 (`prefix ts-`, see `config/tallstackui.php`):

- **Layout:** `x-ts-layout`, `x-ts-side-bar`, `x-ts-dropdown`, `x-ts-tooltip`
- **Data:** `x-ts-table` (sorting, pagination, row selection; headers via `index` key), `x-ts-card`
- **Forms:** `x-ts-input`, `x-ts-select.native`, `x-ts-textarea`, `x-ts-radio`, `x-ts-checkbox`, `x-ts-toggle`, `x-ts-file` with validation styling
- **Actions:** `x-ts-button`, `x-ts-badge`, `x-ts-alert`, `x-ts-icon`
- **Overlays:** `x-ts-modal`, `x-ts-dialog` (confirm), `x-ts-slide`
- **Feedback:** TallstackUI toast via `toast()->success()` Interactions (replaces PHPFlasher `flash()->`)

Legacy DaisyUI class tokens (`.btn`, `.badge`, `.card`, `.table`, `.alert` etc.) remain as class names but are shimmed locally in `resources/css/app.css` `@layer components` — no DaisyUI npm package.

---

## 11. Guide Component Pattern

> 📖 Authoritative references: [UX Pattern](../arch/ux-pattern.md) §4.3 — Guide Pattern (NN/g Heuristic #10, placement, FAB, modal, a11y); [Livewire Pattern](../arch/livewire-pattern.md) §11 — implementation.

Every page with a non-trivial workflow MUST include a floating guide button (bottom-right, question
mark icon) that opens a modal with step-by-step instructions. See
`docs/guides/arch/livewire-pattern.md` (§11) for the full implementation pattern.

Implementation reference: `resources/views/setup/components/setup-guide.blade.php`

### Requirements

- **File:** `resources/views/{module}/components/{page-name}-guide.blade.php`
- **Trigger:** Fixed floating button, bottom-right, `z-50`, primary color
- **Modal:** `x-ts-modal` / `x-ts-dialog` with numbered steps and a tip section
- **Localization:** All strings in `__('{module}.guide.*')`
- **Integration:** Parent component includes `@include('{module}.components.{page-name}-guide')` and
  exposes `$showGuide` boolean

---

## 12. Key Locations

| Asset             | Path                                      |
| ----------------- | ----------------------------------------- |
| Layout templates  | `resources/views/ui/layouts/`           |
| UI components     | `resources/views/ui/components/` (incl. `theme-switch.blade.php`) |
| CSS entry point   | `resources/css/app.css` (`@import tallstackui/css/v4.css` + self-hosted palette + shims) |
| JS entry point    | `resources/js/app.js` (theme + flatpickr + marked + choices bridge) |
| Theme resolver    | `app/Modules/Settings/Domain/Theme/Support/Theme.php` (`cssVariables()`, `defaults()`, `presets()`, `all()`) |
| Color utility     | `app/Modules/Core/Support/Color.php` (base shades, contrast, lighten/darken) |
| Color presets     | `config/settings.php` (`colors.defaults` + `colors.presets`, `theme_cache_keys`) |
| Branding form     | `app/Modules/Settings/Domain/Branding/Livewire/Forms/BrandingForm.php` (preset apply/detect) |
| Admin color UI    | `app/Modules/Settings/Livewire/SystemSetting.php` |
| TallstackUI config| `config/tallstackui.php` (`prefix ts-`)   |
| Sidebar menu      | `config/menu.php`                         |
| Theme switcher    | `resources/views/ui/components/theme-switch.blade.php` (`<x-theme-switch>` TallstackUI) |
| Language switcher | `app/Modules/Settings/Livewire/LangSwitcher.php`  |