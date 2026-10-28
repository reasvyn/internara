# UI/UX — Comprehensive Guide Index

## Description

Complete documentation for Internara's UI system, built on the TALL stack:
**T**ailwind CSS v4, **A**lpine.js v3, **L**aravel 13, **L**ivewire v4, enhanced with
**TallStackUI v4** component library.

---

## UI Stack Overview

- **CSS Framework — Tailwind CSS v4.3+**: Utility-first styling, CSS-first configuration, design tokens
- **Interactivity — Alpine.js v3.x**: Lightweight reactivity for dropdowns, modals, toggles
- **Full-Stack Components — Livewire v4.x**: Server-rendered reactive components, SPA-like experience
- **Component Library — TallStackUI v4.x**: 80+ pre-built Blade components (forms, UI, interactions)
- **Build Tool — Vite v8.x**: Fast HMR, production bundling, Tailwind integration

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
│  │  │ (x-ts-modal, │  │  Toggles    │  │   (flex,    │     │ │
│  │  │  x-ts-input) │  │  Tooltips   │  │    grid)    │     │ │
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
│   └── app.css                    # Tailwind imports, @theme tokens, DaisyUI shims
├── fonts/                         # Self-hosted Instrument Sans 400/500/600/700 (+ OFL.txt)
├── js/
│   └── app.js                     # Alpine.js + Livewire init, theme/locale wiring
└── views/
    ├── ui/
    │   ├── layouts/               # Shell: app, base (+base/head, base/footer), guest, header, sidebar
    │   ├── components/            # 34 design-system primitives (x-ui::components.*)
    │   └── widgets/               # 6 composable widgets (x-ui::widgets.*)
    └── {module}/                  # Module views (+ components/, layouts/)

app/Modules/UI/Livewire/           # Livewire base classes (BaseRecordManager, BaseWizard, …)
app/Modules/{Module}/Domain/{Domain}/Livewire/  # Business Livewire components
```

### Key Conventions

- **Component prefix**: TallStackUI uses `x-ts-*` (e.g., `<x-ts-modal>`, `<x-ts-input>`) pinned via `config/tallstackui.php` (`'prefix' => 'ts-'`)
- **Livewire binding**: `wire:model` for two-way, `wire:click` for actions — standard Livewire directives
- **Tailwind config**: CSS-first via `@theme` in `app.css` — v4 standard, no `tailwind.config.js`
- **Dark mode**: Class-based: `@custom-variant dark` — user-controllable theme toggle
- **Component size**: `xs`, `sm`, `md` (default), `lg`, `xl` — consistent across TallStackUI
- **Component color**: semantic variants only — `primary`, `secondary`, `tertiary`, `accent`, `muted`, `ghost`, `neutral`, `info`, `success`, `warning`, `error`. Never Tailwind palette utilities (`bg-red-500`) or arbitrary hex (`bg-[#059669]`)

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
@import 'tailwindcss';
@import '../../vendor/tallstackui/tallstackui/css/v4.css';
@import 'flag-icons/css/flag-icons.min.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../vendor/tallstackui/tallstackui/**/*.php';

```

No `@plugin` line: form styling comes from the TallStackUI v4 CSS import above, not from
`@tailwindcss/forms`.

### Layout Setup (`resources/views/ui/layouts/base.blade.php`)

Internara ships the HTML shell as the `ui` view namespace, so a module layout extends
`<x-ui::layouts.base>` rather than re-declaring `<html>`. An excerpt of the shell:

```blade
{{-- resources/views/ui/layouts/base.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ request()->cookie('theme', setting('default_theme', 'system')) }}"
      @if (request()->cookie('theme', setting('default_theme', 'system')) === 'dark') class="dark" @endif>
<head>
    <x-ui::layouts.base.head :$title />

    {{-- Theme::cssVariables() injected as scoped CSS custom properties --}}
    <style>
        html[data-theme='light'], html:not(.dark):not([data-theme='dark']) { /* light vars */ }
        html[data-theme='dark'], html.dark { /* dark vars */ }
    </style>

    @livewireStyles
</head>
<body class="{{ $bodyClass }}">
    <a href="#main-content" class="sr-only focus:not-sr-only …">{{ __('common.skip_to_content') }}</a>
    {{ $slot }}
    <x-ts-toast />
    <x-ts-dialog />
    @livewireScripts
</body>
</html>

```

`ui::layouts.base.head` carries the asset order that matters: `<tallstackui:script />` **before**
`@vite(['resources/css/app.css', 'resources/js/app.js'])`.

---

## Design System

### Color Palette

Internara ships a **self-hosted semantic palette** (no DaisyUI plugin, no Tailwind-default colour
dependency). `resources/css/app.css` is the single source of truth; the values below mirror it —
never copy from this page into the CSS.

```css
@theme {
  /* Surfaces */
  --color-base-100: #ffffff;
  --color-base-200: #f4f4f5;
  --color-base-300: #e4e4e7;
  --color-base-content: #1a1a1a;

  /* Brand — overridden at runtime by Theme::cssVariables() */
  --color-primary: #059669;
  --color-secondary: #6b7280;
  --color-accent: #f97316;

  /* Status (oklch, chroma kept under ~0.2 to stay in sRGB gamut) */
  --color-info: oklch(60% 0.16 250);
  --color-success: oklch(58% 0.15 150);
  --color-warning: oklch(75% 0.15 80);
  --color-error: oklch(57% 0.19 25);
}

```

The error variant is `--color-error` (there is no `--color-danger` token; `.btn-danger` exists only
as a legacy class alias). Runtime brand overrides are injected as inline `<style>` by
`Theme::cssVariables()`, scoped to `html[data-theme='light']` / `html[data-theme='dark']`.

See [Design System §3](./design-system.md#3-theme-system--color-schema) for the resolution chain,
presets, and dark-mode computation. For the semantic meaning of each variant (`primary`,
`secondary`, `accent`, `muted`, `ghost`, `info`, `success`, `warning`, `error`), see
[§3.1](./design-system.md#31-user-oriented-semantic-variant-architecture).

### Typography

One self-hosted family, four weights, four visual tiers:

```css
@theme {
  --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
  /* --font-mono intentionally unset: Tailwind's default monospace stack applies */
}

```

Instrument Sans ships `400/500/600/700` only — there is no 800/900, so `font-extrabold` and
`font-black` are forbidden. The H1–H6 mapping, body/meta sizes, and the hierarchy levers are normative
in [Design System §5](./design-system.md#5-typography--visual-hierarchy).

### Spacing Scale

Tailwind v4 uses a single `--spacing` value (default: `4px`) that drives all spacing utilities:
`px-*`, `py-*`, `m-*`, `w-*`, `h-*`, `gap-*`, etc.

---

## Browser Support

- **Chrome 90+**: Full support
- **Firefox 90+**: Full support
- **Safari 15+**: Full support
- **Edge 90+**: Full support
- **Mobile Chrome (Latest)**: Responsive design
- **Mobile Safari 15+**: Responsive design

---

## Performance Budget

- **First Contentful Paint**: < 1.5s — measured via Lighthouse
- **Largest Contentful Paint**: < 2.5s — measured via Lighthouse
- **Time to Interactive**: < 3.5s — measured via Lighthouse
- **Total CSS size**: < 50KB — measured via `npm run build`
- **JavaScript bundle**: < 200KB — measured via `npm run build`

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

- **Livewire Docs**: https://livewire.laravel.com/docs — Official Livewire 4 documentation
- **Tailwind CSS Docs**: https://tailwindcss.com/docs — Official Tailwind CSS 4 documentation
- **TallStackUI Docs**: https://tallstackui.com/docs — Official TallStackUI 4 documentation
- **Alpine.js Docs**: https://alpinejs.dev/start-here — Official Alpine.js 3 documentation
- **Vite Docs**: https://vite.dev/guide/ — Official Vite documentation
- **TALL Stack**: https://tallstack.dev/ — TALL stack overview