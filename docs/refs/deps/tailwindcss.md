# Tailwind CSS — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for tailwindcss v4.3.3 (@tailwindcss/vite, @tailwindcss/forms)

## Description

Conceptual reference for **Tailwind CSS 4** (`tailwindcss ^4.3.3` plus the `@tailwindcss/*`
plugin family) — the utility-first CSS engine generating all of Internara's styling from scanned
source files.

---

## Installed & Role

| Package | Version | Role |
|---------|---------|------|
| `tailwindcss` | `^4.3.3` | CSS engine (CSS-first configuration via `@theme`) |
| `@tailwindcss/vite` | `^4.3.3` | Vite plugin integrating the engine into the build |
| `@tailwindcss/forms` | `^0.5.11` | Form element reset/normalization presets |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Utility scanning** | All source files are scanned as plain text; CSS is generated only for complete class tokens actually present — dynamic class construction (`text-{{ $color }}-600`) silently produces nothing |
| **CSS-first config** | No required `tailwind.config.js` — theme tokens (colors, fonts, breakpoints) are declared in CSS via `@theme` |
| **Engine** | Oxide-based engine written for speed; content detection replaces v3's `content` array |
| **Plugin family** | First-party plugins extend base behavior (`forms` normalizes inputs) |

---

## How Internara Uses It

- Semantic palette and theming tokens declared CSS-first; dark mode supported across layouts
- Class ordering enforced mechanically by `prettier-plugin-tailwindcss` (see [`prettier.md`](prettier.md))
- Styling conventions documented in `docs/conventions.md` §Theming; utilities guidance in the
  `tailwindcss-development` skill
- Build runs through Vite ([`vite.md`](vite.md)) with `npm run build`

## Quick References

- [Official docs](https://tailwindcss.com/docs) — v4 documentation
- [`docs/conventions.md`](../../conventions.md) — §Theming & Visual Consistency
- [`prettier.md`](prettier.md) — class-sort tooling
