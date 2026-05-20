# Blade UI Conventions

## What It Enforces

Blade views follow a consistent tech stack: Tailwind CSS v4 (CSS-first via `@import "tailwindcss"`), daisyUI component classes, maryUI Livewire components (`x-mary-*`), and Blade Tabler Icons (`o-*` prefix). Views live in `resources/views/{domain}/{component-name}.blade.php`.

## Why It Matters

A consistent UI stack ensures every view looks and works the same way. Developers know which components to reach for, how to style them, and where to find them. Translation keys replace hardcoded strings, making the application localizable.

## When It Applies

Every Blade view uses:
- maryUI components for forms, tables, modals, buttons, inputs
- daisyUI utility classes for styling (btn, card, modal, table)
- Tabler icons with `o-` prefix
- PHPFlasher for flash messages (never maryUI Toast)
- `__('domain.key')` for all user-facing strings
- Layout defined via `#[Layout('layouts::app')]` attribute on the Livewire component

Shared UI components live in `resources/views/components/ui/` and are referenced as `<x-ui::name>`.

Exceptions: None. These conventions apply to all views.
