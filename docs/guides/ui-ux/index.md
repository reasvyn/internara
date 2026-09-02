# UI/UX — Comprehensive Guide Index

## Description

Complete documentation for Internara's UI system, built on the TALL stack:
**T**ailwind CSS v4, **A**lpine.js v3, **L**aravel 13, **L**ivewire v4, enhanced with
**TallStackUI v4** component library.

---

## UI Stack Overview

| Layer | Technology | Version | Role |
|-------|-----------|---------|------|
| **CSS Framework** | Tailwind CSS | v4.3+ | Utility-first styling, CSS-first configuration, design tokens |
| **Interactivity** | Alpine.js | v3.x | Lightweight reactivity for dropdowns, modals, toggles |
| **Full-Stack Components** | Livewire | v4.x | Server-rendered reactive components, SPA-like experience |
| **Component Library** | TallStackUI | v4.x | 80+ pre-built Blade components (forms, UI, interactions) |
| **Build Tool** | Vite | v5.x | Fast HMR, production bundling, Tailwind integration |

---

## Documentation Structure

| Guide | Description | Audience |
|-------|-------------|----------|
| [Design System & Guidelines](./design-system.md) | Design system philosophy, layouts, dark mode, responsive, accessibility, routing, localization, component patterns | All developers |
| [Livewire](./livewire.md) | Complete Livewire 4 guide — components, data binding, events, forms, file uploads, testing | All developers |
| [Tailwind CSS](./tailwindcss.md) | Complete Tailwind CSS 4 guide — utility-first, CSS-first config, theming, dark mode, responsive | Frontend developers |
| [TallStackUI](./tallstackui.md) | Complete TallStackUI v4 reference — form components, UI components, interactions, customization | All developers |
| [Integration](./integration.md) | How all UI technologies work together — patterns, conventions, best practices | All developers |

---

## Quick Reference

### Component Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                               │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              Blade Templates + Livewire                 │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │ │
│  │  │  TallStackUI │  │  Alpine.js  │  │   Tailwind  │     │ │
│  │  │ Components   │  │  Dropdowns  │  │   Utilities │     │ │
│  │  │ (x-modal,    │  │  Toggles    │  │   (flex,    │     │ │
│  │  │  x-input)    │  │  Tooltips   │  │    grid)    │     │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘     │ │
│  └─────────────────────────────────────────────────────────┘ │
│                            │ AJAX                             │
└────────────────────────────┼─────────────────────────────────┘
                             │
┌────────────────────────────┼─────────────────────────────────┐
│                     Laravel Backend                          │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              Livewire Components (PHP)                   │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │ │
│  │  │  Properties  │  │   Actions   │  │   Events    │     │ │
│  │  │  (state)     │  │  (methods)  │  │  (dispatch) │     │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘     │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### File Locations

```
resources/
├── css/
│   └── app.css                    # Tailwind imports + @theme configuration
├── js/
│   └── app.js                     # Alpine.js + Livewire initialization
└── views/
    ├── components/                # Blade components (TallStackUI + custom)
    │   └── ui/                    # Custom UI components
    ├── layouts/                   # App layouts
    │   └── app.blade.php          # Main layout (includes tallstackui:script)
    └── {module}/                  # Module-specific views

app/Modules/{Module}/Livewire/     # Livewire components per module
```

### Key Conventions

| Convention | Rule | Rationale |
|------------|------|-----------|
| **Component prefix** | TallStackUI: `x-` (e.g., `<x-modal>`) | Consistent with Laravel Blade components |
| **Livewire binding** | `wire:model` for two-way, `wire:click` for actions | Standard Livewire directives |
| **Tailwind config** | CSS-first via `@theme` in `app.css` | v4 standard, no `tailwind.config.js` |
| **Dark mode** | Class-based: `@custom-variant dark` | User-controllable theme toggle |
| **Component size** | `xs`, `sm`, `md` (default), `lg`, `xl` | Consistent across TallStackUI |
| **Component color** | `primary` (default), `secondary`, `red`, etc. | Tailwind palette integration |

---

## Installation & Setup

### Prerequisites

```bash
# PHP 8.4+, Composer 2.x, Node.js 20+, npm 10+
composer install
npm install
```

### TallStackUI Setup

```bash
# Install via Composer
composer require tallstackui/tallstackui:^4.0

# Publish config (optional)
php artisan vendor:publish --tag=tallstackui.config
```

### CSS Configuration (`resources/css/app.css`)

```css
@import "tailwindcss";
@import '../../vendor/tallstackui/tallstackui/css/v4.css';

@plugin '@tailwindcss/forms';

@source '../../vendor/tallstackui/tallstackui/**/*.php';
@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
```

### Layout Setup (`resources/views/layouts/app.blade.php`)

```blade
<html>
<head>
    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
```

---

## Design System

### Color Palette

Internara uses Tailwind's default palette with semantic aliases:

```css
@theme {
  /* Brand colors */
  --color-primary: oklch(0.65 0.20 250);
  --color-secondary: oklch(0.70 0.15 160);

  /* Semantic aliases */
  --color-success: var(--color-green-500);
  --color-warning: var(--color-amber-500);
  --color-danger: var(--color-red-500);
  --color-info: var(--color-sky-500);
}
```

### Typography

```css
@theme {
  --font-sans: "Inter", system-ui, sans-serif;
  --font-mono: "JetBrains Mono", monospace;
}
```

### Spacing Scale

Tailwind v4 uses a single `--spacing` value (default: `4px`) that drives all spacing utilities:
`px-*`, `py-*`, `m-*`, `w-*`, `h-*`, `gap-*`, etc.

---

## Browser Support

| Browser | Version | Notes |
|---------|---------|-------|
| Chrome | 90+ | Full support |
| Firefox | 90+ | Full support |
| Safari | 15+ | Full support |
| Edge | 90+ | Full support |
| Mobile Chrome | Latest | Responsive design |
| Mobile Safari | 15+ | Responsive design |

---

## Performance Budget

| Metric | Target | Measurement |
|--------|--------|-------------|
| First Contentful Paint | < 1.5s | Lighthouse |
| Largest Contentful Paint | < 2.5s | Lighthouse |
| Time to Interactive | < 3.5s | Lighthouse |
| Total CSS size | < 50KB | `npm run build` |
| JavaScript bundle | < 200KB | `npm run build` |

---

## Related Documentation

- [Architecture Overview](../../architecture.md) — 4-layer architecture, Action Triad
- [Conventions](../../conventions.md) — Code rules, security, performance
- [Livewire Pattern](../arch/livewire-pattern.md) — Livewire-specific patterns
- [UI Pattern](../arch/ui-pattern.md) — General UI patterns
- [Testing Pattern](../arch/testing-pattern.md) — Testing Livewire components
- [Infrastructure/Deployment](../infra/deployment.md) — Build & deploy process

---

## External Resources

| Resource | URL | Description |
|----------|-----|-------------|
| Livewire Docs | https://livewire.laravel.com/docs | Official Livewire 4 documentation |
| Tailwind CSS Docs | https://tailwindcss.com/docs | Official Tailwind CSS 4 documentation |
| TallStackUI Docs | https://tallstackui.com/docs | Official TallStackUI 4 documentation |
| Alpine.js Docs | https://alpinejs.dev/start-here | Official Alpine.js 3 documentation |
| Vite Docs | https://vitejs.dev/guide/ | Official Vite 5 documentation |
| TALL Stack | https://tallstack.dev/ | TALL stack overview |