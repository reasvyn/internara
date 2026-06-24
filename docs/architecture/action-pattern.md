# Action Triad Pattern Reference

> **Last updated:** 2026-06-10
>
> Comprehensive deep-dive on the Command/Read/Process Action Triad — the single most important
> architectural decision in Internara. This document covers every facet of the Action pattern:
> contracts, mechanics, conventions, and migration workflows.
>
> For the high-level overview, see [Modular Pattern Reference](modular-pattern.md). For the ADR
> that formalised this pattern, see [ADR-003](../adr/adr-action-pattern-over-services.md).

---

## Action Triad Overview

### Intent

Replace traditional Service classes (god objects with multiple public methods) with three distinct
action types, each owning exactly one business operation. The triad mirrors CQRS at the class level
— separate mutation paths from read paths — without the infrastructure cost of separate databases or
event sourcing.

### The Three Types

| Type | Purpose | Base Class | Transaction | Logging |
|------|---------|-----------|-------------|---------|
| **Command** | Every write — create, update, delete, state transitions | `BaseCommandAction` | Required | Required |
| **Read** | Complex queries, aggregations, dashboard assembly | `BaseReadAction` | Never | Never |
| **Process** | Multi-step orchestration composing Command/Read Actions | `BaseProcessAction` | Required | Required |

### Base Class Utilities

Each base class provides utilities tailored to its action type:

| Base Class | Utilities |
|---|---|
| `BaseCommandAction` | `respond()` / `respondDeleted()` / `respondError()` — structured `ActionResponse` returns; `validate()` — inline `Validator::validate()`; `authorize()` — `Gate::authorize()` shortcut; `flash()` — flash message helper; `fail()` — throw `RejectedException`; inherits `transaction()`, `log()`, `dispatchEvent()` from `BaseAction` |
| `BaseReadAction` | `remember()` / `rememberForever()` / `forget()` — caching with auto key generation; `cacheKey()` — module-scoped cache key builder; `mask()` — PII masking; `paginate()` — consistent `LengthAwarePaginator`; `format()` — standardised response envelope; `withErrorHandling()` from `HandlesActionErrors` trait |
| `BaseProcessAction` | `step()` — wrapped step execution with success/failure tracking; `trackProgress()` / `getProgress()` — progress percentage; `getResults()` — per-step result inspection; `allStepsSucceeded()` — quick status check; `fail()` — throw `RejectedException`; `notify()` — send `Notification`; `logProgress()` — log with step context; inherits `transaction()`, `log()`, `dispatchEvent()` from `BaseAction` |

### Clean Code Rationale

Service classes with multiple public methods share one file and one constructor. They accumulate
mixed responsibilities, shared mutable state, and branching conditionals. Testing a single method
means loading the entire service. Actions invert this: one class per operation, testable in
isolation, discoverable by name alone.

The triad refines this further. Not all operations need transactions. Not all need logging. Forcing
every operation into the same mould adds unnecessary ceremony to reads. The triad gives each
operation type the contract it actually needs.

---

## Command Actions

### Intent

The sole entry point for every mutation in the system. If data changes in the database, a Command
Action did it.

### Contract

- MUST extend `BaseCommandAction` (extends `BaseAction`)
- MUST wrap all database operations in `$this->transaction()`
- MUST call `$this->log()` after successful mutation
- MUST be preceded by a policy check in the calling layer
- MUST NOT contain inline `canX()` checks — delegate to Entity methods and throw `RejectedException`
- MUST throw `RejectedException` for business rule violations, never `RuntimeException`
- MUST have exactly one public method: `execute()`
- **MUST accept a DTO (`BaseData`) as the primary parameter** — never raw `array`
- **MUST return `ActionResponse`** — never return the Model directly
- SHOULD dispatch a module event for significant state changes

### Structure

```
declare(strict_types=1);

namespace App\{Module}\{SubModule}\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\{Module}\{SubModule}\Models\{Entity};
use App\{Module}\{SubModule}\Data\{Entity}Data;

class {Verb}{Entity}Action extends BaseCommandAction
{
    public function __construct(
        protected readonly {Dependency}Action $dependency,
    ) {}

    public function execute({Entity} ${entity}, {Entity}Data $data): ActionResponse
    {
        ${entity}->as{Entity}()->ensureCan{Verb}();

        return $this->transaction(function () use (${entity}, $data) {
            // mutation logic

            $this->log('{entity}_{verbed}', ${entity}, [
                '{entity}_id' => ${entity}->id,
            ]);
            event(new {Entity}{Vebed}(${entity}));

            return $this->respondUpdated(${entity});
        });
    }
}
```

### Return Type Conventions

- Create: `ActionResponse::created()` — wraps the created Model
- Update: `ActionResponse::updated()` — wraps the updated Model
- Delete: `ActionResponse::deleted()` — success message
- State transition: `ActionResponse::updated()` with entity data
- State transition: returns the Model
- Complex operations: return an array, DTO, or `ActionResponse`

---

## Read Actions

### Intent

Encapsulate complex read operations — aggregation, filtering, cross-module data assembly, dashboard
statistics — that are too heavy for inline `Model::query()` in a Livewire component.

### Contract

- MUST extend `BaseReadAction`
- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()`
- Single public `execute()` method — never add a second public method
- SHOULD return typed objects or collections, never raw arrays
- MUST pass through authorization unless the calling layer already authorized

### When to Use vs. Inline Queries

Simple `Model::find()` or single `where` clauses should remain inline in Livewire. Use a Read Action
for:
- Aggregation with multiple conditions
- Cross-module data assembly
- Dashboard with charts and stats
- Queries with complex authorization rules

### Naming Convention

`Read{Entity}Action`

---

## Process Actions

### Intent

Orchestrate multi-step workflows that coordinate multiple Command and Read Actions. The "how" of
complex business processes.

### Contract

- MUST extend `BaseProcessAction` (extends `BaseAction` — transaction + logging at the process level)
- MUST compose other Actions via constructor injection
- MUST handle partial failure — if step N of M fails, what happens to earlier steps?
- SHOULD emit a single module event representing the completed process
- MUST NOT duplicate business logic that already exists in Command Actions

### Partial Failure Handling

Every Process Action must consider what happens when a composed Action fails. There is no
one-size-fits-all answer — the business decides:

- **All-or-nothing:** The transaction rolls back everything. The caller retries after fixing the
  issue. This is the most common approach.
- **Compensating action:** A later step fails after earlier steps committed (e.g., an API call that
  can't be rolled back). Execute a compensating action to undo.
- **Flag-and-continue:** Mark the process as partially complete, log the failure, and let an admin
  resolve it manually.

The default approach is **all-or-nothing** via `$this->transaction()`. Compensating actions and
flag-and-continue are documented in the Process Action's docblock.

---

## Transaction Safety

### BaseAction::transaction() Mechanics

The `transaction()` method handles three critical concerns:

**1. Nested transaction detection:**
When a Process Action calls `$this->transaction()` which calls a Command Action that also calls
`$this->transaction()`, the inner call detects it is already inside a transaction via
`DB::transactionLevel() > 0` and executes the callback directly without wrapping. This prevents
Laravel's `DB::transaction()` from creating a savepoint or committing prematurely.

**2. Deferred event dispatch:**
Events are collected via `$this->dispatchEvent()` into a `$pendingEvents` array and dispatched
only after the transaction commits (via `dispatchPendingEvents()`). This prevents listeners from
seeing uncommitted data.

**3. Deadlock retry:**
The outer `DB::transaction()` retries on serialisation failures. This is important for
high-concurrency workflows.

### Lifecycle Hooks

```php
protected function beforeExecute(): void {}  // Called before every transaction
protected function afterExecute(mixed $result): void {}  // Called after every transaction
```

Override these in Command/Process Actions to set up context or clean up resources. Most Actions
do not need them.

---

## Logging Protocol

### The log() Method

Every Command and Process Action MUST call `$this->log()` after a successful mutation. The method
writes to both the system log and activity log:

```php
protected function log(string $action, ?Model $subject = null, array $payload = []): void
{
    SmartLogger::info($action)
        ->event($action)
        ->module($this->moduleName())
        ->about($subject)
        ->withPayload($payload)
        ->withPiiMasking()
        ->both()
        ->save();
}
```

### What to Log

| Data Point | Included? | Notes |
|------------|-----------|-------|
| Action identifier | Always | `snake_case` describing what happened |
| Subject model | Always | The affected entity |
| Context payload | Recommended | IDs, status values, relevant metadata |
| PII | Masked | `withPiiMasking()` handles this |

### Where NOT to Log

Read Actions must NEVER call `log()`. If you need to log a read operation (e.g., for analytics),
use an explicit SmartLogger call outside the Action — never via `$this->log()`.

---

## Event Dispatch

### Pattern

Command and Process Actions dispatch module events for significant state changes. The pattern is:

```php
$this->transaction(function () use ($data) {
    // ... mutation ...
    $this->log('{entity}_{action}', ${entity});
    event(new {Entity}{Actioned}(${entity}));
    return ${entity};
});
```

### dispatchEvent() vs event()

Two mechanisms exist:

| Method | Behaviour | When to Use |
|--------|-----------|-------------|
| `$this->dispatchEvent(BaseEvent $event)` | Queues the event; dispatched after transaction commits | Inside `transaction()` callback |
| `event($event)` or `Event::dispatch()` | Dispatches immediately | After `transaction()` returns |

In most cases, use `event()` inside the `transaction()` callback — the deferred dispatch in
`BaseAction::transaction()` handles the "dispatch after commit" concern automatically.

Use `$this->dispatchEvent()` when you want to collect events and guarantee they fire only after
transaction success, even in nested contexts.

### Event-to-Action Ratio

| Action Type | Events |
|-------------|--------|
| Command (create/update/delete) | 0–1 recommended |
| Command (state transition) | 1 required |
| Command (notification-only) | 0 |
| Process | 1 required (the completed-process event) |

---

## Error Handling

### Three Failure Modes

The error-handling strategy distinguishes three distinct failure modes:

| Failure Mode | Exception | Handled By | User Experience |
|-------------|-----------|------------|-----------------|
| Format/invalid input | `ValidationException` | Livewire error bag | Inline field errors |
| Business rule violation | `RejectedException` | Component try/catch | Flash error message |
| Infrastructure failure | `RuntimeException` (rethrown) | Component try/catch | Generic error message |

### HandlesActionErrors Trait

Known exception types pass through unmodified. Unknown `Throwable` is logged to the system log
(with full context) and rethrown as a generic `RuntimeException`. The trait is used by `BaseAction`
and is available to any class that needs it.

### Error Handling Rules

1. Business rule violations → `RejectedException` (never bare `RuntimeException`)
2. Format validation → `Validator::validate()` → `ValidationException` (automatic)
3. Infrastructure failure → `HandlesActionErrors` logs + rethrows as `RuntimeException`
4. `RejectedException` is ONLY for business rules — do not use it for validation or infrastructure errors

---

## Validation Strategy

### Two Layers of Validation

| Layer | Purpose | Mechanism | Authoritative? |
|-------|---------|-----------|----------------|
| Livewire component | User experience — inline errors, button state | `$this->validate()` | No (UX only) |
| Action | Data integrity — last gate before persistence | `Validator::make()->validate()` | Yes |

### Why Validate in Both Layers

Livewire validation runs in the browser context and can be bypassed — accidentally (JavaScript
disabled) or intentionally (crafted requests). The Action runs server-side and cannot be circumvented
because it's the last validation gate before persistence. This is defence in depth.

### Types of Validation

| Concern | Tool | Exception |
|---------|------|-----------|
| Format (required, email, length) | `Validator::validate()` | `ValidationException` |
| Uniqueness constraints | `Validator` with `unique:` rule | `ValidationException` |
| State-based business rules | Entity method + `RejectedException` | `RejectedException` |
| Authorisation | Policy `Gate` check | `AuthorizationException` |

### Where Rules Live

- **Shared validation rules** across multiple Actions → Entity static `rules()` methods
- **Action-specific rules** → inline `Validator::make()` in the Action
- **Form-level rules** → Form Object `rules()` method (for UX, re-validated in Action)
- **HTTP-level rules** → FormRequest `rules()` method (for controller endpoints)

---

## ActionResponse Contract

### Intent

Standardise the return envelope from Actions so every caller — Livewire, Controller, Artisan command
— handles results the same way. Not every Action must return an `ActionResponse`; simple create/
update/delete operations may return the model directly. Use `ActionResponse` when the caller needs
structured feedback beyond the model.

### Factory Methods

```php
ActionResponse::ok($data, 'Operation completed');          // Generic success
ActionResponse::created($model, '{Entity} created');       // Resource created
ActionResponse::updated($model, '{Entity} updated');       // Resource updated
ActionResponse::deleted('{Entity} removed');               // Resource deleted
ActionResponse::error('Something went wrong', $errors);    // Failure
```

All factory methods accept an optional message. `created()`, `updated()`, and `deleted()` have
sensible defaults via `__()` translation keys.

### WithRedirect

```php
return ActionResponse::created(${entity})
    ->withRedirect(route('{entities}.show', ${entity}));
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `success` | `bool` | Whether the operation succeeded |
| `data` | `mixed` | The result model, collection, or array |
| `message` | `?string` | User-facing message |
| `redirect` | `?string` | URL to redirect to |
| `errors` | `array` | Validation or business errors |

### JSON Serialization

`jsonSerialize()` automatically converts Models to arrays via `->toArray()` and strips `null`/empty
values.

### When to Use ActionResponse vs. Direct Return

| Return Type | When |
|-------------|------|
| `Model` directly | Simple create/update — caller just needs the model |
| `ActionResponse` | Caller needs structured feedback (message, redirect, error context) |
| `void` | Delete operations |
| `array` | Complex results that don't map to a single model |
| `Collection` | Read Action returning multiple results |
| `int` / `bool` | Simple counters or existence checks in Read Actions |

---

## DTO Migration Path

### Intent

Data Transfer Objects (DTOs) provide type safety for Action parameters. Instead of passing `array $data`
around, a DTO gives you named, typed parameters, IDE autocompletion, and compile-time safety.

### BaseData

All DTOs extend `BaseData`:

```php
abstract readonly class BaseData implements JsonSerializable
{
    public function toArray(): array { ... }
    public function jsonSerialize(): array { ... }
    public function only(string ...$keys): array { ... }
    public function except(string ...$keys): array { ... }
    public function merge(array $overrides): static { ... }
    public static function fromArray(array $data): static { ... }
    public static function from(mixed $source): static { ... }
}
```

### Three-Phase Migration Path

- **Phase 1 — Rapid development:** accept `array`
- **Phase 2 — Migration:** accept both via union type, normalise internally
- **Phase 3 — Final:** DTO only

### When to Introduce a DTO

- The Action has multiple parameters
- The Action has multiple callers
- The parameters have stabilised (no longer in rapid prototyping)
- The Action is part of a public API consumed by other modules

### fromArray() and from()

`BaseData::fromArray()` maps `snake_case` array keys to `camelCase` constructor parameters.

`BaseData::from()` accepts arrays or objects with `toArray()`.

---

## Naming Conventions

### Action Names

| Type | Pattern |
|------|---------|
| Command | `{Verb}{Entity}Action` |
| Read | `Read{Entity}Action` |
| Process | `Process{Entity}Action` |

### File Location

```
app/{Module}/{SubModule}/Actions/{ClassName}.php
app/{Module}/Actions/{ClassName}.php  ← cross-submodule
```

### Common Verbs

`Create`, `Update`, `Delete`, `Activate`, `Deactivate`, `Finalize`, `Verify`, `Submit`, `Approve`,
`Reject`, `Upload`, `Set`, `Reset`, `Generate`, `Validate`, `Provision`, `Setup`, `Install`,
`Recover`, `Initialize`, `Toggle`, `Lock`, `Unlock`, `Score`, `Evaluate`, `Renew`, `Terminate`,
`Batch`, `Bypass`, `Notify`.

### File Header Order

1. `declare(strict_types=1)`
2. Namespace
3. Use statements (`BaseCommandAction`, `BaseReadAction`, `BaseProcessAction`, `RejectedException`, Model, Validator, dependencies)
4. Class declaration extending the appropriate base class
5. Constructor with `protected readonly` promotion for injected dependencies
6. Single `execute()` method

### Class Name Rule

The class name must never be repeated in the path:

- ✅ `app/{Module}/Models/{Entity}.php`
- ❌ `app/{Module}/{Entity}/{Entity}/Actions/Create{Entity}Action.php`

---

## Testing Actions

### Scope Isolation

Every Action has its own test file. One class → one test file.

### File Structure

```
tests/Feature/{Module}/{SubModule}/{Name}Test.php
```

### What to Test

| Concern | How |
|---------|-----|
| Happy path | Execute → assert model state/event/log |
| Business rule violation | Assert `RejectedException` is thrown |
| Validation failure | Assert `ValidationException` is thrown |
| Side effects | Assert `event()` dispatched, `log()` called |
| Partial failure (Process) | Test rollback when a composed Action fails |
| Policy enforcement | Test via feature test with authorised/unauthorised users |

### Testing Conventions

- Use `LazilyRefreshDatabase` over `RefreshDatabase`
- Use `assertModelExists()` over `assertDatabaseHas()`
- Use Pest `it()` with descriptive strings
- Mock SmartLogger in unit tests, use real SmartLogger in feature tests
- Do NOT test Eloquent relationships or model scopes through Actions — test them separately

---

## Action Extraction Workflow

### When to Extract

Extract business logic from Livewire components or Controllers into an Action when you see:

- `Model::create()`, `Model::update()`, `Model::delete()` inside a component
- `DB::transaction()` call in a component
- `Mail::send()` or `Notification::send()` in a component (unless trivial)
- `if`/`switch` on record status or state in a component
- Any inline validation beyond simple required-field checks
- Business logic that you need to test independently

### Step-by-Step Extraction

**1. Identify the operation.**

Find the inline persistence call in the Livewire component or Controller. Determine whether it is a
Command, Read, or Process operation.

**2. Create the Action class.**

Write the file with the prescribed header order (declare → namespace → use → class → constructor →
execute).

**3. Move validation into the Action.**

Copy the validation rules from the component's `rules()` method into the Action's `execute()` method.
The component may keep its own validation for UX purposes, but the Action is the authoritative source.

**4. Wrap persistence in `$this->transaction()`.**

Move `Model::create()`, `Model::update()`, `DB::` calls into the transaction callback.

**5. Add logging.**

```php
$this->log('{action_key}', $subject, ['context' => $value]);
```

**6. Dispatch an event.**

```php
event(new {Entity}{Actioned}($subject));
```

**7. Delegate business rules to Entities.**

Replace inline `if ($record->status === 'x')` with `$record->as{Entity}()->ensureCan{Action}()`,
which throws `RejectedException` on violation.

**8. Inject the Action into the caller.**

Replace inline persistence in the caller with `$action->execute(...)` via dependency injection.

**9. Catch RejectedException.**

Wrap the Action call in `try/catch` to display user-friendly error messages.

**10. Write the test.**

Create the test file covering happy path, rule violations, validation failures, and side effects.

### Extraction Checklist

- [ ] New Action class in correct module/submodule directory
- [ ] `declare(strict_types=1)` and proper namespace
- [ ] Extends the correct base class
- [ ] Single `execute()` method
- [ ] Constructor uses `protected readonly` promotion
- [ ] DB writes wrapped in `$this->transaction()`
- [ ] `$this->log()` called after mutation
- [ ] `event()` dispatched for significant state changes
- [ ] Business rules delegated to Entity methods
- [ ] `RejectedException` for rule violations
- [ ] Original caller injects Action via method parameter
- [ ] Policy check in calling layer precedes the Action call
- [ ] Test file created with happy path + edge cases
- [ ] DTO introduced (phase 2/3) if applicable
