---
name: code-refactoring
description: SDLC Phase: DESIGN / REFACTORING. Systematic refactoring patterns for all code layers — Actions, Entities, Models, Livewire, Controllers, Middleware, Services, Enums, Views. Focuses on clean code (SOLID, DRY, SOC) and enforcing architectural patterns across the entire codebase.
upstream: [audit-protocol, security-audit, feature-building]
downstream: [feature-building, pest-testing, sync-docs]
---

# Code Refactoring Skill

## When to Activate

Apply this skill when refactoring any code in the codebase — extracting business logic from fat
classes, eliminating code smells, enforcing clean code principles (SOLID, DRY, SOC, KISS), or
migrating toward the project's architectural patterns. Covers ALL layers:

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `audit-protocol` — code smell and pattern violation findings |
| | `security-audit` — security issue findings |
| | `feature-building` — refactoring subtasks from roadmap |
| **This skill** | **DESIGN / REFACTORING** — produces restructured code |
| **Downstream (output)** | `feature-building` — refactored code integrated into feature work |
| | `pest-testing` — tests for refactored code |
| | `sync-docs` — documentation updated after refactoring |
| **Phase** | [Planning] → [Analysis] → Design → [Implementation] → [Testing] → [Maintenance] |

- **Actions** (Command/Read/Process) — extract from Livewire/Controllers
- **Entities** (final readonly) — extract business rules from Models
- **Livewire components** — thin component rule, extract to Actions/Entities
- **Controllers** — thin controllers, delegate to Actions
- **Models** — remove business logic, add Entity bridges
- **Enums** — add LabelEnum/StatusEnum, consolidate scattered magic strings
- **Middleware** — extract inline request logic
- **Views** — extract repeated Blade partials, reduce logic in templates
- **Services** — migrate toward Action Triad

---

## Core Principles

### Clean Code Signals — When to Refactor

| Signal | Smell | Refactor Toward |
|---------|-------|-----------------|
| Method > 20 lines | Too many responsibilities | Extract method / Extract to Action |
| Class > 300 lines | God object | Extract class / Split by concern |
| Nested > 3 levels | Arrow anti-pattern | Early return / Extract method |
| `Model::create/update/delete` in Livewire | Business logic in UI layer | Extract to Command Action |
| `if/switch` on record status in Livewire | Business rule in UI layer | Extract to Entity method |
| `canX()`, `isY()` on Model | Business logic in persistence layer | Extract to Entity |
| Duplicate query logic in 3+ places | DRY violation | Extract to Read Action or Model scope |
| `app()->make()` or `new Class()` in component | DI violation | Inject via constructor/method parameter |
| `catch (\Exception $e)` | Too broad | Catch specific exception types |
| `TODO`/`FIXME`/`HACK`/`XXX` without date | Unknown tech debt | Add date or resolve |
| Public method that is never called | Dead code | Remove |
| Import that is never used | Dead import | Remove |
| Magic string/number used in 3+ places | Magic constant | Extract to constant/enum |

### Refactoring Safety Rules

1. **Behavior preservation** — refactoring changes structure, not behavior. Tests must pass before
   and after. If tests don't exist, write them first (characterization tests).
2. **One concern per commit** — never mix refactoring with feature work or bug fixes.
3. **Small, frequent commits** — each commit is a single, verifiable transformation.
4. **Compile/test after each step** — never go more than 5 minutes without running the test suite.
5. **Strangler pattern** — new code alongside old, route traffic gradually, remove old when safe.

---

## Refactoring Workflows

### Workflow A — Extract Business Logic to Action

**When:** Livewire component, Controller, or Middleware contains `Model::create/update/delete`,
`DB::transaction()`, inline validation, or business logic.

**Archetype:** The "God Livewire" or "Fat Controller"

#### Step 1 — Identify the Operation

Find the inline persistence call. Determine the Action Triad type:

| Operation | Action Type |
|-----------|-------------|
| `Model::create()` | Command |
| `Model::update()` | Command |
| `Model::delete()` | Command |
| State transition (`status = 'x'`) | Command |
| Complex aggregation query | Read |
| Multi-step workflow | Process |

#### Step 2 — Create the Action Class

```
app/{Module}/{SubModule}/Actions/{Verb}{Entity}Action.php
```

```php
declare(strict_types=1);

namespace App\{Module}\{SubModule}\Actions;

use App\Core\Actions\BaseCommandAction; // or BaseReadAction / BaseProcessAction
use App\{Module}\{SubModule}\Models\{Entity};

final class {Verb}{Entity}Action extends BaseCommandAction
{
    public function __construct(
        protected readonly {Dependency} $dependency,
    ) {}

    public function execute({Entity} ${entity}, {Entity}Data $data): ActionResponse
    {
        ${entity}->as{Entity}()->ensureCan{Verb}();

        return $this->transaction(function () use (${entity}, $data) {
            // mutation logic

            $this->log('{entity}_{verbed}', ${entity});
            event(new {Entity}{Vebed}(${entity}));

            return $this->respondUpdated(${entity});
        });
    }
}
```

**Key rules:**
- Single `execute()` method — never add a second public method
- **Accept DTO for 3+ params** — simple ops may use typed scalars. Never raw `array`
- **Return `ActionResponse`** when caller needs message/redirect/errors. Model return OK for simple cases
- Command: `$this->transaction()` + `$this->log()`
- Read: NO `transaction()` or `log()`
- Process: compose other Actions, handle partial failure
- Business rules via Entity method + `RejectedException`

#### Step 3 — Move Validation

Copy validation rules from the component into the Action:

```php
// In the Action's execute() or a helper:
Validator::make($data, [
    'name' => ['required', 'string', 'max:255'],
])->validate();
```

The component may keep UX validation, but the Action is the authoritative validation layer.

#### Step 4 — Add Transaction + Log

```php
return $this->transaction(function () use (${entity}, $data) {
    ${entity}->update($data);

    $this->log('{entity}_{action}', ${entity}, [
        '{entity}_id' => ${entity}->id,
    ]);

    return ${entity};
});
```

#### Step 5 — Dispatch Event

```php
event(new {Entity}{Actioned}(${entity}));
```

#### Step 6 — Delegate Business Rules to Entity

Replace:
```php
if ($record->status === 'active' && $record->start_date <= now()) { ... }
```

With:
```php
$record->as{Entity}()->ensureCan{Action}(); // throws RejectedException
```

#### Step 7 — Inject into Caller

```php
// Before:
$user = User::create([...]);

// After:
public function save(CreateUserAction $action): void
{
    $dto = UserData::from($this->form->toArray());
    $result = $action->execute($dto);
    flash()->success($result->message);
}
```

#### Step 8 — Handle Exceptions

```php
public function save(CreateUserAction $action): void
{
    try {
        $dto = UserData::from($this->form->toArray());
        $result = $action->execute($dto);
        flash()->success($result->message);
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

**Verification checklist:**
- [ ] Action created in correct module/submodule directory
- [ ] Extends correct base class (Command/Read/Process)
- [ ] Single `execute()` method
- [ ] **Command/Process Action accepts DTO for 3+ params — never raw `array`**
- [ ] **Command/Process Action returns `ActionResponse` for structured feedback**
- [ ] DB writes wrapped in `$this->transaction()`
- [ ] `$this->log()` called after mutation
- [ ] Event dispatched for significant state change
- [ ] Business rules delegated to Entity
- [ ] `RejectedException` for rule violations
- [ ] Caller injects Action via method parameter
- [ ] Policy check precedes Action call
- [ ] Test file created (happy path + edge cases)

---

### Workflow B — Extract Business Rules to Entity

**When:** Model accumulates `canX()`, `isY()`, `hasZ()` methods, or Actions contain inline conditionals
on record state.

**Archetype:** The "Fat Model" / "Anemic Action"

#### Step 1 — Identify Conditionals

Find every inline business rule in the module's Actions, Policies, and Livewire components:

```
if ($model->status === 'x')
if ($model->start_date > now())
if (! $model->is_active)
```

Each distinct conditional group is a candidate for an Entity method.

#### Step 2 — Create the Entity

```
app/{Module}/{SubModule}/Entities/{Name}Entity.php
```

```php
declare(strict_types=1);

namespace App\{Module}\{SubModule}\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class {Name}Entity extends BaseEntity
{
    public function __construct(
        private string $status,
        private ?Carbon $startDate,
        private ?Carbon $endDate,
        private bool $isActive,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
            startDate: $model->start_date,
            endDate: $model->end_date,
            isActive: (bool) $model->is_active,
        );
    }

    public function canBeActivated(): bool
    {
        return $this->status === 'draft' && $this->isActive === false;
    }

    public function isWithinWindow(): bool
    {
        if ($this->startDate === null || $this->endDate === null) {
            return false;
        }

        $now = new Carbon;

        return $now->between($this->startDate, $this->endDate);
    }
}
```

**Entity purity rules:**
- `final readonly` — no inheritance, no mutation
- All properties `private` typed — expose via methods
- Zero I/O — no DB, HTTP, cache, events, facades
- Only `Carbon\Carbon` and `Illuminate\Database\Eloquent\Model` (in `fromModel`) allowed as framework deps
- Business methods return `bool` answers: `canX()`, `isY()`, `hasZ()`

#### Step 3 — Add Named Bridge to Model

```php
// On the Model:
public function as{Name}(): {Name}Entity
{
    return {Name}Entity::fromModel($this);
}
```

The accessor name describes the **business role**, not the class name:
- ✅ `asApprentice()` — role is "apprentice"
- ✅ `asPeriod()` — role is "period"
- ❌ `asEntity()` — too generic

A Model may expose **multiple** entities for different roles:
```php
public function asRegistrationState(): RegistrationState { ... }
public function asCapacity(): CapacityEntity { ... }
```

#### Step 4 — Replace Inline Conditionals

**Before (in Action):**
```php
if ($entry->status === 'draft' && ! $entry->is_locked) {
    $entry->update(['status' => 'submitted']);
}
```

**After (in Action):**
```php
$entry->as{Name}()->ensureCanSubmit(); // throws RejectedException
$entry->update(['status' => 'submitted']);
```

Where `ensureCanSubmit()` combines `canSubmit()` check + throw:
```php
public function canSubmit(): bool
{
    return $this->status === 'draft' && ! $this->isLocked;
}

public function ensureCanSubmit(): void
{
    if (! $this->canSubmit()) {
        throw new RejectedException('Cannot submit in current state.');
    }
}
```

#### Step 5 — Migrate Callers

Update all Actions, Policies, and Livewire components that used inline conditionals:
- Replace `if ($m->status === 'x')` with `$m->as{Name}()->can{Action}()`
- Replace `throw new \Exception(...)` with `$m->as{Name}()->ensureCan{Action}()`

#### Step 6 — Clean Up Model

Remove the business rule methods from the Model:
```php
// ❌ Remove from Model:
public function isActive(): bool { return $this->status === 'active'; }

// ✅ Keep on Model:
public function scopeActive(Builder $query): Builder { ... }
public function asState(): StateEntity { return StateEntity::fromModel($this); }
```

**Verification checklist:**
- [ ] Entity is `final readonly` extending `BaseEntity`
- [ ] `fromModel()` extracts only the fields needed for business rules
- [ ] Bridge method name describes the role (`as{Role}()`)
- [ ] Business rule methods return `bool` (not raw state)
- [ ] Entity has zero DB/HTTP/cache/event dependencies
- [ ] All inline conditionals in module migrated to Entity methods
- [ ] Model cleaned of business rule methods
- [ ] Test file created for Entity (unit, no DB)

---

### Workflow C — Thin Livewire Component

**When:** Livewire component has inline DB calls, business rules, side effects, or exceeds ~300 lines.

**Archetype:** The "Fat Component"

#### Step 1 — Identify Misplaced Code

Categorize every line that doesn't belong in a Livewire component:

| Category | Examples | Extract to |
|----------|----------|------------|
| Business logic | `Model::create()`, `Model::update()`, `DB::transaction()` | Command Action |
| Business rules | `if ($status === 'x')`, date comparisons | Entity method |
| Complex queries | Aggregations, cross-module data assembly | Read Action |
| Side effects | `event()`, `Notification::send()`, `Log::info()` | Action + Listener |
| Repeated UI | Same table/modal/form in 2+ components | Blade component / Trait |
| Static helpers | `public static function formatX()` | Support class |
| Form state 5+ fields | Inline `$this->property` validation | Form Object |

#### Step 2 — Extract Business Logic to Action

Follow Workflow A. Each `Model::create/update/delete` becomes a Command Action.

```php
// Before:
public function save(): void
{
    $this->validate();
    User::create($this->form->toArray());
    flash()->success(__('created'));
}

// After:
public function save(CreateUserAction $action): void
{
    $this->form->validate();
    $action->execute($this->form->toArray());
    flash()->success(__('created'));
}
```

#### Step 3 — Extract Business Rules to Entity

Follow Workflow B. Replace inline status checks with `$model->as{Entity}()->can{Action}()`.

#### Step 4 — Extract Repeated UI Patterns

If the same table, modal, or form appears in 2+ components:

```blade
{{-- Extract to resources/views/components/{name}.blade.php --}}
@props(['title', 'message'])
<x-mary-modal wire:model="showModal">
    <x-mary-header :title="$title" />
    <p>{{ $message }}</p>
</x-mary-modal>
```

For shared behavior (sorting, selection), use a Concern trait:

```php
trait WithBulkActions
{
    public array $selectedIds = [];

    public function selectAll(): void { ... }
    public function clearSelection(): void { ... }
}
```

#### Step 5 — Extract Complex Forms

Forms with 5+ fields or conditional validation:

```
app/{Module}/Livewire/Forms/{Name}Form.php
```

```php
class {Name}Form extends Form
{
    public string $name = '';
    public string $code = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:table,code',
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
        ];
    }
}
```

#### Step 6 — Verify Thin Component

After extraction, the component should contain ONLY:

- [ ] Public properties for UI state (form bindings, modal visibility, search)
- [ ] `$this->validate()` for UX feedback only
- [ ] Delegation to Actions via method parameter injection
- [ ] Read-only queries in `render()` (or via Read Action)
- [ ] `$this->authorize()` in relevant methods
- [ ] `flash()->success()` / `flash()->error()` for user feedback

**Verification checklist:**
- [ ] No `Model::create/update/delete` in component
- [ ] No `DB::transaction()` in component
- [ ] No inline business rules on record state
- [ ] No side effects (`event()`, `Notification::send()`, `Log::info()`)
- [ ] No `app()->make()` or `new Action()`
- [ ] No maryUI Toast methods (`$this->success()`, `$this->error()`)
- [ ] No static helper methods
- [ ] `try/catch` catches `RejectedException` specifically
- [ ] Forms with 5+ fields extracted to Form Object

---

### Workflow D — Thin Controller / Non-Livewire Endpoint

**When:** A Controller (rare in this Livewire-dominant project) has business logic, inline queries,
or multiple responsibilities. Controllers exist mainly for non-interactive endpoints (webhooks,
file downloads, simple redirects).

**Archetype:** The "Fat Controller"

#### Step 1 — Delegate to Actions

```php
// Before:
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([...]);
    $user = User::create($validated);
    $user->assignRole('student');
    event(new UserRegistered($user));

    return redirect()->route('users.index');
}

// After:
public function store(CreateUserAction $action, Request $request): RedirectResponse
{
    $user = $action->execute($request->validated());

    return redirect()->route('users.index');
}
```

#### Step 2 — Validation

For controller endpoints, use inline `$request->validate()` for simple cases or a Form Object
(`app/{Module}/Livewire/Forms/{Name}Form.php`) when the same validation rules are shared with
a Livewire component. Do NOT create a dedicated FormRequest class — this project uses Livewire's
Form Objects as the standard validation layer. If a Controller needs validation that already
exists in a Form Object, extract the rules into a reusable location (e.g., Entity static
`rules()` method or a dedicated Rules class).

```php
// Preferred: shared Form Object
public function store(CreateUserAction $action, Request $request): RedirectResponse
{
    $data = (new SomeForm)->validate($request->all());
    $user = $action->execute($data);

    return redirect()->route('users.index');
}
```

**Verification checklist:**
- [ ] No `Model::create/update/delete` in controller
- [ ] No business logic — delegate to Action
- [ ] No inline queries for complex reads — delegate to Read Action
- [ ] Validation reuses existing Form Objects where possible

---

### Workflow E — Fix Exception Handling

**When:** Code throws `RuntimeException` for business rules, or catches exceptions too broadly.

#### Step 1 — Replace Business Rule Exceptions

```php
// ❌ Wrong:
throw new RuntimeException('User cannot be deleted.');

// ✅ Correct:
throw new RejectedException('User cannot be deleted because they have active internships.');
```

#### Step 2 — Narrow Catch Blocks

```php
// ❌ Wrong — swallows everything:
try {
    $action->execute($data);
} catch (\Exception $e) {
    flash()->error(__('generic_error'));
}

// ✅ Correct — business vs infrastructure:
try {
    $action->execute($data);
} catch (RejectedException $e) {
    flash()->error($e->getMessage());
} catch (\Throwable $e) {
    flash()->error(__('generic_error'));
}
```

#### Step 3 — Use Correct Exception Type

| Scenario | Exception |
|----------|-----------|
| Business rule violation | `RejectedException` |
| Input validation | `ValidationFailedException` (via `Validator::validate()`) |
| Resource not found | `NotFoundException` |
| Duplicate / conflict | `ConflictException` |
| Unauthorized | `UnauthorizedException` |
| Rate limited | `RateLimitException` |
| Infrastructure failure | `HandlesActionErrors` → logs + rethrows as `RuntimeException` |

**Verification checklist:**
- [ ] No `throw new RuntimeException(...)` for business rules
- [ ] Livewire catches `RejectedException` before `Throwable`
- [ ] `catch (\Exception $e)` replaced with specific types

---

### Workflow F — Clean Up Code Smells

#### Remove Dead Code

```bash
# Find unused private methods
rg -n "private function" app/ --type php | grep -v "test"

# Find unused imports
vendor/bin/phpstan analyse --no-progress | grep "unused"

# Find dead assignments
rg "=\s*null" app/ --type php | grep -v "??\|?->\|default\|initial"
```

#### Extract Magic Strings to Enum

```php
// ❌ Wrong:
if ($user->role === 'super_admin') { ... }

// ✅ Correct:
if ($user->hasRole(Role::SUPER_ADMIN)) { ... }
```

#### Extract Magic Numbers to Constant

```php
// ❌ Wrong:
if ($attempts > 5) { ... }

// ✅ Correct:
private const int MAX_LOGIN_ATTEMPTS = 5;
if ($this->attempts > self::MAX_LOGIN_ATTEMPTS) { ... }
```

#### Flatten Nested Conditionals

```php
// ❌ Wrong — 4 levels deep:
public function canAccess(User $user): bool
{
    if ($user->isActive()) {
        if ($this->isPublished()) {
            if ($user->hasRole('teacher')) {
                return true;
            }
        }
    }
    return false;
}

// ✅ Correct — early returns:
public function canAccess(User $user): bool
{
    if (! $user->isActive()) {
        return false;
    }
    if (! $this->isPublished()) {
        return false;
    }
    return $user->hasRole('teacher');
}
```

**Verification checklist:**
- [ ] No dead code (unused methods, imports, variables)
- [ ] Magic strings consolidated to enums/constants
- [ ] Magic numbers extracted to named constants
- [ ] Nesting depth ≤ 3 levels
- [ ] No duplicated code blocks (DRY)

---

## Refactoring by Code Smell

| Smell | Refactoring Technique | Workflow |
|-------|----------------------|----------|
| Long Method (>20 lines) | Extract Method, Extract to Action | A |
| Large Class (>300 lines) | Extract Class, Split by Concern | A, B, C |
| Primitive Obsession | Replace with Enum, Value Object | F |
| Data Clump | Extract Parameter Object | B (Entity) |
| Shotgun Surgery | Move Method, Move Field | B |
| Feature Envy | Move Method to Entity | B |
| Switch Statements | Replace with Polymorphism / Enum | B, F |
| Temporary Field | Extract Class | B |
| Middle Man | Remove Middle Man, Inline | A (inject Action directly) |
| Inappropriate Intimacy | Move Method, Extract Class | B |
| Alternative Classes with Different Interface | Rename Method, Extract Interface | F |
| Lazy Class | Inline Class, Collapse Hierarchy | F |
| Speculative Generality | Remove Dead Code | F |
| Message Chains | Hide Delegate, Extract Method | B |
| Divergent Change | Extract Class (separate concerns) | A, B |

---

## Cross-Cutting Verification

### After Every Refactoring

- [ ] `vendor/bin/pint --format agent` — code style clean
- [ ] `php artisan test --compact` — all tests pass
- [ ] No new `dd()`, `dump()`, `ray()`, `var_dump()`, `die()` introduced
- [ ] No new `TODO`/`FIXME` without date
- [ ] Imports sorted (Pint does this automatically)
- [ ] `declare(strict_types=1)` present in new files

### Pattern Enforcement After Refactoring

- [ ] Livewire: no inline DB mutations, no Entity access, no business rules, no side effects
- [ ] Action: single `execute()`, correct base class, **accepts DTO**, **returns ActionResponse**, `transaction()` + `log()` for commands
- [ ] DTO: `final readonly` extends `BaseData`, carries only scalars/enums/Carbon — never Models
- [ ] Entity: `final readonly`, zero I/O, zero Action/Service imports, `fromModel()` factory
- [ ] Model: no business rule methods, `#[Fillable]`, entity bridges
- [ ] Enum: `implements LabelEnum`, state machines `implements StatusEnum`
- [ ] Exception: business rules → `RejectedException`, not `RuntimeException`

---

## References

| Document | Purpose |
|----------|---------|
| `docs/architecture.md` | 4-layer architecture, Action Triad |
| `docs/conventions.md` | All coding conventions |
| `docs/architecture/action-pattern.md` | Action Triad deep-dive |
| `docs/architecture/entity-pattern.md` | Entity-Model separation |
| `docs/architecture/livewire-pattern.md` | Thin component rule |
| `docs/architecture/exception-pattern.md` | Dual exception hierarchy |
| `docs/architecture/enum-pattern.md` | LabelEnum, StatusEnum |
| `docs/architecture/model-pattern.md` | BaseModel, Fillable, scopes |
| `docs/conventions.md §8` | DI etiquette |
| `docs/conventions.md §10` | Testing conventions |
| `docs/architecture/service-pattern.md` | Service vs Support vs Action (infra vs domain vs static) |
| `docs/architecture/support-pattern.md` | Support utilities: static-only, no constructor injection |
| `AGENTS.md` | Project invariants |
| `.agents/skills/feature-building/SKILL.md` | Feature implementation workflow |
