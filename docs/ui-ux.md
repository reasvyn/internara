# UI/UX Design System

Internara uses a modern, reactive UI stack designed for speed and clarity.

## 1. Technical Stack

- **Styling**: TailwindCSS v4 with OKLCH color system.
- **Components**: DaisyUI 5 (Theme engine) and maryUI (Blade components).
- **Interactivity**: Livewire 4 (Server-side state) and Alpine.js (Instant client-side behavior).
- **Typography**: Instrument Sans (Self-hosted).

## 2. Design Tokens

The system uses **semantic colors** based on the OKLCH color space for better contrast and accessibility.
- **Primary**: Main institutional color.
- **Base**: Backgrounds (100, 200, 300 levels).
- **Feedback**: Info, Success, Warning, Error.

## 3. Layout System

- **Responsive**: Mobile-first design with `md` and `lg` breakpoints.
- **Theming**: Light and Dark mode with automatic system detection.
- **SPA Mode**: `wire:navigate` is used for instant page transitions without full reloads.

## 4. Interaction Patterns

### Alpine.js (Client-Side)
Used for instant UI feedback like toggling menus, theme switching, or client-side validation.

### Livewire (Server-Side)
Used for data-intensive operations, form processing, and syncing server state with the UI.

## 5. Components

We prefer using **maryUI** components for consistency:
- **Tables**: `x-mary-table` for data lists with pagination.
- **Forms**: `x-mary-input`, `x-mary-select`, etc.
- **Feedback**: `x-mary-badge` for statuses and `x-mary-toast` for notifications.
