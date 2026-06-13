---
name: audit-protocol
description: Systematic multi-layer audit of the entire codebase against conventions, architecture patterns, industry standards, security best practices, and project requirements. Every finding must be recorded in docs/known-issues.md with reproduction steps and fix recommendations. Focus on bug fixes, code smells, convention violations, and security holes — NOT feature enhancements.
---

# Audit Protocol Skill

## When to Activate

Apply this skill when performing a comprehensive codebase audit. This covers code quality, architecture compliance, security, performance, convention enforcement, testing adequacy, and documentation alignment. Every phase produces structured entries in `docs/known-issues.md`.

## Audit Layers (Execute in Order)

Each layer depends on the previous. Do not skip layers.

The audit covers these layers in sequence:

| Phase | Layer | Scope |
|-------|-------|-------|
| 0 | Preparation | Load context, run baseline, initialize findings |
| 1 | Application (`app/`) | Models, Actions, Entities, Enums, Policies, Livewire, Exceptions |
| 2 | Configuration (`config/`) | Module registration, cache keys, environment vars, permissions |
| 3 | Jobs & Queue (`app/Jobs/`) | Serialization, error handling, queue configuration |
| 4 | Views (`resources/views/`) | Blade templates, localization, accessibility, XSS |
| 5 | Routes (`routes/`) | Route files, middleware, naming, security |
| 6 | Tests (`tests/`) | Coverage, conventions, quality |
| 7 | Documentation Alignment | Cross-reference, known issues |
| 8 | Industry Standards | OWASP, PSR, SOLID, PHP 8.4 |
| 9 | Report Generation | Consolidation, classification, final verification |

---

## Phase 0 — Preparation

### 0.1 Load Context

Read these documents before starting (concurrent read):
- `docs/architecture.md` — 12-layer architecture, Action Triad, patterns
- `docs/conventions.md` — all coding conventions
- `docs/modules/module-index.md` — module boundaries
- `docs/doc-index.md` — full documentation catalog
- `AGENTS.md` — project invariants, quick rules
- `docs/infrastructure/testing.md` — testing conventions
- `docs/known-issues.md` — existing known issues (to avoid duplicates)

### 0.2 Establish Baseline

```
php artisan test --compact              # verify current test suite passes
vendor/bin/pint --format agent          # check code style baseline
composer run test:coverage              # coverage baseline
```

### 0.3 Initialize Findings

Create or append to `docs/known-issues.md`. Every finding entry follows this template:

```markdown
### ID-{N} — {Severity}: {Short Description}

| Attribute | Detail |
|-----------|--------|
| **Severity** | HIGH / MEDIUM / LOW |
| **File** | `{file}:{line}` |
| **Pattern violated** | `docs/conventions.md #{section}` or `docs/architecture.md #{section}` |
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

## Phase 1 — Application Layer Audit (`app/`)

### 1.1 File Structure Audit

**Scope:** Every directory and file under `app/`.

**Checks:**
1. Does every module match `docs/modules/module-index.md`? Are there undeclared modules?
2. Does every submodule directory have a corresponding `resources/views/{module}/{submodule}/` directory? (Unless it's a shared cross-module component.)
3. Are there orphan view directories (in `resources/views/`) with no matching `app/` submodule?
4. Does the directory structure follow the convention:
   ```
   app/{Module}/{SubModule}/{Component}/{ClassName}.php
   ```
   Check for redundant namespace segments (e.g., `app/User/User/Models/User.php`).

**Record findings** under `STRUCTURE-*` IDs.

### 1.2 Convention Compliance Audit

#### 1.2.1 `declare(strict_types=1)`

Grep all PHP files under `app/` (exclude `config/` and `database/migrations/`). List files missing the declaration.

**Requirement:** `docs/conventions.md` §2 — every PHP file except migrations and config.

#### 1.2.2 Base Class Mandate

Every file must use the correct base class:

| You need | You must use |
|----------|-------------|
| Database table (not User) | `extends BaseModel` |
| Auth model | `extends BaseAuthenticatable` |
| Business mutation | `extends BaseCommandAction` (NOT bare `BaseAction`) |
| Business query | `extends BaseReadAction` (NOT bare `BaseAction`) |
| Multi-step orchestration | `extends BaseProcessAction` (NOT bare `BaseAction`) |
| Business rules (immutable) | `extends BaseEntity` (final readonly) |
| Authorization gate | `extends BasePolicy` |
| CRUD table UI | `extends BaseRecordManager` |
| DTO / value object | `extends BaseData` (final readonly) |
| Event | `extends BaseEvent` (final) |
| Enum | `implements LabelEnum` |
| State machine enum | `implements StatusEnum` (+ `LabelEnum`) |
| Exception | `extends AppException` or `extends ModuleException` |

**Check:** List every file that violates its mandated base class. Pay special attention to:
- Actions still extending `BaseAction` instead of `BaseCommandAction`/`BaseReadAction`/`BaseProcessAction`
- Enums missing `LabelEnum`
- Exceptions not in the dual hierarchy

**Record findings** under `BASE-*` IDs.

#### 1.2.3 Naming Convention Audit

| Element | Rule | Examples |
|---------|------|----------|
| Command Action | `{Verb}{Entity}Action` | `CreateUserAction` |
| Read Action | `Read{Entity}Action` | `ReadActivityLogAction` |
| Process Action | `Process{Entity}Action` | `ProcessRegistrationAction` |
| Entity | `{Name}` (business role) | `Apprentice`, `InternshipPeriod` |
| DTO | `{Verb}{Entity}Data` or `{Entity}Data` | `LoginData`, `SetupTokenData` |
| Event | `{Entity}{PastTenseAction}` | `InternshipCreated` |
| Console command | `{module}:{action}` | `system:health` |
| Route name | `{prefix}.{resource}.{action}` | `admin.users.index` |
| Cache key | `{module}.{purpose}[.{qualifier}]` | `setup.is_installed` |
| Boolean method | `is`/`has`/`can`/`requires`/`allows` prefix | `isActive()`, `canTransitionTo()` |

**Check:** List naming violations. Flag old `Get*` / `Check*` prefix still used instead of `Read*`.

**Record findings** under `NAMING-*` IDs.

### 1.3 Action Triad Audit

#### 1.3.1 Command Actions

**Requirement:** `docs/architecture.md` §Action Triad.

1. Check that Command Actions extend `BaseCommandAction` (or at minimum `BaseAction` for legacy).
2. **MUST** call `$this->transaction()` wrapping all DB writes.
3. **MUST** call `$this->log()` after successful mutation.
4. **SHOULD** dispatch an event (`event(new ...)`) for significant state changes.
5. **MUST** have exactly one public method: `execute()`.
6. **MUST NOT** contain inline business rules — delegate to Entity methods or check via `RejectedException`.
7. **MUST NOT** use `dd()`, `dump()`, `ray()`, `var_dump()`, `die()`.

**Check each Action file.** List violations per Action.

#### 1.3.2 Read Actions

1. Check that Read Actions extend `BaseReadAction` (or are plain classes).
2. **MUST NOT** call `transaction()` or `log()`.
3. **MUST NOT** mutate any database state.
4. **SHOULD** return typed objects or collections.

#### 1.3.3 Process Actions

1. **MUST** extend `BaseProcessAction`.
2. **MUST** compose other Actions via constructor injection.
3. **MUST** handle partial failure.
4. **SHOULD** emit a single module event after completion.

### 1.4 Exception Handling Audit

1. Flag every `throw new RuntimeException(...)` in Actions that should be `throw new RejectedException(...)`.
2. **Rule:** Business rule violations → `RejectedException`. Never bare `RuntimeException`.
3. **Rule:** Input validation → `ValidationFailedException`.
4. **Rule:** Duplicate/conflict → `ConflictException`.
5. **Rule:** Resource missing → `NotFoundException`.
6. Check that Entities never throw exceptions that require framework imports — they should only throw `RejectedException` or nothing.
7. Check that `try/catch` blocks in Livewire catch `RejectedException` (not `RuntimeException`) from Action calls.

**Record findings** under `EXCEPTION-*` IDs.

### 1.5 Event & Listener Audit

1. Every Command Action that changes significant state SHOULD dispatch an event.
2. Every event MUST extend `BaseEvent`.
3. Every listener MUST be registered in `config/event.php`.
4. Listeners performing I/O (email, cache clear, API calls) MUST implement `ShouldQueue`.
5. **Check:** Are there actions that update status/state but don't dispatch events?

**Record findings** under `EVENT-*` IDs.

### 1.6 Cache Audit

1. Every cache key MUST be declared in `config/cache-keys.php`.
2. Every cache read MUST reference via `config('cache-keys.{key}')` — never raw strings.
3. **Check:** Grep for `Cache::remember('` or `Cache::put('` or `Cache::forget('` with string literals — flag all.
4. **Check:** Grep for `Cache::` calls in Actions — should invalidate on mutations.

**Record findings** under `CACHE-*` IDs.

### 1.7 Model Audit

1. **Business logic check:** Every `canX()`, `isX()`, `hasX()` method on a Model that is not a simple passthrough should be in an Entity. List violations.
2. **Entity bridge check:** Every Model with business-rule conditionals in its module's Actions should have an `as{Entity}()` bridge method. Check if entities exist but aren't used.
3. **Fillable check:** All Models MUST use `#[Fillable]` attribute, NOT `$fillable` property.
4. **Relationship naming:** `BelongsTo`/`HasOne` = singular, `HasMany`/`BelongsToMany` = plural.
5. **Cast check:** Status columns use enum FQCN casts (`'status' => StatusEnum::class`), not string.

**Record findings** under `MODEL-*` IDs.

### 1.8 Enum Audit

1. Every enum MUST implement `LabelEnum` — flag violations.
2. State machine enums MUST implement `StatusEnum` with `canTransitionTo()`, `isTerminal()`, `validTransitions()`.
3. Case naming: `UPPER_SNAKE`. Backing values: lowercase.
4. Model defaults use `->value` (e.g., `InternshipStatus::DRAFT->value`), never hardcoded strings.
5. `match()` is exhaustive — every case must appear.

**Record findings** under `ENUM-*` IDs.

### 1.9 Dependency & Cross-Module Audit

1. Core (layers 3-4) must NOT import any business module.
2. Check for broken cross-module references (importing classes that don't exist).
3. Check for circular dependencies between modules.
4. Check that Service classes never do Action work (mutations, transactions, complex queries).

**Record findings** under `DEP-*` IDs.

### 1.10 Security Audit

1. **Mass assignment:** Check for `Model::create($request->all())` or `Model::create($this->all())` — flag violations.
2. **SQL injection:** Search for `DB::raw()`, `whereRaw()`, `orderByRaw()` with concatenated user input.
3. **XSS:** Check Blade templates for `{!! $var !!}` with user-supplied content — flag as HIGH.
4. **Authorization:** Every Livewire mutation method must have `$this->authorize()` or `Gate::authorize()`. Flag missing checks.
5. **PII masking:** Every SmartLogger call outside Action context should call `withPiiMasking()`. Flag omissions.
6. **Rate limiting:** Auth endpoints should have rate limiting.
7. **CSV injection:** CSV exports must escape formula characters (=, +, -, @).

**Record findings** under `SEC-*` IDs.

### 1.11 Performance Audit

1. **N+1 queries:** Check for relationship access inside loops in Livewire components and Blade views.
2. **Heavy queries in Livewire:** Check `render()` methods for expensive aggregations — should use Read Actions + cache.
3. **Missing eager loading:** Check for `->load()` after collection retrieval instead of `->with()` on the query.
4. **Missing cache:** Check dashboard/aggregation queries for caching.
5. **Missing `->fresh()`:** Check if models are used after `save()`/`update()` without `->fresh()`.

**Record findings** under `PERF-*` IDs.

---

## Phase 2 — Configuration Layer Audit (`config/`)

### 2.1 Module Registration Audit

1. Check `config/module.php` `'list'` array includes every business module directory under `app/`.
2. Flag missing modules: every `app/{Module}/` directory with Actions/ or Models/ must be registered.
3. Flag orphan modules: modules listed in config but with no corresponding `app/{Module}/` directory.
4. Verify module dependency order in `'list'` matches actual import dependencies.

### 2.2 Cache Key Registry Audit

1. Open `config/cache-keys.php` — verify every key follows `{module}.{purpose}[.{qualifier}]` naming.
2. **Critical check:** Search all `app/` PHP files for raw `Cache::remember('`, `Cache::put('`, `Cache::forget('`, `Cache::get('` calls with string literal keys that are NOT in the registry. Flag each as a violation — every cache key MUST be declared in `config/cache-keys.php`.
3. Check that every key declared in `config/cache-keys.php` is actually used somewhere in `app/`. Flag unused keys.

### 2.3 Environment Variable Audit

1. Check `.env.example` against all `env()` calls in `config/*.php`:
   - Every `env('KEY', default)` in config files should have a corresponding entry in `.env.example`.
   - Flag missing example values.
2. Check for hardcoded secrets in config files (API keys, passwords, tokens).
3. Verify `APP_KEY` generation and `APP_ENV` production settings.

### 2.4 Permission & Auth Config Audit

1. `config/permission.php` — verify spatie/laravel-permission configuration is correct.
2. `config/auth.php` — verify guard configuration, password policies.
3. `config/cors.php` or equivalent — verify CORS settings are locked down for production.
4. `config/session.php` — verify session driver (`database` for tier 1, `redis` for tier 2+), secure cookies in production.

### 2.5 Service-Specific Config Audit

Check each module's config section:
1. `config/settings.php` — theme cache keys, default values, supported types.
2. `config/setup.php` — defaults for admin name/username, security limits, token expiry.
3. `config/module.php` — paths, livewire discovery, policy discovery, factory namespaces.
4. `config/event.php` — all event-listener mappings are valid (both classes exist).
5. `config/media-library.php` — file size limits, image conversions, queue status.
6. `config/pulse.php` — recorders, thresholds, ingest settings.

**Record findings** under `CONFIG-*` IDs.

---

## Phase 4 — Jobs & Queue Audit (`app/Jobs/`)

### 4.1 Job Serialization Audit

1. Every Job constructor should accept **model IDs** (strings), not full model instances. Full models are serialized and can cause stale data or large payloads.
2. Search for `public function __construct(Model $model)` in Jobs — flag each as HIGH severity.

### 4.2 Job Error Handling Audit

1. Every Job should implement a `failed()` method or have `$tries` and `$backoff` configured.
2. Search for Jobs without `$tries` or `$backoff` properties — flag as MEDIUM.
3. Check that Jobs use `dispatch()->onQueue()` correctly for the `documents` vs `default` pipeline.

### 4.3 Queue Configuration Audit

1. Check `config/queue.php` for proper connection settings.
2. Verify `QUEUE_CONNECTION` default (`sync` for tier 1, `redis` for tier 2+).
3. Check that the dual pipeline (`default` + `documents`) matches what Supervisor config expects.

**Record findings** under `JOB-*` IDs.

---

## Phase 5 — View Layer Audit (`resources/views/`)

### 5.1 Structure Audit

1. Every `resources/views/{module}/{submodule}/` directory must correspond to an `app/{Module}/{SubModule}/` directory.
2. No orphan view directories (views without backend code).
3. View naming: no redundant nesting (e.g., `auth.login` not `auth.login.login`).

### 5.2 Convention Audit

1. All user-facing strings use `__()` helper — flag hardcoded English text.
2. maryUI Toast (`$this->success()`/`$this->error()`) NOT used — should be `flash()->success()`/`flash()->error()`.
3. Icon-only buttons have `aria-label` with translated text.
4. No `dd()`/`dump()`/`var_dump()` in Blade.

### 5.3 Security Audit (XSS)

1. `{!! $var !!}` is dangerous with user content. Flag every occurrence and verify the variable is trusted/sanitized.
2. `wire:model` on user-input fields is safe (Livewire handles escaping).
3. Check for inline `<script>` tags.

### 5.4 Accessibility Audit

1. Forms have `<label>` elements associated with inputs.
2. Buttons have descriptive text or `aria-label`.
3. Error messages are associated with inputs.

**Record findings** under `VIEW-*` IDs.

---

## Phase 6 — Route Layer Audit (`routes/`)

### 6.1 Structure Audit

1. Every module (except Core) should have a corresponding `routes/web/{module}.php`.
2. `routes/web.php` should include them in dependency order.
3. No Closure-based routes in route files (incompatible with `route:cache`).

### 6.2 Naming Audit

1. Route names follow `{prefix}.{resource}.{action}` pattern.
2. Route URIs follow `kebab-case`.
3. Middleware groups applied correctly: `auth`, `guest`, `role:...`, `throttle`.

### 6.3 Security Audit

1. Every state-changing route has `auth` middleware.
2. Admin routes have `role:super_admin|admin` middleware.
3. No debug routes in production (`_debugbar`, `telescope`, `clockwork`).
4. Route model binding uses UUID columns.

**Record findings** under `ROUTE-*` IDs.

---

## Phase 7 — Test Layer Audit (`tests/`)

### 7.1 Coverage Audit

1. Every Action file has a corresponding test file — flag missing tests.
2. Every Livewire component has a test — flag missing tests.
3. Entity tests don't use `LazilyRefreshDatabase` or `RefreshDatabase`.
4. Feature tests use `LazilyRefreshDatabase` (preferred) or `RefreshDatabase`.

### 7.2 Convention Audit

1. Test file naming: `{Name}Test.php`.
2. Test structure: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`.
3. Assertion preference: `assertModelExists()` over `assertDatabaseHas()`.
4. `Event::fake()` positioned AFTER factory setup (not before).
5. No `dd()`/`dump()` in test files.

### 7.3 Quality Audit

1. Run the full test suite — record any failures.
2. Check for flaky tests (tests that depend on state from other tests).
3. Check for slow tests (queries in loops, missing `LazilyRefreshDatabase`).
4. Run `vendor/bin/phpstan analyse --no-progress` — record any level > 0 findings.

**Record findings** under `TEST-*` IDs.

---

## Phase 8 — Documentation Alignment Audit

### 8.1 Cross-Reference Audit

1. Every module documented in `docs/modules/` matches `app/{Module}/`.
2. Every reference doc's claims (models count, actions count, enum values) match actual code.
3. `docs/doc-index.md` links are valid (no broken links).
4. `docs/modules/module-index.md` numbers match actual implementation.

### 8.2 Known Issues Verification

1. Re-check every existing entry in `docs/known-issues.md` — is it still valid? Mark resolved issues.
2. Add all new findings from this audit to `docs/known-issues.md`.
3. Validate that all `[RESOLVED]` entries are actually resolved.

**Record findings** under `DOC-*` IDs.

---

## Phase 9 — Industry Standards Audit

### 9.1 OWASP Top 10

Check for:
1. Broken Access Control — verify policy enforcement in all mutation endpoints.
2. Cryptographic Failures — passwords hashed? PII encrypted? UUIDs not sequential?
3. Injection — SQL, XSS, CSV injection.
4. Insecure Design — business logic flaws, missing rate limits.
5. Security Misconfiguration — debug mode, CORS, CSP headers.
6. Vulnerable Components — `composer audit` for package vulnerabilities.

### 9.2 PSR/Laravel Standards

1. PSR-4 autoloading compliance.
2. PSR-12 code style (via Pint).
3. SOLID principles — especially Single Responsibility (Actions), Dependency Inversion (injection).

### 9.3 PHP 8.4 Features

1. Property promotion used consistently in constructors.
2. `readonly` classes where appropriate (DTOs, Entities).
3. No deprecated PHP 8.3/8.4 features.

**Record findings** under `STANDARD-*` IDs.

---

## Phase 10 — Report Generation

### 10.1 Consolidate Findings

1. Remove duplicate findings (same issue found by multiple checks).
2. Sort by severity (CRITICAL → HIGH → MEDIUM → LOW).
3. Update the master change summary at the top of `docs/known-issues.md`.

### 10.2 Summary Statistics

Include in the report:
```
## Audit Summary — {date}

| Severity | Count |
|----------|-------|
| CRITICAL | N |
| HIGH     | N |
| MEDIUM   | N |
| LOW      | N |
| **Total** | **N** |

### By Category
{Category breakdown}
```

### 10.3 Final Verification

```bash
php artisan test --compact              # verify nothing is broken
vendor/bin/pint --format agent          # verify code style
vendor/bin/phpstan analyse --no-progress # verify static analysis
```

## References

- `docs/architecture.md` — all architecture patterns
- `docs/conventions.md` — all conventions
- `docs/doc-index.md` — documentation catalog
- `docs/modules/module-index.md` — module catalog
- `docs/known-issues.md` — findings target
- `AGENTS.md` — project invariants
