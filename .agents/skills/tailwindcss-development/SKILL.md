---
name: tailwindcss-development
---

# Tailwind CSS Development Skill

## When to Activate

Apply this skill whenever styling Blade templates, building responsive layouts, implementing dark
mode, fixing spacing or typography, or working with daisyUI and maryUI components. Always invoke
when the task involves Tailwind utility classes in HTML/Blade/JSX templates.

## Core Principles

### Tech Stack

The project uses Tailwind CSS v4 with CSS-first configuration (no `tailwind.config.js`), daisyUI
component classes (`btn`, `card`, `modal`, `table`), and maryUI Livewire components
(`x-mary-button`, `x-mary-card`, `x-mary-table`). Icons come from Blade Tabler Icons (`o-` prefix).
Dark mode is class-based (`.dark` on `<html>`).

### Tailwind v4 Configuration

All configuration is in `resources/css/app.css` using `@import "tailwindcss"` and `@theme`
directives. Custom colors, fonts, and spacing are defined in `@theme` blocks. No `@tailwind`
directives are used. v3 utilities like `bg-opacity-*` are replaced with modern slash syntax
(`bg-black/50`).

## Layout Architecture

Pages follow consistent patterns: a flex header with title and action button, a search/filter bar, a
selection bar (shown when items are selected), and the main content (table or grid). Layouts are
responsive using standard breakpoints (sm/md/lg/xl/2xl).

CRUD tables use maryUI's `x-mary-table` inside `x-mary-card`. Modal forms use `x-mary-modal` with
`x-mary-input` and `x-mary-button` for actions. Buttons use `btn-primary`, `btn-ghost`, or
`btn-error` classes with custom border radius.

## Translation and Localization

All user-facing strings use `__()` translation helpers. No hardcoded text in Blade templates. This
applies to labels, placeholders, titles, and button text.

## Verification Before Finalizing

- Are `@import "tailwindcss"` and `@theme` used instead of `@tailwind` and `tailwind.config.js`?
- Are deprecated v3 utilities avoided (no `bg-opacity-*`, `flex-shrink-*`)?
- Is `wire:key` present on all `@foreach` loops?
- Are translations used instead of hardcoded strings?
- Is `gap` used on parent flex/grid containers instead of margins on children?
- Does the layout work at responsive breakpoints?
- Is dark mode handled if the project uses it?
