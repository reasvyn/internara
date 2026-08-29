# UI Pattern — Elegant, Modern & Responsive Interfaces

## Description

This pattern governs how Internara builds **elegant, modern, and responsive** user interfaces. It distills global industry standards — Google Material Design 3, Apple Human Interface Guidelines (HIG), W3C Design Tokens Community Group (DTCG), WCAG 2.2, MDN Responsive Design, and Tailwind CSS v4 doctrine — into actionable rules tied to the project's actual stack: Tailwind CSS v4.3 (CSS-first, no `tailwind.config.js`), TallStackUI v4.3 (`x-ts-*`, prefix `ts-`), Vite 8.1 + `laravel-vite-plugin`, Livewire 4.3, Alpine.js, and self-hosted Instrument Sans.

Without it, UIs drift into arbitrary hex values, inconsistent spacing, desktop-only layouts, shadow sprawl, and slow asset delivery. With it, every screen is token-driven, mobile-first, accessible by construction, and performant.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Tokens over literals** — Never hardcode `style="color:#123456"`, `bg-[#...]`, `w-[930px]`, or arbitrary `11px`. Every color, spacing, radius, shadow, and font value MUST come from `@theme` CSS variables (`--color-primary`, `--spacing-*`, `--radius-*`) and their generated utilities (`bg-primary`, `p-4`, `rounded-xl`). Arbitrary values require explicit justification in review.

2. **CSS-first, no JS config** — Tailwind v4 is CSS-first. Do NOT create `tailwind.config.js`. All customization lives in `resources/css/app.css` via `@import 'tailwindcss'`, `@theme`, `@custom-variant dark`, `@layer`, and `@source`. Runtime brand overrides live in `Theme::cssVariables()` → inline `<style>` in `core::layouts.base`, never in a build-time config.

3. **Semantic palette only** — Use `base-100/200/300/content`, `primary/secondary/accent/neutral`, `info/success/warning/error` (and their `-content` pairs). Never `bg-blue-500`, `text-red-500`, `bg-[#059669]` for decorative color. The palette is pre-validated for WCAG AA contrast (4.5:1 text, 3:1 large/UI, 3:1 non-text contrast per WCAG 1.4.3 / 1.4.11).

4. **Mobile-first authoring** — Base classes are mobile. Breakpoints (`sm:640`, `md:768`, `lg:1024`, `xl:1280`) only add. No `hidden md:block` that hides primary content on mobile. No horizontal scroll at 320px viewport width (WCAG 1.4.10 Reflow). Tables wrap in `overflow-x-auto rounded-xl border`.

5. **Blade is presentation only** — No business or UI computation in `.blade.php`. No `@php` blocks with `round(($a/$b)*100)`, `max()`, stage assembly, or permission expressions. Livewire computes and exposes `public` ready-to-render values; Alpine handles client-only toggles. See `livewire-pattern.md` §1.1 and `conventions.md` §14.1.

6. **TallStackUI as primitive** — New code MUST use `x-ts-*` (`x-ts-button`, `x-ts-modal`, `x-ts-table`, `x-ts-input`, `x-ts-card`, `x-ts-badge`, `x-ts-dropdown`) with prefix `ts-`. DaisyUI shims in `@layer components` (`.btn`, `.badge`, `.table`, `.alert`) are legacy bridges — do not author new DaisyUI class usage.

7. **Touch & focus minima** — Interactive targets ≥44×44px (WCAG 2.5.8 Minimum, Apple HIG 44pt). Icon-only buttons use `size-10`+ or `p-2` + `aria-label`. Every focusable element has a visible `focus:ring` — never `outline-none` without replacement.

8. **No inline shadows sprawl** — Elevation is `border border-base-content/10` + `rounded-xl` for cards, `shadow-xl` only for floating layers (modal, dropdown, popover). Max two shadow levels.

---

## How to Apply

### 1. Visual Design — Hierarchy, Whitespace, Grid, Color

**Hierarchy via type scale, not custom CSS.** Tailwind's type scale is the single scale.

| Role | Utility | Example |
|------|---------|---------|
| Page `h1` | `text-2xl font-black tracking-tight` | `core::ui.page-header` |
| Section `h2` | `text-lg font-semibold` | Card or modal title |
| Card title | `text-sm font-semibold` | `stat-card` title |
| Body | `text-sm leading-relaxed` (1.6) | Descriptions, table cells |
| Caption/meta | `text-xs text-base-content/50` | Timestamps, helpers |
| Stat value | `text-2xl font-bold` | Dashboard KPIs |

Limit to Instrument Sans `400/500/600` + `900` for display `h1`. Line length `max-w-prose` (65ch) for reading passages; use `truncate` + `min-w-0` in flex cards. Never `style="font-size:27px"`.

**Whitespace as separator (Material 3 baseline 4dp / 8-point grid).**

```blade
{{-- ✅ Correct — 8-point grid, border over heavy shadow --}}
<div class="rounded-xl border border-base-content/10 bg-base-100 p-5">
  <h2 class="text-sm font-semibold">{{ $title }}</h2>
  <p class="text-xs text-base-content/60">{{ $description }}</p>
</div>

{{-- ❌ Wrong — arbitrary spacing, hardcoded shadow/color --}}
<div style="margin:12px; box-shadow:0 2px 11px rgba(0,0,0,.12); background:#fff">
```

Use `p-2/p-4/p-6`, `gap-2/gap-4`, `space-y-6`, `py-4/py-5` for page rhythm; `gap-3` for button groups, `gap-4` for stat grids. Divider: `h-px bg-base-300 my-4`.

**Grid & container.**

```blade
{{-- Page container — always --}}
<div class="mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- stat cards --}}
  </div>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    {{-- header + actions --}}
  </div>
</div>
```

12-column mental model. Never fixed `w-[930px]`. Sidebar: `lg:drawer-open` with overlay label; hamburger only `lg:hidden` (see `core::layouts.sidebar`).

**Color hierarchy (Material 3 roles → Internara tokens).**

- Surfaces: `base-100/200/300` + `base-content` (neutral).
- Brand: `primary` (single accent, default `#059669`) for primary actions only; `secondary`/`accent` sparingly.
- Semantic: `info/success/warning/error` (oklch) only for state — never decorative `bg-blue-500`.
- Alert tints: `color-mix(in oklch, var(--color-info) 10%, white)` (keeps contrast while signaling).

### 2. Responsive Design — Mobile-First, Reflow, Fluid, Container Queries

Author mobile first, enhance upward. This is MDN Responsive Design + WCAG 1.4.10 + Google Web Fundamentals.

```blade
{{-- ✅ Mobile-first — flex-col base, row at sm; search full on mobile, constrained at sm --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <h1 class="text-2xl font-black">{{ __('module.title') }}</h1>
  <x-ts-input wire:model.live.debounce.300ms="search" class="w-full sm:max-w-xs" />
</div>

{{-- ❌ Desktop-first — hides content on mobile, breaks reflow --}}
<div class="hidden md:flex flex-row gap-6 w-[900px]">
```

**Rules:**

- Breakpoints: `sm` filter collapses to full-width, `md` sidebar drawer, `lg` persistent sidebar + `lg:grid-cols-4`. Verify at 320 / 768 / 1024.
- Reflow 320px: `body` has `overflow-x-hidden antialiased`; table wrapper MUST be `overflow-x-auto rounded-xl border`. No page-level horizontal scroll. Grid wraps to 1 col before scroll.
- Fluid: `p-4 md:p-6 lg:p-8`, `text-sm` → `lg:text-base`, `max-w-7xl` container, `min-h-screen` root. Use `text-2xl` not `text-[27px]`.
- Tables mobile: hide secondary columns `hidden md:table-cell` or keep scroll with visible indicator; at ≤640 offer card fallback. `table-zebra table-sm` for density.
- Touch: FAB `fixed right-6 bottom-6 z-50 size-12 rounded-full` (48px) is the canonical anchor; `btn-square` → `size-10`+ for icon-only. Gap between targets ≥8px.
- Container queries (Tailwind v4 native `@container`): prefer `grid` + `@container` for card-internal reflow where row ≠ viewport. Adopt `container-type:inline-size` when a card needs an independent breakpoint; otherwise media queries suffice.

### 3. Component Design — Atomic, Reusable, Consistent

Follow **Atomic Design** (Brad Frost) + **W3C Design Tokens** + Material 3 Component Spec.

- **Atoms** → `x-ts-button`, `x-ts-badge`, `x-ts-icon` (Heroicons `heroicon-o-*` via `blade-heroicons`, prefix `heroicon`).
- **Molecules** → `core::widgets.stat-card`, `core::widgets.empty-state`, `core::ui.page-header` (composes atoms, no business logic).
- **Organisms** → `core::ui.record-manager` (canonical CRUD scaffold: header, stats `grid-cols-2 lg:grid-cols-4`, search `wire:model.live.debounce.300ms`, `perPage`, Alpine `filtersOpen` with `x-trap`+`x-cloak`, `selectionBar` with `@entangle`, table `overflow-x-auto`, emptyState/modal slots).

**Consistency contract:**

- Every form field MUST have `label` + Heroicon (`o-user`, `o-envelope`, `o-calendar`, `o-clock`, `o-magnifying-glass`, `o-chevron-down`) — never icon as sole indicator (WCAG 1.4.1). Icons via `x-ts-icon` or `x-ts-*` `icon` prop.
- Cards: `rounded-xl border border-base-content/10 bg-base-100 p-5`; floating: `rounded-xl border shadow-xl mt-2 w-80 p-4`.
- Do not duplicate a molecule — extract to `resources/views/ui/components/` or `resources/views/{module}/components/` and reuse. Three similar blocks → extract.

### 4. Tailwind CSS v4 — CSS-First & Design Tokens

Tailwind v4 doctrine: **CSS is the config.** No `tailwind.config.js` exists nor should be created (confirmed absent; see `tailwindcss.com/blog/tailwindcss-v4`).

**Setup (already in `resources/css/app.css`):**

```css
@import 'tailwindcss';
@import '../../vendor/tallstackui/tallstackui/css/v4.css';

@source '../views';
@source '../../vendor/tallstackui/tallstackui/**/*.php';

@custom-variant dark (&:where(.dark, .dark *));

@theme {
  --color-base-100: #ffffff;
  --color-base-200: #f4f4f5;
  --color-primary: #059669; /* overridden at runtime by Theme::cssVariables() */
  --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
```

**Three-layer token hierarchy (W3C DTCG):**

| Layer | What | Where | Example |
|-------|------|-------|---------|
| **Base (primitive)** | Raw values | `@theme` primitives | `--color-primary: #059669`, `--spacing-4: 1rem`, `--radius-xl: 0.75rem` |
| **Semantic (purpose)** | Intent, not value | `@theme` semantic + `Theme::cssVariables()` runtime | `--color-primary` → `bg-primary`, `text-primary-content`; `base-100` → `bg-base-100` |
| **Component (variant)** | Component API | TallStackUI props + `@layer components` shims | `<x-ts-button color="primary">`, `.btn-primary` |

What components MUST use: semantic tokens (`bg-primary`, `text-success`, `border-warning`, `bg-info/10`, `bg-base-100`). Generates via `@theme` → utilities (`bg-*`, `text-*`, `border-*`, `fill-*`). OKLCH for semantic colors (`oklch(60% 0.55 250)`) gives perceptually even scales and safe `color-mix()` tints.

**Theming bridge:** `app/Modules/Settings/Theme/Support/Theme.php::cssVariables()` injects inline `<style>` scoped to `html[data-theme='light']` / `html[data-theme='dark']` (light brand → 40% `Color::lighten()` for dark, base → `Color::computeDarkShades()` 80% darken). Components consume `var(--color-primary)` — never `brand('primary')` in CSS. `brand()` is for JS/meta only. New token requires entries in both `light` and `dark` scopes and `Color::isValid()` validation.

**Do / Don't:**

```blade
{{-- ✅ Token-driven --}}
<div class="bg-primary text-primary-content rounded-xl border border-base-300 p-4">
  <x-ts-badge color="success" :text="__('common.verified')" />
</div>

{{-- ❌ Drift — arbitrary value, hardcoded color, bypasses dark mode --}}
<div class="bg-[#059669] text-white rounded-[7px] p-[13px] shadow-[0_2px_11px_rgba(0,0,0,.12)]">
```

Prevent drift: ESLint/Prettier checks, code review flags arbitrary values (`[value]`), quarterly token audit. Fix by mapping arbitrary to nearest token; create new token only if truly needed (document why).

### 5. Typography

- **Scale:** Tailwind `text-xs/sm/base/lg/xl/2xl` + `font-normal/medium/semibold/black` + `tracking-tight` for display. `leading-relaxed` (1.6) for body, `leading-none/tight` for badges/buttons.
- **Measure:** `max-w-prose` for articles, `max-w-7xl` for pages, `truncate` in flex.
- **Font:** Self-hosted Instrument Sans `400/500/600` with `font-display:swap` (already in `app.css`). No external fetch. Email/PDF use `Arial`/`Courier New`. Never load Google Fonts at runtime without CSP update in `SecurityHeadersMiddleware`.
- **Responsive type:** `text-sm` base → `lg:text-base`; `text-2xl` for `h1` (not viewport-jacked `clamp()` unless editorial hero — avoid for admin UI).

### 6. Iconography

- **Library:** Heroicons via `blade-heroicons` (`heroicon-o-*` outline, `heroicon-s-*` solid). Rendered via `x-ts-icon` or TallStackUI `icon` prop.
- **Rules:** Every form field has an icon (see `conventions.md` §13 table); icons never sole indicator — always paired with `label` text. Icon-only controls require `aria-label="{{ __('common.delete') }}"` (WCAG 4.1.2 Name/Role/Value).

### 7. Form, Table, Modal, Navigation

**Forms:** `x-ts-input`/`x-ts-select.native`/`x-ts-textarea`/`x-ts-file` with `label` prop (auto `for` association). `required` prop for required fields (not just visual asterisk). Errors via TallStackUI `aria-live` — do not suppress. Layout `space-y-4` inside modal, `grid grid-cols-1 md:grid-cols-2 gap-4` for two-column forms.

**Tables:** `x-ts-table` with `headers` array (`label`, `field`, `sortable` → auto `aria-sort`). Wrap in `overflow-x-auto rounded-xl border`. Bulk checkbox header needs `aria-label="Select all rows"`. Density `table-sm`, zebra `table-zebra`. Empty state via `core::widgets.empty-state` slot, not bare text.

**Modals/Dialogs:** `x-ts-modal` (`wire="showModal"`), `separator blur size="lg"` for guides. Focus traps automatically; focus moves to first focusable on open, returns to trigger on close. Confirm destructive via two-step `ask{Action}()` → `confirm{Action}()` + shared `core::ui.confirm` (`wire="showConfirm"`), never bare `wire:confirm`. Cancel via `Escape`.

**Navigation:** App shell `core::layouts.app` → `<x-ts-layout>` with `menu` (sidebar `x-ts-side-bar navigate smart collapsible` reading `config/menu.php` role-filtered), `header` (`h1[tabindex=-1]` for focus management), `footer`, `max-w-7xl mx-auto px-4 md:px-6 lg:px-8`, breadcrumb `nav[aria-label]`, content `aria-live="polite"`. SPA `wire:navigate` with focus reset `x-init="$nextTick(() => $el.querySelector('h1,[autofocus]')?.focus())"` in `core::layouts.base`. Skip link as first focusable element (`<a href="#main">`).

### 8. Performance & Asset Delivery

Tailwind v4 engine: 5× full builds, 100× incremental (µs) via `@tailwindcss/vite`. Vite 8.1 + `laravel-vite-plugin` with entries `resources/css/app.css`, `resources/js/app.js` (see `vite.config.js`). Head order in `core::layouts.base/head`: `preconnect`+`dns-prefetch`, `viewport`, `csrf-token`, `<tallstackui:script/>` **before** `@vite` (required). `@source` covers `resources/views` + TallStackUI vendor. Verify with `npm run build`; no external font/script without CSP `default-src`/`script-src`/`img-src` allowance.

Apply performance heuristics: avoid `@apply` bloat, keep `@layer components` shims minimal, prefer utilities over custom CSS where possible, lazy-load heavy JS (`flatpickr`, `marked+DOMPurify`, Choices bridge) already in `resources/js/app.js`.

---

## Anti-Patterns

| You see… | It should be… | Violation |
|----------|---------------|-----------|
| `style="color:#059669"` / `bg-[#059669]` / `text-[#1a1a1a]` | `bg-primary text-base-content` (semantic token) | Token drift, breaks dark mode & contrast |
| `w-[930px]` / `m-[11px]` / `p-[13px]` / `rounded-[7px]` / `shadow-[0_2px_11px_...]` | `max-w-7xl`, `p-4`, `rounded-xl`, `shadow-xl` (scale) | Arbitrary value, no 8-point grid |
| `tailwind.config.js` with `theme.extend.colors` | `@theme { --color-* }` in `resources/css/app.css` + `Theme::cssVariables()` runtime | CSS-first violation, splits SSOT |
| `bg-blue-500 text-red-500` decorative | `bg-info text-info-content` / `bg-primary` semantic | Palette violation, fails WCAG contrast gate |
| `hidden md:flex` hiding primary content | `flex flex-col sm:flex-row` mobile-first | Desktop-first, fails 320px reflow |
| Table without `overflow-x-auto` wrapper | `<div class="overflow-x-auto rounded-xl border"><x-ts-table /></div>` | Horizontal scroll at 320px (WCAG 1.4.10) |
| `@php $rate = round(($a/$b)*100) @endphp` in Blade | Livewire `public int $completionRate` computed in `mount()`/`#[Computed]` | Blade logic (C1-adjacent, `conventions.md` §14) |
| `@if ($user->role === 'admin' && $stats['x']>0)` in Blade | `public bool $isSuperAdmin` / `@hasrole('super_admin')` / Alpine `x-show` | Business logic in view |
| `<button><x-ts-icon name="trash" /></button>` (icon-only, no label) | `<x-ts-button icon="trash" aria-label="{{ __('common.delete') }}" />` | WCAG 4.1.2 Name/Role/Value |
| `class="outline-none"` without `focus:ring` replacement | Keep Tailwind `focus:ring` / TallStackUI default | WCAG 2.4.7 Focus Visible |
| New `.btn .badge .alert` custom CSS | `<x-ts-button>` / `<x-ts-badge>` / `x-ts-alert` | DaisyUI shim misuse, should use TallStackUI primitive |
| Inline `<script>` in Blade | Alpine `x-data` / `@entangle` + CSP-allowed external | CSP violation (`SecurityHeadersMiddleware`) |
| Hardcoded English `"Record created"` in Blade/PHP | `__('{module}.{entity}.created')` | Localization (`conventions.md` §15) |

---

## Quick References

- `livewire-pattern.md` — Thin components, Blade no-logic, `BaseRecordManager`/`BaseRecordEntry`/`BaseRecordList`, Guide pattern, a11y, localization (§1, §1.1, §11, §13, §14)
- `../conventions.md` §13 Theming & Visual Consistency — CSS variables, form icons; §14 Frontend & Blade Presentation — Blade no-logic, `@hasrole`
- `modular-pattern.md` §22 Accessibility (WCAG 2.1 AA) & §23 Localization — project-wide a11y/i18n contracts
- `resources/css/app.css` — `@theme` palette, `@custom-variant dark`, `@layer components` shims, `data-theme` overrides
- `resources/views/ui/layouts/base.blade.php` + `app.blade.php` + `sidebar.blade.php` + `header.blade.php` — app shell, `Theme::cssVariables()`, skip-link, focus reset
- `resources/views/ui/components/record-manager.blade.php` — canonical CRUD scaffold (reference for every manager)
- `resources/js/app.js` — `resolveTheme()`/`applyTheme()` dual-signal dark mode, `livewire:init` locale reload
- `app/Modules/Settings/Theme/Support/Theme.php` + `app/Modules/Core/Support/Color.php` — runtime branding, contrast (`contrastColor()`), `computeDarkShades()`, `isValid()`
- `vite.config.js` + `package.json` — Vite 8.1, `@tailwindcss/vite` 4.3, `laravel-vite-plugin`
- [Tailwind CSS v4.0](https://tailwindcss.com/blog/tailwindcss-v4) — CSS-first, `@theme`, `@source`, `@custom-variant`, `color-mix()`, container queries
- [Tailwind Theme Variables](https://tailwindcss.com/docs/theme) — `@theme` generation of `bg-*`/`text-*`/`border-*`
- [W3C Design Tokens (DTCG)](https://www.w3.org/community/design-tokens/) — token layers (base/semantic/component)
- [Material Design 3 — Color Roles & Dynamic Color](https://m3.material.io/styles/color/roles) — semantic roles, tonal palettes
- [Apple HIG — Layout](https://developer.apple.com/design/human-interface-guidelines/layout) — hierarchy, spacing, 44pt touch targets
- [MDN — Responsive Design](https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Responsive_Design) — mobile-first, fluid, media queries
- [WCAG 2.2 QuickRef](https://www.w3.org/WAI/WCAG22/quickref/) — 1.4.3 Contrast (Minimum), 1.4.10 Reflow, 1.4.11 Non-text Contrast, 2.5.8 Target Size
