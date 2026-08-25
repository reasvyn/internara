# Alpine.js — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for Alpine.js (bundled via Livewire)

## Description

Reference for **Alpine.js** — the lightweight reactive-directive layer inside Blade templates.
Notably, it is **not a direct `package.json` dependency**: Livewire 4 bundles Alpine and exposes
it globally as `window.Alpine`.

---

## Installed & Role

| | |
|---|---|
| Source | Bundled inside Livewire 4 (no standalone npm pin) |
| Role | Declarative interactivity (`x-data`, `x-show`, `x-on:`) sprinkled into Blade where Livewire components are overkill |
| Access pattern | Global `window.Alpine` + `alpine:init` lifecycle event |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Directives** | HTML attributes — `x-data` (component scope), `x-show`, `x-if`, `x-for`, `x-on:click`, `x-model`, `x-effect` |
| **Stores** | Global reactive state registered during `alpine:init` (`Alpine.store(...)`) |
| **Plugins** | Optional behaviors (intersect, persist, focus) — some mirrored natively by Livewire directives such as `wire:intersect` |
| **`$data()` introspection** | Programmatic access to an element's Alpine scope — used by helper code to read component state |

---

## How Internara Uses It

- Global initialization in `resources/js/app.js` under `document.addEventListener('alpine:init', ...)`
- Helper functions read component state via `window.Alpine.$data(element)`
- Micro-interactions inside TallStackUI components rely on Alpine under the hood

Rule of thumb in this codebase: if behavior needs server state → Livewire component; if purely
client-side show/hide/toggle → inline Alpine directives.

## Quick References

- [Official docs](https://alpinejs.dev) — directive and store reference
- [`livewire.md`](livewire.md) — the bundler providing Alpine at runtime
