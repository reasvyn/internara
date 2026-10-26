# UI — Presentation & Design System

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

UI has no submodules. Its code is organized by presentation layer:

**Layouts** — the app shell and guest shell, plus the header, sidebar, and page chrome they
compose. Every page renders inside one of these, so shell behaviour (theme bootstrap, skip link,
toast and dialog mount points, focus reset on navigation) is defined once.

**UI primitives** — the design-system vocabulary: navigation, page header, tables and selection
bars, buttons, cards, badges, alerts, confirmations, modals, form inputs, tooltips, icons,
loading states, and the markdown editor. Each primitive wraps TallStackUI or plain Blade and
exposes a props-driven API.

**Widgets** — composable blocks for dashboards and landing pages: stat tiles, action buttons,
empty states, profile summary, quick links, and the build-info card.

**Livewire base classes** — the list, form, entry, and wizard scaffolds that business modules
extend, plus the selection and sorting concerns they compose. This is the only place a UI class
carries behaviour, and it is presentation behaviour only.

**Support** — helpers for semantic colour resolution and view detection.

Concrete file names, component inventories, and class tables live in
[ui-reference.md](ui-reference.md).

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

`UI → Core` only. Core never imports UI. Business modules (`Program`, `Journals`, etc.) import UI layouts via Blade (`ui::layouts.app`) but UI never imports business Models/Actions. Enforced by `scripts/py/scan_module_boundaries/cli.py` (`MOD_CORE_IMPORT`, `MOD_XMOD_INTERNAL`).

## Technical Notes

- **View namespace:** the module service registers the UI view directory as `x-ui::` / `ui::` from the `UI` entry in `config/module.php`
- **Livewire layout:** the configured component layout is the UI app shell; business components declare it explicitly via `#[Layout]`
- **Discovery:** UI is a presentation module (no Actions or Models); view discovery treats the `ui` subdirectories as component namespaces
- **Styling:** Tailwind v4 `@theme` tokens are injected by the base layout from the Setting module's theme variables; dark mode is driven by the `data-theme` cookie and TallStackUI's dark-theme helper

## Related Docs

- `docs/guides/arch/ui-pattern.md` · `docs/guides/arch/ux-pattern.md` · `docs/guides/arch/modular-pattern.md` §1.x · `docs/architecture.md` §4-Layer · `docs/conventions.md` §13-14

## Design Principles

- **UI → Core only, never the reverse** — UI depends only on Core (contracts, helpers like `brand()`/`setting()`) and framework packages (TallstackUI, Alpine). Business modules (`Program`, `Journals`, etc.) import UI layouts via Blade but UI never imports any business Model or Action. Enforced by `scan_module_boundaries.py` (`MOD_CORE_IMPORT`, `MOD_XMOD_INTERNAL`).
- **All colors are semantic tokens, never raw hex literals** — every colour references a Tailwind v4 `--color-*` token (`--color-primary`, `--color-danger`, etc.). No `bg-red-500` or hardcoded hex outside the semantic palette. This ensures the entire UI updates when the brand palette changes.
- **Components are props-driven with no hardcoded menu items** — layouts and primitives accept `items`, `links`, `breadcrumbs`, `title`, and `actions` via `@props` or slots. Defaults read from `config/menu.php` inside the component, not as inline arrays. This keeps the shell decoupled from business navigation logic.
- **Accessibility is structural, not optional** — sidebar has focus traps and `aria-label`; header has `role="banner"` and `h1[tabindex=-1]` for focus reset; theme and language switches have `aria-label` and `aria-current`. WCAG 2.2 AA compliance is a default, not a checklist.

## How It Works

UI is a presentation-only module, and the discipline it keeps is what keeps it from drifting into
a second Core. It owns the app shell — the layout, navigation, header, footer, and the theme and
language switches — plus the reusable primitives that appear on every screen. It holds no business
logic, owns no models, and imports no feature module; anything that needed to know what a
"placement" is would be evidence that the component belongs in that module instead.

Everything is props-driven for the same reason. A sidebar does not know its menu items; it is
given them, with defaults read from configuration. That is what allows a module to add a page
without editing the shell, and what makes the design system enforceable: colour comes from semantic
tokens rather than hardcoded utilities, so re-skinning the application is a token change.

The two switches are worth calling out because they are user-facing promises. Theme selection is
applied through a document-level attribute and honoured by the underlying component library, so a
choice survives navigation and reload. Language selection does the same through the locale
resolution chain, and because every user-facing string goes through translation, the Indonesian
interface is complete rather than partial.
