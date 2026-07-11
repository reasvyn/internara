---
name: context-awareness
description: SDLC Phase: ORIENTATION. Universal project orientation for Internara — architecture rules, module map, decision framework, critical rules, and navigation patterns. Must be loaded first on every session. All other skills assume this context.
downstream:
  - audit-protocol
  - code-refactoring
  - code-writing
  - doc-writing
  - feature-building
  - laravel-best-practices
  - livewire-development
  - medialibrary-development
  - pest-testing
  - pulse-development
  - roadmap-planning
  - security-audit
  - sync-docs
  - tailwindcss-development
  - test-writing
  - arch-guard
  - script-automation
---

# Context Awareness

> **Prerequisite:** None — this is the first skill to load.

## When to Activate

Load this skill at the start of every session. It provides the mental model needed by all other
skills. Without it, you lack the architectural context to make sound decisions.

## Agent Workflow

This skill is the **orientation phase** — it does NOT write code or run tests. It builds the mental
model that all subsequent skills depend on.

### 1. Construct — Knowledge, Context & Scope

- Read the user's instruction carefully; identify the **intent**, not just the literal request
- Determine scope: is this a single file change, a cross-module refactor, or a new feature?
- Identify which module(s) are affected
- Read relevant docs: module docs, pattern docs, reference docs
- Verify paths, class names, signatures against actual code — never trust docs blindly

### 2. Execute — Build Mental Model

- Read `docs/architecture.md`, `docs/conventions.md`, `docs/modules/index.md`
- Understand 4-layer architecture, Action Triad, DTO boundaries
- Read module docs: `docs/modules/{module}.md` and `docs/modules/{module}-reference.md`
- Map the data flow: Livewire → Action → Entity → Model → DB
- Identify which layer the task touches and what constraints apply
- Output: project mental model — architecture, module boundaries, critical rules

### 3. Verify — Orientation Completeness

Before handing off to any downstream skill, confirm:
- [ ] Which module(s) and layer(s) are affected
- [ ] Which class types need to be created or modified
- [ ] What invariants (C1-C8, D1-D6) apply to this task
- [ ] What existing code can be followed as a pattern
- [ ] What docs need to be read before writing code

### 4. Report — Hand Off to Downstream Skill

- Deliver orientation summary to the user:
  - Affected modules and layers
  - Architecture constraints that apply
  - Existing patterns to follow
  - Risks or edge cases identified
- Recommend the appropriate downstream skill(s) for execution

---

## Codebase Senses

The ability to navigate, understand, and reason about the codebase efficiently.

### Navigation Patterns

| Need to find... | Look here |
|-----------------|-----------|
| Business logic | `app/{Module}/{Submodule}/Actions/` |
| Business rules | `app/{Module}/{Submodule}/Entities/` |
| Data structure | `app/{Module}/{Submodule}/Models/` |
| Data transfer | `app/{Module}/{Submodule}/Entities/` (DTOs) |
| State machines | `app/{Module}/{Submodule}/Enums/` |
| UI components | `app/{Module}/{Submodule}/Livewire/` |
| Authorization | `app/{Module}/{Submodule}/Policies/` |
| Side effects | `app/{Module}/{Submodule}/Events/` and `Listeners/` |
| Infrastructure | `app/{Module}/{Submodule}/Services/` or `Support/` |
| Base contracts | `app/Core/Actions/`, `app/Core/Entities/`, `app/Core/Enums/` |
| Tests | `tests/{Module}/{Submodule}/` |
| Config | `config/{module}.php` |
| Routes | `routes/web/{module}.php` |
| Translations | `lang/en/{module}.php`, `lang/id/{module}.php` |

### Pattern Recognition

When you see this code pattern, recognize what it should be:

| You see... | It should be... | Violation? |
|------------|-----------------|------------|
| `Model::create()` in a Livewire component | Command Action | **C1 violation** |
| `app()->make(SomeAction::class)` | Constructor injection | **C2 violation** |
| `DB::raw("...")` without binding | Eloquent query builder | **C3 violation** |
| `'cache_key'` string inline | Key in `config/cache-keys.php` | **C4 violation** |
| Entity importing `Action` or `Service` | Entity should be pure | **C5 violation** |
| DTO importing `Model` or `Entity` | DTO should carry scalars only | **C6 violation** |
| Action accepting raw `array` for 3+ params | Should accept DTO | **C7 violation** |
| `throw new RuntimeException('business rule')` | Should be `RejectedException` | **C8 violation** |
| `$fillable = [...]` property | `#[Fillable([...])]` attribute | **D4 violation** |
| `$request->all()` in create/update | `->only()` or `->toArray()` | **D5 violation** |

### Data Flow Tracing

Every mutation in the system follows this path:

```
User interaction
  → Livewire component (validates input, catches RejectedException)
    → Command Action::execute(DTO)
      → Entity::fromModel(model) → business rules
      → Model::create/update(values from DTO)
      → $this->log()
      → $this->dispatchEvent() [queued, fires after commit]
    ← ActionResponse
  ← Flash message / redirect / re-render
```

When debugging or reviewing code, trace this path. If any step is missing or out of order, there's
likely a bug or architecture violation.

### Module Boundary Awareness

- Each module owns its full stack: Models, Actions, Livewire, Events, Policies, Services
- Cross-module imports are **allowed** but prefer events for side effects
- If Module A needs to react to Module B's mutation, use an Event — don't import B's Actions
- Shared code (base classes, contracts, exceptions) lives in `app/Core/`

---

## Testing Senses

The ability to choose the right verification strategy, write effective tests, and detect problems.

### Verification Strategy Selection

**Core principle:** Always ask "can I verify this without running tests?" before reaching for the
test suite. The full suite consumes ~2GB+ RAM and 10+ minutes.

| Change type | Lightest verification |
|-------------|----------------------|
| Translation keys | `php -l` + tinker echo |
| Config / docs | Visual inspection |
| Blade / CSS / JS | `npm run build` |
| Single method refactor | `php artisan test --compact --filter={ClassName}` |
| Cross-module refactor | `vendor/bin/pest --testsuite={Module}` |
| New feature / business logic | Full suite ONCE, after all changes batched |

### Test Pattern Recognition

| What you're testing | Pattern to follow |
|---------------------|-------------------|
| Command Action | Arrange (factory + DTO) → Act (execute) → Assert (assertModelExists + ActionResponse) |
| Read Action | Arrange (seed data) → Act (execute) → Assert (typed return, collection shape) |
| Entity | Test every `canX()` / `isX()` method; no DB needed |
| DTO | Test `fromArray()` / `toArray()` roundtrip; no DB needed |
| Enum | Test every case has `label()`; test `validTransitions()` exhaustively |
| Livewire | Test render, mount, form submission, authorization; use `actingAs()` |
| Policy | Test `allow` / `deny` for each role; no DB needed beyond the model |

### Test Health Indicators

| Symptom | Diagnosis |
|---------|-----------|
| Test passes in isolation, fails in suite | Shared state or ordering issue — check `LazilyRefreshDatabase` |
| `Class "X" not found` | Autoload stale — `composer dump-autoload` |
| `SQLSTATE[HY000]` | Migration missing — `php artisan migrate:fresh` |
| Test times out | Infinite loop or queue not drained — add `Queue::fake()` |
| Flaky test (sometimes passes) | Race condition or missing `RefreshDatabase` — isolate the test |
| Test was failing before your change | Pre-existing issue — flag it, don't fix it unless asked |

### Coverage Priorities

| Priority | Layer | Target | Why |
|----------|-------|--------|-----|
| 1 | Enums | 100% | State machines — wrong transitions break the system |
| 2 | Entities | 100% | Business rules — the core correctness guarantee |
| 3 | DTOs | 100% | Data contracts — wrong shapes cascade errors |
| 4 | Command Actions | ≥90% | Mutations — most likely to introduce bugs |
| 5 | Policies | 100% | Security — wrong auth = data breach |
| 6 | Read Actions | ≥80% | Queries — wrong data = wrong decisions |
| 7 | Livewire | ≥80% | UI — wrong input handling = bad UX |

---

## Documentation Senses

The ability to detect doc drift, choose the right doc tier, and maintain documentation integrity.

### Doc Drift Detection

Doc drift happens when code changes but docs don't. Detect it by asking:

| Question | How to check |
|----------|-------------|
| Does the doc's file listing match the actual directory? | `ls app/{Module}/{Submodule}/` vs doc |
| Does the Actions table list all current Actions? | `find app/{Module}/Actions -name '*Action.php'` |
| Does the Entity description match the actual methods? | Read the Entity class |
| Do the enum cases in the doc match the code? | Read the Enum class |
| Do the migration descriptions match the actual migrations? | Check `database/migrations/` |
| Are the cross-references still valid? | Verify every `[text](path)` resolves |

### Tier Selection

| Content type | Tier | Example |
|-------------|------|---------|
| "Why does this module exist?" | Conceptual | `docs/modules/{module}.md` |
| "What business rules govern enrollment?" | Conceptual | `docs/modules/enrollment.md` |
| "Which files implement the Action?" | Reference | `docs/modules/enrollment-reference.md` |
| "What's the table schema?" | Reference | `docs/modules/enrollment-reference.md` |
| "Why did we choose Actions over Services?" | Conceptual (architecture) | `docs/architecture/action-pattern.md` |
| "What's the Action contract?" | Reference (architecture) | `docs/architecture/action-pattern.md` |

**Rule of thumb:** If it explains *why*, it's conceptual. If it explains *what* or *how*, it's
reference.

### When to Update Docs

| Code change | Doc to update |
|-------------|--------------|
| New Action added | Module reference doc (Actions table) |
| Entity method changed | Module conceptual doc (business rules) |
| Enum case added/removed | Module reference doc (enum table) |
| New migration | Module reference doc (schema section) |
| New module created | `docs/modules/index.md` + conceptual + reference |
| Config key added | Module reference doc (config section) |
| Route added/changed | Module reference doc (Routes table) |
| Base class method changed | `docs/architecture/{pattern}-pattern.md` |
| Invariant added/changed | `AGENTS.md` + `context-awareness` SKILL.md |

### Metadata Discipline

Every markdown file MUST have on line 3:

```markdown
> **Last updated:** YYYY-MM-DD **Changes:** brief description
```

When you change content, update the date. When you sync without content changes, use `sync —`
prefix. This is not optional — it's how we track documentation freshness.

### Link Integrity

Before committing any doc change:
1. Every `[text](path)` resolves to an existing file
2. Every `[text](path#anchor)` matches an existing heading
3. No content is duplicated — cross-reference instead
4. `## Where to Find It` is the standard footer (not `## References`)

---

## Metacognitive Loop

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — Read relevant docs and existing code; verify paths and signatures; consider
   multiple approaches
2. **EVALUATE** — Does it match requirements? Respect layer boundaries? Do ONE thing?
3. **VERIFY** — Lint + static analysis + tests pass; no debug calls; `__()` for strings
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer

---

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

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_files.py` | File counts and lines of code per module | `python3 scripts/scan_files.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 scripts/scan_architecture.py` |

Output: `scripts/outputs/{timestamp}-{description}.json`.
