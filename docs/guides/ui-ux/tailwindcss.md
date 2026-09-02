# Tailwind CSS 4 — Complete Styling Guide

## Description

Tailwind CSS is a utility-first CSS framework that enables rapid UI development by composing
small, single-purpose classes directly in markup. This guide covers Tailwind CSS v4's complete
system, from core concepts to advanced theming, aligned with Internara's design system.

---

## Table of Contents

1. [Core Concepts & Philosophy](#core-concepts--philosophy)
2. [CSS-First Configuration](#css-first-configuration)
3. [New Features in v4](#new-features-in-v4)
4. [Color System & Semantic Tokens](#color-system--semantic-tokens)
5. [Responsive Design Patterns](#responsive-design-patterns)
6. [Dark Mode Implementation](#dark-mode-implementation)
7. [Component Styling Patterns](#component-styling-patterns)
8. [Performance Optimization](#performance-optimization)
9. [Integration with Laravel/Livewire](#integration-with-laravellivewire)
10. [Best Practices](#best-practices-for-internara)

---

## Core Concepts & Philosophy

### Utility-First Approach

Tailwind is a utility-first CSS framework: you compose design by combining many single-purpose,
presentational classes directly in markup rather than authoring named CSS classes.

**Benefits:**
- Faster authoring (no class naming, no HTML↔CSS context switching)
- Safer changes (a utility only affects its element)
- Easier maintenance of long-lived projects
- Portable code (structure + styling live together)
- CSS stops growing linearly with features

### Why Not Inline Styles?

Utilities enforce design constraints (predefined scale), support states (`hover:`, `focus:`),
and respect media queries (`md:`, `dark:`) — none of which inline styles can do.

### How Tailwind Works

Tailwind scans your project files for symbols that look like class names and generates only the
CSS for classes you use. That's why arbitrary values like `bg-[#316ff6]` or
`grid-cols-[24rem_2.5rem_minmax(0,1fr)]` work without prior registration.

### Class Composition

Utilities like `blur-sm` and `grayscale` each set their own CSS variable and contribute to a
shared `filter` property, allowing composition:

```css
.blur-sm    { --tw-blur: blur(var(--blur-sm)); filter: var(--tw-blur,) var(--tw-brightness,) var(--tw-grayscale,); }
.grayscale  { --tw-grayscale: grayscale(100%); filter: var(--tw-blur,) var(--tw-brightness,) var(--tw-grayscale,); }
```

### Complex Selectors & Variants Stacking

```html
<button class="dark:lg:data-current:hover:bg-indigo-600">…</button>
```

Compiles to:
```css
@media (prefers-color-scheme: dark) and (width >= 64rem) {
  button[data-current]:hover { background-color: var(--color-indigo-600); }
}
```

### Arbitrary Variants

For selectors Tailwind doesn't pre-define:
```html
<div class="[&>[data-active]+span]:text-blue-600">
  <span data-active>…</span>
  <span>This text will be blue</span>
</div>
```

### Group Variants

Style a child based on parent state without writing CSS:
```html
<button class="group">
  <span class="group-hover:text-blue-500">Hover me</span>
</button>
```

---

## CSS-First Configuration

### The Biggest v4 Change

**`tailwind.config.js` is gone by default** — all customization happens in CSS.

### Installation (Minimum Viable)

```bash
npm install tailwindcss @tailwindcss/vite
```

```ts
// vite.config.ts
import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
export default defineConfig({ plugins: [tailwindcss()] })
```

```css
/* app.css */
@import "tailwindcss";
```

That's it — no `content` array, no `tailwind.config.js`, no `postcss.config.js` needed for Vite.

### `@theme` Directive

Theme variables are CSS variables defined in `@theme` that **also instruct Tailwind to create
utility classes**.

```css
@import "tailwindcss";

@theme {
  --font-display: "Satoshi", "sans-serif";
  --breakpoint-3xl: 120rem;
  --color-avocado-100: oklch(0.99 0 0);
  --color-avocado-500: oklch(0.84 0.18 117.33);
  --ease-fluid: cubic-bezier(0.3, 0, 0, 1);
}
```

Adding `--color-mint-500` automatically creates `bg-mint-500`, `text-mint-500`, `fill-mint-500`,
etc. Adding `--font-poppins` creates `font-poppins`. Adding `--breakpoint-3xl` creates the `3xl:*`
variant.

### Generated Output

Every theme variable is also emitted as a regular CSS variable in `:root`, so you can reference
tokens anywhere:

```html
<div style="background-color: var(--color-mint-500)">…</div>
<div class="bg-[calc(var(--color-blue-500)/0.5)]">…</div>
```

### Theme Variable Namespaces

| Namespace | Generated utilities/variants |
|-----------|------------------------------|
| `--color-*` | `bg-*`, `text-*`, `border-*`, `fill-*`, `stroke-*`, `ring-*`, `shadow-*`, `accent-*`, `caret-*`, `outline-*`, `decoration-*`, `inset-shadow-*`, `inset-ring-*`, `scrollbar-thumb-*`, `scrollbar-track-*` |
| `--font-*` | `font-sans`, `font-mono`, … |
| `--text-*` | `text-xl`, `text-base`, … |
| `--font-weight-*` | `font-bold`, … |
| `--tracking-*` | `tracking-wide`, … |
| `--leading-*` | `leading-tight`, … |
| `--breakpoint-*` | `sm:*`, `md:*`, `lg:*`, `xl:*`, `2xl:*` |
| `--container-*` | `@3xs:*` … `@7xl:*` + `max-w-*`/`w-*` size utilities |
| `--spacing` | Single base value drives all `px-*`, `py-*`, `m-*`, `w-*`, `h-*`, `gap-*`, etc. |
| `--radius-*` | `rounded-sm`, `rounded-lg`, … |
| `--shadow-*` | `shadow-sm`, `shadow-md`, … |
| `--inset-shadow-*` | `inset-shadow-xs`, … |
| `--drop-shadow-*` | `drop-shadow-md`, … |
| `--blur-*` | `blur-md`, … |
| `--perspective-*` | `perspective-near`, … |
| `--zoom-*` | `zoom-compact`, … |
| `--aspect-*` | `aspect-video`, … |
| `--ease-*` | `ease-out`, `ease-in-out`, … |
| `--animate-*` | `animate-spin`, `animate-pulse`, … |

### Extending vs Overriding vs Disabling Defaults

**Extend** — add new variables alongside defaults:
```css
@theme { --font-script: Great Vibes, cursive; }
```

**Override** — redefine an existing one:
```css
@theme { --breakpoint-sm: 30rem; }   /* sm:* now fires at 480px */
```

**Disable a namespace** — set to `initial` with `*`:
```css
@theme {
  --color-*: initial;
  --color-white: #fff;
  --color-purple: #3f3cbb;
}
```

**Wipe the entire default theme:**
```css
@theme {
  --*: initial;
  --spacing: 4px;
  --font-body: Inter, sans-serif;
  --color-lagoon: oklch(0.72 0.11 221.19);
}
```

### `@theme inline` — Resolving Variable Chains

When a theme variable references another variable, use `inline` so the generated utility uses
the *resolved value*:

```css
@theme inline { --font-sans: var(--font-inter); }
```

Generates:
```css
.font-sans { font-family: var(--font-inter); }
```

Instead of `font-family: var(--font-sans)`. This matters for nested contexts.

### `@theme static` — Emit All Variables

By default Tailwind only emits CSS variables actually used. To force every defined variable into
the final output (e.g., for white-label theming):

```css
@theme static {
  --color-primary: var(--color-red-500);
  --color-secondary: var(--color-blue-500);
}
```

### Custom Keyframes

```css
@theme {
  --animate-fade-in-scale: fade-in-scale 0.3s ease-out;
  @keyframes fade-in-scale {
    0%   { opacity: 0; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1);    }
  }
}
```

### Sharing Across Projects / Monorepos

```css
/* packages/brand/theme.css */
@theme { --color-primary: oklch(0.7 0.15 250); /* … */ }
```

```css
/* packages/admin/app.css */
@import "tailwindcss";
@import "../brand/theme.css";
```

---

## New Features in v4

### High-Performance Oxide Engine

| Metric | v3.4 | v4.0 | Improvement |
|--------|------|------|-------------|
| Full build | 378 ms | 100 ms | **3.78×** |
| Incremental rebuild with new CSS | 44 ms | 5 ms | **8.8×** |
| Incremental rebuild with no new CSS | 35 ms | **192 µs** | **182×** |

### `@import "tailwindcss"` Replaces Three `@tailwind` Directives

Old (v3):
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

New (v4):
```css
@import "tailwindcss";
```

### Automatic Content Detection

- No `content: [...]` array needed
- Auto-ignores everything in `.gitignore`
- Auto-ignores binary file types (images, videos, .zip, …)
- Add explicit sources with `@source`:
  ```css
  @import "tailwindcss";
  @source "../node_modules/@my-company/ui-lib";
  ```

### First-Party Vite Plugin

Best performance path via `@tailwindcss/vite`. CLI moved to `@tailwindcss/cli`; PostCSS plugin
moved to `@tailwindcss/postcss`.

### Designed for the Modern Web

```css
@layer theme, base, components, utilities;

@layer utilities {
  .mx-6          { margin-inline: calc(var(--spacing) * 6); }
  .bg-blue-500\/50 { background-color: color-mix(in oklab, var(--color-blue-500) 50%, transparent); }
}

@property --tw-gradient-from {
  syntax: "<color>";
  inherits: false;
  initial-value: #0000;
}
```

- **Cascade layers** for ordering control
- **`@property` registered custom properties** — enables animating gradient stops
- **`color-mix()`** for opacity adjustments on any color
- **Logical properties** for cleaner RTL support and smaller CSS

### Dynamic Utility Values and Variants

No configuration needed for things you used to declare in `safelist`/`extend`:
```html
<div class="grid grid-cols-15">…</div> <!-- any column count -->
<div data-current class="opacity-75 data-current:opacity-100">…</div>
```

### Container Queries — Built Into Core

```html
<div class="@container">
  <div class="grid grid-cols-1 @sm:grid-cols-3 @lg:grid-cols-4">…</div>
</div>
```

- `@min-*` / `@max-*` for ranges (`@min-md:@max-xl:hidden`)
- Named containers: `@container/main` … `@sm/main:flex-col`
- 13 default container sizes from `@3xs` (16 rem) to `@7xl` (80 rem)

### New 3D Transform Utilities

`rotate-x-*`, `rotate-y-*`, `scale-z-*`, `translate-z-*`, `perspective-near|distant|…`,
`perspective-origin-*` — for `transform-3d` workflows.

### Expanded Gradient APIs

```html
<div class="bg-linear-45 from-indigo-500 via-purple-500 to-pink-500"></div>
<div class="bg-linear-to-r/srgb from-indigo-500 to-teal-400"></div> <!-- sRGB interpolation -->
<div class="bg-linear-to-r/oklch from-indigo-500 to-teal-400"></div>    <!-- OKLCH interpolation -->
<div class="size-24 rounded-full bg-conic/[in_hsl_longer_hue] from-red-600 to-red-600"></div>
<div class="size-24 rounded-full bg-radial-[at_25%_25%] from-white to-zinc-900 to-75%"></div>
```

Default interpolation is OKLAB in v4 (more vivid gradients across the color wheel).

### `@starting-style` Variant — CSS-Only Enter/Exit Transitions

```html
<button popovertarget="my-popover">Check for updates</button>
<div popover id="my-popover" class="transition-discrete starting:open:opacity-0 …">…</div>
```

### `not-*` Variant — Negates Anything

```html
<div class="not-hover:opacity-75">…</div>
<div class="not-supports-hanging-punctuation:px-4">…</div>
```

### Other Notable Additions

- `inset-shadow-*`, `inset-ring-*` (stack up to 4 shadow layers on one element)
- `field-sizing-*` (auto-resize textareas without JS)
- `color-scheme` utilities (fix dark-mode scrollbars)
- `font-stretch-*` utilities (variable font widths)
- `inert:` variant
- `nth-*` variants
- `in-*` variant (like `group-*` without needing `group`)
- `:popover-open` support via the `open:` variant
- New descendant variant for styling all descendants

### Modernized P3 Color Palette

The default 22-color × 11-step palette was rewritten in `oklch` — wider gamut, more vivid at
the saturated end; perceptual balance preserved from v3.

---

## Color System & Semantic Tokens

### Default Palette

22 hues × 11 steps (50 → 950): **red, orange, amber, yellow, lime, green, emerald, teal, cyan,
sky, blue, indigo, violet, purple, fuchsia, pink, rose, slate, gray, zinc, neutral, stone**.

All values are `oklch()`:
```css
--color-red-500: oklch(63.7% 0.237 25.331);
--color-sky-500: oklch(0.685 0.169 237.323);
```

### Semantic Tokens (Recommended Approach)

Define primitives with `--color-*`, then alias them in custom properties:

```css
@theme {
  /* primitive palette */
  --color-brand-500: oklch(0.65 0.20 250);
}

:root {
  --color-bg: var(--color-white);
  --color-fg: var(--color-gray-900);
  --color-accent: var(--color-blue-500);
}

[data-theme="dark"] {
  --color-bg: var(--color-gray-950);
  --color-fg: var(--color-gray-50);
  --color-accent: var(--color-blue-400);
}

@theme inline {
  --color-canvas: var(--color-bg);
  --color-text:   var(--color-fg);
}
```

Now use `bg-canvas`, `text-text` and have them swap automatically by toggling `data-theme="dark"`.

### Opacity Modifier

Slash syntax works on any color, including arbitrary values:
```html
<div class="bg-sky-500/50">…</div>
<div class="bg-pink-500/[71.37%]">…</div>
<div class="bg-cyan-400/(--my-alpha-value)">…</div>   <!-- CSS-var shorthand -->
```

### `light-Dual-Theme

```css
.bg-canvas { background: light-dark(var(--color-white), var(--color-gray-950)); }
```

### Color Utilities That Consume the Palette

`bg-*`, `text-*`, `decoration-*`, `border-*`, `outline-*`, `shadow-*`, `inset-shadow-*`,
`ring-*`, `inset-ring-*`, `accent-*`, `caret-*`, `scrollbar-thumb-*`, `scrollbar-track-*`,
`fill-*`, `stroke-*`.

---

## Responsive Design Patterns

### Mobile-First Breakpoints (5 Defaults)

| Prefix | Min width | Media query |
|--------|-----------|-------------|
| (none) | 0 | base |
| `sm` | 40 rem (640 px) | `@media (width >= 40rem)` |
| `md` | 48 rem (768 px) | `@media (width >= 48rem)` |
| `lg` | 64 rem (1024 px) | `@media (width >= 64rem)` |
| `xl` | 80 rem (1280 px) | `@media (width >= 80rem)` |
| `2xl` | 96 rem (1536 px) | `@media (width >= 96rem)` |

Unprefixed = mobile. `md:` means "at md and above". Each breakpoint has a `max-*` companion:
`max-md` = `@media (width < 48rem)`.

### Targeting a Range / Single Breakpoint

```html
<!-- md to below xl -->
<div class="md:max-xl:flex">…</div>

<!-- exactly the md band -->
<div class="md:max-lg:flex">…</div>
```

### Custom Breakpoints

```css
@theme {
  --breakpoint-xs: 30rem;
  --breakpoint-3xl: 120rem;
}
/* or wipe and redefine: */
@theme {
  --breakpoint-*: initial;
  --breakpoint-tablet: 40rem;
  --breakpoint-laptop: 64rem;
  --breakpoint-desktop: 80rem;
}
```

### One-Off Arbitrary Breakpoints

```html
<div class="max-[600px]:bg-sky-300 min-[320px]:text-center">…</div>
```

### Container Queries (Component-Level Responsive)

13 default sizes from `@3xs` (16 rem) to `@7xl` (80 rem), plus named containers and size
containers.

---

## Dark Mode Implementation

### Default

Out of the box, `dark:` = `@media (prefers-color-scheme: dark)`:
```html
<div class="bg-white dark:bg-gray-800 …">…</div>
```

### Class-Based Toggling (Recommended for User-Controlled Themes)

```css
@import "tailwindcss";
@custom-variant dark (&:where(.dark, .dark *));
```

```html
<html class="dark">
  <body><div class="bg-white dark:bg-black">…</div></body>
</html>
```

### Data-Attribute-Based Toggling

```css
@custom-variant dark (&:where([data-theme=dark], [data-theme=dark] *));
```

### Three-Way: Light / Dark / System

```js
// On page load or when changing themes, add inline in <head> to avoid FOUC
document.documentElement.classList.toggle(
  "dark",
  localStorage.theme === "dark" ||
    (!("theme" in localStorage) && window.matchMedia("(prefers-color-scheme: dark)").matches),
);
localStorage.theme = "light";        // user picks light
localStorage.theme = "dark";         // user picks dark
localStorage.removeItem("theme"); // user picks "system"
```

### Beyond Dark/Light — Multi-Theme via Data Attribute

```css
@custom-variant theme-midnight (&:where([data-theme="midnight"] *));
```

```html
<html data-theme="midnight">
  …
  <button class="theme-midnight:bg-black theme-midnight:text-white">…</button>
</html>
```

---

## Component Styling Patterns

### Layered Approach

Tailwind recommends a layered approach:

1. **Base styles** — reset, typography defaults
2. **Component classes** — `.btn`, `.card` (use `@layer components`)
3. **Utility classes** — applied directly in markup

### Using `@apply` (Sparingly)

```css
@layer components {
  .btn-primary {
    @apply bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600;
  }
}
```

**Note:** Internara prefers direct utility classes in markup over `@apply` for better
portability and clarity.

### Card Component Example

```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
  <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Card Title</h2>
  <p class="mt-2 text-gray-600 dark:text-gray-300">Card content goes here.</p>
</div>
```

### Button Variants

```html
<!-- Primary -->
<button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
  Primary
</button>

<!-- Secondary -->
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
  Secondary
</button>

<!-- Danger -->
<button class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
  Danger
</button>

<!-- Outline -->
<button class="border border-blue-500 text-blue-500 hover:bg-blue-50 px-4 py-2 rounded-lg">
  Outline
</button>
```

### Form Input Styling

```html
<!-- Standard input -->
<input type="text"
  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">

<!-- With error state -->
<input type="text"
  class="w-full px-3 py-2 border border-red-500 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-red-400">
<p class="mt-1 text-sm text-red-500">This field is required</p>
```

---

## Performance Optimization

### Content Detection

Tailwind automatically scans your files for class names. Ensure all files using Tailwind
classes are included in `@source` paths:

```css
@import "tailwindcss";
@source '../views';
@source '../../vendor/tallstackui/tallstackui/**/*.php';
```

### PurgeCSS (Built-In)

Unused classes are automatically removed in production builds. No configuration needed.

### Minification

Vite automatically minifies CSS in production mode (`npm run build`).

### Critical CSS

For above-the-fold content, consider inlining critical styles or using `preload` for fonts.

### Font Loading

```html
<link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
```

---

## Integration with Laravel/Livewire

### Vite Configuration

```ts
// vite.config.ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    tailwindcss(),
  ],
})
```

### Blade Directive for Assets

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Livewire-Specific Patterns

```blade
<!-- Loading state -->
<div wire:loading>
  <div class="animate-pulse">
    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
  </div>
</div>

<!-- Targeted loading -->
<button wire:click="save">
  <span wire:loading.remove wire:target="save">Save</span>
  <span wire:loading wire:target="save">Saving...</span>
</button>

<!-- Transition effects -->
<div wire:transition>
  Content fades in/out on update
</div>
```

### Alpine.js Integration

```html
<!-- x-cloak prevents flash of unstyled content -->
<div x-data="{ open: false }" x-cloak>
  <button @click="open = !open">Toggle</button>
  <div x-show="open" x-transition>
    Dropdown content
  </div>
</div>

<style>
  [x-cloak] { display: none !important; }
</style>
```

---

## Best Practices for Internara

### 1. Use Semantic Tokens

Define semantic color aliases for consistent theming:

```css
@theme {
  --color-primary: oklch(0.65 0.20 250);
  --color-secondary: oklch(0.70 0.15 160);
}
```

### 2. Mobile-First Approach

Start with mobile styles, then add responsive prefixes:

```html
<div class="w-full md:w-1/2 lg:w-1/3">
  <!-- Full width on mobile, half on tablet, third on desktop -->
</div>
```

### 3. Consistent Spacing Scale

Use Tailwind's default spacing scale (4px base):

| Class | Value |
|-------|-------|
| `p-1` | 4px |
| `p-2` | 8px |
| `p-4` | 16px |
| `p-6` | 24px |
| `p-8` | 32px |

### 4. Dark Mode Support

Always provide dark mode variants:

```html
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
  Content
</div>
```

### 5. Focus States

Include focus states for accessibility:

```html
<button class="focus:outline-none focus:ring-2 focus:ring-blue-500">
  Accessible button
</button>
```

### 6. Avoid `@apply` in CSS

Prefer utility classes directly in markup for better portability:

```html
<!-- Good -->
<button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
  Save
</button>

<!-- Avoid -->
<style>
  .btn-save {
    @apply bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg;
  }
</style>
<button class="btn-save">Save</button>
```

### 7. Use Arbitrary Values Sparingly

Arbitrary values are powerful but reduce consistency:

```html
<!-- Good: Use theme token -->
<div class="bg-primary">…</div>

<!-- Acceptable: One-off value -->
<div class="bg-[#316ff6]">…</div>

<!-- Avoid: Multiple arbitrary values -->
<div class="bg-[#316ff6] text-[13px] p-[7px]">…</div>
```

### 8. Group Related Classes

Organize classes by purpose for readability:

```html
<div class="
  /* Layout */
  flex items-center justify-between
  /* Spacing */
  px-4 py-3
  /* Visual */
  bg-white dark:bg-gray-800
  border-b border-gray-200 dark:border-gray-700
  /* Typography */
  text-sm font-medium text-gray-900 dark:text-white
">
  Content
</div>
```

---

## Related Documentation

- [UI/UX Index](./index.md) — Complete UI system overview
- [Livewire Guide](./livewire.md) — Reactive components
- [TallStackUI Guide](./tallstackui.md) — Pre-built components
- [Integration Guide](./integration.md) — How all UI technologies work together
- [UI Pattern](../arch/ui-pattern.md) — Project-specific UI patterns

---

## External Resources

| Resource | URL |
|----------|-----|
| Tailwind CSS Official Docs | https://tailwindcss.com/docs |
| Tailwind CSS v4 Blog Post | https://tailwindcss.com/blog/tailwindcss-v4 |
| Tailwind CSS Colors | https://tailwindcss.com/docs/customizing-colors |
| Tailwind CSS Theme | https://tailwindcss.com/docs/theme |
| Tailwind UI (Components) | https://tailwindui.com/components |
| Heroicons (Icons) | https://heroicons.com/ |