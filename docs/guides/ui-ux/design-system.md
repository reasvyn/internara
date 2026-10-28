# UI/UX Design — Principles & Guidelines

## Description

UI/UX design principles, component library usage (TallstackUI v4 + Tailwind CSS v4), layout patterns,
and accessibility guidelines.


## Prerequisites

See [Installation](../installation.md#prerequisites) for full server requirements and verification commands.

## Steps

This document is reference-oriented. For installation and setup procedures, see:

1. [Installation](../installation.md) — server preparation and CLI provisioning
2. [Setup Wizard](../setup-wizard.md) — browser-based initial configuration
3. [Post-Setup](../post-setup.md) — initial data population after wizard completion

## 1. Design System Philosophy

> 📖 Authoritative references: [UI Pattern](../arch/ui-pattern.md) — visual hierarchy, Tailwind v4 tokens, component design, performance; [UX Pattern](../arch/ux-pattern.md) — theming, accessibility, localization, user flow.

Internara's interface is built on three layers:

| Layer                  | Purpose                    | Provides                                                                 |
| ---------------------- | -------------------------- | ------------------------------------------------------------------------ |
| **Tailwind CSS v4**    | Utility foundation         | Spacing, typography, responsive grid, colors (`dark:` via `.dark` class) |
| **TallstackUI v4**     | Component library          | Buttons, cards, tables, forms, selects, modals, dialogs, badges, alerts, layout, dropdowns, toast |
| **Self-hosted palette**| Semantic color bridge      | `--color-base-100/200/300`, `--color-primary/secondary/accent`, `info/success/warning/error` — mirrors legacy DaisyUI tokens via `@theme` + `@layer components` shims in `resources/css/app.css` (no DaisyUI plugin) |

Livewire manages server-side state; Alpine.js handles client-side behavior. The visual language is
clean, compact, minimalist, and modern: neutral monochrome base, single accent brand color, low-saturation
backgrounds, subtle borders, bold typography, minimal shadows, and balanced, restrained curvature (**tidak terlalu bulat dan tidak lancip**, calibrated across the `xs`–`l` spectrum with **zero sharp corners**).

### 1.1 Core Aesthetic Principles

#### 1. Compact Rhythm & Viewport Efficiency
- Interfaces prioritize vertical efficiency without feeling cramped. Excessive hero heights, bloated paddings, and arbitrary white space are eliminated.
- Authentication screens, modal dialogues, and overview cards are designed to fit comfortably within the standard viewport on desktop and laptop screens (768px–900px height) without requiring unnecessary vertical scrolling to reach primary action buttons.
- Standard form spacing is consolidated to `space-y-3.5` (mobile) / `space-y-4` (desktop).
- Card padding adheres to `p-5 sm:p-6` (main cards) or `p-4 sm:p-5` (feature/stat cards).

#### 2. Modern Minimalism
- Clutter-free visual hierarchy: content-first layouts, subtle micro-interactions, and low-contrast surface dividers (`border-base-content/10`) instead of heavy box shadows or busy graphic dividers.
- Elevation relies on delicate border definition (`border border-base-content/10`) coupled with soft shadows (`shadow-xs` or `shadow-sm`), reserving deeper shadows (`shadow-xl`) strictly for floating overlays (modals, dropdown menus).

#### 3. Balanced Soft Curvature — Zero Sharp Corners ("Tidak Terlalu Bulat, Tidak Lancip", Skala xs–l)
Every structural container, surface, input, button, badge, and interactive element across Internara MUST feature balanced, proportionate curvature. Elements must neither be excessively circular/bubbly nor sharp/boxy: **sharp 90-degree corners (`rounded-none`) and overly bulbous radii (`rounded-2xl`, `rounded-3xl`) are strictly prohibited**. The design system strictly uses the **`xs` to `l` (`rounded-xs` through `rounded-lg`) spectrum** tailored to each element's functional role:

| Component Category | Token / Class | Curvature Purpose |
| ------------------ | ------------- | ----------------- |
| **Elevated Surfaces, Cards & Modals** | `rounded-lg` (0.5rem / 8px) / `round="lg"` | Crisp, modern card surfaces, modal dialogs, drawer panels, and structural containers without excessive ballooning. |
| **Interactive Controls & Inputs** | `rounded-md` (0.375rem / 6px) / `round="md"` | Form inputs, textareas, styled selects, alert banners, and primary/secondary buttons. Ergonomic and tactile without being bulbous. |
| **Badges, Tags & Tooltips** | `rounded-sm` (0.25rem / 4px) to `rounded-md` (0.375rem / 6px) | Compact status indicators, metadata tags, filter chips, and floating tooltips. Replaces oversized pill shapes for a refined professional density. |
| **Checkboxes, Micro-Toggles & Action Buttons** | `rounded-xs` (0.125rem / 2px) to `rounded-sm` (0.25rem / 4px) | Checkboxes, switches, table row action buttons, and small icon buttons. Preserves crisp edge definition while eliminating 90° sharp corners. |
| **Subtle Transitions & Headers** | `rounded-t-lg sm:rounded-t-lg` | Section separators, drawer headers, and bottom sheets transitioning across surface contrasts. |

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

### 3.1 User-Oriented Semantic Variant Architecture

Every color, variant, and interactive treatment in Internara communicates intent, urgency, and hierarchy. Users do not parse raw CSS; they navigate by scanning visual affordances. Semantic variants are classified into three functional groups:

1. **Action & Interaction Hierarchy:** `primary`, `secondary`, `tertiary`, `ghost`, `error/danger`
2. **Context & Expression:** `accent`, `muted`, `neutral`
3. **Feedback & System State:** `info`, `success`, `warning`, `error`

#### 1. Semantic Variant Matrix

| Variant | User Mental Model & Perception | Applied Components | Token / Class Binding | Mandatory User-Oriented Rules |
| :--- | :--- | :--- | :--- | :--- |
| **`primary`** | **"Aksi Utama / Focal Point"**<br>Langkah utama yang disarankan sistem untuk menyelesaikan alur saat ini. | Primary CTA button, active navigation, active step indicator, focused input ring. | `bg-primary text-primary-content`<br>`color="primary"` | **Maksimal 1 primary action per layar/viewport/card**. Jangan pernah menduplikasi dua tombol primary berdampingan untuk menghindari kebingungan pengguna (*decision paralysis*). |
| **`secondary`** | **"Aksi Alternatif / Pendukung"**<br>Pilihan tindakan pelengkap yang konstruktif tanpa menyaingi aksi utama. | Tombol "Ekspor Data", "Filter Lanjutan", "Simpan Draf", sub-tab navigasi. | `border border-base-300 bg-base-100 text-base-content hover:bg-base-200`<br>`color="secondary"` atau `outline` | Dampingkan dengan tombol primary saat menyediakan jalur alternatif yang aman. |
| **`tertiary`** | **"Aksi Tambahan / Low-Priority"**<br>Opsi pelengkap berbobot rendah; tersedia tanpa menarik perhatian visual. | Tombol "Reset Filter", "Lihat Log Audit", "Buka Dokumentasi", chip filter non-aktif. | `bg-base-200/60 text-base-content/80 hover:bg-base-200 border-transparent`<br>`color="slate" flat` | Gunakan saat ada tiga tingkatan aksi untuk menjaga tampilan tetap tenang. |
| **`accent`** | **"Sorotan Khusus / Highlight"**<br>Penanda fitur unggulan, promosi penting, atau milestone bernilai tinggi. | Badge "Fitur Baru", "Rekomendasi", highlight statistik pencapaian, banner onboarding khusus. | `bg-accent text-accent-content`<br>`color="orange"` | **Gunakan secara selektif (<5% luas visual)**. Dilarang digunakan sebagai tombol submit biasa agar tidak mendistorsi hirarki primary. |
| **`muted`** | **"Informasi Pasif / De-emphasized"**<br>Label pendukung atau informasi kontekstual yang dibaca saat diperlukan. | Timestamp ("2 jam lalu"), helper text / caption input, deskripsi sekunder tabel, label status archived. | `text-base-content/60` (light) / `text-base-content/50` (dark), `bg-base-200/50` | Kontras teks **wajib memenuhi WCAG AA (min 4.5:1)**. Dilarang digunakan untuk pesan validasi, peringatan, atau tombol aksi interaktif utama. |
| **`ghost`** | **"Interaksi Bebas Beban / Low-Friction"**<br>Tombol interaktif yang transparan saat diam agar antarmuka bebas dari kumpulan kotak tombol yang berisik. | Row action icons di tabel (View, Edit, Download), navigasi pagination, tombol "Batal" (Cancel), close dialog. | `bg-transparent hover:bg-base-200 text-base-content border-transparent`<br>`color="slate" flat` atau `ghost` | **Wajib menyertakan `aria-label`** pada icon-only buttons. Hover state dan focus ring harus tegas terlihat saat berinteraksi. |
| **`neutral`** | **"Data Struktural / Objektif"**<br>Status data bawaan yang faktual tanpa konotasi emosional positif/negatif. | Badge "Draf", "Offline", "Tersimpan", kartu informasi umum, surface kontainer sekunder. | `bg-base-300/80 text-base-content`<br>`badge-neutral`, `color="slate"` | Digunakan ketika sistem menyajikan metadata statis yang belum membutuhkan intervensi atau evaluasi pengguna. |
| **`info`** | **"Panduan Edukatif / Status Berjalan"**<br>Petunjuk alur atau informasi bahwa suatu proses sedang berlangsung secara wajar. | Banner panduan workflow, badge "Menunggu Verifikasi", "Dalam Proses", "Dijadwalkan", tooltip edukasi. | `bg-info/10 text-info border-info/20`<br>`color="blue"`, `<x-ts-alert color="info">` | Dilarang digunakan untuk menandai bahaya atau kesalahan; fokuskan pada bantuan kontekstual dan transparansi progres sistem. |
| **`success`** | **"Konfirmasi Positif / Selesai"**<br>Pemberitahuan bahwa tindakan berhasil atau data dalam kondisi valid dan aman. | Toast sukses, badge "Aktif", "Disetujui", "Lulus", "Terverifikasi", icon validasi form. | `bg-success/10 text-success border-success/20`<br>`color="green"`, `<x-ts-alert color="success">` | Menandakan kondisi positif permanen atau penyelesaian sukses dari suatu interaksi pengguna. |
| **`warning`** | **"Peringatan Dini / Kehati-hatian"**<br>Perhatian bahwa ada batas kuota, masa tenggang, atau aksi yang membutuhkan ketelitian. | Alert kuota hampir habis, badge "Revisi Diperlukan", pengingat tenggat waktu, konfirmasi aksi berdampak luas. | `bg-warning/15 text-warning border-warning/30`<br>`color="amber"`, `<x-ts-alert color="warning">` | **Wajib menyertakan instruksi tindakan konkret** (*actionable guidance*) agar pengguna tahu apa yang harus diperiksa atau diperbaiki. |
| **`error` / `danger`** | **"Kesalahan Kritis / Tindakan Destruktif"**<br>Peringatan kegagalan fatal, data tidak valid, atau aksi berbahaya yang tidak dapat dibatalkan. | Validasi form merah di bawah input, alert kegagalan proses, badge "Ditolak", "Dibatalkan", tombol "Hapus Data". | `bg-error/10 text-error border-error/20`<br>`color="red"`, `<x-ts-alert color="error">` | **Semua tombol tindakan destruktif wajib disertai dialog konfirmasi dua tahap** (`askDelete()` → `confirmDelete()`). |

#### 2. User-Oriented Composition Patterns

1. **Form Actions Toolbar (Hierarki Tindakan Formulir):**
   - **Kiri / Sekunder:** Tombol `ghost` `[ Batal ]` — tidak membebani perhatian visual pengguna saat mengisi form.
   - **Kanan / Utama:** Tombol `primary` `[ Simpan Perubahan ]` — penanda jelas titik penyelesaian alur.
   - **Opsional Perantara:** Tombol `secondary` `[ Simpan Draf ]` — alternatif aman bagi pengguna yang ingin melanjutkan nanti.

2. **Destructive Confirmation Modals:**
   - **Kiri:** Tombol `ghost` `[ Batal ]` (opsi pelarian aman).
   - **Kanan:** Tombol `error` / `danger` `[ Ya, Hapus Data ]` (warna merah tegas menandai konsekuensi permanen).

3. **Table & Datagrid Actions:**
   - Tombol aksi per baris (row actions) **wajib menggunakan varian `ghost`** (icon-only dengan tooltip dan `aria-label`). Menghindari tampilan tabel menyerupai "hutan tombol".

4. **Status Badges Lifecycle Alignment:**
   - `neutral`: "Draf", "Belum Diajukan", "Diarsipkan"
   - `info`: "Diajukan", "Menunggu Verifikasi", "Dijadwalkan"
   - `warning`: "Perlu Revisi", "Mendekati Batas Waktu", "Verifikasi Tertunda"
   - `success`: "Disetujui", "Terverifikasi", "Aktif", "Lulus"
   - `error`: "Ditolak", "Dibatalkan", "Gagal", "Kedaluwarsa"

---

### 3.2 The Two Signals — Light / Dark

Each page carries **two** attributes on `<html>` (set in `resources/views/ui/layouts/base.blade.php`):

| Signal | Source | Purpose |
| ------ | ------ | ------- |
| `data-theme` (`light`/`dark`/`system`) | `theme` cookie (`request()->cookie('theme', 'system')`) | Selects the injected CSS-property block (`html[data-theme='light']` / `html[data-theme='dark']`) |
| `.dark` class | present when the cookie equals `dark` | Drives Tailwind/TallstackUI `dark:` variant (`@custom-variant dark`) and static status-token overrides |

The `@custom-variant dark (&:where(.dark, .dark *))` in `resources/css/app.css` means the
`dark:` variant keys off the `.dark` class only, while the semantic brand/surface properties key off
`data-theme`. Both must stay in sync — `resources/js/app.js` `applyTheme()` sets them together and
mirrors to the `theme` cookie for SSR accuracy, preventing flash-of-unstyled-content (FOUC).

### 3.3 Resolution Chain

Theme values never hardcode a color. Each slot resolves at runtime:

```
DB setting (cached forever) → Theme::defaults() from config → hardcoded fallback

```

`app/Modules/Setting/Domain/Theme/Support/Theme.php` exposes the chain:

- `defaults()` → `config('settings.colors.defaults')`
- `get($key)` → `all()[key] ?? defaults()[key] ?? '#000000'`
- `base()` → `getSetting('base_color', defaults()['base'])`
- `all()` → primary/secondary/accent/accent from DB, `base` via `base()`, `content` via `Color::computeBaseShades(base())['content']`

### 3.4 Brand Color Schema (Configurable)

Four brand colors are configurable in the admin panel (`SystemSetting` → Branding form;
`app/Modules/Setting/Livewire/SystemSetting.php`), stored as **`*_color`** settings, validated as
`#RRGGBB` hex in `BrandingForm`, and written via `SaveSystemSettingsAction`.

**Default palette is green (Emerald) — `primary = #059669`:**

| Slot | Setting key | Default (`config/settings.php`) | Usage |
| ---- | ----------- | ------------------------------- | ----- |
| Primary | `primary_color` | `#059669` (Emerald green) | Main actions, links, active navigation |
| Secondary | `secondary_color` | `#6b7280` | Supporting elements |
| Accent | `accent_color` | `#f97316` | Highlights, call-to-action |
| Base | `base_color` | `#ffffff` | Page/surface background anchor |

`content_color` is declared in `config('settings.theme_cache_keys')` for invalidation but the resolved
content color is **computed**, not read from a setting — see §3.6.

### 3.5 Presets

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

### 3.6 Color Computation (`app/Modules/Core/Support/Color.php`)

`Theme::cssVariables()` computes these values using `Color`:

**Base surface hierarchy from `base_color`:**
- `computeBaseShades($hex)` (light theme) — if `relativeLuminance > 0.5` (light base): `base-100 = base`, `base-200 = darken 3%`, `base-300 = darken 6%`, `content = #1a1a1a`. Dark base inverts the logic: lighten by 10%/20%, `content = #f0f0f0`.
- `computeDarkShades($hex)` (dark theme) — returns a fixed **black** surface scale (`base-100` = `#262626` neutral-800, `base-200` = `#171717` neutral-900, `base-300` = `#0a0a0a` neutral-950, `content` = `#e5e5e5`) so the dark background reads as **black, not gray**. `base-200` is the main page background (`bg-base-200` across the app/guest/auth/home layouts), `base-100` are elevated surfaces (cards/modals), `base-300` borders/dividers.

**Brand contrast + dark mode for primary/secondary/accent:**
- `--color-{key}-content` / `--{c}` = `contrastColor($hex)` — dark `#1a1a1a` when luminance > 0.5, else light `#f0f0f0`.
- Dark theme lightens each brand color by **40%** (`Color::lighten($hex, 40)`) for visibility on dark backgrounds; dark brand content is fixed `#ffffff`.

### 3.7 Injected CSS Variables

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
| `--color-info` | `oklch(60% 0.16 250)` | `oklch(70% 0.14 250)` |
| `--color-success` | `oklch(58% 0.15 150)` | `oklch(70% 0.14 150)` |
| `--color-warning` | `oklch(75% 0.15 80)` | `oklch(80% 0.14 80)` |
| `--color-error` | `oklch(57% 0.19 25)` | `oklch(66% 0.17 25)` |

Chroma stays below ~0.2: values above that leave the sRGB gamut and the browser clamps them to the
nearest primary, which is what previously made status badges and funnel bars read as neon
(`resources/css/app.css` carries the same note). `resources/css/app.css` is the single source of
truth for these numbers — treat this table as a mirror, never as the value to copy from.

Each status token also ships a `-content` counterpart (e.g. `--color-success-content`) that flips
between white and near-black in dark mode. These are pre-validated for WCAG contrast — never override
them with arbitrary utilities (see §7.1).

### 3.8 DaisyUI Shim Bridge

`app.css` `@layer components` re-declares the legacy DaisyUI component classes (`.btn`,
`.badge`, `.table`, `.card`, `.alert`, `.input`, `.menu`, …) that still appear across committed views,
mapping them to the CSS variables above (`background: var(--color-primary)`, etc.). This keeps old
class-based markup styled while the codebase migrates to `x-ts-*` components. There is **no DaisyUI
npm package**.

### 3.9 Cache Invalidation

`Settings::forget()` (and `forgetGroup()`) invalidates the theme variable cache. `Settings::forget()`
checks `in_array($key, config('settings.theme_cache_keys'))` — the keys `primary_color`,
`secondary_color`, `accent_color`, `base_color`, `content_color`, `logo`, `favicon`, `name`,
`title` — and calls `Cache::forget(theme_css_variables)` when a matching key changes. So saving a new
brand color invalidates the 1-hour CSS-variable cache and the change appears on the next page render
without `php artisan cache:clear`.

### 3.10 Theme Switcher & Dark Mode

Dual-signal dark mode with a three-state switcher (light / dark / system):

- **Component:** `resources/views/setting/locale/theme-switch.blade.php`, consumed as
  `<x-setting::locale.theme-switch>`; it wraps TallstackUI's `<x-ts-dropdown>` with Alpine's
  `tallstackui_darkTheme()` scope (button icon/label follow the current mode) and is **not** a
  Livewire component. It lives in the Setting module's `Locale` domain because the choice persists
  beyond the shell.
- **Persistence:** the three dropdown items set `mode` and `$dispatch('theme', { mode })`; `app.js`
  `applyTheme()` stores the preference in `localStorage` `dark-theme` (`light`/`dark`/`system` plus
  legacy `true`/`false`) and sets `data-theme` + `.dark` + the `theme` cookie.
- **Legend:** the `theme` cookie → server sets `class="dark"` in `base.blade.php` already at first
  render, so the correct theme is applied before hydration.

---

## 4. Responsive Strategy

Internara employs a strict mobile-first responsive architecture designed to render seamlessly across small, medium, and large screens without layout jitter, horizontal scrolling, or truncated interactions.

### 4.1 Screen Classification & Adaptive Rules

| Screen Class | Breakpoint | Target Devices | Layout & Sizing Invariants |
| ------------ | ---------- | -------------- | -------------------------- |
| **Small (Mobile)** | `< 640px` (base) | Smartphones (320px–639px) | • 100% full-width cards (`w-full`) with `px-4`.<br>• Single column stacks (`grid-cols-1`, `flex-col`).<br>• Compact sticky headers (`h-14`) with hamburger navigation.<br>• Minimum touch target size ≥ 44×44px.<br>• Zero horizontal scrolling at 320px viewport width (WCAG 1.4.10 Reflow).<br>• Secondary table columns hidden via `hidden sm:table-cell`. |
| **Medium (Tablet)** | `640px – 1023px` (`sm:` & `md:`) | Tablets, iPad, foldable devices (640px–1023px) | • Balanced two-column grid (`sm:grid-cols-2`).<br>• Comfortable fluid horizontal padding (`px-6`).<br>• Form and button actions transition from stacked to horizontal inline clusters (`sm:flex-row`, `sm:w-auto`).<br>• Reading measure constrained to optimal line length (`max-w-xl` / `max-w-2xl`).<br>• Drawer sidebar with backdrop overlay on tablet portrait. |
| **Large (Desktop)** | `≥ 1024px` (`lg:` & `xl:`) | Laptops, desktops, ultrawide monitors (≥1024px) | • Multi-column layouts (`lg:grid-cols-3` or `lg:grid-cols-4`).<br>• Persistent sidebar navigation (`lg:drawer-open`).<br>• Compact, viewport-optimized vertical rhythm (`py-6 sm:py-8 lg:py-10`) so key workflows fit within a single screen without vertical scroll.<br>• Max containers: `max-w-7xl` for main application portals, `max-w-md` for auth views, and `max-w-4xl` for landing/guest flows. |

### 4.2 Form & Card Adaptability

1. **Authentication & Solo Cards:**
   - `<sm`: Full screen width with `px-4`, form fields full width, actions stacked.
   - `sm`: Centered with `max-w-md`, comfortable card padding `p-6`, horizontal remember-me and action links.
   - `lg`: Stays compact (`max-w-md`) with soft shadow, centered vertically within the viewport.

2. **Dashboard & Feature Grids:**
   - `<sm`: `grid-cols-1 gap-3.5`.
   - `sm`: `sm:grid-cols-2 gap-4`.
   - `lg`: `lg:grid-cols-3` or `lg:grid-cols-4` with `gap-5`.

3. **Tables & Complex Datagrids:**
   - Tables are wrapped inside `<div class="overflow-x-auto rounded-lg border border-base-content/10">`.
   - Primary identifier stays visible on all screens; auxiliary metadata columns collapse on `<md`.

---

## 5. Typography & Visual Hierarchy

> 📖 Authoritative references: [UI Pattern](../arch/ui-pattern.md) §1 (hierarchy, whitespace, grid) and §5 (type utilities); [UX Pattern](../arch/ux-pattern.md) §2 (WCAG 2.2 AA reading order and focus). This section is the normative scale — other guides reference it instead of restating it.

Typography carries the reading order; colour, spacing, and curvature support it and never replace it.
Two rules frame everything below:

1. **Heading level = document structure; utility classes = visual size.** The level is chosen from the
   content outline (it is what assistive technology announces); the size is chosen from the scale
   below. `<h3 class="text-sm font-semibold">` is correct when the structure is an `h3` and the visual
   weight is card-level. Picking `<h2>` because it *looks* the right size is not.
2. **Hierarchy must survive without colour.** Every tier stays distinguishable in grayscale, in dark
   mode, and for colour-blind readers. Colour may reinforce a tier, never be its only signal
   (WCAG 1.4.1 Use of Color).

### 5.1 Typeface & weight range

One family, self-hosted, no runtime fetch: **Instrument Sans** (`--font-sans` in
`resources/css/app.css`), `font-display: swap`. Monospace (`--font-mono`) is reserved for codes,
tokens, IDs, and numeric tables.

The family ships **four cuts — 400 / 500 / 600 / 700 — and nothing heavier**. Instrument Sans has no
Black (900) and no ExtraBold (800); the variable range stops at `400..700`. Requesting
`font-extrabold` or `font-black` forces the browser to synthesise a faux-bold face (dilated stems,
wrong metrics), so both are **forbidden**.

| Utility | Weight | Use |
| ------- | ------ | --- |
| `font-normal` | 400 | Body copy, table cells, descriptions, help text |
| `font-medium` | 500 | Inline emphasis, current breadcrumb, active nav item, form labels |
| `font-semibold` | 600 | Card and sub-panel titles, table headers, buttons, badges |
| `font-bold` | 700 | Page, section, and card headings; KPI values |
| ~~`font-extrabold`~~ / ~~`font-black`~~ | 800 / 900 | Not shipped — synthesised, forbidden |

### 5.2 Heading scale (H1–H6)

Four tiers carry the visual hierarchy, which is what the codebase already reflects (`h1` 15, `h2` 35,
`h3` 72, `h4` 114 occurrences; `h5`/`h6` zero). H5 and H6 are **structural only** — they add no new
size. If a screen seems to need a fifth visual tier, the nesting is the problem, not the scale.

| Level | Semantic role | Classes | Size / weight |
| ----- | ------------- | ------- | ------------- |
| `h1` | Page title — **exactly one per page** | `text-2xl font-bold tracking-tight` | 24px / 700 |
| `h2` | Major section of a page | `text-xl font-bold tracking-tight` | 20px / 700 |
| `h3` | Card, panel, or modal title | `text-lg font-bold` | 18px / 700 |
| `h4` | Title inside a card (field group, sub-panel) — the workhorse | `text-sm font-semibold` | 14px / 600 |
| `h5` | Dense group label inside an `h4` block — no size of its own | `text-sm font-semibold text-base-content/70` | 14px / 600, muted |
| `h6` | Overline / eyebrow — at most 3 words | `text-xs font-bold uppercase tracking-wider` | 12px / 700 |

- **`h1` comes from a component, not from markup.** `<x-ui::components.page-header :title="…">` for
  pages and `<x-ui::components.record-manager>` for list screens; both render the canonical `h1`. The
  layout `$header` prop also contains an `h1` (`ui/layouts/header.blade.php`), but **no view passes
  it** — leaving it unset is what keeps one `h1` per page. Never pass `header="…"` to a page that
  already renders `page-header`.
- **Hero exception.** Landing/marketing surfaces may scale `h1` to `text-3xl sm:text-4xl lg:text-5xl`
  with `text-balance`; admin surfaces never do.
- **Compact shells are the other exception.** Auth cards and the setup wizard render their `h1` at
  `text-base`/`text-lg` because the shell is a single narrow panel — level is what matters, not size.
  The rule this must never break is *one `h1` per page*: an auth page whose only heading was `h2`
  announced no page title at all.
- **Desktop scaling** is `sm:`-based and reserved for the `h1` hero variant; section and card headings
  stay fixed, so tiers cannot collide at any breakpoint. No viewport-jacked `clamp()` outside hero
  surfaces.
- **Do not skip levels.** An `h4` directly under an `h2` breaks the announced outline (WCAG 1.3.1);
  insert the `h3`, visually as `text-sm font-semibold` when the design does not call for a bigger step.
- **`uppercase` belongs to `h6` overlines only** (≤ 3 words, with `tracking-wider`) — never on a
  sentence, and never on `h1`–`h4`.

### 5.3 Body, meta, and numeric sizes

| Role | Classes | Size |
| ---- | ------- | ---- |
| Body copy on dense admin surfaces | `text-sm leading-relaxed` | 14px / 1.6 |
| Reading passages (guides, legal, long-form) | `text-base leading-relaxed` | 16px / 1.6 |
| Subtitle under an `h1`/`h2` | `text-sm text-base-content/60` | 14px |
| Meta, timestamp, helper | `text-xs text-base-content/60` | 12px |
| Overline / badge label | `text-xs font-semibold uppercase tracking-wide` | 12px |
| KPI value | `text-2xl font-bold tabular-nums` | 24px |
| Code, token, ID | `font-mono text-xs` | 12px |

**`text-xs` (12px) is the floor** for text a user must read: no `text-[11px]`, and no inline
`style="font-size:27px"` — the scale is the only source of sizes. `tracking-tight` belongs to `h1`–`h2`
only; body copy keeps Tailwind's default tracking.

### 5.4 Visual hierarchy

Priority is expressed with four levers, **in this order**; reach for the next only when the previous
is exhausted:

1. **Size** — a step on the scale (§5.2, §5.3).
2. **Weight** — 400 → 500 → 600 → 700, never beyond 700.
3. **Colour and opacity** — `text-base-content` → `/70` → `/60` → `/50` de-emphasises; semantic
   variants (`primary`, `error`, `success`) carry *meaning*, not decoration.
4. **Space** — density and grouping (`space-y-*`, `gap-*`, `py-*`) separates blocks without visual noise.

Decoration — gradients, shadows, borders, tinted surfaces — is **not** a hierarchy lever; it may only
reinforce a tier that already reads correctly without it.

- **Adjacent tiers must differ.** Two levels are visually distinct when they differ by ≥ 2px size,
  one weight step, or ≥ 20% opacity. `h2` and `h3` both at `text-lg` is a defect — which is how 9
  `h2`s drifted into `text-base font-bold sm:text-lg` (#550).
- **The `h2` → `h3` step is the tightest one** (20px → 18px, both weight 700). Do not compensate by
  inflating `h3`; compensate with the space lever — `mt-8`/`mb-4` before an `h2`, `mt-4`/`mb-2` before
  an `h3`. If two blocks still read as siblings, the section boundary is wrong, not the font size.
- **One primary action per viewport** (invariant of the semantic-variant system, §3.1) — typography
  supports it by never out-shouting the primary CTA with a heading.
- **Squint test.** Blur the screen: title → section → card → content must still separate in that order.
  If two tiers merge, one of them is wrong.
- **Grayscale test.** With saturation removed, the hierarchy must survive (rule 2 above).
- **DOM order is reading order.** No visual reordering that contradicts markup order — the heading
  sequence *is* the screen-reader outline.
- **Focus lands on the heading** after `wire:navigate` (§8), so the `h1` must carry the page name.

### 5.5 Anti-patterns

| Anti-pattern | Replace with | Why |
| ------------ | ------------ | --- |
| `font-black` / `font-extrabold` | `font-bold` | 800/900 not shipped; faux-bold synthesis (#548) |
| `<h2>` chosen to get a bigger font | `<h3>` + explicit size class | Level is structure; corrupted outline (WCAG 1.3.1) |
| `h2` and `h3` both `text-lg` | `text-xl` for `h2`, `text-lg` for `h3` | Collapsed tiers — no perceptible hierarchy |
| `style="font-size:27px"` | Nearest scale step | Escapes the scale and token audit |
| `text-[13px]`, `text-[28px]` | `text-sm`, `text-3xl` | Arbitrary values escape review |
| Two `h1`s (`header` prop + `page-header`) | One source: `page-header` / `record-manager` | Heading map must be unambiguous |
| `uppercase` on a sentence or on `h1`–`h4` | Uppercase only on `h6` overlines | Uppercase destroys word-shape legibility |
| Gradient or coloured heading as priority signal | Size + weight + space | Decoration fails in dark mode and for colour blindness |

---

## 6. View Namespaces

Each module's view directory (`resources/views/{module}/`) is registered as a Blade namespace by
`AppServiceProvider::registerBladeNamespaces()`.

| Pattern             | Syntax                | Example                |
| ------------------- | --------------------- | ---------------------- |
| Anonymous component | `x-{module}::name`    | `x-setup::brand`       |
| View include        | `{module}::view.name` | `setup::layouts.setup` |

Excluded directories: `components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor`.

---

## 7. Accessibility (WCAG 2.2 AA)

> 📖 Authoritative references: [UX Pattern](../arch/ux-pattern.md) §2 — WCAG 2.2 AA (Perceivable/Operable/Understandable/Robust), focus, ARIA, `aria-live`; [UI Pattern](../arch/ui-pattern.md) §7 — forms/tables/modals/navigation a11y. This section is the UI-layer checklist.

All user-facing interfaces MUST meet WCAG 2.2 Level AA. This section defines UI-layer
requirements. See `docs/guides/arch/modular-pattern.md` §22 for architectural rules and
`docs/guides/arch/livewire-pattern.md` §13 for component-specific patterns. The 2.2-only criteria
add: 2.4.11 Focus Not Obscured, 2.4.13 Focus Appearance, 2.5.8 Target Size (Minimum, 24×24px),
3.3.7 Redundant Entry, and 3.3.8 Accessible Authentication.

### 7.1 Color & Contrast

- **Minimum contrast ratios:** 4.5:1 for normal text, 3:1 for large text (≥18pt or ≥14pt bold),
  3:1 for UI components and graphical objects.
- **Semantic palette colors** (`--color-success`, `--color-warning`, etc. in `resources/css/app.css`) are pre-validated for contrast. Never override with arbitrary Tailwind color utilities that fail contrast checks.
- **Color is never the sole indicator:** Status badges (success/warning/error), capacity gauges,
  and validation states must include text labels, icons, or patterns alongside color (see UX Pattern §2.1).

### 7.2 Keyboard Navigation

- **Tab order:** Must follow logical reading order (top-to-bottom, left-to-right for LTR). No
  positive `tabindex` values.
- **Focus indicators:** Every focusable element must have a visible focus ring. TallstackUI provides
  `focus:ring` by default — do not suppress with `outline-none` without a visible replacement.
- **Skip links:** Every page with navigation must provide a "Skip to main content" link as the
  first focusable element in the DOM.
- **Interactive elements:** All buttons, links, form fields, modals, and dropdowns must be
  reachable and operable via keyboard alone (Enter, Space, Arrow keys, Escape).

### 7.3 Modal & Dialog Focus

- **Focus trap:** Modals (`x-ts-modal`, `x-ts-dialog`) must trap focus within the modal when open.
- **Focus return:** On modal close, focus must return to the trigger element.
- **Escape key:** All modals and dropdowns must close on Escape key press (TallstackUI default).

### 7.4 Screen Reader Support

- **ARIA landmarks:** Layout must use semantic HTML5 elements: `<nav>` (sidebar), `<main>`
  (content), `<header>` (top bar), `<footer>`. The app layout provides these by default.
- **aria-live for dynamic content:** Flash messages, Livewire partial updates, and real-time
  validation feedback must be wrapped in `aria-live="polite"` (or `"assertive"` for errors)
  containers.
- **Icon-only buttons:** Any button or link with only an icon must include `aria-label`:
  `<button aria-label="Close">`.
- **Image alt text:** All `<img>` tags require `alt`. Decorative images use `alt=""`.

### 7.5 Form Accessibility

- **Labels:** Every form input must have an associated `<label>` (via `for`/`id` or wrapping
  TallstackUI `label` prop). Placeholder text is not a label substitute.
- **Required indicators:** Use the `required` HTML attribute (TallstackUI `required` prop), not just
  visual asterisks.
- **Error messaging:** Validation errors must be associated with their field via `aria-describedby`
  and announced to screen readers via `aria-live` regions. TallstackUI handles this automatically.
- **Error focus:** After failed validation, focus must move to the first invalid field or an error
  summary.

### 7.6 Content Reflow

- No horizontal scrolling at 320px viewport width (WCAG 1.4.10).
- Responsive breakpoints must prevent content clipping or overlap.
- Tables must reflow to card layout or horizontal scroll with visible scroll indicators on mobile.

---

## 8. SPA Navigation

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

## 9. Routing

### URL Structure

Routes follow a predictable, human-readable URL pattern:

| Scope          | Pattern                         | Example                                  |
| -------------- | ------------------------------- | ---------------------------------------- |
| Guest          | `/{resource}`                   | `/apply`, `/login`                       |
| Authenticated  | `/{resource}`                   | `/registration`, `/dashboard`            |
| Student        | `/student/{module}/{resource}`  | `/student/journals/logbook`              |
| Teacher        | `/teacher/{module}/{resource}`  | `/teacher/journals/logbook`              |
| Supervisor     | `/partner-assessment/{token}`   | `/partner-assessment/{token}` (magic link)|
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

## 10. Localization

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

The `LangSwitch` Livewire component (`app/Modules/Setting/Domain/Locale/Livewire/LangSwitch.php`) toggles between
EN and ID (plain Tailwind trigger with globe icons; no TallstackUI built-in). Locale preference is stored in a cookie (`locale`) and applied via `SetLocale` middleware
on every request.

### Theme Switcher

The theme switcher is **not** a Livewire component. It is the Blade partial `resources/views/setting/locale/theme-switch.blade.php`, consumed as `<x-setting::locale.theme-switch>`, wrapping TallstackUI's `<x-ts-theme-switch>` (`tallstackui_darkTheme` Alpine scope). See §3 for persistence and JS wiring. Legacy `ThemeSwitcher.php` Livewire component and its test were removed in `0.15.0`.

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

## 11. Component Library Patterns

### TallstackUI Components (`x-ts-*`)

All UI is built with TallstackUI v4 (`prefix ts-`, see `config/tallstackui.php`):

- **Layout:** `x-ts-layout`, `x-ts-side-bar`, `x-ts-dropdown`, `x-ts-tooltip`
- **Data:** `x-ts-table` (sorting, pagination, row selection; headers via `index` key), `x-ts-card`
- **Forms:** `x-ts-input`, `x-ts-select.native`, `x-ts-textarea`, `x-ts-radio`, `x-ts-checkbox`, `x-ts-toggle`, `x-ts-file` with validation styling
- **Actions:** `x-ts-button`, `x-ts-badge`, `x-ts-alert`, `x-ts-icon`
- **Overlays:** `x-ts-modal`, `x-ts-dialog` (confirm), `x-ts-slide`
- **Feedback:** TallstackUI toast via `toast()->success()` Interactions (replaces PHPFlasher `flash()->`)

Legacy DaisyUI class tokens (`.btn`, `.badge`, `.card`, `.table`, `.alert` etc.) remain as class names but are shimmed locally in `resources/css/app.css` `@layer components` — no DaisyUI npm package.

### Form Input Design System

Form inputs are the primary mutation surface across all 19 modules. All inputs must adhere to strict visual consistency, accessibility (WCAG 2.2 AA), and responsive behavior using TallStackUI primitives (`x-ts-*`).

#### 1. Input Anatomy & Token Specifications

Every input field is composed of five standard layers:

| Layer | Token / Class | Description |
|---|---|---|
| **Label** | `text-xs font-semibold text-base-content/70` | Descriptive title above field; required fields append visual asterisk `*` |
| **Input Surface** | `bg-base-100 border border-base-content/10 rounded-md` | Neutral background, subtle border, balanced rounded corners (scale xs–l) |
| **Focus Ring** | `focus:ring-2 focus:ring-primary/20 focus:border-primary` | Visible focus indicator driven by semantic `--color-primary` (Emerald `#059669`) |
| **Hint / Caption** | `text-xs text-base-content/50 mt-1` | Supplementary contextual help text below input |
| **Validation Error** | `text-xs text-error mt-1` | High-contrast error message linked via `aria-describedby` |

Touch target heights must maintain a minimum of 40px–44px (`h-10` to `h-11`) to satisfy WCAG 2.5.8 (Target Size Minimum).

#### 2. Form Input Component Matrix

Internara standardizes input fields using TallStackUI v4 components (`prefix ts-`):

```blade
{{-- 1. Standard Text Input with Icon --}}
<x-ts-input
    :label="__('academic_year.name')"
    wire:model="form.name"
    :placeholder="__('academic_year.name_placeholder')"
    icon="academic-cap"
/>

{{-- 2. Search Field with Debounce and Clearable Button --}}
<x-ts-input
    :placeholder="__('common.search_placeholder')"
    wire:model.live.debounce.300ms="search"
    icon="magnifying-glass"
    clearable
/>

{{-- 3. Date Input with Calendar Icon --}}
<x-ts-input
    type="date"
    :label="__('academic_year.start_date')"
    wire:model="form.start_date"
    icon="calendar"
/>

{{-- 4. Native Select Dropdown --}}
<x-ts-select.native
    :label="__('journals.absence.reason_type')"
    wire:model="reasonType"
    :options="ts_options($reasonTypes, __('journals.absence.select_reason'))"
/>

{{-- 5. Textarea with Configurable Rows --}}
<x-ts-textarea
    :label="__('journals.absence.description')"
    wire:model="reasonDescription"
    rows="4"
    :placeholder="__('journals.absence.description_placeholder')"
/>

{{-- 6. Boolean Toggle Switch --}}
<x-ts-toggle
    :label="__('user.is_active')"
    wire:model="form.is_active"
    color="primary"
/>

{{-- 7. File Upload Input --}}
<x-ts-upload
    :label="__('document.upload_file')"
    wire:model="documentFile"
    accept="application/pdf,image/png,image/jpeg"
    hint="PDF, PNG, JPG (Maks. 5MB)"
/>
```

#### 3. Standard Form Layout Patterns

##### A. Modal Form (Create / Edit Dialogs)
Used inside `x-ts-modal` for `BaseRecordManager` workflows:
- **Spacing:** `space-y-4` container.
- **Grid:** Single column for full-width fields; 2-column grid (`grid grid-cols-1 gap-4 sm:grid-cols-2`) for paired attributes (e.g., start date & end date, first name & last name).
- **Footer Actions:** Right-aligned button group with `Cancel` (`color="slate" outline sm`) and `Save/Update` (`color="primary" sm loading="store"`).

```blade
<x-ts-modal wire="showModal" :title="$editingId ? __('common.edit') : __('common.create')" blur>
    <div class="space-y-4">
        <x-ts-input :label="__('module.title')" wire:model="form.title" icon="document-text" />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ts-input type="date" :label="__('module.start_date')" wire:model="form.start_date" />
            <x-ts-input type="date" :label="__('module.end_date')" wire:model="form.end_date" />
        </div>
    </div>
    <x-slot:footer>
        <div class="flex justify-end gap-2">
            <x-ts-button :text="__('common.actions.cancel')" color="slate" outline sm wire:click="$set('showModal', false)" />
            <x-ts-button :text="__('common.actions.save')" color="primary" sm wire:click="store" loading="store" />
        </div>
    </x-slot:footer>
</x-ts-modal>
```

##### B. Full Page Form (Multi-section / Wizard)
Used for setup wizards, profile editors, and complex submission forms:
- **Container:** Wrapped in `<x-ts-card shadowless class="border border-base-content/10 bg-base-100">` with `max-w-4xl mx-auto`.
- **Sections:** Separated by section headings (`text-sm font-semibold text-base-content/80`) and dividers (`h-px bg-base-300 my-4`).
- **Responsive Stacking:** Desktop 2-column or 3-column grids collapse to single-column at `<sm` (320px–640px) without horizontal scroll.

#### 4. Interaction States & Validation Feedback

- **Default:** Subtle border `border-base-content/10`, clean contrast, readable placeholder.
- **Hover & Focus:** Border transitions to `border-primary` with subtle glow ring `focus:ring-2 focus:ring-primary/20`.
- **Validation Failure:** Border turns `border-error` (`--color-error`), displays red helper text, and screen reader receives `aria-invalid="true"`.
- **Submission Loading:** Submit button disables automatically and renders an animated spinner via `loading="submit"`, preventing double-submits.

---

## 12. Guide Component Pattern

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

## 13. Key Locations

| Asset             | Path                                      |
| ----------------- | ----------------------------------------- |
| Layout templates  | `resources/views/ui/layouts/`           |
| UI components     | `resources/views/ui/components/`          |
| Theme switcher    | `resources/views/setting/locale/theme-switch.blade.php` (`<x-setting::locale.theme-switch>`) |
| CSS entry point   | `resources/css/app.css` (`@import tallstackui/css/v4.css` + self-hosted palette + shims) |
| JS entry point    | `resources/js/app.js` (theme + flatpickr + marked + choices bridge) |
| Theme resolver    | `app/Modules/Setting/Domain/Theme/Support/Theme.php` (`cssVariables()`, `defaults()`, `presets()`, `all()`) |
| Color utility     | `app/Modules/Core/Support/Color.php` (base shades, contrast, lighten/darken) |
| Color presets     | `config/settings.php` (`colors.defaults` + `colors.presets`, `theme_cache_keys`) |
| Branding form     | `app/Modules/Setting/Domain/Branding/Livewire/Forms/BrandingForm.php` (preset apply/detect) |
| Admin color UI    | `app/Modules/Setting/Livewire/SystemSetting.php` |
| TallstackUI config| `config/tallstackui.php` (`prefix ts-`)   |
| Sidebar menu      | `config/menu.php`                         |
| Language switcher | `app/Modules/Setting/Domain/Locale/Livewire/LangSwitch.php`  |
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
