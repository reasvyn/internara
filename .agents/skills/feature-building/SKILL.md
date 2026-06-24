---
name: feature-building
description: SDLC Phase: IMPLEMENTATION (Orchestrator). Execution phase following roadmap-planning. Takes task specifications from docs/roadmap.md and implements them — bug fixes, security patches, performance optimization, refactoring, feature development, tests, and documentation. Orchestrates specialized implementation skills.
upstream: [roadmap-planning, code-refactoring]
downstream: [pest-testing, sync-docs, livewire-development, tailwindcss-development, medialibrary-development, pulse-development]
---

# Feature Building Skill

## When to Activate

Apply this skill when executing a task from `docs/roadmap.md`. This is the **implementation phase**
that follows the **planning phase** (`roadmap-planning`). It reads task specifications from the
roadmap, implements them following project conventions, and reports completion.

This is the **primary orchestrator** for implementation. It delegates specialized work to
sub-skills: `livewire-development`, `tailwindcss-development`, `medialibrary-development`, and
`pulse-development` as needed.

**Prerequisites:** A roadmap task specification in `docs/roadmap.md` with:
- Current state / Target state
- Files to modify
- Implementation notes
- Testing requirements

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `roadmap-planning` — task specifications from `docs/roadmap.md` |
| | `code-refactoring` — refactored code for clean-up tasks |
| **This skill** | **IMPLEMENTATION (Orchestrator)** — produces working code |
| **Sub-skills** | `livewire-development` — Livewire component work |
| | `tailwindcss-development` — styling and UI |
| | `medialibrary-development` — file upload and media |
| | `pulse-development` — Pulse monitoring |
| | `laravel-best-practices` — cross-cutting Laravel guidance |
| **Downstream (output)** | `pest-testing` — tests for new/modified code |
| | `sync-docs` — documentation updated after implementation |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

---

## Execution Workflow

### Phase 0 — Task Intake

#### 0.1 Read Roadmap Task

Read `docs/roadmap.md` and identify the specific task to implement. A task specification looks like:

```
### Task N.M — {Verb}{Entity}

| Field | Value |
|-------|-------|
| **Pipeline** | fix / security / perf / refactor / feat / test / docs / chore |
| **Module** | {Module} |
| **Effort** | Small / Medium / Large |
| **Files** | `{file}`, `{file}` |
| **Depends on** | Task X.Y |

**Current state:** What the code does now.

**Target state:** What the code should do after implementation.

**Implementation notes:** Step-by-step approach, pattern references.

**Testing:** What to test and how.
```

#### 0.2 Load Context

| Source | What to extract |
|--------|-----------------|
| `docs/modules/{module}.md` | Module purpose, boundary, lifecycle |
| `docs/modules/{module}-reference.md` | Existing files, table schemas, dependencies |
| `docs/architecture/{pattern}-pattern.md` | The pattern to follow (Action, Entity, Livewire, etc.) |
| `docs/conventions.md` | Conventions to comply with |
| `AGENTS.md` | Project invariants |

#### 0.3 Validate Prerequisites

- [ ] Task dependencies (Depends on) are completed — check roadmap for status.
- [ ] Module directory exists at `app/{Module}/`.
- [ ] All referenced files in the task spec exist.
- [ ] Pattern docs exist for the approach.

**If prerequisites are not met:** Stop and report. Do not start implementation.

---

### Phase 1 — Execute by Pipeline

Each pipeline follows a different execution path. Identify the task's pipeline and apply the
corresponding workflow.

---

#### 1.1 Bug Fix (`fix`)

**Goal:** Fix incorrect behavior with minimal diff.

```
Read failing test / bug report
  → Reproduce the bug
  → Identify root cause (not the symptom)
  → Write a failing test that captures the bug
  → Apply the minimal fix
  → Verify test passes
  → Check for similar bugs (same pattern elsewhere)
```

**Key checks:**
- [ ] Root cause identified (not just symptom masked).
- [ ] Test written BEFORE fix (TDD) that reproduces the exact bug.
- [ ] Fix is minimal — no scope creep.
- [ ] No magic numbers or strings — use enums or constants.
- [ ] Check for same bug pattern in sibling files (grep the root cause pattern).

---

#### 1.2 Security (`security`)

**Goal:** Remove vulnerability without changing behavior.

```
Identify vulnerability type (XSS, SQLi, mass assignment, CSRF, auth bypass, etc.)
  → Read conventions.md §3 (Security Conventions)
  → Read OWASP cheat sheet for the vulnerability type
  → Apply the fix:
    • XSS: {!! !!} → {{ }}, add HTML purifier for trusted content
    • SQLi: raw concatenation → parameterized queries or Eloquent
    • Mass assignment: $request->all() → $request->only([...])
    • CSRF: add @csrf or ensure Livewire handles it
    • Auth: add Gate::authorize() or policy check
  → Write test that proves the vulnerability is closed
  → Search for same vulnerability pattern across the codebase
```

**Key checks:**
- [ ] Vulnerability confirmed closed (test proves it).
- [ ] Same vulnerability pattern searched across codebase — fix similar occurrences.
- [ ] No breaking changes to existing functionality.
- [ ] Security fix has an entry in [GitHub Issues](https://github.com/reasvyn/internara/issues) (if not already there).

---

#### 1.3 Performance (`perf`)

**Goal:** Reduce response time, memory, or query count.

```
Profile the bottleneck (query log, debugbar, or manual inspection)
  → Identify the specific N+1, slow query, or memory issue
  → Apply optimization:
    • N+1: add ->with() eager loading on the parent query
    • Slow query: add index, restructure query, add cache
    • Memory: replace get()→filter() with database filter, use chunk/lazy
    • Cache: add Cache::remember() around expensive computation
  → Verify optimization (query count reduced, response time improved)
  → Check for same pattern in related files
```

**Key checks:**
- [ ] Query count reduced (verify with `DB::listen()` or Telescope).
- [ ] No behavior change — only performance.
- [ ] Cache invalidation is in place (event-driven or explicit `Cache::forget()`).
- [ ] Same performance antipattern searched across codebase.

---

#### 1.4 Refactor (`refactor`)

**Goal:** Improve code structure with zero behavior change.

```
Read the current code
  → Understand what behavior must be preserved
  → Apply the refactoring:
    • Extract inline logic to Action (see action-pattern.md)
    • Extract business rules to Entity (see entity-pattern.md)
    • Rename class/method/variable (see conventions.md §4)
    • Move file to correct module/submodule
    • Change base class (e.g., BaseAction → BaseCommandAction)
  → Write/update tests that lock in the behavior
  → Remove dead code (unused imports, variables, methods)
```

**Key checks:**
- [ ] Behavior preserved — tests pass without modification.
- [ ] Dead code removed (unused imports, dead assignments).
- [ ] All references updated (grep for old names).
- [ ] No inline persistence or business logic left in Livewire components.
- [ ] `vendor/bin/pint --dirty` passes.

---

#### 1.5 Feature (`feat`)

**Goal:** Build new capability following the full feature lifecycle.

Follow the Build Sequence (Phase 2 below) for the complete feature workflow.

---

#### 1.6 Test (`test`)

**Goal:** Add or fix tests.

```
Read the source file that needs tests
  → Identify what needs testing:
    • Happy path
    • Edge cases (null, empty, boundary values)
    • Error handling (validation, business rules, authorization)
    • Side effects (events dispatched, cache cleared, notifications sent)
  → Write tests following conventions:
    • Unit test: Entity, Enum, DTO, Policy
    • Feature test: Action, Livewire, Console Command
    • Use LazilyRefreshDatabase for feature tests
    • Use assertModelExists() over assertDatabaseHas()
  → For flaky tests: identify the race condition or state leak, fix it
```

**Key checks:**
- [ ] Coverage meets thresholds (§10.2 conventions.md):
  - Entity/Enum/DTO: 100%
  - Command/Process Action: ≥ 90%
  - Read Action: ≥ 80%
  - Livewire: ≥ 80%
  - Policy: 100%
- [ ] No `dd()`/`dump()` in test files.
- [ ] `Event::fake()` positioned AFTER factory setup.
- [ ] Tests are deterministic (no time-sensitive assertions that can flake).

---

#### 1.7 Docs (`docs`)

**Goal:** Update documentation to reflect implementation.

```
Read the module's conceptual doc and reference doc
  → Identify what's stale:
    • New files not listed in reference doc
    • Deleted files still listed
    • Changed behavior not reflected
  → Update the docs:
    • Conceptual doc ({module}.md): purpose, boundary, principles
    • Reference doc ({module}-reference.md): files, schemas, deps
    • Doc-index.md: links, catalog entries
    • Known-issues.md: mark resolved items
  → Verify with doc-index cross-reference
```

---

#### 1.8 Chore (`chore`)

**Goal:** Update tooling, config, dependencies.

```
Identify the specific change (dependency version, config key, CI change)
  → Apply the change
  → Verify nothing breaks:
    • Vendor updates: composer install / npm install, run full test suite
    • Config changes: verify config caching works
    • CI changes: verify pipeline passes
  → Pin versions in composer.json / package.json (no ranges for critical deps)
```

---

### Phase 2 — Build Sequence (for Feature Pipeline)

When the task is a new feature (`feat`), follow this sequence in order:

```
Docs → Migration/Model → Enum → Entity → Action → Policy → Livewire → Blade → Routes → Translations → Tests → Quality
```

#### Step 2.1 — Read Module Context

Read `docs/modules/{module}.md` and `docs/modules/{module}-reference.md`. Understand:
- Module boundary (what belongs in this module vs. others)
- Existing submodules, models, actions
- Dependencies on other modules

#### Step 2.2 — Migration & Model

- Migration naming: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- Model extends `BaseModel` (UUID PK via `HasUuids`)
- Uses `#[Fillable]` attribute (not `$fillable` property)
- Uses `HasFactory` trait
- Migration: `$table->uuid('id')->primary()`, `foreignUuid()->constrained()`, explicit `onDelete()`
- Entity bridge: `as{Name}(): EntityType` if business rules exist

#### Step 2.3 — Enum (if state machine)

- `string`-backed, implements `LabelEnum`
- State machine additionally implements `StatusEnum`
- Cases: `UPPER_SNAKE`, values: lowercase
- Exhaustive `match()` in `validTransitions()`

#### Step 2.4 — Entity (if business rules exist)

- `final readonly class extends BaseEntity`
- `fromModel(Model $model): static` — only place where Eloquent field access happens
- `private` typed constructor properties — expose via getter methods
- Business rule methods return `bool` answers: `canX()`, `isY()`, `hasZ()`
- Zero I/O: no DB, HTTP, cache, events, facades

#### Step 2.5 — Action

**Command Action (mutation):**
- Extends `BaseCommandAction`
- Single `execute()` method
- `$this->transaction()` wrapping all DB writes
- `$this->log()` after successful mutation
- Dispatch event for significant state changes
- Business rules via Entity method + `RejectedException`

**Read Action (complex query):**
- Extends `BaseReadAction`
- Single `execute()` method
- Returns typed objects or collections
- No `transaction()` or `log()`

**Process Action (orchestration):**
- Extends `BaseProcessAction`
- Composes other Actions via constructor injection
- Handles partial failure
- Emits single module event

#### Step 2.6 — Policy (if authorization needed)

- Extends `BasePolicy`
- Auto-discovered from `app/*/Policies/` by convention
- `super_admin` bypasses all gates via `Gate::before()`
- Use `mentorProxyFor()` if proxy-aware authorization needed (see roadmap.md for Cross-Role Proxy)

#### Step 2.7 — Livewire Component

- CRUD tables extend `BaseRecordManager`
- Thin: delegates writes to Actions, complex queries to Read Actions
- Form state in Form Object (`app/{Module}/Livewire/Forms/{Name}Form.php`) for 5+ fields
- Action injection via method parameters, never `app()` or `new`
- Catch `RejectedException` specifically — show user-facing error
- Flash messages via `flash()->success()` / `flash()->error()`, never maryUI Toast

#### Step 2.8 — Blade View

- Uses maryUI components (`x-mary-table`, `x-mary-modal`, `x-mary-button`)
- Tailwind CSS v4 with `@import "tailwindcss"` + `@theme` directives
- All user-facing strings use `__()` translation helpers
- Guide component pattern for non-trivial workflows (`{page-name}-guide.blade.php`)
- Confirmation dialog pattern for destructive operations
- View mirrors app/ submodule structure: `resources/views/{module}/{submodule}/`

#### Step 2.9 — Routes

- Route file: `routes/web/{module}.php`
- Named routes: `{prefix}.{resource}.{action}`
- Imported in `routes/web.php` in dependency order
- Middleware: `auth`, `role:{roles}`, `throttle` as appropriate

#### Step 2.10 — Translations

- Both locales: `lang/en/{module}.php` and `lang/id/{module}.php`
- Every user-facing string uses `__()` — never hardcoded
- Keys follow `{module}.{page}.{element}` pattern
- Parameters use `:param` syntax

#### Step 2.11 — Tests

- Unit tests (no DB): Entity, Enum, DTO, Policy
- Feature tests (with DB): Action, Livewire, Console Command
- `LazilyRefreshDatabase` (not `RefreshDatabase`)
- `assertModelExists()` over `assertDatabaseHas()`
- `Event::fake()` positioned AFTER factory setup
- Coverage meets thresholds per layer

---

### Phase 3 — Cross-Pipeline Requirements

These checks apply to ALL pipelines, not just features.

#### 3.1 Convention Compliance

- [ ] `declare(strict_types=1)` in every PHP file (except migrations and config).
- [ ] No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code.
- [ ] All user-facing strings use `__()` helper.
- [ ] Constructor property promotion: `protected readonly` for injected dependencies.
- [ ] Explicit return types and parameter type hints on all methods.
- [ ] `===` over `==` (unless loose comparison intentional).
- [ ] Trailing commas on multiline arrays, function calls, constructor params.

#### 3.2 Security Compliance (§3 conventions.md)

- [ ] No `{!! $var !!}` with user-supplied content (XSS).
- [ ] No raw SQL with concatenated input (SQL injection).
- [ ] No `Model::create($request->all())` or `$this->all()` (mass assignment).
- [ ] All state-changing forms have CSRF protection.
- [ ] File uploads go through Spatie MediaLibrary, not `Storage::put()`.
- [ ] Every state-changing Livewire/controller method has authorization check.

#### 3.3 Performance Compliance (§5 conventions.md)

- [ ] No N+1 queries — all relationships in loops have `->with()` on the query.
- [ ] Large datasets use `chunk()` or `lazy()`, not `get()`.
- [ ] `exists()` over `count() > 0`.
- [ ] No `$collection->filter()` — filter at database level.

#### 3.4 Exception Handling (§5 architecture)

- [ ] Business rules throw `RejectedException`, not `RuntimeException`.
- [ ] Livewire catches `RejectedException` specifically before `Throwable`.
- [ ] Input validation uses `Validator::validate()` → `ValidationException`.

#### 3.5 Cache Compliance

- [ ] Every new cache key declared in `config/cache-keys.php`.
- [ ] Cache invalidation follows event-driven pattern.
- [ ] No inline string cache keys.

---

### Phase 4 — Documentation & Reporting

#### 4.1 Update Roadmap

After completing a task, update `docs/roadmap.md`:

1. Mark the task as `[COMPLETED]` in the implementation phases section.
2. Update the Integration Order table — set status to `DONE`.
3. Add a note about any deviations from the original plan (files changed, approach differences).

```markdown
<!-- Before -->
| 4 | 2 | LogbookPolicy | `LogbookPolicy.php` | 1 |

<!-- After -->
| 4 | 2 | LogbookPolicy | `LogbookPolicy.php` | 1 | ✅ Done |
```

#### 4.2 Update Known Issues

If the task resolves a known issue in [GitHub Issues](https://github.com/reasvyn/internara/issues):

```markdown
### ID-{N} — {Original Severity}: {Original Description} [RESOLVED in Roadmap Task N.M]
```

Add `[RESOLVED in Roadmap Task N.M]` to the title and verify the fix is complete.

#### 4.3 Completion Report

```
## Task N.M — {Title} — ✅ Complete

| Field | Detail |
|-------|--------|
| **Pipeline** | fix / security / perf / refactor / feat / test / docs / chore |
| **Files modified** | `{file}`, `{file}` |
| **Files created** | `{file}`, `{file}` |
| **Tests** | `{test}` — {passed count} tests |
| **Deviation from plan** | {none, or description of changes} |
| **Blockers** | {none, or description} |
```

---

### Phase 5 — Quality Gate

Every task, regardless of pipeline, must pass:

```bash
vendor/bin/pint --format agent          # code style
php artisan test --compact              # test suite
vendor/bin/phpstan analyse --no-progress # static analysis (if configured)
```

If any gate fails, fix before marking the task complete.

---

## References

| Document | Purpose |
|----------|---------|
| `docs/roadmap.md` | Task specification (input) |
| `docs/architecture.md` | 12-layer architecture, Action Triad |
| `docs/conventions.md` | All coding conventions |
| `docs/modules/{module}.md` | Module-specific context |
| `docs/modules/{module}-reference.md` | Module API reference |
| `docs/architecture/action-pattern.md` | Action Triad deep-dive |
| `docs/architecture/livewire-pattern.md` | Thin component rule |
| `docs/architecture/entity-pattern.md` | Entity-Model separation |
| `docs/architecture/enum-pattern.md` | LabelEnum, StatusEnum |
| `docs/architecture/exception-pattern.md` | Dual exception hierarchy |
| `docs/architecture/event-pattern.md` | BaseEvent, dispatch patterns |
| `docs/architecture/cache-pattern.md` | Centralized key registry |
| `docs/architecture/model-pattern.md` | BaseModel, UUID, Fillable |
| `docs/architecture/testing-pattern.md` | Testing conventions |
| `docs/infrastructure/testing.md` | Testing infrastructure |
| [GitHub Issues](https://github.com/reasvyn/internara/issues) | Known issues to resolve |
| `AGENTS.md` | Project invariants |
| `.agents/skills/roadmap-planning/SKILL.md` | Planning phase (upstream) |
| `.agents/skills/code-refactoring/SKILL.md` | Refactoring guidance (upstream for clean-up tasks) |
| `.agents/skills/livewire-development/SKILL.md` | Livewire-specific guidance (sub-skill) |
| `.agents/skills/tailwindcss-development/SKILL.md` | UI/styling guidance (sub-skill) |
| `.agents/skills/medialibrary-development/SKILL.md` | Media upload guidance (sub-skill) |
| `.agents/skills/pulse-development/SKILL.md` | Pulse monitoring guidance (sub-skill) |
| `.agents/skills/laravel-best-practices/SKILL.md` | Cross-cutting Laravel guidance |
| `.agents/skills/pest-testing/SKILL.md` | Testing guidance (downstream) |
| `.agents/skills/sync-docs/SKILL.md` | Documentation sync (downstream) |
