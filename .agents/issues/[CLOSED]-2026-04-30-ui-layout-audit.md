[CLOSED] # UI/UX Layout Hierarchy Audit & Migration Gap

**Date:** 2026-04-30  
**Type:** Architecture / UI Audit  
**Priority:** P2 — Visual consistency & UX quality  
**Status:** CLOSED — Partially fixed, remaining deferred to Phase 3

---

## Resolution Summary (2026-05-01)

✅ **Fixed (Step 8-9):**
- `auth.blade.php` now extends `x-layouts.base` instead of standalone DOCTYPE
- `@livewireStyles`/`@livewireScripts` moved to base layout
- CSRF meta added to `base/head.blade.php`
- Skip-to-content link added (WCAG)
- ReportsManager migrated to maryUI ✅

⏳ **Deferred (Phase 3):**
- `dashboard.blade.php` layout — needs sidebar/drawer implementation
- `wizard.blade.php` layout — needs progress bar + step navigation
- `with-navbar` wrapper — needs sticky nav + scrollable content
- Scaffolded views (AcademicYear, Handbook, Schedule) — keep plain HTML (root cause: `$this` context error in non-Livewire views)

**Result:** Layout hierarchy gaps documented, critical fixes applied, decision recorded for remaining work.

---

## Executive Summary

The current `app/` layout system is **structurally incomplete** compared to the legacy `modules/` UI. The current implementation has a flat, inconsistent hierarchy that causes:

1. **Duplication** — `auth.blade.php` is a standalone DOCTYPE document instead of extending `base.blade.php`
2. **Missing layers** — No dedicated `dashboard` layout, `wizard` layout, or `with-navbar` wrapper
3. **Inconsistent styling** — Scaffolded views use plain HTML while existing admin views use maryUI components
4. **Regressed UX** — No preloader, no skip-to-content link, no mobile sidebar/drawer, no sticky action footers

The legacy module system had **7 layout types** with clear responsibilities. The current system has **4**, and the responsibilities are blurred.

---

## Current Layout Hierarchy (`app/`)

```
resources/views/components/layouts/
├── base.blade.php           ← Root: minimal DOCTYPE, head, body, slot
│   └── base/head.blade.php  ← Meta, fonts, vite, static favicon
│
├── app.blade.php            ← Extends base: header slot, main, footer (with app-signature)
│
├── auth.blade.php           ← Standalone DOCTYPE ❌ (should extend base)
│                              Centered card layout, brand, app-signature
│
└── header.blade.php         ← Standalone component (not a layout)
                               Sticky navbar, role-based nav links, maryUI dropdown
```

**Problems:**
- `auth.blade.php` does NOT extend `base.blade.php` — duplicates `<html>`, `<head>`, `<body>` tags
- `header.blade.php` is a component, not used by `app.blade.php` — `app.blade.php` uses a `$header` slot instead
- No `dashboard` layout (sidebar, drawer, mobile-responsive nav)
- No `wizard` layout (progress bar, step navigation, sticky footer)
- No `with-navbar` wrapper (sticky nav + scrollable content area)
- `base/head.blade.php` uses **static** favicon paths; legacy uses `setting()` for dynamic branding

---

## Legacy Layout Hierarchy (`modules/UI/` + `modules/Setup/`)

```
modules/UI/resources/views/components/layouts/
├── base.blade.php              ← Root: DOCTYPE, preloader, skip-to-content, flasher theme sync
│   ├── base/head.blade.php     ← Dynamic favicon from setting(), CSRF, manifest, DNS prefetch
│   ├── base/preloader.blade.php← Branded spinner with shimmer animation
│   └── with-navbar.blade.php   ← Extends base: sticky navbar + scrollable main + footer
│
├── setup.blade.php             ← Extends base: drawer layout with sidebar + hamburger + brand
│
├── dashboard.blade.php         ← Extends base: full navbar + drawer/sidebar + setup_required alert
│                                + email_unverified alert + context breadcrumb + max-w-7xl content
│
└── (auth via modules/Auth/)
    └── auth.blade.php          ← Extends base: drawer layout, h-screen, centered auth content

modules/Setup/resources/views/components/layouts/
└── setup-wizard.blade.php      ← Progress bar + step dots navigation + sticky footer actions
                                  ← Defines 6 steps with icons, labels, wire:click navigation
```

**Strengths of legacy:**
- All layouts extend `base` — single source of truth for HTML shell
- `preloader` — branded loading experience on every page
- `skip-to-content` — WCAG accessibility requirement
- `flasher theme sync` — toast notifications match theme automatically
- `setup-wizard` — proper step navigation with progress dots, completion states
- `dashboard` — role-aware alerts (setup required, email unverified)
- `with-navbar` — sticky nav pattern for scrollable content
- Dynamic favicon/branding from `setting()`

---

## Detailed Gap Analysis

### 1. `base.blade.php` — Root Layout

| Feature | Legacy (`modules/UI`) | Current (`app/`) | Gap |
|---------|----------------------|------------------|-----|
| Preloader | ✅ Branded spinner + shimmer | ❌ Missing | **Regressed** |
| Skip-to-content link | ✅ `sr-only` accessibility | ❌ Missing | **Regressed (a11y)** |
| Flasher theme sync | ✅ MutationObserver | ❌ Missing | **Regressed** |
| Debug JS globals | ✅ `window.isDebugMode` etc. | ❌ Missing | Neutral (may be intentional) |
| Dynamic favicon | ✅ `setting('site_favicon')` | ❌ Static `/favicon.ico` | **Regressed** |
| Manifest / web app | ✅ `site.webmanifest` | ❌ Missing | **Regressed** |
| DNS prefetch | ✅ `config('app.url')` | ❌ Missing | Minor |
| CSRF meta | ✅ In head.blade.php | ❌ Only in auth.blade.php | **Inconsistent** |

### 2. `auth.blade.php` — Auth Layout

| Feature | Legacy (`modules/Auth`) | Current (`app/`) | Gap |
|---------|------------------------|------------------|-----|
| Extends base | ✅ `x-ui::layouts.base` | ❌ Standalone DOCTYPE | **Structural** |
| Drawer layout | ✅ Full drawer with sidebar | ❌ Simple centered card | **Regressed** |
| Honeypot | ✅ `<x-honeypot />` | ❌ Missing | **Security** |
| Custom scrollbar | ✅ `custom-scrollbar` class | ❌ Missing | Minor |
| `bodyClass` prop | ✅ Via base layout | ❌ Hardcoded classes | Minor |
| `@livewireStyles` / `@livewireScripts` | ❌ In base layout | ⚠️ In auth only | **Inconsistent** |

**Critical:** `auth.blade.php` being a standalone DOCTYPE means it doesn't inherit preloader, skip-to-content, flasher sync, or dynamic favicon from base. This is a structural regression.

### 3. `app.blade.php` — App Layout

| Feature | Legacy (`modules/UI::setup.blade.php`) | Current (`app/`) | Gap |
|---------|----------------------------------------|------------------|-----|
| Drawer/sidebar | ✅ Full responsive drawer | ❌ No sidebar at all | **Regressed** |
| Mobile hamburger | ✅ `label for="main-drawer"` | ⚠️ header.blade.php has button but no drawer | **Broken** |
| Sticky header | ✅ `sticky top-0 z-40` | ✅ `sticky top-0 z-50` | OK |
| Backdrop blur | ✅ `backdrop-blur-md` | ❌ Solid shadow | Minor |
| Brand with version | ✅ AppInfo + version badge | ✅ AppInfo via mary-avatar | OK |
| Footer | ✅ `x-ui::footer` | ✅ `app-signature` Livewire | OK |
| Header slot usage | Navbar component | `$header` prop (title only) | **Different pattern** |

**Critical:** `header.blade.php` dispatches `$dispatch('toggle-sidebar')` but there is no sidebar/drawer in `app.blade.php` to receive it. The mobile menu button is dead.

### 4. Missing Layouts (No equivalent in `app/`)

| Legacy Layout | Purpose | Current Equivalent | Gap |
|---------------|---------|-------------------|-----|
| `with-navbar.blade.php` | Sticky nav + scrollable content area | None | **Missing** |
| `dashboard.blade.php` | Full dashboard with sidebar + alerts | None | **Missing** |
| `setup-wizard.blade.php` | Wizard with progress dots, step nav, sticky footer | `setup-wizard.blade.php` (flat, inside Livewire) | **Flattened** |
| `auth.blade.php` (modules/Auth) | Drawer-based auth layout | `auth.blade.php` (standalone card) | **Simplified** |

### 5. Setup Wizard — Layout Pattern Regression

**Legacy** (`modules/Setup`):
```
setup-wizard.blade.php (layout)
├── Progress bar + step dots (desktop) / step badge (mobile)
├── $header slot → wizard-header.blade.php (badge + title + description)
├── $content slot → step-specific content card
└── $footer slot → action-footer.blade.php (back/skip/continue buttons)
```

**Current** (`app/`):
```
setup-wizard.blade.php (Livewire view, NOT a layout)
├── Progress bar only (no step dots, no mobile differentiation)
├── All 7 steps inline with @if($currentStep === N)
├── Buttons inline in each step (not a reusable footer)
└── No separate header/content/footer slots
```

**Impact:** The current wizard is a monolithic 188-line Livewire view. The legacy separated concerns: layout handles chrome, header handles title/badge, footer handles actions, each step is its own view file. The current approach is harder to maintain and doesn't scale if more steps are added.

### 6. Scaffolded Admin Views — Styling Inconsistency

| View | Styling | Pattern |
|------|---------|---------|
| `admin/school/school-profile.blade.php` | ✅ maryUI (`x-mary-header`, `x-mary-card`, `x-mary-input`) | Consistent with admin style |
| `admin/department/department-index.blade.php` | ✅ maryUI | Consistent |
| `admin/user-manager.blade.php` | ✅ maryUI | Consistent |
| `admin/academic-years/index.blade.php` | ❌ Plain HTML table | **Inconsistent** (new scaffold) |
| `admin/handbooks/index.blade.php` | ❌ Plain HTML table | **Inconsistent** (new scaffold) |
| `admin/schedules/index.blade.php` | ❌ Plain HTML table | **Inconsistent** (new scaffold) |
| `admin/reports/index.blade.php` | ❌ Plain HTML table | **Inconsistent** (new scaffold) |

The 4 newly scaffolded views use plain HTML because maryUI components caused `$this` context errors. This creates a split: old admin pages use maryUI, new admin pages use plain HTML. Both look different.

---

## Recommendations

### P2 — Structural Fixes (Required)

1. **Make `auth.blade.php` extend `base.blade.php`** — Remove duplicated HTML shell, ensure all pages share the same root.

2. **Move `@livewireStyles` to `base/head.blade.php`** and `@livewireScripts` to `base.blade.php` body — Currently they're only in `auth.blade.php`, which means pages using `app.blade.php` may not have them (depending on whether they're auto-injected).

3. **Add CSRF meta to `base/head.blade.php`** — Currently only in `auth.blade.php`.

### P3 — Feature Parity (Desired)

4. **Restore preloader** — Add `preloader.blade.php` component and include in `base.blade.php`.

5. **Add skip-to-content link** — WCAG requirement, one-liner in `base.blade.php`.

6. **Fix mobile sidebar** — Either add a drawer to `app.blade.php` or remove the dead `toggle-sidebar` dispatch from `header.blade.php`.

7. **Create `dashboard.blade.php` layout** — Scaffolded admin views should use a dashboard layout with sidebar navigation, not plain pages with `p-8` padding.

### P3 — Wizard Restructure (Desired)

8. **Extract wizard into layout + step views** — Create `resources/views/components/layouts/wizard.blade.php` with progress/header/footer slots. Extract each step into its own partial.

### P4 — Visual Consistency (Nice to have)

9. **Unify admin view styling** — Either fix maryUI so scaffolded views can use it, or migrate all existing admin views to plain HTML. Having two parallel styling patterns is unsustainable.

10. **Dynamic favicon from `setting()`** — `base/head.blade.php` should read favicon from AppInfo/settings, not hardcoded paths.

---

## Files Involved

### Current (needs changes)
- `resources/views/components/layouts/base.blade.php`
- `resources/views/components/layouts/base/head.blade.php`
- `resources/views/components/layouts/app.blade.php`
- `resources/views/components/layouts/auth.blade.php`
- `resources/views/components/layouts/header.blade.php`
- `resources/views/livewire/setup/setup-wizard.blade.php`
- `resources/views/livewire/admin/academic-years/index.blade.php`
- `resources/views/livewire/admin/handbooks/index.blade.php`
- `resources/views/livewire/admin/schedules/index.blade.php`
- `resources/views/livewire/admin/reports/index.blade.php`

### Legacy (reference only — DO NOT modify)
- `modules/UI/resources/views/components/layouts/base.blade.php`
- `modules/UI/resources/views/components/layouts/base/head.blade.php`
- `modules/UI/resources/views/components/layouts/base/preloader.blade.php`
- `modules/UI/resources/views/components/layouts/base/with-navbar.blade.php`
- `modules/UI/resources/views/components/layouts/dashboard.blade.php`
- `modules/Auth/resources/views/components/layouts/auth.blade.php`
- `modules/Setup/resources/views/components/layouts/setup-wizard.blade.php`
- `modules/Setup/resources/views/components/wizard-header.blade.php`
- `modules/Setup/resources/views/components/action-footer.blade.php`

---

**Next review:** After P2 structural fixes are applied.
