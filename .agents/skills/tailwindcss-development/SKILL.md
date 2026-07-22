---
name: tailwindcss-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized UI/styling development — Blade templates, responsive layouts, dark mode, daisyUI, maryUI, Tailwind CSS v4."
upstream:
  - feature-building
  - livewire-development
downstream:
  - sync-docs
---

# Tailwind CSS Development

> **Prerequisite:** Load `context-awareness` for project orientation. Loading `livewire-development`
> provides component context.

## When to Activate

Use this skill when building or styling UI — Blade templates, responsive layouts, dark mode,
component styling with daisyUI and maryUI, and Tailwind CSS v4 utilities.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — UI/Styling Development

- Use maryUI components for consistency (table, modal, form)
- Use DaisyUI theme colors (primary, secondary, accent)
- Ensure responsive on mobile, tablet, desktop
- Ensure dark mode works without visual breakage
- Avoid custom CSS if DaisyUI/maryUI suffice
- Output: styled Blade views with consistent maryUI/DaisyUI components, responsive layout, and dark
  mode support

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of UI work done
    - Files created or modified
    - Responsive breakpoints and dark mode tested
- Feeds into: sync-docs (UI documentation)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                               |
| -------------- | ----------------------------------------------------------------------------------- |
| **Upstream**   | `feature-building` (implementation), `livewire-development` (components needing UI) |
| **This skill** | **IMPLEMENTATION (Sub-skill)** — UI/styling                                         |
| **Downstream** | `sync-docs`                                                                         |

## UI Stack

| Layer               | Purpose                                                        |
| ------------------- | -------------------------------------------------------------- |
| **Tailwind CSS v4** | Utility-first CSS framework                                    |
| **DaisyUI 5**       | Tailwind component library (themed, accessible)                |
| **maryUI 2**        | Laravel-specific Livewire component library (built on DaisyUI) |
| **Alpine.js**       | Lightweight JavaScript interactivity (dropdowns, modals)       |

## Key Patterns

### Layout

- Use DaisyUI's `drawer` for sidebar navigation
- Use DaisyUI's `navbar` for top navigation
- Responsive: mobile-first with `sm:`, `md:`, `lg:` breakpoints
- Container: `max-w-7xl mx-auto` for content width

### Dark Mode

- DaisyUI supports dark mode via `data-theme="dark"` attribute
- Implement theme toggle via Alpine.js + Livewire
- Use CSS variables for brand colors (defined in Settings module)

### Component Usage

| Need          | maryUI Component                                   |
| ------------- | -------------------------------------------------- |
| Tables        | `x-mary-table` (sorting, pagination, selection)    |
| Forms         | `x-mary-input`, `x-mary-select`, `x-mary-textarea` |
| Modals        | `x-mary-modal`                                     |
| Notifications | `x-mary-toast` (via flasher)                       |
| Buttons       | `x-mary-button`                                    |
| Cards         | `x-mary-card`                                      |
| Stats         | `x-mary-stat`                                      |
| Alerts        | `x-mary-alert`                                     |
| Tabs          | `x-mary-tabs`                                      |
| Choices       | `x-mary-choices` (multi-select)                    |

### View Structure

```
resources/views/{module}/{submodule}/{action}.blade.php
```

- Extends layout: `<x-layouts.app>` or module-specific layout
- Use Livewire components for interactive sections
- Use Blade components for reusable UI fragments
- Keep logic in Livewire, not in Blade directives

### Tailwind v4 Specifics

- CSS-first configuration (not `tailwind.config.js` — check `resources/css/`)
- Uses `@theme` directive for custom values
- `@import` for layers instead of `@layer`
- Check `resources/css/app.css` for project-specific theme setup

## Styling Principles

1. Prefer maryUI components over custom HTML for consistency
2. Use DaisyUI theme colors (`primary`, `secondary`, `accent`, etc.) over arbitrary colors
3. Responsive design is mandatory — test at mobile, tablet, desktop
4. Dark mode must work without visual breakage
5. Do NOT write custom CSS unless DaisyUI/maryUI cannot achieve the design
6. Follow existing component patterns in the same module
7. **Accessibility is mandatory** — WCAG 2.1 AA compliance (see below)

## Accessibility (WCAG 2.1 AA)

Every styled component MUST meet accessibility requirements. See `docs/architecture/modular-pattern.md`
§22 and `docs/foundation/ui-ux.md` §6 for full rules.

### Color & Contrast

- Use DaisyUI theme colors — they are pre-validated for contrast ratios.
- Minimum 4.5:1 for normal text, 3:1 for large text (≥18pt or ≥14pt bold).
- Never use arbitrary Tailwind color utilities (`text-red-500`, `bg-blue-200`) that may fail
  contrast checks — prefer DaisyUI semantic colors (`text-error`, `bg-info/10`).
- Status indicators must include text labels alongside color (e.g., `badge-success` + "Active",
  not just a green badge).

### Focus Indicators

- Never suppress focus rings with `outline-none` without providing a visible replacement.
- DaisyUI `focus:ring` is the default — preserve it on all interactive elements.
- Custom interactive elements (Alpine.js dropdowns, custom buttons) must include
  `focus:ring focus:ring-primary`.

### Keyboard Navigation

- All interactive elements must be reachable via Tab key.
- Dropdowns must open on Enter/Space and close on Escape.
- Modals must trap focus (DaisyUI default — verify it's not overridden).
- No positive `tabindex` values — follow natural DOM order.

### Responsive & Reflow

- No horizontal scrolling at 320px viewport width (WCAG 1.4.10).
- Tables must reflow to card layout or provide horizontal scroll with visible indicators on mobile.
- Content must not be clipped or overlap at any breakpoint.

### Icon Accessibility

- Icon-only buttons must include `aria-label`:
  ```blade
  <x-mary-button icon="o-trash" aria-label="{{ __('common.delete') }}" />
  ```
- Icons paired with text should NOT duplicate the text in `alt` attributes.

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

- [ ] Uses maryUI / DaisyUI components where available
- [ ] Responsive at mobile, tablet, desktop viewports
- [ ] Dark mode renders correctly
- [ ] Follows existing view patterns in the module
- [ ] No custom CSS when framework components suffice
- [ ] No inline styles — use Tailwind utilities
- [ ] All visible text uses `__()` for localization
- [ ] Focus indicators visible on all interactive elements
- [ ] Icon-only buttons include `aria-label`
- [ ] Color is not the sole indicator for status/errors
- [ ] Color contrast meets WCAG 2.1 AA (4.5:1 normal, 3:1 large)
- [ ] No horizontal scrolling at 320px viewport width

## References

| Topic                       | Doc                                     |
| --------------------------- | --------------------------------------- |
| UI/UX design system         | `docs/foundation/ui-ux.md`              |
| Branding & themes           | `docs/foundation/branding.md`           |
| Livewire component patterns | `docs/architecture/livewire-pattern.md` |
| maryUI documentation        | `search-docs` with `robsontenorio/mary` |
| DaisyUI documentation       | `search-docs` with `daisyui`            |
| Tailwind CSS v4             | `search-docs` with tailwindcss          |
