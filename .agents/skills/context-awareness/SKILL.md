---
name: context-awareness
description: SDLC Phase: ORIENTATION. Universal project orientation for Internara — architecture rules, module map, decision framework, critical rules, and navigation patterns. Must be loaded first on every session. All other skills assume this context.
downstream:
  - audit-protocol
  - code-refactoring
  - feature-building
  - laravel-best-practices
  - livewire-development
  - medialibrary-development
  - pest-testing
  - pulse-development
  - verify-and-testing
  - roadmap-planning
  - security-audit
  - sync-docs
  - tailwindcss-development
---

# Context Awareness

> **Prerequisite:** None — this is the first skill to load.

## When to Activate

Load this skill at the start of every session. It provides the mental model needed by all other
skills. Without it, you lack the architectural context to make sound decisions.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Context Awareness

- Read `docs/architecture.md`, `docs/conventions.md`, `docs/modules/index.md`
- Understand 4-layer architecture, Action Triad, DTO boundaries
- Identify which module is relevant to the task
- Read module docs: `docs/modules/{module}.md` and `docs/modules/{module}-reference.md`
- Build project mental model before using other skills
- Output: project mental model — understanding of 4-layer architecture, Action Triad, module
  boundaries, and critical rules

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of orientation work done
    - Architecture understanding: layers, Action Triad, patterns
    - Module map understanding
- Feeds into: any downstream skill (audit, planning, refactoring, implementation, testing,
  maintenance)
- Commit using format: `type(scope): description`
- Push if requested

## Project Identity

Self-hosted, single-tenant PKL management for Indonesian SMA/SMK. MIT license. Repository:
`reasvyn/internara`.

**Tech:** PHP 8.4, Laravel 13, Livewire 4, Alpine.js, maryUI 2, DaisyUI 5, Tailwind CSS v4.
Database: SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+. Testing: Pest 4, PHPStan,
Laravel Pint.

## Architecture Compass

### 4-Layer Model

Strictly downward — each layer depends only on layers below.

| Layer                       | Content                                                     | Directory Prefix                         |
| --------------------------- | ----------------------------------------------------------- | ---------------------------------------- |
| **4 — Presentation/UI**     | Livewire, Blade, Policies, Routes, Controllers              | `{Module}/Livewire/`, `routes/web/`      |
| **3 — Business/Domain Ops** | Command/Read/Process Actions, Events, Listeners             | `{Module}/Actions/`, `{Module}/Events/`  |
| **2 — Data/Persistent**     | Models, Entities (final readonly), DTOs (BaseData), Enums   | `{Module}/Models/`, `{Module}/Entities/` |
| **1 — Framework/Infra**     | Core base classes, Contracts, Exceptions, Services, Support | `app/Core/`, `{Module}/Services/`        |

### Action Triad

| Type        | Base                | Transaction | Log | Use                      |
| ----------- | ------------------- | ----------- | --- | ------------------------ |
| **Command** | `BaseCommandAction` | ✅          | ✅  | All mutations            |
| **Read**    | `BaseReadAction`    | ❌          | ❌  | Complex queries          |
| **Process** | `BaseProcessAction` | ✅          | ✅  | Multi-step orchestration |

- Exactly one public method: `execute()`
- Actions are the ONLY entry point for mutations — never in Livewire
- Accept DTO for 3+ params; return ActionResponse for structured feedback
- Delegate business rules to Entities; throw `RejectedException` on violation
- Events for async only — skip if no listener exists

### Key Design Decisions

| Principle         | Rule                                                                         |
| ----------------- | ---------------------------------------------------------------------------- |
| Module colocation | Business logic lives with its module, not globally                           |
| DTO boundaries    | UI↔Business via BaseData; Business↔UI via ActionResponse                     |
| Entity purity     | `final readonly`, zero I/O, `fromModel(Model)`, business rules return `bool` |
| Model role        | Persistence only — no business methods, use `as{Role}()` bridges             |
| Cross-module      | Direct imports allowed; prefer events for side effects                       |

## Metacognitive Loop

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — Read relevant docs and existing code; verify paths and signatures; consider
   multiple approaches
2. **EVALUATE** — Does it match requirements? Respect layer boundaries? Do ONE thing?
3. **VERIFY** — Lint + static analysis + tests pass; no debug calls; `__()` for strings
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer

## Critical Invariants

| #   | Rule                                                                    |
| --- | ----------------------------------------------------------------------- |
| C1  | No `Model::create/update/delete` in Livewire                            |
| C2  | No `app()->make()` / `resolve()` — use injection                        |
| C3  | No raw SQL without parameterized binding                                |
| C4  | Cache keys in `config/cache-keys.php` — never inline                    |
| C5  | Entities must not import Actions/Services/Livewire/Controllers          |
| C6  | DTOs must not import Models/Entities/Actions                            |
| C7  | Business rules → `RejectedException`, not `RuntimeException`            |
| D1  | `declare(strict_types=1)` in all PHP files except migrations and config |
| D2  | No `dd/dump/ray/var_dump/print_r/die` in committed code                 |
| D3  | All user-facing strings use `__()` — both `lang/en/` and `lang/id/`     |

## Pre-commit Checklist

- `declare(strict_types=1)` present
- No debug calls in code
- Action uses correct triad base class
- DTO for 3+ params; ActionResponse for structured returns
- Business rules in Entity, not inline
- Cache keys registered
- No N+1 queries
- Tests pass; Pint clean; PHPStan passes
- Docs updated for new/changed behavior

## Documentation Map

Start here for any topic:

| Topic                               | Doc                                                              |
| ----------------------------------- | ---------------------------------------------------------------- |
| Architecture                        | `docs/architecture.md`                                           |
| 4-layer, Action Triad, Base Classes | `docs/architecture.md` (§Action Triad, §Base Class Mandate)      |
| Coding conventions                  | `docs/conventions.md`                                            |
| Module overviews                    | `docs/modules/index.md`                                          |
| Pattern deep-dives                  | `docs/architecture/{pattern}-pattern.md`                         |
| RBAC & Policies                     | `docs/foundation/rbac.md`, `docs/architecture/policy-pattern.md` |
| Exception hierarchy                 | `docs/architecture/exception-pattern.md`                         |
| Caching                             | `docs/architecture/cache-pattern.md`                             |
| Logging                             | `docs/architecture/logging-pattern.md`                           |
| Testing                             | `docs/architecture/testing-pattern.md`                           |
| Deployment                          | `docs/infrastructure/deployment.md`                              |
| Database schema                     | `docs/infrastructure/database.md`                                |
| Full doc catalog                    | `docs/index.md`                                                  |
| ADRs                                | `docs/adr/index.md`                                              |
| Known issues                        | GitHub Issues                                                    |
