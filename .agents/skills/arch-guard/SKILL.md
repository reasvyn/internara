---
name: arch-guard
description: >
  SDLC Phase: QUALITY GATE. Comprehensive architecture, convention, and pattern
  enforcement. Scans PHP/Blade code for violations of C1-C8, D1-D6, class contracts,
  naming conventions, security anti-patterns, and performance issues. Produces structured
  JSON reports for issue creation. Use after any code change, before commit, or as a
  periodic audit. All other code skills (code-writing, code-refactoring, livewire-development,
  pest-testing) delegate quality checks to this skill.
---

# Architecture Guard

Comprehensive enforcement of Internara's architecture, conventions, and patterns.
This is the **single source of truth** for all quality rules — every other skill defers here.

## When to Use

| Scenario | Action |
|----------|--------|
| Before any commit | Run targeted checks on changed files |
| After feature implementation | Run full module scan |
| Periodic audit | Run full codebase scan |
| Onboarding new code | Validate against all contracts |
| CI/CD gate | Run automated checks in pipeline |

## Rule Reference Hierarchy

Rules are checked in this priority order:

1. **AGENTS.md Critical Invariants** (C1-C8, D1-D6) — non-negotiable
2. **Architecture patterns** (`docs/architecture/*.md`) — structural contracts
3. **Coding conventions** (`docs/conventions.md`) — style and naming
4. **Security rules** — OWASP, Laravel security best practices
5. **Performance rules** — N+1, query optimization, eager loading

## Critical Invariants (Non-Negotiable)

### C-Invariants (Architecture)

| ID | Rule | Detection | Reference |
|----|------|-----------|-----------|
| **C1** | No `Model::create/update/delete/forceDelete/trash` in Livewire | Livewire PHP files: `Model::create(`, `Model::update(`, `Model::delete(`, `Model::forceDelete(`, `->delete(` on Model instances in Livewire methods | `docs/architecture/action-pattern.md` §Non-Negotiable |
| **C2** | No `app()->make()` / `app()->makeWith()` / `resolve()` / `app()->bind()` | All PHP: `app()->make(`, `app()->makeWith(`, `resolve(`, `app()->bind(`, `app()->singleton(` (in non-Providers) | `docs/conventions.md` §Dependency Injection |
| **C3** | No `DB::raw()` / `whereRaw()` / `selectRaw()` without parameterized binding | All PHP: `DB::raw(`, `->whereRaw(`, `->selectRaw(`, `->havingRaw(`, `->orderByRaw(` | `docs/conventions.md` §SQL Injection Prevention |
| **C4** | No inline cache keys — register in `config/cache-keys.php` | `Cache::remember(`, `Cache::get(`, `Cache::put(` with string literals not from config | `docs/architecture/cache-pattern.md` §Registration |
| **C5** | Entities must NOT import Actions, Services, Livewire, Controllers | `app/{Module}/Entities/` files: forbidden `use` statements | `docs/architecture/entity-pattern.md` §Non-Negotiable |
| **C6** | DTOs must NOT import Models, Entities, Actions | `app/{Module}/Data/` files: forbidden `use` statements | `docs/architecture/data-pattern.md` §Non-Negotiable |
| **C7** | Command/Process Actions: DTO for 3+ params, return ActionResponse | Command/Process actions with execute() params > 2 not using BaseData | `docs/architecture/action-pattern.md` §Command Action |
| **C8** | Business rules → `RejectedException`, not `RuntimeException` | `throw new RuntimeException(` in Action/Entity methods | `docs/architecture/exception-pattern.md` §Usage |

### D-Invariants (Coding)

| ID | Rule | Detection | Reference |
|----|------|-----------|-----------|
| **D1** | `declare(strict_types=1)` in ALL PHP (except migrations, configs) | PHP files without `declare(strict_types=1)` | `docs/conventions.md` §Strict Types |
| **D2** | No `dd/dump/ray/var_dump/print_r/die` in committed code | Any PHP/Blade: `dd(`, `dump(`, `ray(`, `var_dump(`, `print_r(`, `die(`, `exit(` | `docs/conventions.md` §Debug Calls |
| **D3** | All user-facing strings use `__()` | Blade/Livewire: hardcoded strings in UI, missing `__()` wrapper | `docs/conventions.md` §Localization |
| **D4** | Models use `#[Fillable]` attribute (NOT `$fillable`/`$guarded`) | Model files: `$fillable =`, `$guarded =`, missing `#[Fillable]` | `docs/architecture/model-pattern.md` §Non-Negotiable |
| **D5** | Never pass raw request input to `create()`/`update()` | `->create($this->validate(...))` without `->only()`/`->toArray()` | `docs/conventions.md` §Input Sanitization |
| **D6** | Foreign keys use `foreignUuid()->constrained()->onDelete()->onUpdate()` | Migrations: missing `onDelete`/`onUpdate` on foreign keys | `docs/conventions.md` §Database Conventions |

## Class Contract Rules

### Action Contracts

| Type | Base Class | Required Methods | Forbidden |
|------|-----------|------------------|-----------|
| **Command** | `BaseCommandAction` | `execute()` (single public), `transaction()`, `log()` | `handle()`, multiple public methods |
| **Read** | `BaseReadAction` | `execute()` (single public) | `handle()`, transactions, event dispatch |
| **Process** | `BaseProcessAction` | `execute()` (single public), `transaction()`, `log()` | `handle()`, multiple public methods |

**Action file checks:**
- File header: `declare(strict_types=1)` → namespace → use → class → constructor → `execute()`
- Constructor: `final public function __construct(private readonly DependencyService $service)`
- No `Model::create/update/delete` — use Command Actions
- No `app()->make()` — use constructor injection
- No `DB::raw()` — use parameterized queries
- Command/Process: DTO for 3+ params, returns `ActionResponse`
- Events: `$this->dispatchEvent()` not `$event::dispatch()`

### Entity Contracts

| Property | Rule |
|----------|------|
| Class | `final readonly class` |
| Properties | `private readonly` scalars/enums/Carbon/ValueObjects only |
| Methods | Business question methods, `fromModel()`, `toArray()`, `toJson()` |
| Forbidden imports | Actions, Services, Livewire, Controllers, Repository interfaces |
| Forbidden I/O | Database, HTTP, cache, filesystem, logging, event dispatch |
| Forbidden construction | Static factory methods on Entity itself (use Actions) |

### DTO/Data Contracts

| Property | Rule |
|----------|------|
| Class | `final readonly class` extending `BaseData` |
| Properties | `private readonly` — scalars, enums, Carbon only |
| Constructor | Single `public function __construct()` |
| Invariants | Enforced via `__construct` + private validation helpers |
| Forbidden imports | Models, Entities, Actions, Repositories |

### Model Contracts

| Property | Rule |
|----------|------|
| Fillable | `#[Fillable]` attribute (PHP 8.4) — NOT `$fillable`/`$guarded` |
| Business methods | None — delegate to Entity via `entity()` bridge |
| Entity bridge | `public function entity(): {ModuleName}Entity` |
| Forbidden | `update()`, `delete()`, `forceDelete()` calls in Model methods |
| Relationships | `return $this->hasMany(...)` (not string-based) |

### Enum Contracts

| Type | Base | Required | Forbidden |
|------|------|----------|-----------|
| **LabelEnum** | `LabelEnum` | `label()` method, backed string | Mutable state, I/O |
| **StatusEnum** | `StatusEnum` | `validTransitions()`, `allowedTransitions()`, `label()`, `color()`, `icon()` | Mutable state, I/O |
| **IntBacked** | `IntBackedEnum` | Integer backing values | — |
| **StringBacked** | `StringBackedEnum` | String backing values | — |

### Event Contracts

| Property | Rule |
|----------|------|
| Dispatch | `$this->dispatchEvent()` in Actions (auto-flushed after TX commit) |
| Forbidden | `$event::dispatch()` in Actions |
| Listeners | Must implement `ShouldHandleEventsAfterCommit` |
| Properties | `private readonly` with business meaning in name |

### Policy Contracts

| Property | Rule |
|----------|------|
| Authorization | `$this->authorize()` in Policy methods (NOT `$this->policy()->authorize()`) |
| Ability methods | `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` |
| Forbidden | Business logic in Policies — only authorization |

### Livewire Contracts

| Property | Rule |
|----------|------|
| Business logic | None — delegate to Actions |
| Model mutations | Use Command Actions — NEVER `Model::create/update/delete` |
| State management | `#[Computed]`, `#[Url]`, `#[Locked]` attributes |
| Security | `#[Authorize]` on sensitive methods |
| File uploads | Use Actions with Spatie MediaLibrary |
| Component structure | Properties → Lifecycle Hooks → Computed → Render → Actions → Private Helpers |

### Service Contracts

| Property | Rule |
|----------|------|
| Registration | `bind()`, `scoped()`, or `singleton()` in Providers |
| Constructor injection | `public function __construct(...)` |
| Forbidden | Facade static calls (prefer injected instances) |
| State | Stateless — no mutable properties |

## Security Rules

| ID | Rule | Detection |
|----|------|-----------|
| **S1** | XSS: `{!! !!}` only for trusted content, `{!! e() !!}` for escaping | Blade: `{!! $var !!}` without `e()` wrapper |
| **S2** | SQL Injection: parameterized queries only | Raw SQL without bindings |
| **S3** | Mass Assignment: `Model::create($validated)` with only allowed fields | `create($request->all())` |
| **S4** | CSRF: `@csrf` in forms, `csrf_token()` in AJAX | Missing CSRF tokens |
| **S5** | Authentication: `auth()->check()` or `@auth` before sensitive operations | Unprotected endpoints |
| **S6** | Authorization: `$this->authorize()` or `@can` before actions | Missing authorization checks |
| **S7** | Rate limiting: `RateLimiter::` or `throttle` middleware | No rate limiting on auth endpoints |
| **S8** | Secrets: no hardcoded passwords/tokens/keys | Hardcoded credentials |
| **S9** | File upload: validate type, size, scan content | Unrestricted uploads |
| **S10** | Headers: security headers set | Missing CSP, X-Frame-Options, etc. |

## Performance Rules

| ID | Rule | Detection |
|----|------|-----------|
| **P1** | No N+1 queries: eager load with `with()` | Missing `with()` on relationship access |
| **P2** | Use `select()` to limit columns | `Model::all()` without column selection |
| **P3** | Use `chunk()`/`cursor()` for large datasets | `->get()` on potentially large collections |
| **P4** | Cache expensive queries | Repeated expensive queries without caching |
| **P5** | Use `exists()` instead of `count() > 0` | `count() > 0` pattern |

## Naming Conventions

### File Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Actions | `{Verb}{Entity}Action.php` | `StoreStudentAction.php` |
| Entities | `{Entity}.php` (singular) | `Student.php` |
| DTOs | `{Entity}Data.php` or `{Action}Request.php` | `StudentData.php` |
| Enums | `{Description}{Type}Enum.php` | `StudentStatusEnum.php` |
| Models | `{Entity}.php` (singular) | `Student.php` |
| Livewire | `{Action}{Entity}{Layer}.php` | `StoreStudentForm.php` |
| Policies | `{Entity}Policy.php` | `StudentPolicy.php` |
| Events | `{PastTenseVerb}{Entity}Event.php` | `StudentCreatedEvent.php` |
| Listeners | `{Event}Listener.php` | `StudentCreatedListener.php` |
| Migrations | `{timestamp}_{description}.php` | `2026_01_01_000001_create_students_table.php` |
| Tests | `{Feature/Unit}/{Described}_Test.php` | `StoreStudentAction_Test.php` |

### Class Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Actions | `{Verb}{Entity}Action` | `StoreStudentAction` |
| Entities | `{Entity}` (singular) | `Student` |
| DTOs | `{Entity}Data` or `{Action}Request` | `StudentData` |
| Enums | `{Description}{Type}Enum` | `StudentStatusEnum` |
| Models | `{Entity}` (singular) | `Student` |
| Livewire | `{Action}{Entity}{Layer}` | `StoreStudentForm` |
| Policies | `{Entity}Policy` | `StudentPolicy` |
| Events | `{PastTenseVerb}{Entity}Event` | `StudentCreatedEvent` |
| Listeners | `{Event}Listener` | `StudentCreatedListener` |

### Method Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Action execute | `execute()` (single public) | `execute(): ActionResponse` |
| Entity questions | `is{Question}(): bool` | `isEligibleForCertification(): bool` |
| Entity fromModel | `fromModel(Model $model): static` | `fromModel(Student $student): static` |
| Entity toArray | `toArray(): array` | `toArray(): array` |
| Model entity | `entity(): {Entity}Entity` | `entity(): StudentEntity` |
| LabelEnum label | `label(): string` | `label(): string` |
| StatusEnum transitions | `validTransitions(): array` | `validTransitions(): array` |
| Test methods | `it_{behavior}()` or `test_{behavior}()` | `it_rejects_invalid_email()` |

### Variable/Property Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Actions | `$action` | `$action = new StoreStudentAction()` |
| Entities | `$entity` | `$entity = StudentEntity::fromModel($student)` |
| DTOs | `$data` or `$request` | `$data = new StudentData(...)` |
| Models | `$model` | `$model = Student::findOrFail($id)` |
| Collections | `$items` or plural | `$students = Student::all()` |
| Boolean | `$is{State}` / `$has{Thing}` | `$isActive`, `$hasPermission` |

## Naming Anti-Patterns to Detect

| Anti-Pattern | Replace With |
|-------------|-------------|
| `handle()` method | `execute()` |
| `$this->request` in Actions | `$this->dto` or `$this->data` |
| `App\Models\{Model}` in Entity | Import at use statement, keep Entity clean |
| `$student_data` (snake_case) | `$studentData` (camelCase) |
| `$results_array` | `$results` |
| `getData()` | Property access on DTO |

## Quality Gate Commands

```bash
# Full violation scan
python3 scripts/scan_violations.py

# Class contract compliance
python3 scripts/scan_class_contracts.py

# Security patterns
python3 scripts/scan_security.py

# Naming conventions
python3 scripts/scan_naming.py

# Combined architecture audit
python3 scripts/scan_architecture.py

# Conventions check
python3 scripts/scan_conventions.py

# Doc link integrity
python3 scripts/scan_doc_links.py
```

## Integration with Other Skills

| Skill | How arch-guard integrates |
|-------|--------------------------|
| `code-writing` | Validate new code before commit |
| `code-refactoring` | Verify refactored code maintains contracts |
| `livewire-development` | Check Livewire components for C1 violations |
| `pest-testing` | Verify test structure conventions |
| `audit-protocol` | Use as reference for finding severity classification |
| `writing-issues` | Use violation data for issue descriptions |
| `sync-docs` | Use conventions for documentation accuracy |
| `test-writing` | Validate test file conventions |
| `doc-writing` | Validate doc structure conventions |

## Severity Classification

| Severity | Definition | Example |
|----------|-----------|---------|
| **CRITICAL** | Security vulnerability, data loss risk | SQL injection, mass assignment |
| **HIGH** | Architecture violation, breaks invariants | C1-C8, D1-D6 violations |
| **MEDIUM** | Convention violation, maintainability impact | Naming errors, missing type hints |
| **LOW** | Style issue, minor improvement | Comment style, formatting |

## Automation

### In `scripts/scan_violations.py`
Checks C1-C8, D1-D6, security, performance rules.

### In `scripts/scan_class_contracts.py`
Checks Action/Entity/DTO/Model/Enum contracts.

### In `scripts/scan_security.py`
Checks XSS, SQL injection, mass assignment, auth patterns.

### In `scripts/scan_naming.py`
Checks file/class/method/variable naming conventions.

### In `scripts/scan_conventions.py`
Checks strict_types, Fillable, debug calls, hardcoded strings.

## Report Structure

```json
{
  "scan_version": "1.0.0",
  "scan_type": "full|targeted|module",
  "module": null,
  "timestamp": "2026-07-11T12:00:00+07:00",
  "summary": {
    "total_checks": 0,
    "passed": 0,
    "failed": 0,
    "by_severity": { "critical": 0, "high": 0, "medium": 0, "low": 0 }
  },
  "findings": [
    {
      "id": "C1-001",
      "rule": "C1",
      "severity": "high",
      "file": "app/Student/Livewire/StoreStudentForm.php",
      "line": 42,
      "message": "Model::create() found in Livewire component",
      "suggestion": "Use StoreStudentAction instead",
      "reference": "docs/architecture/action-pattern.md"
    }
  ]
}
```
