---
name: quality-assurance
description: Comprehensive quality assurance skill covering code review, testing verification, formatting enforcement, static analysis, and pre-commit/pre-PR quality gates. Use for any task involving code quality verification, test creation/review, or ensuring compliance with project standards.
---

# Quality Assurance Skill

## When to Activate

Apply this skill when performing code review, writing or reviewing tests, enforcing code style, running static analysis, or executing the pre-commit/pre-PR quality gate. This skill encodes the complete quality workflow — from formatting to coverage.

---

## Section 1 — Code Review

### 1.1 Pre-Review Checklist

Before reviewing any code, ensure:

- [ ] Understand the module context (read `docs/modules/{module}.md`)
- [ ] Understand the architecture patterns (`docs/architecture.md`)
- [ ] Know the coding conventions (`docs/conventions.md`)
- [ ] Check `docs/known-issues.md` for related unresolved issues

### 1.2 Convention Compliance

Check every PHP file for:

| Check | Rule | Reference |
|-------|------|-----------|
| `declare(strict_types=1)` | Present on line 3 of every file except migrations and config | `docs/conventions.md` §2 |
| Base class | Correct base class for the layer (see table below) | `docs/architecture.md` §Base Class Mandate |
| Return types | Explicit on every method | `docs/conventions.md` §2 |
| Parameter types | Type hints on all parameters | `docs/conventions.md` §2 |
| Property promotion | `public function __construct(protected readonly X $x)` | `docs/conventions.md` §2 |
| Translations | All user-facing strings use `__()` helper | `docs/conventions.md` §2 |
| No debug calls | No `dd()`, `dump()`, `ray()`, `var_dump()`, `die()` | `docs/conventions.md` §2 |
| Trailing commas | On multiline arrays, function calls, constructor params | `docs/conventions.md` §2 |

#### Base Class Mandate

| You need | You must use | Not this |
|----------|-------------|----------|
| Database table | `extends BaseModel` | `extends Model` |
| Auth model | `extends BaseAuthenticatable` | `extends Authenticatable` |
| Business mutation | `extends BaseCommandAction` | `extends BaseAction` (legacy) |
| Business query | `extends BaseReadAction` | `extends BaseAction` |
| Multi-step orchestration | `extends BaseProcessAction` | `extends BaseAction` |
| Business rules | `extends BaseEntity` (final readonly) | Inline in model |
| Authorization gate | `extends BasePolicy` | Custom closures |
| CRUD table UI | `extends BaseRecordManager` | Bespoke component |
| DTO / value object | `extends BaseData` (final readonly) | Raw arrays |
| Event | `extends BaseEvent` (final) | Plain class |
| Enum | `implements LabelEnum` | Plain enum |
| State machine enum | `implements StatusEnum` (+ LabelEnum) | Boolean fields |
| Exception | `extends AppException` or `extends ModuleException` | `extends \\Exception` |

### 1.3 Architecture Pattern Compliance

#### Action Triad

| Type | Must have | Must NOT have |
|------|-----------|---------------|
| Command | `$this->transaction()`, `$this->log()`, single `execute()` | Inline business rules |
| Read | Typed returns | `transaction()`, `log()`, mutations |
| Process | Constructor-injected Actions, partial failure handling | Duplicated Command logic |

Checklist for every Action file:
- [ ] Single public `execute()` method
- [ ] `$this->transaction()` wrapping DB writes (Command/Process)
- [ ] `$this->log()` after successful mutation (Command/Process)
- [ ] `event(...)` dispatched for significant state changes (Command)
- [ ] Business rules delegated to Entity methods or `RejectedException`
- [ ] `$this->authorize()` or `Gate::authorize()` in calling layer
- [ ] `catch (RejectedException $e)` in calling Livewire/Controller

#### Entity-Model Separation

- [ ] Models have NO business rule methods (no `canX()`, `isX()`, `hasX()`)
- [ ] Business rules in `final readonly` Entities extending `BaseEntity`
- [ ] Models expose entities via `as{EntityName}()` named accessors
- [ ] Entity `fromModel()` is the only place Eloquent fields are accessed
- [ ] Entity tests do NOT use `LazilyRefreshDatabase` (no DB)

#### Enum Contract

- [ ] `implements LabelEnum` — all enums
- [ ] `implements StatusEnum` — state machine enums (with `canTransitionTo()`, `isTerminal()`, `validTransitions()`)
- [ ] Case names: `UPPER_SNAKE`. Backing values: lowercase
- [ ] Model defaults use `->value` — never hardcoded strings
- [ ] `match()` is exhaustive — every case appears

### 1.4 Security Review

For every state-changing endpoint:

- [ ] Authentication enforced (`auth` middleware or `@auth` guard)
- [ ] Authorization checked (`$this->authorize()` or `Gate::authorize()`)
- [ ] Input validated (Form Request, Form Object, or inline `$this->validate()`)
- [ ] CSRF protection active (Livewire handles this; Blade forms need `@csrf`)
- [ ] Rate limiting on auth endpoints
- [ ] PII masked in log output (`->withPiiMasking()`)

Check for XSS:
- [ ] `{!! $var !!}` only with trusted/sanitized content
- [ ] `Str::markdown()` output is safe by default but verify content source

Check for mass assignment:
- [ ] No `Model::create($request->all())` or `Model::create($this->all())`
- [ ] `#[Fillable]` attribute present on all models

Check for SQL injection:
- [ ] No concatenated strings in `DB::raw()`, `whereRaw()`, `orderByRaw()`

### 1.5 Performance Review

- [ ] No N+1 queries — `->with()` for eager loading, not `->load()` after collection
- [ ] Dashboard/aggregation queries cached or delegated to Read Actions
- [ ] No expensive computations in Livewire `render()`
- [ ] Collection operations not inside Blade `@foreach` loops

### 1.6 Naming Review

| Element | Pattern | Example |
|---------|---------|---------|
| Command Action | `{Verb}{Entity}Action` | `CreateUserAction` |
| Read Action | `Read{Entity}Action` | `ReadActivityLogAction` |
| Process Action | `Process{Entity}Action` | `ProcessRegistrationAction` |
| Entity | `{Name}` (business role) | `Apprentice`, `InternshipPeriod` |
| DTO | `{Verb}{Entity}Data` | `LoginData`, `SetupTokenData` |
| Event | `{Entity}{PastTenseAction}` | `InternshipCreated` |
| Listener | Named by what it does | `NotifyAdminsInternshipCreated` |
| Notification | `{Entity}{Type}Notification` | `InternshipCreatedNotification` |
| Policy | `{Model}Policy` | `UserPolicy` |
| Console command | `{module}:{action}` | `system:health` |
| Route name | `{prefix}.{resource}.{action}` | `admin.users.index` |
| Cache key | `{module}.{purpose}[.{qualifier}]` | `setup.is_installed` |
| Boolean method | `is`/`has`/`can`/`requires`/`allows` | `isActive()`, `canTransitionTo()` |
| Submodule dir | Singular | `User`, `Profile`, `Internship` |
| Migration | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | — |

---

## Section 2 — Testing

### 2.1 Test Structure

Tests mirror source structure exactly:

```
app/{Module}/{SubModule}/{ClassName}.php
  → tests/{Feature,Unit}/{Module}/{SubModule}/{ClassName}Test.php
```

### 2.2 Coverage Requirements

| Layer | Test Type | Database | Required |
|-------|-----------|----------|----------|
| Enum | Unit | Never | Always |
| Entity | Unit | Never | Always |
| DTO | Unit | Never | Always |
| Policy | Unit | Never | Always |
| Command Action | Feature | Yes | Always |
| Read Action | Feature | Yes | Always |
| Process Action | Feature | Yes | Always |
| Livewire | Feature | Yes | When created/modified |
| Console Command | Feature | Yes | When created/modified |
| Support/Utility | Unit | Never | When created/modified |

**Rule:** Every Action file must have a corresponding test file. No exceptions.

### 2.3 Test Quality Checklist

For every test file, verify:

- [ ] Uses `LazilyRefreshDatabase` (Feature tests) — never `RefreshDatabase` unless unavoidable
- [ ] Uses `assertModelExists()` over `assertDatabaseHas()` for ID-based assertions
- [ ] `Event::fake()` positioned AFTER factory setup (not before)
- [ ] No `dd()`, `dump()`, `ray()` in test code
- [ ] Test names are descriptive: `it('creates a user with valid data')`
- [ ] Covers happy path + at least one failure/edge case
- [ ] Entity tests have NO database traits
- [ ] Uses Pest `describe()` + `it()` grouping
- [ ] Mocks only when necessary (Process Action child Actions, external services)

### 2.4 Running Tests

```bash
# Run specific test
php artisan test --compact --filter=TestName

# Run full suite
php artisan test --compact

# Run with coverage (pcov)
composer run coverage

# Run unit tests only
composer run test:unit

# Run feature tests only
composer run test:feature
```

---

## Section 3 — Formatting & Static Analysis

### 3.1 Laravel Pint (PHP Code Style)

Run before every commit:

```bash
vendor/bin/pint --format agent
```

Enforces PSR-12 + Laravel conventions. The `--format agent` flag produces machine-readable output.

To test without modifying:

```bash
vendor/bin/pint --test
```

### 3.2 PHPStan (Static Analysis)

```bash
vendor/bin/phpstan analyse --no-progress
```

Configuration in `phpstan.neon`. Catches:
- Type mismatches
- Dead code (unused parameters, unreachable branches)
- Boundary violations
- Missing return types

### 3.3 Prettier (Markdown, JSON, YAML, Blade)

```bash
npm run format
```

Formats all non-PHP files: Markdown docs, JSON config, YAML workflows, Blade templates.

---

## Section 4 — Pre-Commit / Pre-PR Quality Gate

Execute these steps in order before every commit or PR submission:

### Step 1 — Code Formatting

```bash
vendor/bin/pint --format agent
```

If Pint reports changes, review them and stage the formatted files.

### Step 2 — Static Analysis

```bash
vendor/bin/phpstan analyse --no-progress
```

PHPStan must pass at the configured level. If it fails, fix the reported issues.

### Step 3 — Test Suite

```bash
php artisan test --compact
```

All tests must pass. If a test fails:
1. Check if it's a pre-existing failure (run on `main` branch)
2. If caused by your changes, fix the test or the implementation
3. Do NOT disable or comment out failing tests

### Step 4 — Coverage Check (PR only)

```bash
composer run test:coverage
```

Minimum 80% coverage enforced. If coverage drops, add tests for uncovered paths.

### Step 5 — Documentation Check

- [ ] Updated `docs/known-issues.md` if fixing an existing issue
- [ ] Updated module reference docs if adding/modifying classes
- [ ] No stale file paths in documentation
- [ ] Cross-references in docs are valid

### Step 6 — Security Scan

```bash
composer audit
```

Check for known package vulnerabilities. If found, update affected packages.

### Step 7 — Final Review

Before pushing, verify:

- [ ] All debug calls removed (`dd`, `dump`, `ray`, `var_dump`)
- [ ] No commented-out code blocks
- [ ] No `TODO` or `FIXME` without a tracking issue
- [ ] Translations added in both `lang/en/` and `lang/id/`
- [ ] Cache keys declared in `config/cache-keys.php` if new
- [ ] Events registered in `config/event.php` if new

---

## Section 5 — Continuous Quality Patterns

### 5.1 When Modifying Existing Code

1. Run the existing tests BEFORE making changes
2. Make the change
3. Run tests again — same tests must still pass
4. If fixing a bug, add a test that reproduces the bug first (TDD)

### 5.2 When Adding New Code

1. Every Action needs a test file (start with it)
2. Every Livewire component needs a test
3. Every policy needs a unit test
4. Every console command needs a feature test

### 5.3 When Reviewing PRs

1. Run the quality gate (Sections 1-4)
2. Check for pattern violations listed in Section 1
3. Verify test coverage for new code
4. Check for security issues (Section 1.4)
5. Approve only after all checks pass

---

## Quick Reference

```bash
# Format PHP
vendor/bin/pint --format agent

# Check PHP types
vendor/bin/phpstan analyse --no-progress

# Run tests
php artisan test --compact

# Run specific test
php artisan test --compact --filter=CreateUserAction

# Coverage
composer run test:coverage

# Format non-PHP
npm run format

# Security audit
composer audit
```

## References

- `docs/conventions.md` — all coding conventions
- `docs/architecture.md` — all architecture patterns
- `docs/architecture/action-pattern.md` — Action Triad deep dive
- `docs/architecture/testing-pattern.md` — testing patterns
- `docs/infrastructure/testing.md` — testing infrastructure
- `docs/known-issues.md` — known issues tracker
