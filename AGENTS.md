# AGENTS.md — Project Guidelines for AI Agents

Essential mental model, non-negotiable rules, and quick-reference for AI agents.
Does NOT duplicate content in `docs/` — refer there for depth.

## Agent Workflow — Mandatory Steps

Every task MUST follow these 9 steps in order. **No step may be skipped.** If a step is
not applicable, explicitly note why and move on. Steps may be lightweight for simple tasks, but
they must never be omitted.

```
UNDERSTAND → DEFINE & SCOPE → EXPLORE & CONSTRUCT → PLAN → DESIGN → DEVELOP → TEST & VERIFY → DOCUMENT → COMMIT & REPORT
```

---

### 1. Understand Instruction

**Goal:** Internalize what the user actually wants — not just what they literally said.

- Read the instruction carefully; identify the **intent**, not just the literal request
- Clarify ambiguities: ask the user if the request could mean multiple things
- Identify constraints: deadline, scope, compatibility, performance
- Determine if this is a new feature, bug fix, refactor, docs update, or investigation
- **Output:** Clear restatement of the task in your own words, confirmed with user if ambiguous

### 2. Define & Scope

**Goal:** Bound the work. Know what's in, what's out, and what's affected.

- Identify which module(s) and layer(s) (4-layer model) are affected
- List the files that will be created, modified, or deleted
- Determine dependencies: does this block or get blocked by other work?
- Check for existing issues on GitHub that overlap
- Decide: is this one atomic change or should it be split?
- **Output:** Scope statement — affected modules, layers, files, dependencies, risks

### 3. Explore & Construct

**Goal:** Build a mental model of the existing code before writing anything.

- Read `docs/architecture.md`, `docs/conventions.md`, `docs/modules/index.md`
- Read the relevant module docs: `docs/modules/{module}.md` and `{module}-reference.md`
- Read the relevant pattern doc: `docs/architecture/{pattern}-pattern.md`
- Read existing code in the affected submodule — match its conventions exactly
- Verify paths, class names, signatures against actual source (never trust docs blindly)
- Trace the data flow for the feature you're about to build or change
- **Output:** Complete understanding of existing patterns, conventions, and code structure

### 4. Plan

**Goal:** Determine the implementation strategy before writing code.

- Consider at least 2 approaches; document why you chose one over the other
- Identify which Action type to use (Command / Read / Process) and why
- Determine Entity boundaries: what business rules go where?
- Determine DTO structure: what parameters, what types?
- Plan the test strategy: what to test, what verification approach to use
- Identify risks: N+1 queries, mass assignment, cache invalidation, event side effects
- **Output:** Implementation plan with approach, file list, test strategy, risk assessment

### 5. Design

**Goal:** Define contracts before implementation — class signatures, data flow, error handling.

- Define the Action signature: `execute()` parameters and return type
- Define the Entity: `final readonly` properties, `fromModel()`, business question methods
- Define the DTO: `final readonly` properties (scalars, enums, Carbon only)
- Define the enum: cases, backing values, `validTransitions()`, `label()`
- Define error handling: which exceptions, which failure modes
- Define events: which events fire, which listeners handle them
- Review against Critical Invariants (C1-C8, D1-D6) before coding
- **Output:** Class signatures, data flow diagram, error handling plan

### 6. Develop

**Goal:** Write code that follows all conventions and passes all quality gates.

- Follow file header order: `declare(strict_types=1)` → namespace → use → class → constructor → execute()
- Follow class contracts exactly (see `docs/architecture/{pattern}-pattern.md`)
- Follow naming conventions (see `docs/conventions.md` §4)
- Use `__()` for all user-facing strings
- Register cache keys in `config/cache-keys.php`
- Write code that is testable — inject dependencies, avoid static calls
- **Output:** Working code that matches the design from step 5

### 7. Test & Verify

**Goal:** Confirm the code works correctly and doesn't break anything.

- Choose verification strategy based on change type (see `test-writing` skill)
- Write tests covering: happy path, business rule violations, validation errors
- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run targeted tests: `php artisan test --compact --filter={ClassName}`
- Only run full suite if changes affect core infrastructure or cross-module logic
- Check: no debug calls, no N+1 queries, no missing eager loads
- **Output:** All tests pass, linter clean, static analysis clean

### 8. Document

**Goal:** Ensure documentation reflects the current code state (documentation-first principle).

- Update module conceptual doc if business rules changed
- Update module reference doc if file structure, Actions, or schemas changed
- Update `docs/architecture/{pattern}-pattern.md` if base class contracts changed
- Update metadata: `**Last updated:** YYYY-MM-DD **Changes:** ...`
- Verify all cross-references resolve to existing files
- Add PHPDoc if complex logic needs explanation (see `doc-writing` skill)
- **Output:** Docs match code; metadata current; links valid

### 9. Commit & Report

**Goal:** Deliver a clear summary and clean commit.

- Deliver report to user:
  - What was done (summary)
  - Files created/modified
  - Architecture decisions made
  - Tests written
  - Docs updated
  - Any risks or follow-ups
- Commit using format: `type(scope): description`
- Types: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`
- Push only if requested
- **Output:** Clean commit, user informed

---

## Project Identity

Self-hosted, single-tenant PKL management for Indonesian SMA/SMK (MIT).

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Frontend | Livewire 4, Alpine.js, maryUI 2, DaisyUI 5, Tailwind CSS v4 |
| Database | SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+ |
| Testing | Pest 4, PHPStan (level 8), Laravel Pint |

19 modules, each colocated under `app/{Module}/` owning its full stack.

## Architecture Compass

### 4-Layer Model (strict downward dependency)

| Layer | Content | Location Prefix |
|-------|---------|-----------------|
| 4 — Presentation/UI | Livewire, Blade, Policies, Routes | `{Module}/Livewire/`, `routes/web/` |
| 3 — Business/Domain Ops | Command/Read/Process Actions, Events | `{Module}/Actions/`, `{Module}/Events/` |
| 2 — Data/Persistent | Models, Entities (`final readonly`), DTOs (`BaseData`), Enums | `{Module}/Models/`, `{Module}/Entities/` |
| 1 — Framework/Infra | Core base classes, Contracts, Exceptions, Services | `app/Core/`, `{Module}/Services/` |

### Action Triad

| Type | Base | Transaction | Log | Events | Purpose |
|------|------|-------------|-----|--------|---------|
| **Command** | `BaseCommandAction` | ✅ | ✅ | Optional | All mutations (CUD, state transitions) |
| **Read** | `BaseReadAction` | ❌ | ❌ | ❌ | Complex queries, aggregation (does NOT extend BaseAction) |
| **Process** | `BaseProcessAction` | ✅ | ✅ | Optional | Multi-step orchestration |

**Key rules:**
- Exactly one public method: `execute()`
- No `Model::create/update/delete` in Livewire — always use Command Actions
- Accept `BaseData` DTO for 3+ params (typed scalars OK for 1-2). Never raw `array`
- Return `ActionResponse` for structured feedback
- Delegate business rules to Entities — throw `RejectedException` on violation
- Events queued via `$this->dispatchEvent()` (auto-flushed after transaction commits). Do NOT call `$event::dispatch()` directly in Actions

## Critical Invariants

### Architecture
| # | Rule |
|---|------|
| C1 | No `Model::create/update/delete` in Livewire — use Command Actions |
| C2 | No `app()->make()` / `resolve()` — use constructor injection |
| C3 | No `DB::raw()` / `whereRaw()` without parameterized binding |
| C4 | No inline cache keys — register in `config/cache-keys.php` |
| C5 | Entities must NOT import Actions, Services, Livewire, Controllers |
| C6 | DTOs must NOT import Models, Entities, Actions — Core BaseData, scalars, enums, Carbon only |
| C7 | Command/Process Actions: accept DTO for 3+ params, return ActionResponse |
| C8 | Business rules → `RejectedException`, not `RuntimeException` |

### Super Admin
| # | Rule |
|---|------|
| S1 | Name ALWAYS `Administrator` (config `setup.defaults.admin_name`) |
| S2 | Username ALWAYS `superadmin` (config `setup.defaults.admin_username`) |
| S3 | `SetupSuperAdminAction::execute()` accepts ONLY `(string $email, string $password)` |
| S4 | `InitializeSuperAdminAction` uses config defaults, NOT caller-provided values |

### Reports Module
| # | Rule |
|---|------|
| R1 | Grade card only — final scores, grade letter, archived snapshot |
| R2 | NEVER add thesis/final report content to `app/Reports/` |
| R3 | Student thesis belongs in `app/Assignment/` |

### Coding
| # | Rule |
|---|------|
| D1 | `declare(strict_types=1)` in ALL PHP files except migrations/config |
| D2 | No `dd/dump/ray/var_dump/print_r/die` in committed code |
| D3 | All user-facing strings use `__()` — both `lang/en/` and `lang/id/` |
| D4 | Models use `#[Fillable]` attribute (PHP 8.4), NOT `$fillable` / `$guarded` |
| D5 | Never pass raw request input to `create()`/`update()` — use `->only()` or `->toArray()` |
| D6 | Foreign keys use `foreignUuid()->constrained('{table}')` with explicit `onDelete()`/`onUpdate()` |

## Verification Strategy

**Batch ALL changes first, then verify ONCE.** Full suite is ~2GB+ memory, 10+ minutes.

| Change Type | Verification |
|-------------|-------------|
| Translation keys (`lang/*.php`) | `php -l` + `php artisan tinker --execute="echo __('key');"` |
| Config/docs/markdown | Visual inspection, no tests |
| Blade/CSS/JS | `npm run build` only |
| Refactoring (rename, extract) | Targeted test: `php artisan test --compact --filter={TestSuite}` |
| New feature / business logic | Full suite ONCE after all changes batched |
| Dependency updates | `vendor/bin/pest --testsuite={ModuleName}` (run affected module suites) |

```bash
# Targeted tests
vendor/bin/pest --testsuite={ModuleName}   # Run tests for a specific module (replace {ModuleName})
php artisan test --compact --filter={ClassName}
php -l path/to/file.php
php artisan system:health

# Full verification (after refactoring or before merge)
php artisan test --compact   # Run full test suite (all modules)
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress
```

## Pre-commit Checklist
- [ ] `declare(strict_types=1)` present
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`)
- [ ] All user-facing strings use `__()`
- [ ] Action uses correct triad base class
- [ ] Command/Process: DTO for 3+ params, returns ActionResponse
- [ ] Business rules delegated to Entity (not inline in Action)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] No unescaped `{!! !!}` for user content
- [ ] `php artisan test --compact` passes
- [ ] `vendor/bin/pint --dirty --format agent` clean
- [ ] `vendor/bin/phpstan analyse --no-progress` passes
- [ ] Relevant docs updated (documentation-first approach)

## Quick Reference

### Dev Commands
```bash
composer run dev           # Serve + queue + logs + vite (concurrently)
composer run test          # Full suite (optimize:clear + test)
# Run tests for a specific module:
vendor/bin/pest --testsuite={ModuleName}  # Replace {ModuleName} with module name, e.g., 'User'
composer run analyse       # PHPStan level 8
composer run quality       # Lint + analyse + module tests
php artisan system:health  # Health check
php artisan admin:recover  # Super admin CLI recovery
php artisan setup:install  # Audits env, runs migrations, seeds defaults
npm run build              # Vite build (check frontend)
```

### Commit Format
`type(scope): description` — `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

### Branch Naming
`feat/{kebab}`, `fix/{desc}`, `refactor/{module}-{scope}`, `docs/{what}`, `chore/{task}`, `hotfix/{desc}`

### Language
**English only** — code, comments, commits, docs. Indonesian only in `lang/id/`.
