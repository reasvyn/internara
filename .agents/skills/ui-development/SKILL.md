---
name: ui-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). General UI development — Blade presentation, view structure, layout, responsive, dark mode, TallstackUI component library, accessibility, and localization. Tailwind CSS details are delegated to tailwindcss-development; Livewire specifics to livewire-development."
upstream:
  - feature-building
  - livewire-development
downstream:
  - tailwindcss-development
  - sync-docs
---

# UI Development — General Presentation & Component Library

> **Last updated:** 2026-08-25 **Changes:** new skill — extracted general UI rules from tailwindcss-development (Blade presentation, view structure, layout/responsive/dark mode, component usage, accessibility, localization) so tailwindcss-development can stay Tailwind-only and livewire-development Livewire-only

> **Prerequisite:** Load `context-awareness` for project orientation. `livewire-development` provides component state context; `tailwindcss-development` provides Tailwind utility details.

## When to Activate

Use this skill when building or modifying **any UI** — Blade templates, view structure, layout, responsive breakpoints, dark mode, TallstackUI component usage, accessibility, or localization in views. It is the **general UI skill**. For Tailwind-specific utilities, `@theme`, and CSS architecture, also load `tailwindcss-development`. For Livewire component mechanics (thin component, delegation, tables), load `livewire-development`.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline. This skill adds **general UI guidance** — pure presentation rules, view placement, layout, component library, and UI quality gates — and delegates scope-specific details:

- **Blade & presentation** → this skill (`blade-presentation`)
- **Layout / responsive / dark mode** → this skill (`layout-responsive-dark-mode`)
- **Component library (TallstackUI `x-ts-*`)** → this skill (`ui-stack-and-component-usage`)
- **View structure & routing** → this skill (`view-structure-and-routing`)
- **Accessibility (WCAG 2.1 AA)** → this skill (`accessibility-wcag`)
- **Localization in views** → this skill (`localization-in-views`)
- **Tailwind utilities / `@theme` / semantic palette / no-custom-CSS** → `tailwindcss-development`
- **Livewire mechanics (thin component, Action delegation, tables)** → `livewire-development`

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Blade presentation — no business/UI logic (Livewire computed props or Alpine.js only; `@if` with inline expressions should be avoided) | `rules/blade-presentation.md` | Every Blade file — any derived value, percentage, or array assembly |
| UI stack & component usage (TallstackUI `x-ts-*` primacy, semantic palette, no custom HTML) | `rules/ui-stack-and-component-usage.md` | Building any UI component or styling views |
| Layout, responsiveness & dark mode (drawer/navbar, breakpoints, theming, TallstackUI `x-ts-layout`) | `rules/layout-responsive-dark-mode.md` | Structuring layouts or theming the app |
| View structure & routing (Blade placement, route files, Livewire routes) | `rules/view-structure-and-routing.md` | Creating views or routes |
| Accessibility (WCAG 2.1 AA: contrast, focus, keyboard, reflow, icons) | `rules/accessibility-wcag.md` | Every styled component before release |
| Localization in views (bilingual `__()`, keys, dates, lang attribute) | `rules/localization-in-views.md` | Any user-facing string or date in a view |

## References

| Topic | Doc |
|-------|-----|
| UI/UX design system | `docs/guides/ui-ux.md` |
| Branding & themes | `docs/guides/branding.md` |
| Blade presentation rule | `docs/conventions.md` §14 |
| Livewire component patterns | `docs/guides/arch/livewire-pattern.md` |
| Tailwind CSS specifics | `tailwindcss-development` skill + `resources/css/app.css` |
| Livewire specifics | `livewire-development` skill |

## Delegation

- **Need Tailwind utility, `@theme`, or palette detail?** → Load `tailwindcss-development` (now Tailwind-only).
- **Need Livewire component, thin-component, or table logic?** → Load `livewire-development` (now Livewire-only).
- **Need general Blade/layout/a11y/i18n?** → Stay in this skill.
