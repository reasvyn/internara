---
name: tailwindcss-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized UI/styling development — Blade templates, responsive layouts, dark mode, TallstackUI v4 + Tailwind CSS v4."
upstream:
  - feature-building
  - livewire-development
downstream:
  - sync-docs
---

# Tailwind CSS Development

> **Last updated:** 2026-08-25 **Changes:** sync — replace daisyUI/maryUI with TallstackUI v4 (x-ts-* components, self-hosted palette, dark via .dark)

> **Prerequisite:** Load `context-awareness` for project orientation. Loading `livewire-development`
> provides component context.

## When to Activate

Use this skill when building or styling UI — Blade templates, responsive layouts, dark mode,
component styling with TallstackUI v4 and Tailwind CSS v4 utilities.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting), verification
strategy, and commit format. This skill adds UI/styling guidance — the UI stack, TallstackUI
patterns, dark mode, and accessibility — nothing else.

### Execute — UI/Styling Development

- Use TallstackUI `x-ts-*` components for consistency (table, modal, form, card, badge, alerts)
- Use semantic palette vars (`--color-primary`, `--color-success` etc. in `resources/css/app.css`)
- Ensure responsive on mobile, tablet, desktop
- Ensure dark mode works (`.dark` + `data-theme`, via `core::ui.theme-switch`)
- Avoid custom CSS if TallstackUI suffices (legacy `.btn`/`.badge` shimmed in `@layer components`)

## UI Stack

| Layer                    | Purpose                                                        |
| ------------------------ | -------------------------------------------------------------- |
| **Tailwind CSS v4**      | Utility-first CSS framework (`dark:` via `.dark` class)        |
| **TallstackUI v4**       | Component library (`x-ts-*`, prefix `ts-`, toast via Interactions) |
| **Self-hosted palette**  | Semantic colors via `@theme` + shims (`resources/css/app.css`) |
| **Alpine.js**            | Lightweight JavaScript interactivity (dropdowns, modals)       |

## Key Patterns

### Layout

- Use TallstackUI `x-ts-layout` + `x-ts-side-bar` for sidebar navigation
- Use core header (`resources/views/core/ui/navbar-actions.blade.php`) for top navigation
- Responsive: mobile-first with `sm:`, `md:`, `lg:` breakpoints
- Container: `max-w-7xl mx-auto` for content width

### Dark Mode

- Dual signal: `data-theme` (palette) + `.dark` class (Tailwind/TallstackUI)
- Theme switch is `core::ui.theme-switch` wrapping `<x-theme-switch>` (TallstackUI); persists to `localStorage` `dark-theme`, dispatches `theme` CustomEvent, JS `applyTheme()` mirrors to `theme` cookie for SSR
- Brand colors injected via `Theme::cssVariables()` inline `<style>`

### Component Usage

| Need          | TallstackUI Component (`x-ts-*`)                               |
| ------------- | -------------------------------------------------------------- |
| Tables        | `x-ts-table` (sorting, pagination, selection; headers via `index` key) |
| Forms         | `x-ts-input`, `x-ts-select.native`, `x-ts-textarea`, `x-ts-radio`, `x-ts-checkbox` |
| Modals        | `x-ts-modal`, `x-ts-dialog` (confirm)                          |
| Notifications | Toast via `toast()->success()` (TallstackUI Interactions)      |
| Buttons       | `x-ts-button`                                                  |
| Cards         | `x-ts-card`                                                    |
| Badges        | `x-ts-badge`                                                   |
| Alerts        | `x-ts-alert`                                                   |
| Icons         | `x-ts-icon` (Heroicons)                                        |

### View Structure

```
resources/views/{module}/{submodule}/{action}.blade.php
```

- Extends layout: `<x-layouts.app>` or module-specific layout
- Use Livewire components for interactive sections
- Use Blade components for reusable UI fragments
- Keep ALL business and UI logic in Livewire (computed properties/methods) or Alpine.js (`x-data`) — Blade is presentation only, no `@php` calculations (see `docs/conventions.md` §14)

### Tailwind v4 Specifics

- CSS-first configuration (not `tailwind.config.js` — check `resources/css/`)
- Uses `@theme` directive for custom values
- `@import` for layers instead of `@layer`
- Check `resources/css/app.css` for project-specific theme setup

## Styling Principles

1. Prefer TallstackUI `x-ts-*` components over custom HTML for consistency
2. Use semantic palette vars (`--color-primary`, `--color-success` etc.) over arbitrary colors
3. Responsive design is mandatory — test at mobile, tablet, desktop
4. Dark mode must work without visual breakage (`.dark` + `data-theme`)
5. Do NOT write custom CSS unless TallstackUI cannot achieve the design (shims exist for legacy classes)
6. Follow existing component patterns in the same module
7. **Accessibility is mandatory** — WCAG 2.1 AA compliance (see below)

## Accessibility (WCAG 2.1 AA)

Every styled component MUST meet accessibility requirements. See `docs/architecture/modular-pattern.md`
§22 and `docs/foundation/ui-ux.md` §6 for full rules.

### Color & Contrast

- Use semantic palette vars (`--color-success`, `--color-warning` etc.) — pre-validated for contrast.
- Minimum 4.5:1 for normal text, 3:1 for large text (≥18pt or ≥14pt bold).
- Never use arbitrary Tailwind color utilities (`text-red-500`, `bg-blue-200`) that may fail
  contrast checks — prefer semantic `text-error`, `bg-info/10`.
- Status indicators must include text labels alongside color (e.g., `x-ts-badge color="success"` + "Active",
  not just color).

### Focus Indicators

- Never suppress focus rings with `outline-none` without providing a visible replacement.
- TallstackUI `focus:ring` is the default — preserve it on all interactive elements.
- Custom interactive elements (Alpine.js dropdowns, custom buttons) must include
  `focus:ring focus:ring-primary`.

### Keyboard Navigation

- All interactive elements must be reachable via Tab key.
- Dropdowns (`x-ts-dropdown`) must open on Enter/Space and close on Escape.
- Modals (`x-ts-modal`/`x-ts-dialog`) must trap focus — verify not overridden.
- No positive `tabindex` values — follow natural DOM order.

### Responsive & Reflow

- No horizontal scrolling at 320px viewport width (WCAG 1.4.10).
- Tables must reflow to card layout or provide horizontal scroll with visible indicators on mobile.
- Content must not be clipped or overlap at any breakpoint.

### Icon Accessibility

- Icon-only buttons must include `aria-label`:
  ```blade
  <x-ts-button icon="trash" aria-label="{{ __('common.delete') }}" />
  ```
- Icons via `x-ts-icon` (Heroicons) paired with text should NOT duplicate the text in `alt` attributes.

## Localization

All user-facing strings MUST use `__()` for EN/ID bilingual support. See `docs/conventions.md` §14.

### Rules

- All visible text in Blade views uses `{{ __('key') }}` — no hardcoded English.
- Button labels, modal titles, table headers: all via `__()`.
- Date formatting: `Carbon::locale(app()->getLocale())->isoFormat(...)`.
- HTML `lang` attribute set in `base.blade.php`.
- Every key must exist in both `lang/en/` and `lang/id/`.

### Key Patterns

| Scope            | Pattern                | Example                            |
| ---------------- | ---------------------- | ---------------------------------- |
| Module-level     | `{module}.key`         | `__('enrollment.register')`        |
| Submodule-level  | `{submodule}.key`      | `__('internship.create_success')`  |
| Shared           | `common.key`           | `__('common.actions.save')`        |

## Routing

See `docs/infrastructure/routes.md` and `docs/architecture/modular-pattern.md` §13.

### Route File Convention

- Module-level: `routes/web/{module}.php`
- Submodule-level: `routes/web/{submodule}.php` (no module prefix)

### Route Naming

Flexible — describe the URL path. No rigid `{prefix}.{resource}.{action}` convention.

### Livewire Route Registration

```php
Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
```

Middleware applied at route level: `auth`, `guest`, `role:{roles}`, `auth.throttle`.

### URL Structure

| Scope       | Pattern                         | Example                                  |
| ----------- | ------------------------------- | ---------------------------------------- |
| Guest       | `/{resource}`                   | `/apply`, `/login`                       |
| Student     | `/student/{module}/{resource}`  | `/student/internships/placement-change`  |
| Admin       | `/admin/{module}/{resource}`    | `/admin/internships/placements`          |

## Verification Checklist

- [ ] Uses TallstackUI `x-ts-*` components where available
- [ ] Responsive at mobile, tablet, desktop viewports
- [ ] Dark mode renders correctly (`.dark` + `data-theme`)
- [ ] Follows existing view patterns in the module
- [ ] No custom CSS when TallstackUI suffices (shims are transitional)
- [ ] No inline styles — use Tailwind utilities
- [ ] All visible text uses `__()` for localization
- [ ] Focus indicators visible on all interactive elements
- [ ] Icon-only buttons include `aria-label`
- [ ] Color is not the sole indicator for status/errors
- [ ] Color contrast meets WCAG 2.1 AA (4.5:1 normal, 3:1 large)
- [ ] No horizontal scrolling at 320px viewport width

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| UI stack & component usage (TallstackUI primacy, semantic palette, no custom CSS) | `rules/ui-stack-and-component-usage.md` | Building any UI component or styling views |
| Layout, responsiveness & dark mode (layout/sidebar, breakpoints, theming, Tailwind v4) | `rules/layout-responsive-dark-mode.md` | Structuring layouts or theming the app |
| View structure & routing (Blade placement, route files, Livewire routes) | `rules/view-structure-and-routing.md` | Creating views or routes |
| Blade presentation — no business/UI logic (Livewire computed props or Alpine.js only) | `rules/blade-presentation.md` | Every Blade file — any derived value, percentage, or array assembly |
| Accessibility (WCAG 2.1 AA: contrast, focus, keyboard, reflow, icons) | `rules/accessibility-wcag.md` | Every styled component before release |
| Localization in views (bilingual `__()`, keys, dates, lang attribute) | `rules/localization-in-views.md` | Any user-facing string or date in a view |

## References

| Topic                       | Doc                                     |
| --------------------------- | --------------------------------------- |
| UI/UX design system         | `docs/foundation/ui-ux.md`              |
| Branding & themes           | `docs/foundation/branding.md`           |
| Livewire component patterns | `docs/architecture/livewire-pattern.md` |
| TallstackUI documentation   | `search-docs` with `tallstackui/tallstackui` |
| Tailwind CSS v4             | `search-docs` with tailwindcss          |
