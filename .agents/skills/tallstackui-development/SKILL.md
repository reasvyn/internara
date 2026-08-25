---
name: tallstackui-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). TallStackUI component development — x-ts-* components, interactions, and component customization. General UI layout/a11y/i18n is in ui-development; Tailwind utilities in tailwindcss-development."
upstream:
  - ui-development
downstream:
  - sync-docs
---

# TallStackUI Development — Component Library

> **Last updated:** 2026-08-25 **Changes:** new skill — 1:1 mapping for tallstackui/tallstackui (extracted from ui-development/tailwindcss-development)

> **Prerequisite:** Load `context-awareness` and `ui-development` for general UI context.

## When to Activate

Use this skill when implementing TallStackUI components — `x-ts-table`, `x-ts-card`, `x-ts-modal`, `x-ts-input`, `x-ts-select`, `x-ts-badge`, toast interactions, and component customization. For general layout, Blade presentation, or accessibility, use `ui-development`.

## Workflow

Follow `agent-workflow` pipeline. This skill adds TallStackUI-specific guidance.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Component selection & usage (x-ts-* primacy, semantic palette, interactions) | `rules/component-usage.md` | Any TallStackUI component |
| Customization & theming (personalization, Tailwind integration) | `rules/customization.md` | Customizing TallStackUI components |

## References

| Topic | Doc |
|-------|-----|
| TallStackUI docs | `search-docs` with `tallstackui/tallstackui` |
| UI/UX system | `docs/guides/ui-ux.md` §10 |
| Tailwind specifics | `tailwindcss-development` |
