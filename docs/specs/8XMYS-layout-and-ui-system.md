# Layout & UI System — Cross-Cutting Presentation Shell & Component Library

> **Spec ID:** 8XMYS

## Description

Specification of Internara's cross-cutting layout and UI component system: the authenticated and
guest page shells, the config-driven role-filtered sidebar navigation, the shared `core::ui.*`
component library, SPA navigation via `wire:navigate`, responsive behavior, and the accessibility
contract for all page chrome. Theming (dark/light/system, CSS variables, brand colors) is a separate
initiative — see [branding-theme-locale.md](52O1I-branding-theme-locale.md). Locale switching and
settings storage are likewise covered by that spec and
[settings-infrastructure.md](YB22J-settings-infrastructure.md).

---

## 1. Problem Statements

### PS-1 — Page Shell Drift Across Modules

Every authenticated page needs the same skeleton: sidebar, header, breadcrumb, content container,
footer. Without a canonical shell, modules re-implement chrome independently, producing divergent
headers, inconsistent containers, and duplicated skip-link/landmark code. The system must own the
shell once, in Core, and let every module render only its content slot.

### PS-2 — Navigation Sprawl and Role Leakage

Menus must be visible only to the roles that may use them. Hardcoding menu entries inside each
module's Blade view scatters authorization logic, makes menu changes multi-file edits, and risks
role leakage (a route accessible but not listed, or listed but not gated). Navigation must be a
single config-driven source with role filtering applied centrally.

### PS-3 — Repeated CRUD UI Patterns

Record management pages repeat the same structure: title/subtitle, header actions, stat cards,
search + filters, selection bar, table, empty state, and modals. Copy-pasting this scaffold per
module produces inconsistent search/debounce behavior, divergent empty states, and duplicated
localization. Shared components must encapsulate the pattern once.

### PS-4 — Uncoordinated Navigation Model

Full page reloads on every navigation are slow and break focus and scroll state. A consistent SPA
navigation model (`wire:navigate`) with correct focus management is required so that every internal
link behaves identically and accessibly.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                                 |
| --- | -------------------------------------------------------------------- |
| G1  | One canonical authenticated shell (`core::layouts.app`) used by all modules |
| G2  | One guest shell (`core::layouts.guest`) for public pages              |
| G3  | Config-driven navigation in `config/menu.php` with central role filtering |
| G4  | Shared `core::ui.*` component library (page-header, record-manager, display-field, confirm, navbar-actions) |
| G5  | SPA navigation via `wire:navigate` with focus reset after transitions |
| G6  | Responsive drawer: sidebar hidden <1024px, persistent ≥1024px         |
| G7  | WCAG 2.1 AA shell chrome: skip link, landmarks, aria-live, focus management |

### Non-Goals

| ID   | Non-Goal                                                                 |
| ---- | ------------------------------------------------------------------------ |
| NG1  | Theme switching, dark/light/system, CSS variables, brand colors — see [branding-theme-locale.md](52O1I-branding-theme-locale.md) |
| NG2  | Locale switching, `lang/` file management — see [branding-theme-locale.md](52O1I-branding-theme-locale.md) |
| NG3  | Settings key-value store, type system, caching — see [settings-infrastructure.md](YB22J-settings-infrastructure.md) |
| NG4  | Dashboard widgets and role-based stats — see [dashboard.md](CKKZC-dashboard.md) |
| NG5  | Notification bell behavior, notification center — see [notification-infrastructure.md](TXR2H-notification-infrastructure.md) |
| NG6  | Module-specific page content, forms, tables specific to a feature       |
| NG7  | A full design-token/design-system framework or visual regression tooling |

---

## 3. User Stories / Use Cases

### UC-1 — Authenticated User Navigates the Sidebar

**Actor:** Any authenticated user (admin, teacher, supervisor, student)
**Preconditions:** User is logged in; session role is known
**Flow:**
1. User loads any authenticated page
2. `core::layouts.app` renders the drawer sidebar from `config('menu.groups')`
3. Sidebar filters each group and item against `auth()->user()->hasRole()`
4. The item whose route matches `request()->routeIs()` is highlighted
5. User clicks an item → `wire:navigate` swaps the content area
**Postconditions:** Only authorized items visible; active item highlighted; no full page reload

### UC-2 — Mobile User Opens the Sidebar

**Actor:** Any authenticated user on a viewport <1024px
**Preconditions:** Sidebar is hidden behind the drawer toggle
**Flow:**
1. User taps the hamburger button in the header
2. The drawer pattern opens the sidebar as an overlay
3. Overlay label ("close sidebar") is focusable; Escape closes the drawer
**Postconditions:** Navigation accessible on mobile in one tap

### UC-3 — Module Adds a Menu Item

**Actor:** Developer
**Preconditions:** Menu group exists in `config/menu.php`
**Flow:**
1. Developer adds an entry to the group's `items` array with `route`, `icon`, `label` (a `__()` key)
2. Developer ensures the route name resolves; if the route is not yet built, marks `disabled` or uses a placeholder
3. No Blade change required — the sidebar renders the item automatically
**Postconditions:** Item appears for roles with access; broken routes degrade to `#` without error

### UC-4 — Module Renders a Record Management Page

**Actor:** Developer
**Preconditions:** A CRUD page needs the standard list scaffold
**Flow:**
1. Developer uses `x-core::ui.record-manager` with `title`, `subtitle`, header action slots
2. Adds `$stats`, `$filters`, `$selectionBar`, `$emptyState`, `$modal` slots
3. Puts the TallstackUI `x-ts-table` in the default slot
**Postconditions:** Consistent search (300ms debounce), per-page selector, selection bar, and empty state across all modules

### UC-5 — User Navigates With Keyboard and Screen Reader

**Actor:** Keyboard / screen-reader user
**Preconditions:** User is on any page using a core layout
**Flow:**
1. Tab from page top → first focusable element is the skip-to-content link
2. Landmarks (`nav`, `main`, `header`, `footer`) announced correctly
3. Focus resets to the page heading after each `wire:navigate` transition
**Postconditions:** Full chrome operable and perceivable per WCAG 2.1 AA

---

## 4. Functional Requirements

### Layout Shell

| ID    | Requirement                                                                                              |
| ----- | -------------------------------------------------------------------------------------------------------- |
| FR-L1 | `core::layouts.base` must be the root HTML shell: `<html lang>` from `app()->getLocale()`, `data-theme` from the `theme` cookie, head (meta, favicon, manifest, Vite assets), skip-to-content link, TallstackUI toast container (`<x-ts-toast />` via Interactions), and scripts stack |
| FR-L2 | `core::layouts.app` must compose: drawer sidebar (`core::layouts.sidebar`), sticky header (`core::layouts.header`), breadcrumb (when `$context` given), `max-w-7xl` content container, and footer (`core::layouts.base.footer`) |
| FR-L3 | `core::layouts.guest` must render a centered public shell: header with brand + theme/lang switchers, content slot, footer with credits |
| FR-L4 | Layouts shared by multiple modules must live in `resources/views/ui/layouts/`; layouts specific to one module must live in `resources/views/{module}/layouts/` (e.g., `auth::layouts.auth`, `setup::layouts.setup`) |
| FR-L5 | Livewire pages must select the shell via the `#[Layout('core::layouts.app')]` attribute (or `guest`), never by embedding chrome markup inline |

### Navigation

| ID    | Requirement                                                                                               |
| ----- | --------------------------------------------------------------------------------------------------------- |
| FR-N1 | `config/menu.php` must define `groups`, each with `roles`, a `title` (`__()` key), and `items`; each item has `route`, `icon`, `label` (`__()` key), optional `roles` override, and optional `disabled` flag |
| FR-N2 | `core::layouts.sidebar` must render only groups whose roles match `auth()->user()->hasRole()`, and only items whose roles (item override or group roles) match |
| FR-N3 | The active item must be detected via `request()->routeIs($item['route'])` and highlighted with `bg-primary/10 text-primary font-medium` |
| FR-N4 | Disabled items must render as non-interactive muted `<span>`s (not links) with reduced opacity |
| FR-N5 | Missing routes must degrade gracefully: if `Route::has()` fails, the item links to `#` without throwing |
| FR-N6 | All labels and icons must use `__()` keys and TallstackUI `x-ts-icon` (Heroicons) via `icon` keys; no raw text or inline SVG in menu definitions |

### UI Component Library

| ID    | Requirement                                                                                                |
| ----- | ---------------------------------------------------------------------------------------------------------- |
| FR-C1 | `x-core::ui.page-header` must render a page title, optional description, and an optional `$actions` slot     |
| FR-C2 | `x-core::ui.record-manager` must scaffold the CRUD list page: title/subtitle + header actions + optional `$extraMenu` dropdown, `$stats` grid, search input (`wire:model.live.debounce.300ms`) + per-page selector + optional `$filters`, `$selectionBar`, default table slot, `$emptyState`, and `$modal` |
| FR-C3 | `x-core::ui.display-field` must render a labeled read-only value with optional icon                        |
| FR-C4 | `x-core::ui.confirm` must wrap a destructive action in a modal (title, message, icon, confirm/cancel text, `confirmClass`) bound to `showConfirm` and `confirmAction` |
| FR-C5 | `x-core::ui.navbar-actions` must render theme switcher, language switcher, notification bell, and user dropdown, each toggleable via `showTheme`, `showLanguage`, `showNotifications`, `showUser` props |
| FR-C6 | `x-core::ui.brand` / `x-core::ui.logo` must render the brand mark with `size` and `invert` props, tagline toggle for `brand` |
| FR-C7 | `x-core::ui.avatar` must render a user avatar with configurable `size`                                  |
| FR-C8 | `x-core::ui.credit` / `x-core::ui.credits` must render the footer attribution, with version visible in local only |

### Responsive

| ID    | Requirement                                                                                                   |
| ----- | ------------------------------------------------------------------------------------------------------------- |
| FR-R1 | The sidebar must be hidden below `lg` (1024px) and opened via a hamburger drawer toggle (`drawer` + `lg:drawer-open`) |
| FR-R2 | The sidebar must be persistently visible at `lg` and above                                                    |
| FR-R3 | Theme/lang switchers must appear in the sidebar on mobile (`md:hidden`) and in the header on larger screens (`hidden md:flex`) |
| FR-R4 | Content must use `container mx-auto max-w-7xl` with responsive padding; tables must allow horizontal scroll (`overflow-x-auto`) at small viewports |

### SPA Navigation

| ID    | Requirement                                                                                                    |
| ----- | -------------------------------------------------------------------------------------------------------------- |
| FR-S1 | All internal navigation links must use `wire:navigate` so content swaps without a full page reload             |
| FR-S2 | After a `wire:navigate` transition, focus must reset to the page heading (`<h1>`) or the first interactive element |
| FR-S3 | Toast feedback must render via TallstackUI `<x-ts-toast />` (Interactions `toast()->success()->send()`) and be announced to screen readers (`aria-live`) |

### Accessibility

| ID    | Requirement                                                                                                    |
| ----- | -------------------------------------------------------------------------------------------------------------- |
| FR-A1 | Every core layout must include a skip-to-content link (`sr-only focus:not-sr-only`) as the first focusable element targeting `#main-content` |
| FR-A2 | The shell must use semantic landmarks: `<nav>` (sidebar), `<main id="main-content">` (content), `<header>` (top bar), `<footer>` |
| FR-A3 | The drawer overlay must expose an accessible name ("close sidebar") and close on Escape                         |
| FR-A4 | Icon-only buttons must carry `aria-label`; active-nav state must be conveyed beyond color (font weight + background) |
| FR-A5 | Dynamic content (TallstackUI toast, Livewire updates, validation) must be wrapped in `aria-live` containers                 |

---

## 5. Non-Functional Requirements

| ID      | Requirement                                                                                          |
| ------- | ---------------------------------------------------------------------------------------------------- |
| NFR-A1  | All shell chrome must meet WCAG 2.1 Level AA (see [design-system.md](../guides/ui-ux/design-system.md) §6, [modular-pattern.md](../guides/arch/modular-pattern.md) §22) |
| NFR-P1  | The layout shell must render in < 100 ms server-side; sidebar menu resolution must add no more than 2 queries (no N+1 over menu items) |
| NFR-U1  | Mobile navigation must be reachable in at most 2 taps from any authenticated page                     |
| NFR-L1  | Every chrome string (menu titles, item labels, skip-link, drawer overlay, search/filters/selection labels) must exist in both `lang/en/` and `lang/id/` |
| NFR-M1  | Layout and UI components must be colocated in Core (`resources/views/ui/`) with no per-module duplication; adding a menu item must require only `config/menu.php` |

---

## 6. API / Data Contracts

### Menu Config (`config/menu.php`)

```php
// config/menu.php — groups array
return [
    'groups' => [
        'dashboard' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'student'],
            'title' => 'common.sidebar.navigation',
            'items' => [
                ['route' => 'dashboard', 'icon' => 'o-home', 'label' => 'dashboard.title'],
                // optional: 'roles' => ['super_admin'],  // item-level override
                // optional: 'disabled' => true,          // render as muted span
            ],
        ],
        // ...
    ],
];
```

### Layout Components

| Component                      | Props                                                     | Purpose                        |
| ------------------------------ | --------------------------------------------------------- | ------------------------------ |
| `x-core::layouts.base`         | `title`, `bodyClass`                                      | Root HTML shell                |
| `x-core::layouts.app`          | `title`, `header`, `footer`, `context` (breadcrumb)       | Authenticated shell            |
| `x-core::layouts.guest`        | `title`, `header`, `footer`                               | Public shell                   |
| `x-core::layouts.sidebar`      | `items` (default from `config('menu.groups')`)            | Role-filtered navigation       |
| `x-core::layouts.header`       | `header` (desktop title)                                  | Sticky top bar                 |
| `x-core::layouts.base.head`    | `title`                                                   | `<head>`: meta, favicon, Vite  |
| `x-core::layouts.base.footer`  | `fullWidth`                                               | Footer with credit             |

### UI Components (`x-core::ui.*`)

| Component        | Props                                                      | Purpose                          |
| ---------------- | ---------------------------------------------------------- | -------------------------------- |
| `page-header`    | `title`, `description`, `actions` (slot)                   | Page title block                 |
| `record-manager` | `title`, `subtitle`; slots: `headerActions`, `extraMenu`, `stats`, `filters`, `selectionBar`, `emptyState`, `modal`, default (table) | CRUD list scaffold |
| `display-field`  | `label`, `value`, `icon`                                   | Labeled read-only value          |
| `confirm`        | `title`, `message`, `icon`, `confirmText`, `cancelText`, `confirmClass` | Destructive-action modal |
| `navbar-actions` | `showTheme`, `showLanguage`, `showNotifications`, `showUser` | Header action cluster     |
| `brand`          | `size`, `invert`, `withTagline`                            | Brand mark                       |
| `logo`           | `size`                                                     | Logo mark                        |
| `avatar`         | `user`, `size`                                             | User avatar                      |
| `credit`/`credits`| `showVersion`, `class`                                    | Footer attribution               |

### Livewire Selection

```php
#[Layout('core::layouts.app')] // or 'core::layouts.guest' for public pages
public function render(): View { ... }
```

---

## 7. Design Decisions

### DD-1 — Config-Driven Navigation Over Database Menu

**Decision:** Navigation lives in `config/menu.php` (static, role-tagged), resolved by the sidebar at render.
**Rationale:** Menu entries are code-level concerns (route names, icons). Centralizing them in one
config file makes changes single-file edits and lets `request()->routeIs()` drive active state with
zero DB overhead. Role filtering stays in one place rather than scattered across views.
**Trade-off:** Adding menu items requires a config change + cache clear; acceptable — navigation is
low-frequency and developer-maintained, not school-editable.

### DD-2 — Anonymous Blade Components for Presentation-Only UI

**Decision:** Pure-presentation chrome (`page-header`, `record-manager`, `display-field`, `confirm`,
`navbar-actions`, `brand`, `logo`, `avatar`, `credit`) are anonymous Blade components under
`resources/views/ui/components/`, not Livewire components.
**Rationale:** These components carry no server state — they compose slots and TallstackUI `x-ts-*` components. Blade
components are cheaper, testable at the view layer, and avoid Livewire overhead on every render.
**Trade-off:** They cannot react to server events; any reactivity must be delegated to a parent
Livewire component (e.g., `confirm`'s `showConfirm` binding) — the established pattern.

### DD-3 — SPA Navigation via `wire:navigate`

**Decision:** All internal links use `wire:navigate` for partial-page swaps.
**Rationale:** Full reloads lose focus/scroll state and add latency. `wire:navigate` preserves
browser history, URL, and back-button semantics without a JS framework, and is the Livewire 4
canonical approach. Focus reset (FR-S2) compensates for the accessibility gap a partial swap would
otherwise introduce.
**Trade-off:** JS required for navigation; without it, links degrade to full reloads (progressive
enhancement), which is acceptable.

### DD-4 — Drawer Pattern for Responsive Sidebar

**Decision:** The sidebar uses the drawer pattern (`drawer-toggle` + `drawer-side` +
`lg:drawer-open`, styled via the self-hosted palette shims), with TallstackUI components everywhere else per FB792 FR-TS6a.
**Rationale:** The drawer ships keyboard/ARIA support (Escape to close, focusable overlay) out
of the box, satisfying FR-A3 with no custom JS; the shimmed classes keep it theme-aware without DaisyUI.

### DD-5 — Accessibility Baked Into the Shell, Not Per Page

**Decision:** Skip link, landmarks, and focus-reset are implemented once in `core::layouts.base` /
`app` rather than repeated per module.
**Rationale:** A single implementation guarantees the contract everywhere (FR-A1, FR-A2, FR-S2) and
prevents per-module drift.
**Trade-off:** Pages that bypass the core shell (module-specific layouts) must re-provide these
elements; only `auth` and `setup` do so today, and both inherit the base shell.

---

## 8. Success Metrics

### Usability

| Metric                                   | Target                         |
| ---------------------------------------- | ------------------------------ |
| Authenticated pages using `core::layouts.app` | 100% (all modules)        |
| Mobile sidebar reachable from any page   | ≤ 2 taps                       |
| SPA navigation with no full reload       | 100% of internal links         |
| Menu item added (new feature)            | 1 file (`config/menu.php`)     |

### Accessibility & Performance

| Metric                                   | Target                         |
| ---------------------------------------- | ------------------------------ |
| Lighthouse accessibility score           | ≥ 95                           |
| Keyboard-only full navigation            | Pass (no dead-ends)            |
| Sidebar menu queries per page            | ≤ 2 (no N+1 over items)        |
| Shell server render time                 | < 100 ms                       |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [base-classes.md](SE5Q9-base-classes.md) | `BaseModel`, base architecture the shell pages render against |
| [module-discovery.md](I1BCV-module-discovery.md) | Blade namespace registration (`x-core::`) and module view discovery |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | Settings store and keys read by the shell (brand, theme) |
| [branding-theme-locale.md](52O1I-branding-theme-locale.md) | `brand()` helper, CSS variables, theme/locale switchers, `SetLocaleMiddleware` consumed by `base`/`guest`/`navbar-actions` |

### Build Guide
After implementing this spec, the system has a canonical authenticated and guest shell, a
config-driven role-filtered sidebar, and a reusable `core::ui.*` component library, all meeting
WCAG 2.1 AA. Build the notification infrastructure next (the bell slot in `navbar-actions` hosts its
Livewire component), then the dashboard, which renders inside `core::layouts.app`.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [notification-infrastructure.md](TXR2H-notification-infrastructure.md) | `navbar-actions` bell slot (`livewire:user.notifications.notification-bell`) renders the notification center trigger |
| 2 | [dashboard.md](CKKZC-dashboard.md) | `UserDashboard` renders inside `core::layouts.app` with role-specific widgets |
| 3 | [authentication.md](YB7RG-authentication.md) | Auth pages use the guest/base shell and localized chrome |

---

## Quick References

- `resources/views/ui/layouts/` — Base, app, guest, sidebar, header, head, footer shells
- `resources/views/ui/components/` — Shared `x-core::ui.*` component library
- `config/menu.php` — Role-filtered navigation source
- `resources/js/app.js` — Alpine helpers, flatpickr, markdown bootstrap
- `docs/guides/ui-ux/design-system.md` — Design principles, responsive strategy, accessibility guidance
- `docs/conventions.md` §13 — Theming & visual consistency (CSS variables, form icons)
- `docs/guides/arch/modular-pattern.md` §22 — Project-wide accessibility rules
- **Related specs:** [branding-theme-locale.md](52O1I-branding-theme-locale.md) — theming/locale;
  [settings-infrastructure.md](YB22J-settings-infrastructure.md) — settings store;
  [dashboard.md](CKKZC-dashboard.md) — first consumer of the app shell
