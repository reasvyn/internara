---
name: code-writing
description: "SDLC Phase: IMPLEMENTATION. PHP and Laravel code writing — strict types, Action Triad, Entity/DTO/Model contracts, naming conventions, security patterns, performance rules, and non-negotiable invariants."
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

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (locate the **governing spec**, map FR/NFR/UC IDs), **Size Triage** (S/M/L session
splitting), verification strategy, and commit format. This skill adds the PHP class-writing rules
in §2-§10 — nothing else.

Write code per the contracts and conventions below; register cache keys in `config/cache-keys.php`;
use `__()` for all user-facing strings. Before completing, run the arch-guard scanners
(`scripts/scan_*.py`) on the touched code (see the `arch-guard` skill).

---

## 1. Non-Negotiable Invariants

These rules MUST be followed. No exceptions.

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
| Exception | `{Name}Exception` | `RejectedException` |
| Event | `{Entity}{Actioned}` (past tense) | `InternshipCreated` |
| Listener | `{Verb}{Entity}` | `NotifyAdminsInternshipCreated` |
| Notification | `{Entity}{NotificationType}Notification` | `WelcomeNotification` |
| Console command | `{module}:{action}` | `system:health` |
| Route name | descriptive (mirror URL path) | `login`, `admin.users.index` |
| Config key | `snake_case` with `{file}.{key}` | `app.name` |
| Column/table | `snake_case` | `user_id`, `academic_years` |
| Boolean methods | `is`/`has`/`can`/`should` prefix | `isActive()`, `allowsLogin()` |
| Test method | Pest `test()` with `{SPECID}-{REQ}:` prefix | `test('SE5Q9-FR-A4: step() records success')` |
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
