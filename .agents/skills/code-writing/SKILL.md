---
name: code-writing
description: SDLC Phase: IMPLEMENTATION. PHP and Laravel code writing — strict types, Action Triad, Entity/DTO/Model contracts, naming conventions, security patterns, performance rules, and non-negotiable invariants.
upstream:
  - context-awareness
  - laravel-best-practices
downstream:
  - test-writing
  - pest-testing
  - doc-writing
  - sync-docs
---

# Code Writing

> **Prerequisite:** Load `context-awareness` for project orientation and `laravel-best-practices` for
> Laravel-specific guidance.

## When to Activate

Use this skill when:
- Writing new PHP classes (Actions, Entities, DTOs, Models, Enums, Services)
- Adding methods to existing classes
- Implementing new features or business logic
- Writing Livewire components or Blade templates
- Creating migrations, seeders, or config files

## Agent Workflow

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read `laravel-best-practices` skill for Laravel-specific patterns
- Read relevant pattern docs: `docs/architecture/{pattern}-pattern.md`
- Identify which module and submodule the code belongs to
- Read existing code in the same submodule to match conventions
- Check if the class already exists (avoid duplicates)
- Determine approach: at least 2 options before deciding

### 2. Execute — Write Code

- Follow file header order (strict_types, namespace, use, class)
- Follow class contracts (Action, Entity, DTO, Model, Enum)
- Follow naming conventions exactly
- Apply security patterns (XSS, SQL injection, mass assignment)
- Apply performance rules (N+1 prevention, caching)
- Register cache keys in `config/cache-keys.php`
- Use `__()` for all user-facing strings
- Output: PHP/Blade/JS files matching the conventions below

### 3. Verify — Quality Gates

- `declare(strict_types=1)` present in all new PHP files
- No debug calls (`dd`, `dump`, `ray`, `var_dump`, `print_r`, `die`)
- No `app()->make()` or `resolve()` — constructor injection only
- No `Model::create/update/delete` in Livewire — use Command Actions
- No `$fillable` / `$guarded` — use `#[Fillable]` attribute
- Lint: `vendor/bin/pint --dirty --format agent`
- Static analysis: `vendor/bin/phpstan analyse --no-progress`
- No N+1 queries — eager loading verified

### 4. Report & Commit

- Deliver a report to the user:
  - Summary of code written
  - Files created or modified
  - Architecture decisions made
  - Tests needed (delegates to `test-writing` or `pest-testing`)
- Feeds into: `test-writing` (verification), `doc-writing` (documentation), `sync-docs` (doc sync)
- Commit using format: `type(scope): description`

---

## 1. Non-Negotiable Invariants

These rules MUST be violated. No exceptions.

### Architecture Invariants

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

### Coding Invariants

| # | Rule |
|---|------|
| D1 | `declare(strict_types=1)` in ALL PHP files except migrations/config |
| D2 | No `dd/dump/ray/var_dump/print_r/die` in committed code |
| D3 | All user-facing strings use `__()` — both `lang/en/` and `lang/id/` |
| D4 | Models use `#[Fillable]` attribute (PHP 8.4), NOT `$fillable` / `$guarded` |
| D5 | Never pass raw request input to `create()`/`update()` — use `->only()` or `->toArray()` |
| D6 | Foreign keys use `foreignUuid()->constrained('{table}')` with explicit `onDelete()`/`onUpdate()` |

---

## 2. File Header Order

Every PHP class file MUST follow this exact ordering:

```php
<?php

declare(strict_types=1);

namespace App\{Module}\{SubModule}\{Type};

use App\{Dependency};

class {ClassName} extends {BaseClass}
{
    public function __construct(
        protected readonly {Type} ${param},
    ) {}

    public function execute(): {ReturnType}
    {
        // ...
    }
}
```

**Rules:**
1. `declare(strict_types=1)` — always first (except migrations/config)
2. Namespace — matches directory location
3. Use statements — one per line, sorted alphabetically
4. Class declaration — extends appropriate base class
5. Constructor — `protected readonly` promotion for injected dependencies
6. Single `execute()` method — the only public method on Actions

---

## 3. Class Contract Checklists

### Command Action

```php
class Create{Entity}Action extends BaseCommandAction
{
    public function __construct(
        // Constructor-injected dependencies (readonly promoted)
    ) {}

    public function execute(Create{Entity}Data $data): ActionResponse
    {
        // 1. Business rules via Entity (throw RejectedException on violation)
        // 2. $this->transaction(fn () => ...)
        // 3. Model::create() inside transaction
        // 4. $this->log() after mutation
        // 5. $this->dispatchEvent() if listener exists
        // 6. Return ActionResponse
    }
}
```

**Checklist:**
- [ ] Extends `BaseCommandAction`
- [ ] Single public method: `execute()`
- [ ] Accepts DTO for 3+ params (typed scalars OK for 1-2)
- [ ] Returns `ActionResponse`
- [ ] Wraps DB writes in `$this->transaction()`
- [ ] Calls `$this->log()` after mutation
- [ ] Business rules delegated to Entity
- [ ] Throws `RejectedException` (not `RuntimeException`)
- [ ] Events dispatched via `$this->dispatchEvent()` (not `$event::dispatch()`)

### Read Action

```php
class Read{Entity}Action extends BaseReadAction
{
    public function execute(): {ReturnType}
    {
        // Complex query logic
        // May use Cache::remember()
        // NEVER mutates database state
    }
}
```

**Checklist:**
- [ ] Extends `BaseReadAction`
- [ ] Single public method: `execute()`
- [ ] NO `$this->transaction()` or `$this->log()`
- [ ] NO database mutations
- [ ] Returns typed objects or collections (never raw arrays)

### Process Action

```php
class Process{Entity}Action extends BaseProcessAction
{
    public function __construct(
        // Injected Command/Read Actions
    ) {}

    public function execute(): ActionResponse
    {
        // 1. Compose other Actions via injected dependencies
        // 2. $this->transaction(fn () => ...)
        // 3. $this->log() after orchestration
        // 4. $this->dispatchEvent() if listener exists
    }
}
```

**Checklist:**
- [ ] Extends `BaseProcessAction`
- [ ] Composes other Actions via constructor injection
- [ ] NO direct DB queries (delegate to Actions)
- [ ] Wraps orchestration in `$this->transaction()`
- [ ] Calls `$this->log()` after completion

### Entity

```php
final readonly class {Entity}
{
    public function __construct(
        // private properties from model attributes
    ) {}

    public static function fromModel(Model $model): static
    {
        // Bridge from Model to Entity
    }

    public function canBeDeleted(): bool
    {
        // Business question method
    }
}
```

**Checklist:**
- [ ] `final readonly class`
- [ ] `fromModel(Model $model): static` static factory
- [ ] All properties private, constructor-promoted
- [ ] Methods are business questions only (`canX()`, `isX()`, `hasX()`)
- [ ] NO imports: Actions, Services, Livewire, Controllers, HTTP
- [ ] NO I/O (no DB calls, no HTTP calls, no file operations)

### DTO (BaseData)

```php
final readonly class {Verb}{Entity}Data extends BaseData
{
    public function __construct(
        // scalar, enum, Carbon, or nested DTO properties only
    ) {}
}
```

**Checklist:**
- [ ] `final readonly class`
- [ ] Extends `BaseData`
- [ ] Properties: only `string`, `int`, `float`, `bool`, `enum`, `Carbon`, nested DTO
- [ ] NO imports: Models, Entities, Actions, Livewire

### Model

```php
class {Entity} extends BaseModel
{
    #[Fillable([...])]
    protected function casts(): array
    {
        // ...
    }

    public function as{Role}Entity(): {Entity}
    {
        // Bridge to Entity
    }
}
```

**Checklist:**
- [ ] Extends `BaseModel` (or `BaseAuthenticatable` for user models)
- [ ] Uses `#[Fillable([...])]` attribute (NOT `$fillable` / `$guarded`)
- [ ] Has `protected static function newFactory()`
- [ ] Has entity bridge methods: `as{Role}Entity(): {Entity}`
- [ ] NO business logic methods (`canX()`, `isX()`, `hasX()` — those go in Entities)

### Enum

```php
enum {Name}: string implements LabelEnum, StatusEnum
{
    case STATE_A = 'state_a';
    case STATE_B = 'state_b';

    public function label(): string
    {
        return __('{module}.enums.{name}.{value}');
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::STATE_A => [self::STATE_B],
            self::STATE_B => [],  // terminal
        };
    }

    public function isTerminal(): bool
    {
        return $this->validTransitions() === [];
    }
}
```

**Checklist:**
- [ ] `string`-backed enum
- [ ] Implements `LabelEnum` (all enums)
- [ ] Implements `StatusEnum` (lifecycle enums)
- [ ] `UPPER_SNAKE` case names, `snake_case` backing values
- [ ] `label()` returns translated string via `__()`
- [ ] `validTransitions()` uses exhaustive `match()` on all cases
- [ ] Terminal states return `[]`

---

## 4. Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Submodule directory | Singular `{Name}` | `User`, `Profile`, `Internship` |
| Model | Singular `{Name}` | `User`, `AcademicYear` |
| Command Action | `{Verb}{Entity}Action` | `CreateUserAction` |
| Read Action | `Read{Entity}Action` | `ReadTeacherDashboardAction` |
| Process Action | `Process{Entity}Action` | `ProcessRegistrationAction` |
| Entity | `{Name}` | `Apprentice`, `RegistrationState` |
| DTO | `{Verb}{Entity}Data` or `{Entity}Data` | `SetupTokenData` |
| Livewire | `{Name}` suffixed with Manager/Editor/Center | `UserManager` |
| Livewire alias (submodule) | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `admin.user.user-manager` |
| Livewire Form | `{Entity}Form` | `AcademicYearForm` |
| Policy | `{Name}Policy` | `UserPolicy` |
| Exception | `{Name}Exception` | `ConflictException` |
| Event | `{Entity}{Actioned}` (past tense) | `InternshipCreated` |
| Listener | `{Verb}{Entity}` | `NotifyAdminsInternshipCreated` |
| Notification | `{Entity}{NotificationType}Notification` | `WelcomeNotification` |
| Console command | `{module}:{action}` | `system:health` |
| Route name | `{prefix}.{resource}.{action}` | `admin.users.index` |
| Config key | `snake_case` with `{file}.{key}` | `app.name` |
| Column/table | `snake_case` | `user_id`, `academic_years` |
| Boolean methods | `is`/`has`/`can`/`should` prefix | `isActive()`, `allowsLogin()` |
| Test method | Pest `it()` with descriptive string | `it('creates a user with valid data')` |
| Test file | `{Name}Test.php` | `CreateUserActionTest.php` |
| Factory | `{Name}Factory` | `UserFactory` |
| Migration | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | `2026_04_29_092750_create_users_table.php` |

**Class Name Rule:** The class name must never be repeated in the path (e.g.,
`app/{Module}/Models/{Entity}.php` is valid, but
`app/{Module}/{Entity}/{Entity}/Actions/Create{Entity}Action.php` is wrong).

---

## 5. Security Patterns

### XSS Prevention

- Use `{{ $var }}` for all user content (auto-escaped)
- `{!! $var !!}` only for explicitly sanitized content with inline safety comment
- Alpine.js `x-html` follows same rule — never raw user input

```blade
{{-- SAFE: auto-escaped --}}
{{ $user->name }}

{{-- SAFE: sanitized HTML content --}}
{!! $sanitized_html !!} {{-- HTMLPurifier sanitized --}}

{{-- DANGEROUS: never do this --}}
{!! $user->input !!} {{-- XSS vulnerability --}}
```

### SQL Injection

- Always use Eloquent query builder
- `DB::raw()` / `whereRaw()` forbidden without parameterized binding
- If raw SQL is unavoidable, document the exception in the method's docblock

### Mass Assignment

- Use `#[Fillable([...])]` attribute on every Model
- Never `$request->all()` or `$this->all()` — use `->only()` or `->toArray()`

### CSRF

- `@csrf` or Livewire for all state-changing forms
- Exemptions require explicit code comment explaining why

---

## 6. Performance Rules

### N+1 Prevention

```php
// WRONG: N+1 query in Blade loop
@foreach ($users as $user)
    {{ $user->department->name }}
@endforeach

// CORRECT: eager loading
$users = User::with('department')->get();
```

- Never access relationships in Blade loops without `->with()`
- Use `->when()` for conditional eager loading

### Query Optimization

| Instead of... | Use... | Why |
|---------------|--------|-----|
| `count() > 0` | `exists()` | Stops at first match |
| `get()->pluck()` | `pluck()` | Single query |
| Processing 1000+ rows | `chunk()` or `lazy()` | Memory efficient |
| Filtering in PHP | Filter at DB level | Faster, less memory |

### Caching

- Every cache key MUST be registered in `config/cache-keys.php`
- Use `Cache::remember()` for reads
- Use event-driven invalidation
- Never use inline cache key strings

---

## 7. Laravel Divergences

Internara deliberately differs from stock Laravel in these ways:

| Stock Laravel | Internara |
|---------------|-----------|
| `app/Models/` for all models | Models live in `app/{Module}/{SubModule}/Models/` |
| `app/Http/Livewire/` for all components | Components live in `app/{Module}/{SubModule}/Livewire/` |
| `app/Policies/` for all policies | Policies live in `app/{Module}/{SubModule}/Policies/` |
| Services for business logic | Actions (Command/Read/Process) for business logic |
| FormRequest classes for validation | Livewire Form Objects (`Livewire\Form`) for validation |
| Array parameters | `BaseData` DTO for 3+ params |
| `$fillable` / `$guarded` | `#[Fillable]` attribute on every Model |
| `Storage::put()` for file uploads | Spatie MediaLibrary only |

**When in doubt, follow Internara conventions, not stock Laravel.**

---

## 8. ActionResponse Factory Methods

```php
ActionResponse::ok($data, 'Operation completed');
ActionResponse::created($model, '{Entity} created');
ActionResponse::updated($model, '{Entity} updated');
ActionResponse::deleted('{Entity} removed');
ActionResponse::error('Something went wrong', $errors);
```

**When to use:**
- `ok()` — read results, non-mutating operations
- `created()` — after `Model::create()` in a Command Action
- `updated()` — after `Model::update()` in a Command Action
- `deleted()` — after soft/hard delete in a Command Action
- `error()` — validation failures, infrastructure errors

---

## 9. Error Handling Strategy

| Failure Mode | Exception | Handled By | User Experience |
|-------------|-----------|-----------|-----------------|
| Format/invalid input | `ValidationException` | Livewire error bag | Inline field errors |
| Business rule violation | `RejectedException` | Component try/catch | Flash error message |
| Infrastructure failure | `RuntimeException` (rethrown) | Component try/catch | Generic error message |

**Rule:** Business rules use `RejectedException`. Infrastructure failures use `RuntimeException`. Never use `RuntimeException` for business rules.

---

## 10. Technical Debt Annotations

| Annotation | Meaning | Convention |
|-----------|---------|-----------|
| `TODO(username, YYYY-MM-DD): message` | Planned work | Include author and date |
| `FIXME(username, YYYY-MM-DD): message` | Known bug | Include author and date |
| `HACK` | Suboptimal code that works | Must explain why |
| `XXX` | Danger — fragile or risky code | Must explain the risk |

---

## Phase Context

| Role | Skill |
|------|-------|
| **Upstream** | `context-awareness` (project orientation), `laravel-best-practices` (Laravel-specific) |
| **This skill** | **CODE WRITING** — writes PHP/Blade/JS following all conventions |
| **Downstream** | `test-writing` (verification), `pest-testing` (test writing), `doc-writing` (documentation), `sync-docs` (doc sync) |

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings | `python3 scripts/scan_conventions.py` |

Output: `scripts/outputs/{timestamp}-conventions.json`.

## Quality Gate — arch-guard

Validate all code against `arch-guard` rules before completing:
- Run `python3 scripts/scan_violations.py` for C1-C8, D1-D6
- Run `python3 scripts/scan_class_contracts.py` for class contracts
- Run `python3 scripts/scan_naming.py` for naming conventions
- See `arch-guard` skill for full rule reference

## References

| Topic | Location |
|-------|----------|
| Full conventions | `docs/conventions.md` |
| Architecture overview | `docs/architecture.md` |
| Action Triad pattern | `docs/architecture/action-pattern.md` |
| Entity pattern | `docs/architecture/entity-pattern.md` |
| DTO/Data pattern | `docs/architecture/data-pattern.md` |
| Model pattern | `docs/architecture/model-pattern.md` |
| Enum pattern | `docs/architecture/enum-pattern.md` |
| Exception pattern | `docs/architecture/exception-pattern.md` |
| Livewire pattern | `docs/architecture/livewire-pattern.md` |
| Policy pattern | `docs/architecture/policy-pattern.md` |
| Event pattern | `docs/architecture/event-pattern.md` |
| Cache pattern | `docs/architecture/cache-pattern.md` |
| Module index | `docs/modules/index.md` |
| Laravel best practices | `.agents/skills/laravel-best-practices/SKILL.md` |
| Coding rules (quick) | `.agents/skills/context-awareness/rules/coding-rules.md` |
| Architecture rules (quick) | `.agents/skills/context-awareness/rules/architecture-rules.md` |
