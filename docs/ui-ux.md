# UI/UX Design System

## Stack

| Layer | Technology |
|---|---|
| CSS | TailwindCSS v4 (CSS-first config, OKLCH monochrome themes) |
| Components | DaisyUI 5 + maryUI 2 (`x-mary-*` prefix) |
| Interactivity | Livewire 4 (server state) + Alpine.js (client behavior) |
| Typography | Instrument Sans (self-hosted, 400/500/600) |
| Icons | Blade Tabler Icons |
| Bundler | Vite 7 |

## Theming

Two custom DaisyUI themes defined in `resources/css/app.css`:

| Theme | Default | Notes |
|---|---|---|
| `light` | Yes | Monochrome palette, pure white `base-100`, thin borders |
| `dark` | Prefers-dark | Inverted monochrome, dark gray `base-100` |

Dark mode uses class strategy: `@custom-variant dark (&:where(.dark, .dark *))`.

## Layout & Navigation

- Mobile-first responsive with `md`/`lg` breakpoints
- SPA-style navigation via `wire:navigate`
- Sticky header with theme switcher, language switcher, notification bell, user dropdown
- 280px sidebar with role-based navigation sections
- Enterprise data manager layout with search, filters, bulk actions

## maryUI Components

Use `<x-mary-*>` components for consistency. Commonly used: `x-mary-table`, `x-mary-input`, `x-mary-select`, `x-mary-badge`, `x-mary-card`, `x-mary-header`, `x-mary-icon`, `x-mary-toast`.

The codebase uses a hybrid approach — structural components use maryUI, custom styling uses plain Tailwind classes.

## Localization

- Real-time language toggling (ID/EN) via `SetLocaleMiddleware`
- LTR only (no RTL for initial release)
- OKLCH colors ensure accessibility