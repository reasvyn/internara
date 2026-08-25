# TallStackUI — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** skill path reference reduced to named-skill mention per documentation-split rule

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

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **`x-ts-*` components** | Prefixed Blade components (`<x-ts-button>`, `<x-ts-input/>`, `<x-ts-table/>`) — consistent API across the app instead of hand-rolled markup |
| **Interactions** | Built-in client-side behaviors (color transitions, loading states, enter/leave animations) activated via attributes — no custom JS needed |
| **Personalization** | Component styling customized centrally (theme classes per variant) rather than overriding markup per view |
| **Form integration** | Inputs bind natively to Livewire properties (`wire:model` compatible) |

---

## How Internara Uses It

- Standard component vocabulary for every module view — forms, tables, modals, notifications
- Theming aligned with the self-hosted design system ([`foundation/ui-ux.md`](../../foundation/ui-ux.md),
  [`foundation/branding.md`](../../foundation/branding.md))
- Component conventions and interaction rules documented in the `tallstackui-development`
  skill

## Quick References

- [Official docs](https://tallstackui.com/docs) — component reference and personalization guide
- [`docs/foundation/ui-ux.md`](../../foundation/ui-ux.md) — design system context
