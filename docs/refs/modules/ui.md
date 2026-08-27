# UI — Presentation & Design System

> **Last updated:** 2026-08-27 **Changes:** feat — extract Core UI to standalone UI module (Navbar, Sidebar, Header, Footer, LangSwitch, ThemeSwitch)

## Description

Presentation-only module that owns the visual shell and reusable design-system components. Extracted from Core to enforce the 4-layer rule (Core has zero business dependencies, UI depends only on Core contracts and Tailwind tokens). All Blade layouts and UI primitives live here and are consumed via `x-ui::` anonymous component namespace.

## Purpose & Boundary

UI provides the **app shell** (layouts, navigation, chrome) and **design primitives** (headers, footers, toggles) with no business logic. It is the single source for Tailwind v4 semantic palette, responsive behavior, and WCAG 2.2 AA patterns. UI **must not** import any business module (`Academics`, `Program`, etc.) — it depends only on `Core` (contracts, helpers like `brand()`/`setting()`) and framework packages (TallstackUI, Alpine).

Out of scope: domain logic, Actions, Models, Policies, Livewire business components, email/PDF templates. Business Livewire components live in their own modules and *consume* UI layouts via `ui::layouts.app` / `x-ui::`.

**Design rules (from `docs/guides/arch/ui-pattern.md` & `ux-pattern.md`):**

- Tailwind v4 CSS-first `@theme` with semantic tokens (`--color-primary` etc.); no hardcoded `bg-red-500` outside tokens
- TallstackUI v4 (`x-ts-*`) for base primitives; UI wraps them with app-specific composition
- Props-driven APIs — no hardcoded menu items; `items`/`links`/`breadcrumbs` passed via props or slots, defaults read from `config/menu.php` or helpers
- Responsive-first (mobile drawer + desktop persistent), `dark` via `data-theme` + Alpine `tallstackui_darkTheme()`, `__()` for every user string, full `alt`/`aria-label` coverage

## Submodules

UI has no submodules. Code is organized by presentation layer:

**Layouts** (`resources/views/ui/layouts` → `x-ui::layouts.*`):
`layouts/app.blade.php` (app shell: `x-ts-layout` + menu/header/footer slots), `layouts/base.blade.php` (HTML shell, `data-theme`, branding CSS vars, `skip-to-content`, toast/dialog), `layouts/base/head.blade.php`, `layouts/base/footer.blade.php`, `layouts/header.blade.php`, `layouts/sidebar.blade.php`, `layouts/guest.blade.php`.

**UI primitives** (`resources/views/ui/components` → `x-ui::components.*`):
`navbar.blade.php`, `navbar-actions.blade.php`, `theme-switch.blade.php`, `lang-switch.blade.php`, `page-header.blade.php`, `record-manager.blade.php`, `selection-bar.blade.php`, `brand.blade.php`, `logo.blade.php`, `avatar.blade.php`, `app-signature.blade.php`, etc.

**Widgets** (`resources/views/ui/widgets` → `x-ui::widgets.*`):
`stat-card.blade.php`, `action-button.blade.php`, `empty-state.blade.php`, `profile-summary.blade.php`, `quick-link.blade.php`.

**PHP support** (`app/UI`): `Support/UiHelper.php` (semantic color list, `isUiView()`), `View/Components` (future typed components). No business code.

## Key Concepts

### App Shell Composition

`ui::layouts.app` is the Livewire default (`config/livewire.php` `component_layout`). Business Livewire components declare `#[Layout('ui::layouts.app')]` and render slot content inside `layouts/base`. Shell composes `layouts/sidebar` (menu) + `layouts/header` (title, breadcrumbs, actions) + `layouts/base.footer`. Guest pages use `layouts/guest`.

### Props-Driven, No Hardcoding

Components accept `items`, `links`, `breadcrumbs`, `title`, `actions` via `@props`/`$slot`. Example: `<x-ui::layouts.sidebar :items="$groups" />` where `$groups` defaults to `config('menu.groups')` inside the component, not hardcoded list. Same for `navbar` (`links` slot), `header` (`breadcrumbs` array), `footer` (`links` array). This satisfies “not all components must use Core/UI” — business components may render their own markup when shell is not needed.

### Responsive & Accessibility

- Sidebar: desktop persistent `w-64` + mobile drawer with backdrop (`x-data="{open}"`, `@toggle-sidebar` dispatch from header), focus trap, `role="navigation"` `aria-label`
- Header: sticky `top-0` with `backdrop-blur`, `role="banner"`, breadcrumbs `nav` + `h1[tabindex=-1]` for `livewire:navigated` focus reset (in `base.blade.php`)
- Navbar: standalone top nav with mobile panel (`x-show` transitions), `aria-expanded`/`aria-controls`
- ThemeSwitch/LangSwitch: `aria-label`, `aria-current`, `role="group"`; LangSwitch is Livewire-aware (`wire:click` when inside Livewire, else JS cookie fallback)
- All colors via `--color-*` tokens; no `bg-red-500` outside semantic palette

### Dependency Direction

`UI → Core` only. Core never imports UI. Business modules (`Program`, `Journals`, etc.) import UI layouts via Blade (`ui::layouts.app`) but UI never imports business Models/Actions. Enforced by `scripts/scan_module_boundaries.py` (`MOD_CORE_IMPORT`, `MOD_XMOD_INTERNAL`).

## Technical Notes

- **View namespace:** `resources/views/ui` registered via `ModuleService::registerBladeNamespaces()` as `x-ui::` / `ui::` (from `config/module.php` `UI` entry)
- **Livewire layout:** `config/livewire.php` `component_layout = 'ui::layouts.app'`; 34 Livewire components use `#[Layout('ui::layouts.app')]`
- **Discovery:** `app/UI` is a presentation module (no `Actions`/`Models`); `ModuleManager::viewsPath()` = `resource_path('views')`, `viewsExcludeDirectories` excludes `layouts` top-level but not `ui/*` subdirs, so `ui` is registered correctly
- **Styling:** Tailwind v4 `@theme` tokens injected in `layouts/base.blade.php` via `Theme::cssVariables()` (from `Settings/Theme`); dark mode via `data-theme` cookie + `tallstackui_darkTheme()`

## Related Docs

- `docs/guides/arch/ui-pattern.md` · `docs/guides/arch/ux-pattern.md` · `docs/guides/arch/modular-pattern.md` §1.x · `docs/architecture.md` §4-Layer · `docs/conventions.md` §13-14
