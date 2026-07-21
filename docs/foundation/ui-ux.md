# UI/UX Design — Principles & Guidelines

> **Last updated:** 2026-07-21 **Changes:** feat — expand accessibility (§6), add routing (§8) and localization (§9)

## Description

UI/UX design principles, component library usage (DaisyUI, maryUI), layout patterns, and
accessibility guidelines.

## 1. Design System Philosophy

Internara's interface is built on three CSS layers:

| Layer               | Purpose                    | Provides                                        |
| ------------------- | -------------------------- | ----------------------------------------------- |
| **Tailwind CSS v4** | Utility foundation         | Spacing, typography, responsive grid, colors    |
| **DaisyUI**         | Themed primitives          | Buttons, cards, badges, modals, drawer sidebar  |
| **maryUI**          | High-level form components | Inputs, tables, selects, dropdowns, file upload |

Livewire manages server-side state; Alpine.js handles client-side behavior. The visual language is
clean and professional: neutral monochrome base, single accent brand color, low-saturation
backgrounds, subtle borders, bold typography, minimal shadows.

---

## 2. Layout Structure

### Cross-Cutting Layouts

Located at `resources/views/core/layouts/`:

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

- Layout shared by multiple modules → `resources/views/core/layouts/`
- Layout specific to one module → `resources/views/{module}/layouts/`

---

## 3. Dark Mode

Class-based dark mode via `data-theme` attribute on `<html>`. Three-state switcher: light, dark,
system preference. Preference stored in cookie, applied on subsequent visits.

Both themes defined as DaisyUI theme plugins in CSS. Brand colors are not hardcoded — injected at
runtime via inline `<style>` block. Admin can change brand colors and see them reflected in both
themes immediately, without CSS recompilation.

Dark mode lightens brand colors by 40% for visibility on dark backgrounds.

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

All user-facing interfaces MUST meet WCAG 2.1 Level AA. This section defines UI-layer
requirements. See `docs/architecture/modular-pattern.md` §22 for architectural rules and
`docs/architecture/livewire-pattern.md` §13 for component-specific patterns.

### 6.1 Color & Contrast

- **Minimum contrast ratios:** 4.5:1 for normal text, 3:1 for large text (≥18pt or ≥14pt bold),
  3:1 for UI components and graphical objects.
- **DaisyUI theme colors** are pre-validated for contrast. Never override with arbitrary Tailwind
  color utilities that fail contrast checks.
- **Color is never the sole indicator:** Status badges (success/warning/error), capacity gauges,
  and validation states must include text labels, icons, or patterns alongside color. Example:
  `badge-success` + "Verified" text, not just a green badge.

### 6.2 Keyboard Navigation

- **Tab order:** Must follow logical reading order (top-to-bottom, left-to-right for LTR). No
  positive `tabindex` values.
- **Focus indicators:** Every focusable element must have a visible focus ring. DaisyUI provides
  `focus:ring` by default — do not suppress with `outline-none` without a visible replacement.
- **Skip links:** Every page with navigation must provide a "Skip to main content" link as the
  first focusable element in the DOM.
- **Interactive elements:** All buttons, links, form fields, modals, and dropdowns must be
  reachable and operable via keyboard alone (Enter, Space, Arrow keys, Escape).

### 6.3 Modal & Dialog Focus

- **Focus trap:** Modals (`x-mary-modal`) must trap focus within the modal when open. DaisyUI
  modals handle this by default.
- **Focus return:** On modal close, focus must return to the trigger element.
- **Escape key:** All modals and dropdowns must close on Escape key press (DaisyUI default).

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

- **Labels:** Every form input must have an associated `<label>` (via `for`/`id` or wrapping maryUI
  `label` prop). Placeholder text is not a label substitute.
- **Required indicators:** Use the `required` HTML attribute (maryUI `required` prop), not just
  visual asterisks.
- **Error messaging:** Validation errors must be associated with their field via `aria-describedby`
  and announced to screen readers via `aria-live` regions. maryUI handles this automatically.
- **Error focus:** After failed validation, focus must move to the first invalid field or an error
  summary.

### 6.6 Content Reflow

- No horizontal scrolling at 320px viewport width (WCAG 1.4.10).
- Responsive breakpoints must prevent content clipping or overlap.
- Tables must reflow to card layout or horizontal scroll with visible scroll indicators on mobile.

---

## 7. SPA Navigation

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
`routes/web/{submodule}.php` (no module prefix). See `docs/infrastructure/routes.md`.

### Livewire Routes

Livewire components are registered directly in route files:

```php
Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
```

Route middleware applies at the route level — `auth`, `guest`, `role:{roles}`, `auth.throttle`.
See `docs/architecture/modular-pattern.md` §13 for full route patterns.

---

## 9. Localization

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

The `LangSwitcher` Livewire component (`app/Settings/Livewire/LangSwitcher.php`) toggles between
EN and ID. Locale preference is stored in a cookie (`locale`) and applied via `SetLocale` middleware
on every request.

### Date & Number Formatting

```php
// Locale-aware date
Carbon::locale(app()->getLocale())->isoFormat('D MMMM YYYY');

// Locale-aware number
Number::locale(app()->getLocale())->format(1234567.89);
```

### HTML Language Attribute

`<html lang="{{ app()->getLocale() }}">` is set in `resources/views/core/layouts/base.blade.php`.
Screen readers use this to select the correct pronunciation engine.

### Dual Locale Requirement

Every translation key must exist in both `lang/en/{file}.php` and `lang/id/{file}.php`. Adding a
key to one locale without the other is a bug.

---

## 10. Component Library Patterns

### maryUI Components

Used for data-heavy interfaces:

- `x-table` with sorting, pagination, row selection
- `x-input`, `x-select`, `x-textarea` with validation error styling
- `x-form`, `x-form-section` for form layout
- `x-button`, `x-dropdown` for actions

### DaisyUI Components

Used for structural elements:

- `btn`, `card`, `badge`, `avatar`, `modal`
- `drawer` for sidebar navigation
- `theme-controller` for dark mode toggle

---

## 11. Guide Component Pattern

Every page with a non-trivial workflow MUST include a floating guide button (bottom-right, question
mark icon) that opens a modal with step-by-step instructions. See
`docs/architecture/livewire-pattern.md` (§11) for the full implementation pattern.

Implementation reference: `resources/views/setup/components/setup-guide.blade.php`

### Requirements

- **File:** `resources/views/{module}/components/{page-name}-guide.blade.php`
- **Trigger:** Fixed floating button, bottom-right, `z-50`, primary color
- **Modal:** `x-mary-modal` with numbered steps and a tip section
- **Localization:** All strings in `__('{module}.guide.*')`
- **Integration:** Parent component includes `@include('{module}.components.{page-name}-guide')` and
  exposes `$showGuide` boolean

---

## 12. Key Locations

| Asset             | Path                                      |
| ----------------- | ----------------------------------------- |
| Layout templates  | `resources/views/core/layouts/`           |
| UI components     | `resources/views/core/ui/`                |
| CSS entry point   | `resources/css/app.css`                   |
| JS entry point    | `resources/js/app.js`                     |
| maryUI config     | `config/mary.php`                         |
| Sidebar menu      | `config/menu.php`                         |
| Theme switcher    | `app/Settings/Livewire/ThemeSwitcher.php` |
| Language switcher | `app/Settings/Livewire/LangSwitcher.php`  |
