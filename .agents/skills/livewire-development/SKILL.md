---
name: livewire-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Livewire component development — thin components, Action delegation, Form Objects, tables and file uploads. General UI concerns (Blade, layout, a11y, i18n) are handled by ui-development; Tailwind details by tailwindcss-development."
upstream:
  - feature-building
downstream:
  - code-writing
  - pest-testing
  - ui-development
  - sync-docs
---

# Livewire Development — Livewire Mechanics Only

> **Last updated:** 2026-08-25 **Changes:** narrowed to Livewire-only — general UI rules (Blade presentation, view structure, a11y, i18n) moved to ui-development; skill now focuses on thin components, delegation, structure, and tables

> **Prerequisite:** Load `context-awareness` for project orientation. For general UI (Blade, layout, TallstackUI, a11y, i18n) load `ui-development`; for Tailwind utilities load `tailwindcss-development`.

## When to Activate

Use this skill when building or modifying **Livewire components** — thin-component boundaries, Action delegation via method injection, Form Objects, and table/file-upload patterns. Do not use it for general UI (Blade presentation, view placement, responsive layout, component styling, accessibility, localization) — those belong to `ui-development`.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline. This skill adds **Livewire-only guidance** — thin components, Form Objects, Action delegation, and tables — and delegates general UI to `ui-development`.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Thin component (delegation, boundaries, method injection, read-only entities, Blade no-logic) | `.agents/rules/thin-component.md` | Building or reviewing any Livewire component |
| Component structure & routing (placement, build order, Form Objects) | `.agents/rules/component-structure.md` | Creating a component, form, or route |
| Tables & file uploads (TallstackUI tables, BaseRecordManager, WithFileUploads + MediaLibrary) | `.agents/rules/tables-and-uploads.md` | Any listing/CRUD table or file upload |
| Component verification (thin/safe/complete gate) | `.agents/rules/component-verification.md` | Before commit of any component change |

General UI rules (Blade presentation, view structure, layout, a11y, i18n) are now in `ui-development`.

## References

| Topic              | Doc                                              |
| ------------------ | ------------------------------------------------ |
| Livewire pattern   | `docs/guides/arch/livewire-pattern.md`          |
| Action delegation  | `docs/guides/arch/action-pattern.md`            |
| Form Objects       | `docs/guides/arch/livewire-pattern.md` (§Forms) |
| File uploads       | `docs/guides/infra/media-library.md`           |
| Testing components | `docs/guides/arch/testing-pattern.md`           |
| TallstackUI components | TallstackUI docs (via `search-docs` `tallstackui/tallstackui`) |
| Authorization      | `docs/guides/arch/policy-pattern.md`            |
