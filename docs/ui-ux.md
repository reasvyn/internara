# UI/UX Design System

## Stack

| Layer | Technology |
|---|---|
| CSS | TailwindCSS v4 (OKLCH color system) |
| Component theme | DaisyUI 5 |
| Blade components | maryUI 2 |
| Interactivity | Livewire 4 (server state) + Alpine.js (client behavior) |
| Typography | Instrument Sans (self-hosted) |
| Icons | Blade Tabler Icons |

## Design Tokens

Semantic colors based on OKLCH for contrast and accessibility:

- **Primary** — Institutional brand color
- **Base** — Backgrounds (100, 200, 300 levels)
- **Feedback** — Info, success, warning, error

## Layout

- Mobile-first responsive design with `md` and `lg` breakpoints
- Light and dark mode with automatic system detection
- SPA-style navigation via `wire:navigate` for instant page transitions

## Interaction Patterns

| Tool | Use case |
|---|---|
| Alpine.js | Instant client-side feedback (menus, toggles, validation) |
| Livewire | Data-intensive operations, form processing, server-state sync |

## Preferred Components

Use maryUI components for consistency:

- Tables: `x-mary-table` with pagination
- Forms: `x-mary-input`, `x-mary-select`, etc.
- Status: `x-mary-badge`
- Feedback: `x-mary-toast`
