# Prettier — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for prettier ^3.9.6 family (blade + tailwind plugins)

## Description

Conceptual reference for the **Prettier formatting family** — one formatter core plus two
plugins covering every non-PHP file in the repository. PHP files are formatted by Laravel Pint,
not Prettier.

---

## Installed & Role

| Package | Version | Role |
|---------|---------|------|
| `prettier` | `^3.9.6` | Formatter core (CSS, JS, JSON, YAML, Markdown) |
| `prettier-plugin-blade` | `^3.2.2` | Blade template formatting |
| `prettier-plugin-tailwindcss` | `^0.8.1` | Deterministic Tailwind class sorting |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Opinionated formatting** | Minimal configuration surface — consistency over preference; diffs stay mechanical |
| **Plugin pipeline** | Plugins extend supported syntax (Blade) and post-process output (class sorting) |
| **Class sorting** | Tailwind classes reorder into a canonical order so review diffs show semantic changes, not churn |
| **Scope boundary** | PHP/Blade-in-PHP styling belongs to Pint (`vendor/bin/pint --dirty --format agent`); Prettier owns everything else except PHP |

---

## How Internara Uses It

- Verification split per change type (AGENTS §Verification Strategy):
  - CSS / JS / JSON → `npx prettier --check <file>`
  - PHP + Blade templates → Pint (Blade handled via the `Pint/laravel_blade` rule)
- Markdown is deliberately ignored by Prettier — specs/docs use compact tables whose layout is
  intentional (issue #384)
- Formatter entry points: `composer run format` / `composer run lint`

## Quick References

- [Prettier docs](https://prettier.io/docs) — configuration and plugin model
- [`prettier-plugin-tailwindcss`](https://github.com/tailwindlabs/prettier-plugin-tailwindcss) — sorting behavior
