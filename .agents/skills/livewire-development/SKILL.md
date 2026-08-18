---
name: livewire-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Livewire component development — building new components, debugging reactivity, file uploads, real-time validation, CRUD tables."
upstream:
  - feature-building
downstream:
  - code-writing
  - pest-testing
  - tailwindcss-development
  - sync-docs
---

# Livewire Development

> **Last updated:** 2026-08-18 **Changes:** slim SKILL.md to index form — rule prose lives in `rules/` (thin component, component structure & routing, tables & uploads, accessibility, localization, component verification)

> **Prerequisite:** Load `context-awareness` for project orientation. Loading `feature-building`
> provides the broader implementation flow.

## When to Activate

Use this skill when building or modifying Livewire components. Covers component structure, form
handling, validation, file uploads, table components, and reactive patterns.

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting), verification
strategy, and commit format. This skill adds Livewire-specific guidance — thin components, Form
Objects, Action delegation, accessibility, localization, and routing — rule assets in the Skill
Rules section. Load the rule file only when the component reaches that concern (thin component /
structure / tables-uploads / accessibility / localization / verification).

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Thin component (delegation, boundaries, method injection, read-only entities) | `rules/thin-component.md` | Building or reviewing any Livewire component |
| Component structure & routing (placement, build order, Form Objects, routes) | `rules/component-structure.md` | Creating a component, form, or route |
| Tables & file uploads (maryUI tables, BaseRecordManager, WithFileUploads + MediaLibrary) | `rules/tables-and-uploads.md` | Any listing/CRUD table or file upload |
| Accessibility (WCAG 2.1 AA: focus, dynamic content, forms, tables, icons) | `rules/accessibility.md` | Every component before release |
| Localization (every user-facing string, keying patterns, confirm dialogs) | `rules/localization.md` | Any user-facing string in a component or view |
| Component verification (thin/safe/complete gate) | `rules/component-verification.md` | Before commit of any component change |

## References

| Topic              | Doc                                              |
| ------------------ | ------------------------------------------------ |
| Livewire pattern   | `docs/architecture/livewire-pattern.md`          |
| Action delegation  | `docs/architecture/action-pattern.md`            |
| Form Objects       | `docs/architecture/livewire-pattern.md` (§Forms) |
| File uploads       | `docs/infrastructure/media-library.md`           |
| Testing components | `docs/architecture/testing-pattern.md`           |
| maryUI components  | maryUI docs (via `search-docs`)                  |
| Authorization      | `docs/architecture/policy-pattern.md`            |
