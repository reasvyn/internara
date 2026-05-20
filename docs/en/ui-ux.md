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

The primary authenticated layout uses a drawer sidebar that is hidden on
mobile and visible on desktop. The sidebar contains navigation grouped by
domain, filtered by the user's role. A sticky header spans the top of the
content area with the page title on desktop and a hamburger toggle on mobile.
The header also contains the theme switcher, language switcher, notification
bell, and user dropdown.

Public pages (login, password reset) use a centered card layout. The setup
wizard uses a wider layout suitable for multi-step forms with validation.

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

Layout templates are in `resources/views/layouts/`. UI components are in
`resources/views/components/ui/`. The CSS entry point is
`resources/css/app.css`. JavaScript entry point is `resources/js/app.js`.
The maryUI configuration is in `config/mary.php`. The sidebar menu
structure is in `config/menu.php`. The Livewire components for theme
switching and language switching are in
`app/Domain/Core/Livewire/` and `app/Domain/Shared/Livewire/`.
