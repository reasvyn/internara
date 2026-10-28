# TallStackUI — Dependency Reference

## Description

Conceptual reference for **TallStackUI 4** (`tallstackui/tallstackui v4.1.0`) — the component
library supplying pre-built TALL-stack UI elements (buttons, inputs, tables, modals) used across
all Internara views.

---

## Installed & Role

| | |
|---|---|
| Installed | `v4.1.0` (`composer.json`: `^4.0`) |
| Role | Blade component library on top of Livewire + Tailwind + Alpine |
| Usage footprint | ~141 Blade view files render `x-ts-*` components |
| Needed Alpine plugin | `@alpinejs/anchor` (`npm`) — must be registered via `Alpine.plugin(anchor)` in `resources/js/app.js`, else all floating panels (`x-ts-dropdown`, styled selects, dials) teleport to `<body>` unpositioned instead of anchoring to their trigger |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **`x-ts-*` components** | Prefixed Blade components (`<x-ts-button>`, `<x-ts-input/>`, `<x-ts-table/>`) — consistent API across the app instead of hand-rolled markup |
| **Interactions** | Built-in client-side behaviors (color transitions, loading states, enter/leave animations) activated via attributes — no custom JS needed |
| **Soft Customization** | Component styling customized centrally via `TallStackUi::customize()` (per-component block class replacement) rather than overriding markup per view |
| **Form integration** | Inputs bind natively to Livewire properties (`wire:model` compatible) |

---

## How Internara Uses It

- Standard component vocabulary for every module view — forms, tables, modals, notifications
- Theming aligned with the self-hosted design system ([`ui-ux/design-system.md`](../../guides/ui-ux/design-system.md),
  [`foundation/branding.md`](../../guides/branding.md))
- Component conventions and interaction rules documented in
  [`guides/arch/ui-pattern.md`](../../guides/arch/ui-pattern.md) §7 and the usage guide
  [`guides/ui-ux/tallstackui.md`](../../guides/ui-ux/tallstackui.md); overrides live in
  `app/Providers/AppServiceProvider.php` (`TallStackUi::customize()…`)

## Quick References

- [Official docs](https://tallstackui.com/docs) — component reference and customization guide
- [`docs/guides/ui-ux/design-system.md`](../../guides/ui-ux/design-system.md) — design system context
