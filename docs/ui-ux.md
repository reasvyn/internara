# UI/UX Design

## Stack

| Layer | Technology |
|---|---|
| CSS | TailwindCSS 4 (CSS-first config, OKLCH monochrome themes) |
| Components | DaisyUI 5 + maryUI 2 (x-mary-* prefix) |
| Interactivity | Livewire 4 (server state) + Alpine.js (client behavior) |
| Typography | Instrument Sans (self-hosted, 400/500/600 weights) |
| Icons | Blade Tabler Icons |
| Bundler | Vite 7 |

## Theming

Custom DaisyUI themes are defined in `resources/css/app.css` — light (default, monochrome) and dark (inverted monochrome) — with class-based `.dark` toggle.

Dark mode uses a class strategy: `.dark` class on the HTML element.

## Layout

- Mobile-first responsive design
- SPA-style navigation via `wire:navigate`
- Sticky header with theme switcher, language switcher, notification bell, and user dropdown
- Fixed-width sidebar with role-based navigation sections
- Enterprise data manager layout with search, filters, and bulk actions

## Components

Use `<x-mary-*>` components for consistency. Commonly used: `x-mary-table`, `x-mary-input`, `x-mary-select`, `x-mary-badge`, `x-mary-card`, `x-mary-header`, `x-mary-icon`, `x-mary-toast`.

The codebase uses a hybrid approach — structural components use maryUI, while custom styling uses plain Tailwind classes.

## Localization

The app supports Indonesian (ID) and English (EN) with real-time toggling. Locale is applied per-session via `SetLocaleMiddleware`. LTR only (no RTL for initial release).
