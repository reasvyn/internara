---
name: audit-protocol
description: SDLC Phase: ANALYSIS. Systematic multi-layer codebase audit enforcing conventions, architecture patterns, security, and industry best practices. Every finding is recorded in [GitHub Issues](https://github.com/reasvyn/internara/issues) with actionable fix recommendations. Focus: pattern violations, code smells, security holes, convention drift — NOT feature enhancements.
downstream: [roadmap-planning, code-refactoring]
---

# Audit Protocol Skill

## When to Activate

Apply this skill when performing a comprehensive codebase audit. Covers architecture compliance, convention enforcement, security, performance, testing adequacy, and documentation alignment. Every phase produces structured entries in [GitHub Issues](https://github.com/reasvyn/internara/issues).

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | Existing codebase |
| **This skill** | **ANALYSIS** — produces [GitHub Issues](https://github.com/reasvyn/internara/issues) |
| **Downstream (output)** | `roadmap-planning` — findings feed bug/refactor pipeline |
| | `code-refactoring` — findings guide refactoring targets |
| | `security-audit` — deep-dive on security findings (if needed) |
| **Phase** | [Planning] → Analysis → [Design] → [Implementation] → [Testing] → [Maintenance] |

## Audit Layers (Execute in Order)

Each layer depends on the previous. Do not skip layers.

| Phase | Layer | Scope |
|-------|-------|-------|
| 0 | Preparation | Load context, run baseline, initialize findings |
| 1 | Action Triad (`app/**/Actions/`) | Command/Read/Process patterns, base classes, transaction, log, events |
| 2 | Livewire Components (`app/**/Livewire/`) | Thin component rule, injection, exception handling |
| 3 | Entities & Models (`app/**/Entities/`, `app/**/Models/`) | Entity purity, model responsibilities, bridge pattern |
| 4 | Enums (`app/**/Enums/`, `app/Core/Enums/`) | LabelEnum, StatusEnum, naming, defaults |
| 5 | Exceptions (`app/**/Exceptions/`, `app/Core/Exceptions/`) | Hierarchy, RejectedException usage |
| 6 | Security (`app/`, `routes/`, `resources/views/`) | XSS, SQL injection, mass assignment, CSRF, CSP, PII |
| 7 | Performance (`app/`, `resources/views/`) | N+1 queries, eager loading, query optimization, caching |
| 8 | Configuration (`config/`) | Cache keys, module registration, event mapping, env vars |
| 9 | Tests (`tests/`) | Coverage, conventions, mocking, completeness |
| 10 | Cross-Cutting | DI etiquette, commit conventions, code review, docs alignment |
| 11 | Report Generation | Consolidation, classification, final verification |

---

## Phase 0 — Preparation

### 0.1 Load Context

Read these documents before starting (concurrent read):
- `docs/architecture.md` — 12-layer architecture, Action Triad, patterns
- `docs/conventions.md` — all coding conventions (especially §3 Security, §5 Performance, §7 HTTP, §8 DI, §10 Testing)
- `docs/modules/module-index.md` — module boundaries
- `docs/doc-index.md` — full documentation catalog
- `AGENTS.md` — project invariants, quick rules
- [GitHub Issues](https://github.com/reasvyn/internara/issues) — existing known issues (to avoid duplicates)

### 0.2 Establish Baseline

```bash
php artisan test --compact              # verify current test suite passes
vendor/bin/pint --format agent          # check code style baseline
vendor/bin/phpstan analyse --no-progress # static analysis baseline
composer run test:coverage              # coverage baseline (if available)
```

### 0.3 Initialize Findings

Create or append to [GitHub Issues](https://github.com/reasvyn/internara/issues). Every finding uses this template:

```markdown
### ID-{N} — {Severity}: {Short Description}

| Attribute | Detail |
|-----------|--------|
| **Severity** | CRITICAL / HIGH / MEDIUM / LOW |
| **File** | `{file}:{line}` |
| **Pattern violated** | `{doc-ref}` |
| **What's wrong** | Explain in 1-2 sentences |
| **Fix recommendation** | Actionable steps to resolve |
| **Impact** | Runtime / Maintainability / Security / Performance |
```

Severity definitions:
- **CRITICAL** — Production crash, data loss, security breach
- **HIGH** — Violates enforced convention, will cause bug under specific conditions
- **MEDIUM** — Violates recommended pattern, reduces maintainability
- **LOW** — Cosmetic, naming, minor optimization opportunity

---

## Phase 1 — Action Triad Audit (`app/**/Actions/`)

### 1.1 File Structure

- Actions live at `app/{Module}/{SubModule}/Actions/{ClassName}.php`
- Cross-submodule actions at `app/{Module}/Actions/{ClassName}.php`
- No Action files outside these locations.
- **Check:** `find app -path '*/Actions/*.php' | sort` — verify every Action matches convention.

**Record findings** under `TRIAD-STRUCT-*` IDs.

### 1.2 Base Class Compliance

| You need | Must extend | Violation if extends |
|----------|-------------|---------------------|
| Mutation | `BaseCommandAction` | `BaseReadAction`, `BaseAction`, plain class |
| Query | `BaseReadAction` | `BaseCommandAction`, `BaseProcessAction` |
| Orchestration (multi-step) | `BaseProcessAction` | `BaseCommandAction`, `BaseAction` |

**Check:** For every Action file, verify the `extends` clause matches the operation type.
- Grep for `extends BaseAction` — flag as HIGH (should be Command/Read/Process).
- Grep for Actions that compose other Actions but extend `BaseCommandAction` instead of `BaseProcessAction` (e.g., `FinalizeSetupAction`).

**Record findings** under `TRIAD-BASE-*` IDs.

### 1.3 Command Action Contract

Every Command Action MUST:

1. **`$this->transaction()`** wrapping all DB writes. Grep for `Model::create(`, `Model::update(`, `Model::delete(`, `DB::`, `::updateOrCreate(` inside Actions — if outside `$this->transaction()`, flag as HIGH.
2. **`$this->log()`** after successful mutation. Every Command Action should call `$this->log()`.
3. **Single `execute()`** — exactly one public method. Grep for additional `public function` in Action files.
4. **Dispatch event** for significant state changes (status transitions, creates, deletes). Flag Actions that change status without dispatching an event.
5. **`RejectedException`** for business rule violations, never `RuntimeException`.
6. **No inline `canX()` checks** — delegate to Entity methods.

**Check:**
```bash
# Find Command Actions missing transaction wrapping
rg -l "BaseCommandAction" app/ | xargs rg -l "Model::create\|Model::update\|Model::delete\|DB::" | xargs rg -c '\$this->transaction\(\)' | grep ':0$'

# Find Command Actions missing log call
rg -l "BaseCommandAction" app/ | xargs rg -c '\$this->log('
```

**Record findings** under `TRIAD-CMD-*` IDs.

### 1.4 Read Action Contract

Every Read Action MUST:

1. **Extend `BaseReadAction`** or be a plain class.
2. **NOT** call `transaction()` or `log()` — grep for these in Read Actions.
3. **NOT** mutate database state — grep for any `create/update/delete/save` in Read Actions.
4. Return typed objects or collections, not raw arrays.

**Record findings** under `TRIAD-READ-*` IDs.

### 1.5 Process Action Contract

Every Process Action MUST:

1. **Extend `BaseProcessAction`**.
2. **Compose** other Actions via constructor injection.
3. **Handle partial failure** — document the approach in docblock.
4. **Emit a single module event** after completion.

**Record findings** under `TRIAD-PROC-*` IDs.

### 1.6 ActionResponse Usage

- Return `ActionResponse` when caller needs structured feedback.
- Use factory methods: `ActionResponse::ok()`, `created()`, `updated()`, `deleted()`, `error()`.
- **Check:** Actions returning raw `array` or `mixed` where callers need typed feedback.

**Record findings** under `TRIAD-RESP-*` IDs.

---

## Phase 2 — Livewire Component Audit (`app/**/Livewire/`)

### 2.1 Thin Component Rule

**Allowed:** UI state, `$this->validate()`, delegation to Actions, read-only queries in `render()`, flash messages.
**NOT Allowed:**

1. Inline DB mutations (`Model::create()`, `Model::update()`, `Model::delete()`, `DB::transaction()`).
2. Inline business rules (`if ($model->status === 'x')`, date comparisons).
3. Side effects (`Log::info()`, `event(new ...)`, `Notification::send()`).
4. Static helper methods.
5. maryUI Toast methods (`$this->success()`, `$this->error()`).
6. `app()->make()` or `new Action()` — must use method injection.

**Check:**
```bash
# Find Livewire components with inline DB calls
rg -l "extends Component" app/ --type php | xargs rg -l "Model::create\|Model::update\|Model::delete\|DB::\|::create("
```

**Record findings** under `LW-*` IDs.

### 2.2 Action Injection Pattern

- Actions are injected as **method parameters**, never via `app()` or `new`.
- **Catch `RejectedException`** (not `RuntimeException`) from Action calls.
- Every Action call in a Livewire component should have `try/catch` with user-facing flash message.

**Check:**
```bash
# Find catch RuntimeException (should be RejectedException)
rg "catch.*RuntimeException" app/ --type php
```

**Record findings** under `LW-INJECT-*` IDs.

### 2.3 Form Objects

- Forms with 5+ fields or conditional validation must use a Form Object (`app/{Module}/Livewire/Forms/{Name}Form.php`).
- Form Objects extend `Livewire\Form`, never `BaseAction`.
- Form Objects must NOT call Actions directly — they only prepare data.

**Record findings** under `LW-FORM-*` IDs.

### 2.4 Confirmation Dialog

- Destructive operations must use two-step `askAction()` → `confirmAction()` pattern.
- No bare `wire:confirm` for destructive actions.
- Use shared `<x-core::ui.confirm />` component.

**Record findings** under `LW-CONFIRM-*` IDs.

---

## Phase 3 — Entities & Models Audit

### 3.1 Entity Purity

Every Entity MUST:
- Be `final readonly class` extending `BaseEntity`.
- Have all properties `private` typed constructor properties.
- Implement `fromModel(Model $model): static`.
- **NOT** touch database, HTTP, files, cache, facades, service container.
- **NOT** dispatch events or notifications.
- The only allowed framework import is `Illuminate\Database\Eloquent\Model` (in `fromModel()` parameter).

**Check:**
```bash
# Find non-final or non-readonly entities
rg "class \w+ extends BaseEntity" app/ --type php | grep -v "final readonly"
```

**Record findings** under `ENTITY-*` IDs.

### 3.2 Model Responsibilities

Models MUST:
- Use `#[Fillable]` attribute (not `$fillable` property).
- **NOT** contain business rule methods (`canX()`, `isX()`, `hasX()` that are not simple passthroughs).
- Expose entities via named `as{EntityName}()` bridge methods.
- UUID primary keys via `HasUuids` (BaseModel provides this).
- Status casts: use enum FQCN (`'status' => StatusEnum::class`).

**Check:**
```bash
# Find business logic on models (should be in Entities)
rg "function can\|function is\|function has" app/ --type php --include '**/Models/*.php'
```

**Record findings** under `MODEL-*` IDs.

### 3.3 Entity Bridge Pattern

- Every Model with business-rule conditionals in its module's Actions should have `as{Entity}()` bridge.
- Bridge method naming: `as{Role}(): EntityType` — never generic `entity()`.
- Models may expose multiple entities for different business roles.

**Record findings** under `BRIDGE-*` IDs.

---

## Phase 4 — Enum Audit (`app/**/Enums/`, `app/Core/Enums/`)

### 4.1 LabelEnum Compliance

Every enum MUST implement `LabelEnum`:
```bash
rg "enum \w+: string" app/ --type php | grep -v "implements LabelEnum" | grep -v "implements StatusEnum"
```

### 4.2 StatusEnum Compliance

State machine enums MUST implement `StatusEnum` with:
- `canTransitionTo(self $target): bool`
- `isTerminal(): bool`
- `validTransitions(): array` — exhaustive `match()`, every case listed.

**Check:**
- `match()` must be exhaustive — every case appears.
- Terminal states return `[]`.

### 4.3 Case Naming

- Case names: `UPPER_SNAKE`.
- Backing values: lowercase `snake_case`.
- Model defaults use `->value` (`EnumCase::DRAFT->value`), never hardcoded strings.

**Check:**
```bash
rg "protected \$attributes" app/ --type php -A 5 | grep "=> '"
```

**Record findings** under `ENUM-*` IDs.

---

## Phase 5 — Exception Audit

### 5.1 Exception Hierarchy

Two independent trees:
- `AppException (abstract)` — application/infrastructure/HTTP failures
- `ModuleException (abstract)` → `RejectedException` — business rule violations

Every concrete exception must:
- Extend the correct abstract (either `AppException` or `ModuleException`).
- `RejectedException` is ONLY for business rules — NOT for validation or infrastructure errors.

### 5.2 Action Error Handling

- Business rule violations → `RejectedException` (never `RuntimeException`).
- Input validation → `Validator::validate()` → `ValidationException`.
- Duplicate/conflict → `ConflictException`.
- Resource missing → `NotFoundException`.
- Infrastructure failure → `HandlesActionErrors` logs + rethrows as `RuntimeException`.

**Check:**
```bash
# Find throw RuntimeException in Actions (should be RejectedException)
rg "throw new RuntimeException" app/ --type php
```

### 5.3 Livewire Catch Blocks

- `try/catch` in Livewire must catch `RejectedException` first, then `Throwable`.
- Business errors show `$e->getMessage()`, infrastructure errors show generic message.

**Record findings** under `EX-*` IDs.

---

## Phase 6 — Security Audit

### 6.1 XSS Prevention (§3.1 conventions.md)

- All user-supplied content in Blade uses `{{ $var }}` (escaped).
- `{!! $var !!}` is ONLY permitted for trusted, sanitized content — every occurrence must have an inline comment justifying safety.
- Alpine.js `x-html` follows the same rule.
- **Check:**
  ```bash
  rg '\{!!.*\$' resources/ --type blade
  ```

### 6.2 SQL Injection Prevention (§3.2 conventions.md)

- Raw SQL (`DB::raw()`, `whereRaw()`, `orderByRaw()`, etc.) is FORBIDDEN unless:
  - Uses parameterized binding EXCLUSIVELY (`->whereRaw('col = ?', [$value])`).
  - Has explicit exception documented in method docblock.
- No concatenated user input in queries.
- **Check:**
  ```bash
  rg "whereRaw\|orderByRaw\|havingRaw\|selectRaw\|DB::raw" app/ --type php
  ```

### 6.3 Mass Assignment (§3.3 conventions.md)

- Every model uses `#[Fillable]` attribute.
- **No** `Model::create($request->all())` or `Model::create($this->all())`.
- Always use explicit key selection: `$request->only([...])`, `$this->form->toArray()`.
- **Check:**
  ```bash
  rg "->all\(\)" app/ --type php | grep "create\|update\|fill"
  ```

### 6.4 CSRF & CSP (§3.4–3.5 conventions.md)

- All state-changing HTML forms include `@csrf` (or use Livewire which handles CSRF).
- CSP exemptions in `bootstrap/app.php` must have code comments.
- **Check:** `grep -r 'validateCsrfTokens' bootstrap/app.php`

### 6.5 File Upload Security (§3.6 conventions.md)

- All uploads go through Spatie MediaLibrary, never `Storage::put()`.
- Each media collection defines MIME and size validation.

### 6.6 PII Masking

- Every SmartLogger call outside Action context calls `withPiiMasking()`.
- **Check:** Search for SmartLogger calls missing `withPiiMasking()`.

### 6.7 Authorization

- Every Livewire mutation method has `$this->authorize()` or `Gate::authorize()`.
- **Check:**
  ```bash
  rg "public function (create|update|delete|save|restore|toggle|approve|reject|lock|unlock)" app/ --type php -A 2 | grep -v "authorize"
  ```

**Record findings** under `SEC-*` IDs.

---

## Phase 7 — Performance Audit

### 7.1 N+1 Prevention (§5.1 conventions.md)

- No relationship access inside Blade loops or Livewire `@foreach` without eager loading (`->with()`).
- Livewire `render()` must not trigger N+1 — use `->with()` on the query, never `->load()` in loops.
- **Check:** Look for `\n.*->\w+->\w+\n.*endfor\|endforeach` patterns in Blade views (relationship calls in loops).

### 7.2 Query Optimization (§5.2 conventions.md)

- Large datasets (≥1000 rows) use `chunk()` or `lazy()` instead of `get()`.
- `exists()` over `count() > 0` for existence checks.
- `pluck()` over `get()->pluck()` to avoid hydrating full models.
- No `$collection->filter()` on large collections — move filter to database.
- **Check:** `rg "chunk\|lazy" app/ --type php` — verify large data processing uses these.

### 7.3 Eager Loading (§5.3 conventions.md)

- Default: `->with()` for all relationships used in current view/response.
- Constrained eager loading: `->with(['relation' => fn ($q) => $q->where(...)])`.
- Avoid `->load()` in loops — move to `->with()` on initial query.

### 7.4 Caching Conventions (§5.5 conventions.md)

- Every cache key declared in `config/cache-keys.php` — never inline strings.
- Cache invalidation follows event-driven pattern.
- **Check:**
  ```bash
  # Find inline cache keys not in registry
  rg "Cache::(remember|put|forget|get)\('" app/ --type php
  ```

**Record findings** under `PERF-*` IDs.

---

## Phase 8 — Configuration Audit (`config/`)

### 8.1 Module Registration

- `config/module.php` `'list'` includes every business module under `app/`.
- No orphan modules (listed but no directory).
- Module dependency order matches actual import graph.

### 8.2 Cache Key Registry

- Every cache key in `config/cache-keys.php` follows `{module}.{purpose}[.{qualifier}]`.
- Every key used in `app/` is in the registry.
- No unused keys in the registry.
- **Check:**
  ```bash
  # Compare used vs declared keys
  rg -o "cache-keys\.\w+" app/ --type php | sort -u
  ```

### 8.3 Event Mapping

- `config/event.php` maps every event to its listener(s).
- Both event and listener classes exist and are autoloadable.
- I/O-bound listeners implement `ShouldQueue`.

### 8.4 Environment Variables

- Every `env('KEY', default)` in `config/*.php` has a corresponding entry in `.env.example`.
- No hardcoded secrets in config files.
- **Check:** `rg "env\(" config/ --type php | sort -u`

### 8.5 Service Config

| File | Check |
|------|-------|
| `config/setup.php` | admin name/username defaults, security limits, token expiry |
| `config/permission.php` | spatie/laravel-permission configuration |
| `config/auth.php` | guard configuration, password policies |
| `config/session.php` | session driver, secure cookies in production |
| `config/media-library.php` | file size limits, image conversions, queue config |
| `config/pulse.php` | recorders, thresholds, ingest settings |

**Record findings** under `CONFIG-*` IDs.

---

## Phase 9 — Test Audit (`tests/`)

### 9.1 Coverage Completeness

- Every Action file has a matching test file under `tests/Feature/`.
- Every Livewire component has a matching test file.
- Every Entity has a matching unit test.
- Every Console Command has a matching feature test.
- **Check:**
  ```bash
  # Find Actions without tests
  for f in $(find app -name '*Action.php'); do basename="${f##*/}"; testfile="tests/Feature/$(echo $f | sed 's|app/||; s|\.php|Test.php|')"; [ ! -f "$testfile" ] && echo "MISSING: $testfile"; done
  ```

### 9.2 Convention Compliance

- Test file naming: `{Name}Test.php`.
- Test structure mirrors source: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`.
- Feature tests use `LazilyRefreshDatabase` (preferred) or `RefreshDatabase`.
- Entity tests do NOT use `LazilyRefreshDatabase`/`RefreshDatabase` (no DB needed).
- `assertModelExists()` preferred over `assertDatabaseHas()`.
- `Event::fake()` positioned AFTER factory setup, not before.
- No `dd()`/`dump()` in test files.

### 9.3 Mocking Strategy (§10.1 conventions.md)

| Scenario | Must use | Must NOT use |
|----------|----------|--------------|
| External HTTP | `Http::fake()` | Real HTTP calls |
| SmartLogger (unit) | `Event::fake()` / partial mock | Real logger |
| Eloquent | Factories + real DB | `Mockery::mock(Model::class)` |
| File system | `Storage::fake()` | Real file operations |
| Queue | `Queue::fake()` | Real queue worker |
| Notifications | `Notification::fake()` | Real mail sending |
| Events | `Event::fake([SpecificEvent::class])` | `Mockery::spy()` |

**Check:** Flag use of `Mockery::spy()`, `Mockery::mock(Model::class)`.

### 9.4 Coverage Thresholds (§10.2 conventions.md)

| Layer | Minimum Coverage |
|-------|-----------------|
| Entities | 100% |
| Enums | 100% |
| DTOs / Data | 100% |
| Command Actions | ≥ 90% |
| Read Actions | ≥ 80% |
| Process Actions | ≥ 90% |
| Livewire components | ≥ 80% |
| Policies | 100% |
| Console Commands | ≥ 80% |
| **Overall** | **≥ 85%** |

**Check:** `php artisan test --coverage` or `composer run test:coverage`.

### 9.5 Test Quality

- No flaky tests (tests depending on state from other tests).
- No slow tests (queries in loops, missing `LazilyRefreshDatabase`).
- `vendor/bin/phpstan analyse --no-progress` — record level > 0 findings.

**Record findings** under `TEST-*` IDs.

---

## Phase 10 — Cross-Cutting Audit

### 10.1 Dependency Injection Etiquette (§8 conventions.md)

- Constructor injection for mandatory, long-lived dependencies.
- Method injection for contextual dependencies (Livewire Actions).
- **FORBIDDEN:** `app()->make()`, `new ClassName()` in controllers/Livewire, `resolve()`, static facades for business logic.
- Exception: `app()` permitted in service providers and factory methods.

**Check:**
```bash
rg "app\(\)->make\|resolve\(" app/ --type php -g '!Providers/*' -g '!*Factory.php'
```

### 10.2 Technical Debt Annotations (§10.4 conventions.md)

- Format: `TODO(author, YYYY-MM-DD): message`
- `FIXME(author, YYYY-MM-DD): message` for known bugs
- `HACK` must explain why
- `XXX` must explain the risk

**Check:** Find occurrences without date/author.

### 10.3 Commit & Branch Naming (§10.3 conventions.md)

- Branches: `feat/`, `fix/`, `hotfix/`, `refactor/`, `docs/`, `chore/`.
- Commits: `type(scope): description`.
- **Check:** Git log for recent commit messages.

### 10.4 Code Review Checklist (§9 conventions.md §Code Review Checklist)

Reviewers verify:
- Pattern compliance (Action triad, base classes).
- Security (XSS, SQL injection, mass assignment).
- N+1 audit (eager loading present).
- Exception handling (RejectedException, Livewire catch blocks).
- Cache invalidation (event-driven for every mutation).
- Test coverage (new Actions have tests).
- Documentation updated.

**Record findings** under `CROSS-*` IDs.

---

## Phase 11 — Report Generation

### 11.1 Consolidate Findings

1. Remove duplicate findings (same issue found by multiple phases).
2. Sort by severity (CRITICAL → HIGH → MEDIUM → LOW).
3. Within same severity, sort by module alphabetically.
4. Update the master change summary at the top of [GitHub Issues](https://github.com/reasvyn/internara/issues).

### 11.2 Summary Statistics

```markdown
## Audit Summary — {date}

| Severity | Count |
|----------|-------|
| CRITICAL | N |
| HIGH     | N |
| MEDIUM   | N |
| LOW      | N |
| **Total** | **N** |

### By Module
{Module breakdown with counts}

### By Pattern
| Pattern | Violations |
|---------|-----------|
| Action Triad | N |
| Livewire | N |
| Entity/Model | N |
| Enum | N |
| Exception | N |
| Security | N |
| Performance | N |
| Config | N |
| Tests | N |
| Cross-Cutting | N |
```

### 11.3 Final Verification

```bash
php artisan test --compact              # verify nothing is broken
vendor/bin/pint --format agent          # verify code style
vendor/bin/phpstan analyse --no-progress # verify static analysis
git diff --stat                          # review all changes
```

---

## References

| Document | Purpose |
|----------|---------|
| `docs/architecture.md` | 12-layer architecture, Action Triad, cross-module communication |
| `docs/conventions.md` | All coding conventions (§2–§12) |
| `docs/architecture/action-pattern.md` | Action Triad deep-dive |
| `docs/architecture/livewire-pattern.md` | Thin component rule, Form Objects, BaseRecordManager |
| `docs/architecture/entity-pattern.md` | Entity-Model separation, bridge pattern |
| `docs/architecture/enum-pattern.md` | LabelEnum, StatusEnum, state machines |
| `docs/architecture/exception-pattern.md` | Dual exception hierarchy, RejectedException |
| `docs/architecture/event-pattern.md` | BaseEvent, dispatch patterns, ShouldQueue |
| `docs/architecture/cache-pattern.md` | Centralized key registry, event-driven invalidation |
| `docs/architecture/model-pattern.md` | BaseModel, UUID, Fillable, scopes |
| `docs/architecture/testing-pattern.md` | Testing conventions, scope isolation |
| `docs/architecture/data-pattern.md` | DTO patterns, BaseData |
| `docs/infrastructure/testing.md` | Testing infrastructure, coverage, performance |
| `docs/modules/module-index.md` | Module catalog |
| `docs/doc-index.md` | Documentation catalog |
| [GitHub Issues](https://github.com/reasvyn/internara/issues) | Findings target |
| `AGENTS.md` | Project invariants, quick-reference rules |
