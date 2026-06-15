# UI/UX Design

> **Last updated:** 2026-06-10

---

## 1. Design System Philosophy

Internara's interface is built on three CSS layers:

| Layer | Purpose | Provides |
|-------|---------|----------|
| **Tailwind CSS v4** | Utility foundation | Spacing, typography, responsive grid, colors |
| **DaisyUI** | Themed primitives | Buttons, cards, badges, modals, drawer sidebar |
| **maryUI** | High-level form components | Inputs, tables, selects, dropdowns, file upload |

Livewire manages server-side state; Alpine.js handles client-side behavior. The visual language
is clean and professional: neutral monochrome base, single accent brand color, low-saturation
backgrounds, subtle borders, bold typography, minimal shadows.

---

## 2. Layout Structure

### Cross-Cutting Layouts

Located at `resources/views/core/layouts/`:

| Layout | File | Purpose |
|--------|------|---------|
| Base | `base.blade.php` | Root HTML shell with theme, branding CSS, Alpine.js |
| Head | `base/head.blade.php` | `<head>` element with meta tags and assets |
| Footer | `base/footer.blade.php` | Page footer with credits |
| App | `app.blade.php` | Authenticated layout (drawer sidebar + header + content) |
| Guest | `guest.blade.php` | Public/guest layout (centered card) |
| Sidebar | `sidebar.blade.php` | Drawer sidebar with role-filtered navigation |
| Header | `header.blade.php` | Sticky top header with search and actions |

### Module-Specific Layouts

Located at `resources/views/{module}/layouts/`:

| Layout | Namespace | Used By |
|--------|-----------|---------|
| Auth | `auth::layouts.auth` | Login, password reset pages |
| Setup | `setup::layouts.setup` | Multi-step setup wizard |

### Convention

- Layout shared by multiple modules → `resources/views/core/layouts/`
- Layout specific to one module → `resources/views/{module}/layouts/`

---

## 3. Dark Mode

Class-based dark mode via `data-theme` attribute on `<html>`. Three-state switcher: light, dark,
system preference. Preference stored in cookie, applied on subsequent visits.

Both themes defined as DaisyUI theme plugins in CSS. Brand colors are not hardcoded — injected
at runtime via inline `<style>` block. Admin can change brand colors and see them reflected in
both themes immediately, without CSS recompilation.

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

| Pattern | Syntax | Example |
|---------|--------|---------|
| Anonymous component | `x-{module}::name` | `x-setup::brand` |
| View include | `{module}::view.name` | `setup::layouts.setup` |

Excluded directories: `components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor`.

---

## 6. SPA Navigation

Internal links use `wire:navigate` for AJAX page transitions. Content area swaps without full
page reload. Browser history and URL update normally — bookmarking and back button work as
expected. No JavaScript framework needed.

---

## 7. Component Library Patterns

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

## 8. Guide Component Pattern

Every page with a non-trivial workflow MUST include a floating guide button (bottom-right,
question mark icon) that opens a modal with step-by-step instructions. See
`docs/architecture/livewire-pattern.md` (§11) for the full implementation pattern.

Implementation reference: `resources/views/setup/components/setup-guide.blade.php`

### Requirements

- **File:** `resources/views/{module}/components/{page-name}-guide.blade.php`
- **Trigger:** Fixed floating button, bottom-right, `z-50`, primary color
- **Modal:** `x-mary-modal` with numbered steps and a tip section
- **Localization:** All strings in `__('{module}.guide.*')`
- **Integration:** Parent component includes `@include('{module}.components.{page-name}-guide')` and exposes `$showGuide` boolean

---

## 9. Key Locations

| Asset | Path |
|-------|------|
| Layout templates | `resources/views/core/layouts/` |
| UI components | `resources/views/core/ui/` |
| CSS entry point | `resources/css/app.css` |
| JS entry point | `resources/js/app.js` |
| maryUI config | `config/mary.php` |
| Sidebar menu | `config/menu.php` |
| Theme switcher | `app/Settings/Livewire/ThemeSwitcher.php` |
| Language switcher | `app/Settings/Livewire/LangSwitcher.php` |
