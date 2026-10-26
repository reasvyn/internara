# UI — Technical Reference

## Description

Structural reference for the **UI** module: the presentation layer that owns the app shell,
reusable Blade components, widgets, and the Livewire base classes business modules build on.
For the rationale and boundaries, see [ui.md](ui.md).

---

## Overview

The module has no business logic and no persistence. Everything it ships is a Blade view under
`resources/views/ui/` (registered as `x-ui::` / `ui::`) or a PHP base class under
`app/Modules/UI/`.

### Module Statistics

- **Layouts**: 7 views (`app`, `base`, `guest`, `header`, `sidebar`, plus `base/head`, `base/footer`)
- **UI primitives**: 34 anonymous Blade components
- **Widgets**: 6 anonymous Blade components
- **Livewire base classes**: 6 (`BaseRecordManager`, `BaseRecordList`, `BaseFormView`, `BaseRecordEntry`, `BaseWizard`, `AboutPage`)
- **Livewire concerns**: 2 (`WithRecordSelection`, `WithSorting`)
- **Support classes**: 1 (`UiHelper`)
- **Actions / Models / Policies / Routes**: none — presentation only

---

## Layouts

Located in `resources/views/ui/layouts/`, reachable as `x-ui::layouts.*`:

| View | Purpose |
|---|---|
| `app.blade.php` | Livewire default layout: composes sidebar + header + content inside `base` |
| `base.blade.php` | HTML shell — `data-theme`, branding CSS variables, skip-to-content, toast/dialog mount points |
| `base/head.blade.php` | `<head>` contents: meta, assets, theme bootstrap |
| `base/footer.blade.php` | Page footer |
| `header.blade.php` | Sticky page header with title, breadcrumbs, actions slot |
| `sidebar.blade.php` | Desktop persistent navigation plus mobile drawer |
| `guest.blade.php` | Unauthenticated shell for login, setup, and error pages |

---

## UI Primitives

Located in `resources/views/ui/components/`, reachable as `x-ui::components.*` (34 components):

`alert`, `app-signature`, `avatar`, `badge`, `brand`, `breadcrumbs`, `button`, `card`,
`checkbox`, `confirm`, `credit`, `credits`, `dialog`, `display-field`, `dropdown`,
`dropdown-item`, `icon`, `input`, `loading`, `logo`, `markdown-editor`, `modal`, `navbar`,
`navbar-actions`, `page-header`, `record-manager`, `select`, `selection-bar`, `table`,
`table-action`, `table-actions`, `textarea`, `toggle`, `tooltip`.

Theme and language switches are **not** part of this set — they render from the Setting module
(`resources/views/setting/locale/{theme-switch,lang-switch}.blade.php`) because they persist state.

---

## Widgets

Located in `resources/views/ui/widgets/`, reachable as `x-ui::widgets.*`:

| Widget | Purpose |
|---|---|
| `stat-card` | Single metric tile with label, value, and optional trend |
| `action-button` | Primary call-to-action block |
| `empty-state` | Placeholder for empty collections with optional action |
| `profile-summary` | Compact identity card for the current user |
| `quick-link` | Shortcut tile for a destination route |
| `app-info` | Build/version card sourced from `AppInfo` |

---

## Livewire Base Classes

Six base components live in `app/Modules/UI/Livewire/` (file-level inventory under
[Livewire Components](#livewire-components)):

- the record manager owns list scaffolding — search, filters, sorting, pagination, and both bulk
  and mass actions;
- the record list is its read-only counterpart, and the record entry hosts a single record;
- the form view wires a Livewire form object to create/edit screens;
- the wizard runs multi-step flows with per-step validation;
- the about page renders build metadata from the app-info service.

Two concerns supply row selection (`WithRecordSelection`) and sortable-column resolution
(`WithSorting`).

---

## Support

| File | Purpose |
|---|---|
| `Support/UiHelper.php` | Semantic colour resolution and UI view detection helpers |

---

## Routes

None. UI ships no routes; every page is reached through a business module's route or Livewire
component.

---

## Tests

| Suite | File |
|---|---|
| Arch | `tests/Arch/UI/UiArchitectureTest.php` |
| Feature | `tests/Feature/UI/{LayoutSystemTest,BladeComponentsTest,AboutPageTest,PublicLandingPageTest,BaseRecordManagerTest,BaseRecordListTest,BaseFormViewTest,BaseRecordEntryTest,BaseWizardTest}.php` |
| Unit | `tests/Unit/UI/{UiHelperTest,WithRecordSelectionTest,WithSortingTest}.php` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/AboutPage.php` | `AboutPage` | `Component` |
| `Livewire/BaseFormView.php` | `BaseFormView` | `Component` |
| `Livewire/BaseRecordEntry.php` | `BaseRecordEntry` | `Component` |
| `Livewire/BaseRecordList.php` | `BaseRecordList` | `Component` |
| `Livewire/BaseRecordManager.php` | `BaseRecordManager` | `Component` |
| `Livewire/BaseWizard.php` | `BaseWizard` | `Component` |
| `Livewire/Concerns/WithRecordSelection.php` | `WithRecordSelection` | — |
| `Livewire/Concerns/WithSorting.php` | `WithSorting` | — |

---

## Architectural Integration

- `config/livewire.php` sets `component_layout = 'ui::layouts.app'`; business components that need a
  shell declare `#[Layout('ui::layouts.app')]`.
- `ModuleService::registerBladeNamespaces()` registers `resources/views/ui` as `x-ui::` and `ui::`
  from the `UI` entry in `config/module.php`.
- Dependency direction is enforced by `scripts/py/scan_module_boundaries/` (`MOD_CORE_IMPORT`,
  `MOD_XMOD_INTERNAL`): UI imports Core only.

## Quick References

- [ui.md](ui.md) — conceptual overview, boundaries, design rules
- [ui-pattern.md](../../guides/arch/ui-pattern.md) — UI component conventions
- [ux-pattern.md](../../guides/arch/ux-pattern.md) — interaction and accessibility rules
