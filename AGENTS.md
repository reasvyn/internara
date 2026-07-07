---
name: Internara Project Guide
description:
    Onboarding, critical invariants, architecture compass, and workflow for AI agents working on the
    Internara PKL management system.
version: 0.1.0
last-updated: 2026-07-05
---

# AGENTS.md — Project Guidelines for AI Agents

Provides the essential mental model, non-negotiable rules, and quick-reference navigation for AI
agents working on Internara. Do NOT duplicate content already covered in `docs/` — refer to it.

**Reading order:** Agent Onboarding → Project Identity → Architecture Compass → Critical Invariants
→ Metacognitive Loop → SDLC Phase Map → Documentation Map → Pre-commit Checklist

---

## Agent Onboarding

When entering this project for the first time, follow these steps:

1. **Load `context-awareness` skill** — provides universal project orientation
2. **Read `docs/architecture.md`** — understand 4-layer architecture, Action Triad, data flow,
   dependency rules
3. **Read `docs/conventions.md`** — coding standards, naming, security, testing
4. **Scan `docs/modules/index.md`** — module dependency graph
5. **Read relevant module docs** — `docs/modules/{module}.md` for business rules,
   `docs/modules/{module}-reference.md` for file structure
6. **Read relevant pattern docs** — `docs/architecture/{pattern}-pattern.md` before writing code in
   that area
7. **Read Critical Invariants below** — rules that MUST NOT be violated

---

## Project Identity

- **What:** Self-hosted, single-tenant PKL management for Indonesian SMA/SMK
- **License:** MIT
- **Repository:** `reasvyn/internara`
- **Author:** Reas Vyn (reasvyn@gmail.com)

### Tech Stack

| Layer        | Technology                                                                                                                                     |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| Language     | PHP 8.4                                                                                                                                        |
| Framework    | Laravel 13                                                                                                                                     |
| Frontend     | Livewire 4, Alpine.js, maryUI 2, DaisyUI 5, Tailwind CSS v4                                                                                    |
| Database     | SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+                                                                                      |
| Testing      | Pest 4, PHPStan, Laravel Pint                                                                                                                  |
| Key packages | spatie/laravel-permission, -medialibrary, -activitylog, -model-status, livewire/livewire, barryvdh/laravel-dompdf, php-flasher/flasher-laravel |

---

## Architecture Compass

### 4-Layer Model (strict downward dependency)

| Layer                       | Content                                                     | Location Prefix                          |
| --------------------------- | ----------------------------------------------------------- | ---------------------------------------- |
| **4 — Presentation/UI**     | Livewire, Blade, Policies, Routes, Controllers              | `{Module}/Livewire/`, `routes/web/`      |
| **3 — Business/Domain Ops** | Command/Read/Process Actions, Events, Listeners             | `{Module}/Actions/`, `{Module}/Events/`  |
| **2 — Data/Persistent**     | Models, Entities (final readonly), DTOs (BaseData), Enums   | `{Module}/Models/`, `{Module}/Entities/` |
| **1 — Framework/Infra**     | Core base classes, Contracts, Exceptions, Services, Support | `app/Core/`, `{Module}/Services/`        |

### Action Triad

| Type        | Base                | Transaction | Log         | Events   | Purpose                                |
| ----------- | ------------------- | ----------- | ----------- | -------- | -------------------------------------- |
| **Command** | `BaseCommandAction` | ✅ Required | ✅ Required | Optional | All mutations (CUD, state transitions) |
| **Read**    | `BaseReadAction`    | ❌          | ❌          | ❌       | Complex queries, aggregation           |
| **Process** | `BaseProcessAction` | ✅ Required | ✅ Required | Optional | Multi-step orchestration               |

**Key Action rules:**

- Exactly one public method: `execute()`
- Actions are the ONLY entry point for mutations — Livewire never calls `Model::create()` directly
- Accept `BaseData` DTO for 3+ params (typed scalars OK for 1-2). Never raw `array`
- Return `ActionResponse` for structured feedback (Model return OK for simple cases)
- Delegate business rules to Entities — throw `RejectedException` on violation
- Events are for async communication only — skip if no listener exists

### Key Design Decisions

| Principle           | Rule                                                                                 |
| ------------------- | ------------------------------------------------------------------------------------ |
| Module colocation   | Business logic lives with its module, not in global directories                      |
| DTO boundaries      | UI → Business via BaseData; Business → UI via ActionResponse                         |
| Entity purity       | `final readonly`, zero I/O, `fromModel(Model)`, business rules return `bool`         |
| Model role          | Persistence only — no business methods, use entity bridges (`as{Role}()`)            |
| Cross-module        | Direct imports allowed; prefer events for side effects                               |
| Exception hierarchy | `AppException` (infrastructure) / `ModuleException` → `RejectedException` (business) |

Refer to `docs/architecture.md` and `docs/architecture/{*}-pattern.md` for complete details.

---

## Critical Invariants

These rules MUST NEVER be violated. If you find existing code that violates them, flag it as a bug.

### Architecture

| #   | Rule                                                                                                  |
| --- | ----------------------------------------------------------------------------------------------------- |
| C1  | No `Model::create/update/delete` in Livewire — always use Command Actions                             |
| C2  | No `app()->make()` or `resolve()` in application code — use constructor/method injection              |
| C3  | No `DB::raw()` / `whereRaw()` without parameterized binding                                           |
| C4  | No `Cache::put()` / `Cache::remember()` with inline string keys — register in `config/cache-keys.php` |
| C5  | Entity classes must NOT import Actions, Services, Livewire, or Controllers                            |
| C6  | DTOs must NOT import Models, Entities, Actions — only Core BaseData, scalars, enums, Carbon           |
| C7  | Command/Process Actions: accept DTO for 3+ params, return ActionResponse                              |
| C8  | Business rules → `RejectedException`, not `RuntimeException`                                          |

### Super Admin

| #   | Rule                                                                                |
| --- | ----------------------------------------------------------------------------------- |
| S1  | Name is ALWAYS `Administrator` (config `setup.defaults.admin_name`)                 |
| S2  | Username is ALWAYS `superadmin` (config `setup.defaults.admin_username`)            |
| S3  | `SetupSuperAdminAction::execute()` accepts ONLY `(string $email, string $password)` |
| S4  | `InitializeSuperAdminAction` must use config defaults, NOT caller-provided values   |

### Reports Module

| #   | Rule                                                                                                                                                 |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| R1  | Report module is **grade card only** — final scores, grade letter, archived snapshot                                                                 |
| R2  | NEVER add thesis/final report content to `app/Reports/` (`title`, `content`, `chapter_structure`, `supervisor_notes`, submission/approval workflows) |
| R3  | Student thesis/final project belongs in `app/Assignment/` as an Assignment type                                                                      |

### Coding

| #   | Rule                                                                                             |
| --- | ------------------------------------------------------------------------------------------------ |
| D1  | `declare(strict_types=1)` in ALL PHP files except migrations and config                          |
| D2  | No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code               |
| D3  | All user-facing strings use `__()` helper — both `lang/en/` and `lang/id/`                       |
| D4  | Models use `#[Fillable]` attribute (PHP 8.4), NOT `$fillable` or `$guarded`                      |
| D5  | Never pass raw request input to `create()`/`update()` — use explicit `->only()` or `->toArray()` |
| D6  | Foreign keys use `foreignUuid()->constrained('{table}')` with explicit `onDelete()`/`onUpdate()` |

---

## Metacognitive Loop

Every task follows four phases. Loop until DECIDE resolves to **Accept** or **Escalate**.

```
SCOPE → CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

### 0. SCOPE — Define & Cross-check (Mandatory, Never Skip)

Before any action, regardless of prior knowledge:

- **Define scope** — what exactly needs to be done? Which modules, files, and layers are affected?
- **Define purpose** — why is this change needed? What outcome is expected?
- **Cross-check applicable rules** — re-read the specific docs, patterns, and conventions that apply
  to this task. Do NOT rely solely on memory. Verify AGENTS.md invariants, architecture.md rules,
  and the relevant pattern docs BEFORE writing any code or creating any issue.
- **Identify risks** — what could go wrong? Are there dependencies, breaking changes, or existing
  patterns that must not be violated?
- State the scope + rules check explicitly in your response before proceeding.

This step is **mandatory and non-negotiable** for every task, even for experienced agents.

### 1. CONSTRUCT — Build with Context

- Read relevant `docs/modules/{module}.md` and `docs/architecture/{pattern}-pattern.md`
- Read existing code for patterns — verify paths, signatures, column names
- Consider at least two approaches before committing
- Follow 4-layer dependency rules, Action Triad, DTO boundaries

### 2. EVALUATE — Assess What Was Built

- Does it match requirements? Respect layer boundaries? Use correct base class?
- Convention compliance: `declare(strict_types=1)`, `#[Fillable]`, naming rules
- Scope discipline: does ONE thing? Split if it grew beyond original scope

### 3. VERIFY — Confirm It Works

```bash
php artisan test --compact --filter={TestName}
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress
```

- No debug calls, `__()` for user strings, pre-commit checklist complete

### 4. DECIDE — Next Action

| Decision     | When                                                      |
| ------------ | --------------------------------------------------------- |
| **Accept**   | Correct, complete, verified → commit                      |
| **Revise**   | Works but quality/structure issues → return to CONSTRUCT  |
| **Split**    | Grew beyond scope → create separate task, commit original |
| **Escalate** | Deeper problem → file issue, link blocker                 |
| **Defer**    | Lower priority → move to backlog, document why            |

---

## SDLC Phase Map

Load the relevant skill when working in each phase. Always load **`context-awareness`** first on
every session.

| Phase                    | Skills                                                                                                                                           |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **PLANNING**             | `roadmap-planning`                                                                                                                               |
| **ANALYSIS**             | `audit-protocol`, `security-audit`                                                                                                               |
| **DESIGN / REFACTORING** | `code-refactoring`                                                                                                                               |
| **IMPLEMENTATION**       | `feature-building`, `laravel-best-practices`, `livewire-development`, `pulse-development`, `medialibrary-development`, `tailwindcss-development` |
| **TESTING**              | `pest-testing`                                                                                                                                   |
| **MAINTENANCE**          | `sync-docs`                                                                                                                                      |

---

## MCP & Tooling

### MCP Servers (configured in `opencode.json`)

- **docsgrep** — documentation search (`@anovise/docsgrep`, local npx)
- **laravel-boost** — Laravel ecosystem: DB schema, error logs, docs search, etc.

### Boost Tools (preferred over manual alternatives)

| Tool                          | Use Instead Of          |
| ----------------------------- | ----------------------- |
| `database-query`              | Raw SQL / tinker        |
| `database-schema`             | Reading migrations      |
| `get-absolute-url`            | Manual URL construction |
| `browser-logs`                | Debugging frontend      |
| `search-docs` with `packages` | Googling package docs   |

---

## Documentation Map

All authoritative docs live under `docs/`. Read the relevant doc before making changes.

| Topic                   | Start Here                                                       |
| ----------------------- | ---------------------------------------------------------------- |
| Architecture & 4 layers | `docs/architecture.md`                                           |
| Action Triad            | `docs/architecture.md` (§Action Triad)                           |
| Base Class Mandate      | `docs/architecture.md` (§Base Class Mandate)                     |
| Models & Entities       | `docs/architecture/model-pattern.md`, `entity-pattern.md`        |
| Livewire components     | `docs/architecture/livewire-pattern.md`                          |
| Policies & RBAC         | `docs/architecture/policy-pattern.md`, `docs/foundation/rbac.md` |
| Events & Notifications  | `docs/architecture/event-pattern.md`                             |
| Exception hierarchy     | `docs/architecture/exception-pattern.md`                         |
| Caching                 | `docs/architecture/cache-pattern.md`                             |
| Testing                 | `docs/architecture/testing-pattern.md`                           |
| Deployment              | `docs/infrastructure/deployment.md`                              |
| Database schema         | `docs/infrastructure/database.md`                                |
| Complete doc catalog    | `docs/index.md`                                                  |
| Module index            | `docs/modules/index.md`                                          |
| ADRs                    | `docs/adr/index.md`                                              |
| Known issues            | [GitHub Issues](https://github.com/reasvyn/internara/issues)     |
| Roadmap                 | `docs/roadmap.md`                                                |

### Key Source Files

| File                                     | Purpose                                     |
| ---------------------------------------- | ------------------------------------------- |
| `app/Core/Actions/BaseAction.php`        | Transaction, event queue, logging, fail()   |
| `app/Core/Actions/BaseCommandAction.php` | respond(), validate(), authorize(), flash() |
| `app/Core/Actions/BaseReadAction.php`    | Read action contract                        |
| `app/Core/Actions/BaseProcessAction.php` | Process action contract                     |
| `app/Core/Models/BaseModel.php`          | UUID PK, common scopes                      |
| `app/Core/Data/ActionResponse.php`       | Structured action return envelope           |
| `app/Core/Data/BaseData.php`             | Immutable DTO with fromArray/toArray        |
| `app/Core/Entities/BaseEntity.php`       | Entity base with fromModel()                |

### Key Config Files

| File                    | Purpose                       |
| ----------------------- | ----------------------------- |
| `config/cache-keys.php` | ALL cache keys (never inline) |
| `config/permission.php` | RBAC permissions              |
| `config/setup.php`      | Setup defaults and security   |
| `config/settings.php`   | System settings               |
| `config/module.php`     | Module discovery              |

---

## Pre-commit Checklist

Before committing, verify:

- [ ] `declare(strict_types=1)` present
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`)
- [ ] All user-facing strings use `__()` helper
- [ ] Action uses correct triad base class
- [ ] Command/Process: accepts DTO (for 3+ params), returns ActionResponse
- [ ] Business rules delegated to Entity (not inline in Action)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] No unescaped `{!! !!}` for user content
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`
- [ ] PHPStan passes: `vendor/bin/phpstan analyse --no-progress`
- [ ] Relevant docs updated (documentation-first approach)

---

## Quick Reference

### Commit Format

```
type(scope): description
```

Types: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

### Branch Naming

`feat/{kebab}`, `fix/{desc}`, `refactor/{module}-{scope}`, `docs/{what}`, `chore/{task}`,
`hotfix/{desc}`

### Language

**English only.** Code, comments, commits, docs — all English. Indonesian only in `lang/id/`.

### Dev Commands

```bash
composer run dev                  # Serve + queue + logs + vite
composer run test                 # Full test suite
composer run analyse              # PHPStan
composer run quality              # Lint + analyse + feature tests
php artisan system:health         # Health check
php artisan admin:recover         # Super admin recovery (CLI)
```
