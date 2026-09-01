---
name: tailwindcss-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Tailwind CSS development — utilities, @theme, semantic palette, CSS-first config, and build pipeline. General UI concerns (Blade, layout, components, a11y, i18n) are handled by ui-development."
upstream:
  - ui-development
downstream:
  - sync-docs
---

# Tailwind CSS Development — Utilities & Theme Only

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for project orientation. For general UI (Blade, layout, TallstackUI components, a11y, i18n) load `ui-development`; for Livewire mechanics load `livewire-development`.

## When to Activate

Use this skill when working with **Tailwind CSS specifics** — utility classes, `@theme` tokens, semantic palette, CSS-first configuration, and the Vite build pipeline. Do not use it for general UI tasks (Blade structure, layout, component selection, accessibility, localization) — those belong to `ui-development`.

## Workflow

Follow `AGENTS.md §Agent Workflow` for the canonical 5-step pipeline. This skill adds **Tailwind-only guidance** — the utility layer and theming — and delegates everything else to `ui-development`.

## Tailwind Stack

| Layer | Purpose |
|-------|---------|
| **Tailwind CSS v4** | Utility-first framework — spacing, typography, responsive grid, `dark:` via `.dark` class |
| **Self-hosted palette** | Semantic colors via `@theme` in `resources/css/app.css` (`--color-base-*`, `--color-primary`, etc.) with `[data-theme='dark']` overrides |
| **Vite + laravel-vite-plugin** | Build pipeline (`npm run build`, `resources/css/app.css` entry) |

General UI stack (TallstackUI `x-ts-*`, Alpine.js, Livewire) is documented in `ui-development`.

## Key Patterns — Tailwind Only

### Semantic Palette

- Use `bg-primary`, `text-success`, `border-warning`, `bg-info/10` — tokens from `@theme` in `resources/css/app.css`.
- Never use arbitrary `bg-blue-500`, `text-red-500`, `bg-[#123456]` — they bypass the self-hosted palette and break brand theming/dark mode.

### CSS-First Configuration

- **No `tailwind.config.js`.** Theme tokens, breakpoints, and custom values live in `resources/css/app.css` via `@theme` and `@import`/`@layer`.
- Check `resources/css/app.css` before adding any new token.

### Utilities Over Custom CSS

- Prefer Tailwind utilities (`mt-4`, `flex`, `gap-2`, `rounded-lg`) over custom CSS or `style=""`.
- Legacy DaisyUI shims (`.btn`, `.badge`, `.card` etc.) are in `@layer components` as transitional shims — do not extend them for new designs; use utilities or TallstackUI.

### Dark Mode Variant

- Use `dark:` variant (driven by `.dark` class) for theme-aware utilities: `bg-base-100 dark:bg-base-200`, `text-base-content`.
- Do not hardcode `bg-white` without a `dark:` counterpart. The dual signal is `data-theme` + `.dark` (wiring is in `ui-development`).

## Tailwind v4 Specifics

- CSS-first config (`@theme`, `@import`) — not `tailwind.config.js`.
- `@theme` for custom values, `@import` for layers, `@layer` for shims.
- See `resources/css/app.css` for project-specific palette and shim definitions.

## Styling Principles — Tailwind Scope

1. Use semantic palette vars (`--color-primary`, etc.) over arbitrary colors.
2. Do NOT write custom CSS unless Tailwind utilities + TallstackUI cannot achieve the design.
3. No inline `style=""` — use utilities.

General UI principles (component primacy, responsive mandatory, accessibility) live in `ui-development`.

## Verification Checklist — Tailwind Only

- [ ] No arbitrary color utilities (`bg-blue-500`, `text-red-500`, `bg-[#...]`) — only semantic palette.
- [ ] No inline `style=""` — only utilities.
- [ ] No custom CSS when utilities/TallstackUI suffice (shims are transitional).
- [ ] `npm run build` clean; `npx prettier --check` clean (non-PHP).

For Blade/layout/component/a11y/i18n checks, see `ui-development` skill.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Tailwind utilities & palette (semantic colors, @theme, utilities over custom CSS, no inline style) | `.agents/rules/tailwind-utilities-and-palette.md` | Any Tailwind utility, color, or CSS change |

General UI rules (Blade, view structure, layout, accessibility, localization) are now in `ui-development`.

## References

| Topic | Doc |
|-------|-----|
| UI/UX design system (general) | `docs/guides/ui-ux.md` |
| General UI skill | `ui-development` skill |
| App CSS entry | `resources/css/app.css` |
| Tailwind CSS v4 | `search-docs` with `tailwindcss` |
| Livewire patterns | `docs/guides/arch/livewire-pattern.md` |
