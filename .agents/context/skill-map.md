# Skill Map & Module/Spec Reference

> **Curated mandatory known context** — which skill to load for each task, and module/spec navigation. Read at start of every session.

## Skill Map — Which Skill to Load

| Task | Skill | Notes |
|------|-------|-------|
| Every instruction, any task | `AGENTS.md §Agent Workflow` | **Apply first** — universal workflow, no exceptions (inline in AGENTS.md, not a skill) |
| Every instruction, any task | `AGENTS.md §Context Awareness` | **Apply second** — universal orientation layer, no exceptions (inline in AGENTS.md, not a skill) |
| Writing feature specs | `spec-writing` | 11-section spec template, requirements IDs |
| Writing PHP code | `code-writing` | Action Triad, Entity/DTO/Model contracts |
| Refactoring existing code | `code-refactoring` | Extract Actions, thin Livewire |
| Building a feature end-to-end | `feature-building` | Orchestrator — coordinates sub-skills |
| Laravel/architecture best practices | `laravel-best-practices` | Cross-cutting overrides for the Module-first Action architecture |
| Data architecture (schema, flow, security, contracts, DTO, mapping, formatting) | `data-architect` | Single source for any data-related task — schema, flow, security, interface/struct/type/enum/DTO, mapping, formatting |
| Livewire component | `livewire-development` | Livewire mechanics only — thin component, delegation, tables |
| General UI (Blade, layout, a11y, i18n, TallstackUI) | `ui-development` | General UI — Blade presentation, view structure, layout/responsive/dark mode, component library, accessibility, localization (delegates Tailwind details to tailwindcss-development) |
| Writing spec-driven tests | `pest-testing` | Each test traces to a spec FR/NFR; no orphan tests |
| Deciding verification strategy | `test-writing` | What to run, when, how much; spec-gap & orphan detection |
| Writing documentation | `doc-writing` | Two-tier model, metadata, PHPDoc |
| Syncing docs with code | `sync-docs` | Automated verification |
| Writing GitHub issues | `issue-writing` | Structured issue format |
| Security review | `security-audit` | OWASP, PII, auth patterns |
| Spec↔Code sync audit | `spec-audit` | Bidirectional spec-implementation verification |
| Independent QA audit | `qa-protocol` | Blind test against global standards (OWASP, ISO 25010, CWE, WCAG, PSR) |
| Enforcing architecture rules + ADR | `arch-guard` | C1-C8, D1-D6, contracts, naming, ADR staleness/linkage (docs/adr/*.md) |
| Writing scripts | `script-automation` | Standards for `tools/` devtools |
| Tailwind CSS utilities & palette | `tailwindcss-development` | Tailwind CSS v4 only — utilities, @theme, semantic palette, no general UI |
| TallStackUI components | `tallstackui-development` | TallStackUI v4 — x-ts-* components, interactions (1:1 tallstackui/tallstackui) |
| Laravel framework | `laravel-development` | Laravel core — routing, container, Eloquent, validation (1:1 laravel/framework) |
| File uploads/media | `medialibrary-development` | Spatie MediaLibrary (1:1 spatie/laravel-medialibrary) |
| RBAC / Permission | `permission-development` | Spatie Permission — roles, @hasrole, policies (1:1 spatie/laravel-permission) |
| Audit trail | `activitylog-development` | Spatie Activity Log — audit, SmartLogger (1:1 spatie/laravel-activitylog) |
| PDF generation | `dompdf-development` | DOMPDF — Blade-to-PDF, assets (1:1 barryvdh/laravel-dompdf) |
| Build pipeline | `vite-development` | Vite — entry, plugins, HMR, build (1:1 vite) |
| Laravel Pulse dashboard | `pulse-development` | Dashboard, recorders, cards (1:1 laravel/pulse) |

---
*Source: AGENTS.md §Skill Map. For skill deep-dives, see `.agents/skills/`.*

## Module & Spec Reference

Full module list with docs: `docs/refs/modules/index.md`
Full spec list with build order: `docs/specs/index.md`

---
*For the complete module landscape (19 modules, health tiers, dependencies), see `.agents/context/project-snapshot.md`. For spec build order (12 phases, 64 specs), see `.agents/context/project-snapshot.md` §Spec Build Order.*