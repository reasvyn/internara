# Flatpickr — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for flatpickr ^4.6.13

## Description

Conceptual reference for **Flatpickr 4** (`flatpickr ^4.6.13`) — the lightweight vanilla-JS date
and time picker used in Internara forms.

---

## Installed & Role

| | |
|---|---|
| Installed | `^4.6.13` (npm) |
| Role | Date/datetime/range picker attached to text inputs |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Zero-dependency picker** | Attaches to any `<input>` via `flatpickr(element, options)` — no jQuery or framework binding required |
| **Modes** | `single`, `multiple`, `range`, `time`, `datetime` with min/max constraints and locale support |
| **Integration pattern** | Initialized from JS hooks; value syncs back to the input, which Livewire reads as normal form state |

---

## How Internara Uses It

- Loaded and initialized globally through `resources/js/app.js`
- Used inside TallStackUI-based form views wherever structured dates are captured (program
  periods, MoU windows, absence requests)

## Quick References

- [Official docs](https://flatpickr.js.org) — options and event reference
