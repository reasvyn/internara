# UI/UX Design

## Design System Philosophy

The interface is built on three layers: Tailwind CSS provides the utility
foundation, DaisyUI provides themed primitives (buttons, cards, badges,
modals), and maryUI provides high-level form components (inputs, tables,
selects, dropdowns). Livewire manages server-side state, and Alpine.js
handles client-side behavior.

The visual language is clean and professional: a neutral monochrome base with
a single accent brand color, low-saturation backgrounds, subtle borders, bold
typography, and minimal shadows. The goal is clarity and readability —
information should be easy to scan and actions should be obvious.

## Component Library Choices

maryUI components are used for forms and data-heavy interfaces: tables with
sorting and pagination, input fields with validation, select dropdowns, and
form wrappers. DaisyUI components are used for structural elements: cards,
badges, avatars, modals, and the drawer sidebar.

This combination was chosen because maryUI integrates deeply with Livewire
(automatic spinner states, validation error styling, wire:model support)
while DaisyUI provides a comprehensive design token system (colors, spacing,
typography) that is themeable at runtime.

## Layout Structure

Layouts are split between cross-cutting and domain-specific:

| Scope | Directory | Namespace | Referenced As |
|---|---|---|---|
| Cross-cutting | `resources/views/shared/layouts/` | `shared` (auto-registered via `DomainServiceProvider`) | `x-shared::layouts.base`, `x-shared::layouts.app` |
| Domain-specific | `resources/views/{domain}/layouts/` | `{domain}` (auto-registered via `DomainServiceProvider`) | `setup::layouts.setup`, `auth::layouts.auth` |

Cross-cutting layouts (`layouts/`) are used by the main application shell:
- `base.blade.php` — root HTML shell with theme, branding CSS, Alpine.js
- `base/head.blade.php` — `<head>` element with meta tags and assets
- `base/footer.blade.php` — page footer with credits
- `app.blade.php` — authenticated layout (drawer sidebar + header + content)
- `guest.blade.php` — public/guest layout (centered card)
- `sidebar.blade.php` — drawer sidebar with role-filtered navigation
- `header.blade.php` — sticky top header with search and actions

Domain-specific layouts (`{domain}/layouts/`) are used by domain Livewire
components via the `#[Layout]` attribute:
- `auth/layouts/auth.blade.php` — centered card for login/password-reset (used as `auth::layouts.auth`)
- `setup/layouts/setup.blade.php` — wider multi-step layout for the wizard (used as `setup::layouts.setup`)

The convention for choosing where a layout belongs:
1. If a layout is shared by multiple domains → `resources/views/shared/layouts/`
2. If a layout is specific to one domain → `resources/views/{domain}/layouts/`

This prevents domain-specific layouts from accumulating in the global
directory and keeps the boundary explicit.

## Dark Mode Approach

Dark mode is class-based: a `data-theme` attribute on the `<html>` element
toggles between light and dark themes. A three-state switcher lets users
choose light, dark, or system preference (which follows the operating system
setting). The preference is stored in a cookie and applied on subsequent
visits.

Both themes are defined as DaisyUI theme plugins in the CSS. Brand colors are
not hardcoded in the theme definitions — they are injected at runtime via
inline CSS. This means the admin can change brand colors and see them
reflected in both themes immediately, without recompiling CSS.

Dark mode lightens brand colors by 40% so they remain visible on dark
backgrounds, and adapts the base surface hierarchy with appropriate contrast.

## Responsive Strategy

The layout is mobile-first. On small screens, the sidebar is hidden and
accessed via a hamburger toggle. On desktop (1024px and above), the sidebar
is always visible. Tables use responsive classes to hide secondary columns on
mobile, and stat grids adapt from single column to multi-column as viewport
width increases.

The container width is constrained to `max-w-7xl` for normal pages and
`max-w-5xl` for setup and guest pages, ensuring content does not stretch too
wide on large monitors.

## View Namespaces

Each domain's view directory (`resources/views/{domain}/`) is registered as a
Blade namespace by `DomainServiceProvider::registerBladeNamespaces()`. This
provides two access patterns:

| Pattern | Syntax | Example | Mechanism |
|---|---|---|---|
| Anonymous component | `x-{domain}::component-name` | `x-setup::brand` | `Blade::anonymousComponentPath()` |
| View include | `{domain}::view.name` | `setup::layouts.setup` | `View::addNamespace()` |

Anonymous components are used for reusable UI fragments (cards, modals, buttons).
View namespace includes are required by Livewire's `#[Layout]` attribute and
explicit `@include('{domain}::view')` directives.

The registration happens at boot time in `DomainServiceProvider`:

```php
foreach ($domainDirs as $dir) {
    $name = basename($dir);

    if (in_array($name, $excluded, true)) {
        continue;
    }

    Blade::anonymousComponentPath($dir, $name);
    View::addNamespace($name, $dir);
}
```

Directories excluded from registration: `components`, `emails`, `errors`,
`layouts`, `mcp`, `pdf`, `vendor`. These are either core structural directories
or belong to third-party packages that manage their own namespaces.

The `setup::layouts.setup` layout used by `SetupWizard` depends on this
namespace registration. Without `View::addNamespace('setup', ...)`, the
Livewire `#[Layout('setup::layouts.setup')]` attribute would fail with
"No hint path defined for [setup]".

## SPA Navigation

Internal links use `wire:navigate` for page transitions. This converts
navigation into AJAX requests that swap only the content area, avoiding a
full page reload. The browser's history and URL are updated normally, so
bookmarking and the back button work as expected.

This approach provides the feel of a single-page application without the
complexity of a JavaScript framework. Livewire re-renders only the changed
content, and the browser does not re-download CSS and JavaScript on every
navigation.

## Where to Find It

Layout templates are in `resources/views/shared/layouts/`. UI components are in
`resources/views/components/ui/`. The CSS entry point is
`resources/css/app.css`. JavaScript entry point is `resources/js/app.js`.
The maryUI configuration is in `config/mary.php`. The sidebar menu
structure is in `config/menu.php`. The Livewire components for theme
switching and language switching are in
`app/Domain/Core/Livewire/` and `app/Domain/Shared/Livewire/`.
