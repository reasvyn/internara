---
name: code-writing
description: "SDLC Phase: IMPLEMENTATION. PHP and Laravel code writing — strict types, Action Triad, Entity/DTO/Model contracts, naming conventions, security patterns, performance rules, and non-negotiable invariants."
upstream:
  - laravel-best-practices
downstream:
  - test-writing
  - pest-testing
  - doc-writing
  - sync-docs
---

# Code Writing

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for project orientation and `laravel-best-practices` for
> Laravel-specific guidance.

## When to Activate

Use this skill when:
- Writing new PHP classes (Actions, Entities, DTOs, Models, Enums, Services)
- Adding methods to existing classes
- Implementing new features or business logic
- Writing Livewire components or Blade templates
- Creating migrations, seeders, or config files

## Workflow

Follow `AGENTS.md §Agent Workflow` for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (locate the **governing spec**, map FR/NFR/UC IDs), **Size Triage** (S/M/L session
splitting), verification strategy, and commit format. This skill adds the PHP class-writing rules
and contracts found in the Skill Rules section — nothing else.

Write code per the contracts and conventions in the rule assets; register cache keys in
`config/cache-keys.php`; use `__()` for all user-facing strings. Before completing, run the
arch-guard scanners (`tools/scan_*.py`) on the touched code (see the `arch-guard` skill).

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Non-negotiable invariants (C1-C8, D1-D6) | `.agents/rules/invariants.md` | Every class written or touched |
| Class contract checklists (Action/Entity/DTO/Model/Enum) | `.agents/rules/class-contracts.md` | Creating or modifying a component type |
| Naming conventions | `.agents/rules/naming-conventions.md` | Naming files, classes, routes, tests |
| Performance rules (N+1, queries, caching) | `.agents/rules/performance.md` | Any query-heavy or list/dashboard code |
| Security patterns (XSS, SQLi, mass assignment, CSRF) | `.agents/rules/security.md` | Any user input, output, or form |
| Code quality checklist (file/class/method) | `.agents/rules/code-quality.md` | Pre-commit hygiene on every PHP file |
| File header order | `.agents/rules/file-header.md` | Every new PHP class file |
| Laravel divergences | `.agents/rules/laravel-divergences.md` | Whenever stock-Laravel patterns conflict |
| ActionResponse factory methods | `.agents/rules/action-response.md` | Returning structured action results |
| Error handling strategy | `.agents/rules/error-handling.md` | Any exception thrown or caught |
| Technical debt annotations | `.agents/rules/tech-debt.md` | Adding TODO/FIXME/HACK/XXX markers |

---
## References

| Topic | Location |
|-------|----------|
| Full conventions | `docs/conventions.md` |
| Architecture overview | `docs/architecture.md` |
| Action Triad pattern | `docs/guides/arch/action-pattern.md` |
| SRP & modularity rules | `docs/guides/arch/modular-pattern.md` §1.6 |
| Entity pattern | `docs/guides/arch/entity-pattern.md` |
| DTO/Data pattern | `docs/guides/arch/data-pattern.md` |
| Model pattern | `docs/guides/arch/model-pattern.md` |
| Enum pattern | `docs/guides/arch/enum-pattern.md` |
| Exception pattern | `docs/guides/arch/exception-pattern.md` |
| Livewire pattern | `docs/guides/arch/livewire-pattern.md` |
| Policy pattern | `docs/guides/arch/policy-pattern.md` |
| Event pattern | `docs/guides/arch/event-pattern.md` |
| Cache pattern | `docs/guides/arch/cache-pattern.md` |
| Module index | `docs/refs/modules/index.md` |
| Laravel best practices | `.agents/skills/laravel-best-practices/SKILL.md` |
| Coding rules (quick) | `.agents/rules/coding-rules.md` |
| Architecture rules (quick) | `.agents/rules/architecture-rules.md` |
