# Livewire — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for livewire/livewire v4.3.5

## Description

Conceptual reference for **Livewire 4** (`livewire/livewire v4.3.5`) — the reactive component
layer that renders Internara's entire UI without a JavaScript SPA framework. Component mechanics
and thin-component rules live in [`architecture/livewire-pattern.md`](../../guides/arch/livewire-pattern.md);
this file documents the library itself.

---

## Installed & Role

| | |
|---|---|
| Installed | `v4.3.5` (`composer.json`: `^4.0`) |
| Role | Full presentation layer — server-rendered reactivity over Blade |
| Ships with | Bundled Alpine.js (`window.Alpine`), `wire:*` directives, morphing DOM engine |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Components** | PHP classes paired with Blade templates; public properties and methods are reachable from the template via `$wire` |
| **Component formats** | v4 adds single-file components (PHP + Blade in one `.blade.php`) and multi-file components (class/view/JS/tests in one directory) alongside classic class+view pairs |
| **Actions & modifiers** | Methods invoked declaratively — `wire:click`, `.live`, `.debounce`, `.async` (parallel non-blocking actions) |
| **Islands** | Isolated regions inside a component that update independently (`@island`) — performance without child-component overhead |
| **Loading control** | `lazy` (viewport-based), `defer` (after initial page load), `.bundle` to group their network round-trips |
| **Directives** | `wire:model`, `wire:loading`, `wire:navigate` (SPA-style links), `wire:sort` (drag-drop lists), `wire:intersect` (viewport triggers) |
| **JavaScript integration** | `<script>` blocks in view-based components get `$wire` bound automatically; global hooks via `alpine:init` |

---

## How Internara Uses It

- Every module's UI lives under `app/{Module}/{Submodule}/Livewire/` — tables, forms, dashboards
- Thin-component doctrine: components validate input and catch `RejectedException`; all mutations
  delegate to Actions (C1)
- Alpine interactivity initialized globally in `resources/js/app.js` via the `alpine:init` hook
- Dev loop runs Livewire alongside queue worker, logs, and Vite through `composer run dev`

## Quick References

- [Official docs](https://livewire.laravel.com/docs) — full Livewire documentation
- [`docs/guides/arch/livewire-pattern.md`](../../guides/arch/livewire-pattern.md) — thin-component rules
- [`alpinejs.md`](alpinejs.md) — the bundled interactivity layer used inside components
