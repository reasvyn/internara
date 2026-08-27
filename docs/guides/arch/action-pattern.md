# Action Triad Pattern — Command/Read/Process Deep-Dive

> **Last updated:** 2026-08-27 **Changes:** rewrite — integrate global standards (CQRS, Command Pattern, SOLID, PoEAA, Unit of Work) with anti-pattern table, Quick References

## Description

This pattern governs how Internara organizes business operations into three distinct Action types — **Command**, **Read**, and **Process** — replacing traditional Service classes (god objects with multiple public methods). It synthesizes global industry standards — **CQRS** (Martin Fowler / Greg Young), **Command Pattern** (Gang of Four), **Single Responsibility Principle** (Robert C. Martin, SOLID), **Unit of Work** and **Transaction Script** (Martin Fowler, *Patterns of Enterprise Application Architecture*) — into enforceable rules tied to Internara's stack: `BaseCommandAction`/`BaseReadAction`/`BaseProcessAction`, `BaseData` DTOs, `SmartLogger`, `BaseEvent`, and `ActionResponse`.

Without it, business logic scatters across Livewire components, Controllers accumulate mutation logic, reads mix with writes, and testing a single operation means loading the entire service. With it, every operation is isolated, testable, transaction-safe, and discoverable by name alone.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **One class, one operation.** Every Action has exactly **one public method: `execute()`**. A second public method means two responsibilities — extract into a separate Action. This enforces the **Single Responsibility Principle** (Robert C. Martin, *Clean Architecture*) at the class level. SRP boundary rules are defined in [Modular Pattern §1.6](modular-pattern.md#16-single-responsibility--modularity-rules).

2. **Livewire never mutates directly.** No `Model::create()`, `Model::update()`, `Model::delete()`, `DB::` queries, or `DB::transaction()` in any Livewire component or Controller. All mutations go through Command Actions. This is **C1** (invariant from `docs/conventions.md` §9). Livewire delegates; Actions execute.

3. **Transaction safety is mandatory for writes.** Every Command and Process Action MUST wrap mutations in `$this->transaction()`. The method auto-detects nesting (`DB::transactionLevel() > 0` → inner call executes directly), defers events until commit, and retries on deadlock. This is the **Unit of Work** pattern (Fowler, PoEAA) applied at the Action level.

4. **`RejectedException` for business rules, never `RuntimeException`.** Business rule violations (state machine, entity invariants, domain constraints) MUST throw `RejectedException`. `RuntimeException` is reserved for infrastructure failures. This is **C8** (invariant from `docs/guides/arch/exception-pattern.md`).

5. **DTO for 3+ parameters, never raw `array`.** Command/Process Actions with 3+ parameters MUST accept a `BaseData` DTO (C7). Never `execute(array $data)` for complex operations. Simple 1-2 scalar ops may use typed scalars. This enforces type safety and IDE autocompletion.

6. **Read Actions are pure queries.** No `transaction()`, no `log()`, no `event()`. Read Actions compute and return — they never side-effect. This mirrors the **CQRS principle** (Fowler): separate the model for reading information from the model for updating information.

7. **Policy check precedes Action call.** Authorization is the caller's responsibility — Livewire `authorize()`, Controller `Gate::authorize()`, or route middleware. Actions assume authorization is done; they do not re-authorize internally (though Entity invariants may enforce additional business rules).

8. **Events only when a listener exists.** Do NOT create events preemptively. Add an event only when a listener needs to react asynchronously (cache invalidation, cross-module notification). Event-to-action ratio: 0–1 for Commands, 1 required for Process.

---

## How to Apply

### 1. Action Triad — CQRS at Class Level

The Action Triad mirrors **CQRS** (Command Query Responsibility Segregation, Fowler/Young) at the class level — separating mutation paths from read paths — without the infrastructure cost of separate databases or event sourcing. Class-level SRP and modularity boundaries are defined in [Modular Pattern §1.6](modular-pattern.md#16-single-responsibility--modularity-rules).

| Type | Purpose | Base Class | Transaction | Logging | Global Reference |
|------|---------|-----------|------------|---------|-----------------|
| **Command** | Every write — create, update, delete, state transitions | `BaseCommandAction` | Required | Required | **Command Pattern** (GoF) — encapsulates request as object |
| **Read** | Complex queries, aggregations, dashboard assembly | `BaseReadAction` | Never | Never | **CQRS Query Side** (Fowler) — separate read model |
| **Process** | Multi-step orchestration composing Command/Read Actions | `BaseProcessAction` | Required | Required | **Transaction Script** (PoEAA) + **Unit of Work** (Fowler) |

**Why not just Services?** Service classes with multiple public methods share one file and one constructor. They accumulate mixed responsibilities, shared mutable state, and branching conditionals. Testing a single method means loading the entire service. Actions invert this: one class per operation, testable in isolation, discoverable by name alone. This is the **God Object** anti-pattern (Fowler) that Actions prevent.

The triad refines further: not all operations need transactions. Not all need logging. Forcing every operation into the same mould adds unnecessary ceremony to reads. The triad gives each operation type the contract it actually needs — this is the **Interface Segregation Principle** (Robert C. Martin, SOLID).

### 2. Base Class Utilities

Each base class provides utilities tailored to its action type:

| Base Class | Utilities |
|-----------|-----------|
| `BaseCommandAction` | `respond()` / `respondDeleted()` / `respondError()` — structured `ActionResponse` returns; `validate()` — inline `Validator::validate()`; `authorize()` — `Gate::authorize()` shortcut; `flash()` — flash message helper; `fail()` — throw `RejectedException`; inherits `transaction()`, `log()`, `dispatchEvent()` from `BaseAction` |
| `BaseReadAction` | `remember()` / `rememberForever()` / `forget()` — caching with auto key generation; `cacheKey()` — module-scoped cache key builder; `mask()` — PII masking; `paginate()` — consistent `LengthAwarePaginator`; `format()` — standardised response envelope; `withErrorHandling()` from `HandlesActionErrors` trait |
| `BaseProcessAction` | `step()` — wrapped step execution with success/failure tracking; `trackProgress()` / `getProgress()` — progress percentage; `getResults()` — per-step result inspection; `allStepsSucceeded()` — quick status check; `fail()` — throw `RejectedException`; `notify()` — send `Notification`; `logProgress()` — log with step context; inherits `transaction()`, `log()`, `dispatchEvent()` from `BaseAction` |

---

### 3. Command Actions — The Mutation Gateway

The sole entry point for every mutation in the system. If data changes in the database, a Command Action did it. Mirrors the **Command Pattern** (GoF) — encapsulating a request as an object with `execute()`.

#### Contract

- MUST extend `BaseCommandAction` (extends `BaseAction`)
- MUST wrap all database operations in `$this->transaction()` — **Unit of Work** (Fowler)
- MUST call `$this->log()` after successful mutation
- MUST be preceded by a policy check in the calling layer
- MUST NOT contain inline `canX()` checks — delegate to Entity methods and throw `RejectedException`
- MUST throw `RejectedException` for business rule violations, never `RuntimeException` (C8)
- MUST have exactly one public method: `execute()`
- **SHOULD accept a DTO (`BaseData`) for 3+ params or multiple callers** (C7)
- **SHOULD return `ActionResponse`** when the caller needs message/redirect/error context
- MAY dispatch an event if a listener needs to react asynchronously

#### Structure

```php
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
            event(new {Entity}{Actioned}(${entity}));

            return $this->respondUpdated(${entity});
        });
    }
}
```

#### Return Type Conventions

| Return Type | When | Pattern |
|------------|------|---------|
| `ActionResponse::created()` | Simple create — caller needs feedback | Command Pattern response |
| `ActionResponse::updated()` | Simple update — caller needs feedback | Command Pattern response |
| `ActionResponse::deleted()` | Delete operations | void semantics |
| `ActionResponse::error()` | Failure with message | Structured error envelope |
| Model directly | Simple create/update — caller just needs the model | Active Record return |
| `void` | Fire-and-forget mutations | Command Pattern fire-and-forget |
| `array` / DTO | Complex results that don't map to a single model | Value Object return |

---

### 4. Read Actions — The Query Side

Encapsulate complex read operations — aggregation, filtering, cross-module data assembly, dashboard statistics — that are too heavy for inline `Model::query()` in a Livewire component. Mirrors the **CQRS Query Side** (Fowler): "The query side of CQRS is typically very simple — you just need to return data."

#### Contract

- MUST extend `BaseReadAction`
- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()`
- Single public `execute()` method
- MAY accept typed scalars (e.g., `int $id`, `string $status`). Use a DTO for 3+ filter params.
- SHOULD return typed objects or collections, never raw arrays
- MUST pass through authorization unless the calling layer already authorized

#### When to Use vs. Inline Queries

Simple `Model::find()` or single `where` clauses should remain inline in Livewire. Use a Read Action for:

- Aggregation with multiple conditions
- Cross-module data assembly
- Dashboard with charts and stats
- Queries with complex authorization rules
- Any query that needs to be reused across multiple Livewire components

#### Naming Convention

`Read{Entity}Action`

---

### 5. Process Actions — Orchestration

Orchestrate multi-step workflows that coordinate multiple Command and Read Actions. Mirrors the **Transaction Script** pattern (PoEAA) — "organizes business logic as one procedure that handles one request from the presentation" — but decomposed into discrete steps with tracking.

#### Contract

- MUST extend `BaseProcessAction` (extends `BaseAction` — transaction + logging at the process level)
- MUST compose other Actions via constructor injection
- MUST handle partial failure — document what happens when step N of M fails
- MAY emit an event if downstream listeners exist
- MUST NOT duplicate business logic that already exists in Command Actions

#### Partial Failure Handling

Every Process Action must consider what happens when a composed Action fails. Three strategies, all documented in the Process Action's docblock:

| Strategy | When | Implementation |
|----------|------|----------------|
| **All-or-nothing** | Default — transaction rolls back everything | `$this->transaction()` wraps all steps |
| **Compensating action** | Later step fails after earlier committed (e.g., external API) | Execute undo logic for committed steps |
| **Flag-and-continue** | Partial completion acceptable, admin resolves manually | Log failure, set process status to `PARTIAL` |

---

### 6. Transaction Safety — Unit of Work

The `transaction()` method implements the **Unit of Work** pattern (Fowler, PoEAA) — "maintaining a list of objects affected by a business transaction and coordinating the writing out of changes and the resolution of concurrency problems."

#### Three Critical Concerns

**1. Nested transaction detection.** When a Process Action calls `$this->transaction()` which calls a Command Action that also calls `$this->transaction()`, the inner call detects it is already inside a transaction via `DB::transactionLevel() > 0` and executes the callback directly without wrapping. This prevents Laravel's `DB::transaction()` from creating a savepoint or committing prematurely.

**2. Deferred event dispatch.** Events are collected via `$this->dispatchEvent()` into a `$pendingEvents` array and dispatched only after the transaction commits (via `dispatchPendingEvents()`). This prevents listeners from seeing uncommitted data — the **Read Committed** isolation level principle.

**3. Deadlock retry.** The outer `DB::transaction()` retries on serialisation failures. This is important for high-concurrency workflows.

#### Lifecycle Hooks

```php
protected function beforeExecute(): void {}  // Called before every transaction
protected function afterExecute(mixed $result): void {}  // Called after every transaction
```

Override these in Command/Process Actions to set up context or clean up resources. Most Actions do not need them.

---

### 7. Logging Protocol — Structured Audit Trail

Every Command and Process Action MUST call `$this->log()` after a successful mutation. The method writes to both the system log and activity log via **SmartLogger** (fluent dual-channel API). This mirrors the **Audit Trail** pattern — every mutation is traceable.

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

**What to Log:** Action identifier (always), subject model (always), context payload (recommended), PII (masked via `withPiiMasking()`).

**Where NOT to Log:** Read Actions must NEVER call `log()`. If you need to log a read operation (e.g., for analytics), use an explicit SmartLogger call outside the Action — never via `$this->log()`.

---

### 8. Event Dispatch — Domain Events

Command and Process Actions dispatch module events for significant state changes. Mirrors **Domain Events** (Eric Evans, DDD) — "something that happened in the domain that domain experts care about."

#### dispatchEvent() vs event()

| Method | Behaviour | When to Use |
|--------|-----------|-------------|
| `$this->dispatchEvent(BaseEvent $event)` | Queues the event; dispatched after transaction commits | Inside `transaction()` callback — **Unit of Work** safety |
| `event($event)` or `Event::dispatch()` | Dispatches immediately | After `transaction()` returns |

Use `event()` inside the `transaction()` callback — the deferred dispatch in `BaseAction::transaction()` handles "dispatch after commit" automatically. Use `$this->dispatchEvent()` when you want to collect events and guarantee they fire only after transaction success, even in nested contexts.

#### Event-to-Action Ratio

| Action Type | Events | Rationale |
|------------|--------|-----------|
| Command (create/update/delete) | 0–1 recommended | Only if a listener needs to react |
| Command (state transition) | 1 required | State changes are domain events |
| Command (notification-only) | 0 | Notifications are side effects, not domain events |
| Process | 1 required | Completed-process event for downstream |

---

### 9. Error Handling — Three Failure Modes

The error-handling strategy distinguishes three distinct failure modes, aligned with **SOLID Exception Handling** (Robert C. Martin) — exceptions should be specific, not generic:

| Failure Mode | Exception | Handled By | User Experience |
|-------------|-----------|-----------|----------------|
| Format/invalid input | `ValidationException` | Livewire error bag | Inline field errors |
| Business rule violation | `RejectedException` | Component try/catch | Flash error message |
| Infrastructure failure | `RuntimeException` (rethrown) | Component try/catch | Generic error message |

**HandlesActionErrors Trait:** Known exception types pass through unmodified. Unknown `Throwable` is logged to the system log (with full context) and rethrown as a generic `RuntimeException`. The trait is used by `BaseAction` and is available to any class that needs it.

**Error Handling Rules:**
1. Business rule violations → `RejectedException` (never bare `RuntimeException`) — C8
2. Format validation → `Validator::validate()` → `ValidationException` (automatic)
3. Infrastructure failure → `HandlesActionErrors` logs + rethrows as `RuntimeException`
4. `RejectedException` is ONLY for business rules — do not use it for validation or infrastructure errors

---

### 10. Validation Strategy — Defence in Depth

Two layers of validation, mirroring the **Defence in Depth** principle (NIST) — never rely on a single validation gate:

| Layer | Purpose | Mechanism | Authoritative? |
|-------|---------|-----------|---------------|
| Livewire component | User experience — inline errors, button state | `$this->validate()` | No (UX only) |
| Action | Data integrity — last gate before persistence | `Validator::make()->validate()` | Yes |

**Why validate in both layers:** Livewire validation runs in the browser context and can be bypassed (JavaScript disabled, crafted requests). The Action runs server-side and cannot be circumvented — it is the last validation gate before persistence.

**Where Rules Live:**
- **Shared validation rules** across multiple Actions → Entity static `rules()` methods
- **Action-specific rules** → inline `Validator::make()` in the Action
- **Form-level rules** → Form Object `rules()` method (for UX, re-validated in Action)
- **HTTP-level rules** → FormRequest `rules()` method (for controller endpoints)

---

### 11. ActionResponse Contract — Standardized Return Envelope

Standardize the return envelope from Actions so every caller — Livewire, Controller, Artisan command — handles results the same way. Mirrors the **Result Object** pattern — encapsulating success/failure in a structured return.

#### Factory Methods

```php
ActionResponse::ok($data, 'Operation completed');
ActionResponse::created($model, '{Entity} created');
ActionResponse::updated($model, '{Entity} updated');
ActionResponse::deleted('{Entity} removed');
ActionResponse::error('Something went wrong', $errors);
```

#### When to Use vs. Direct Return

| Return Type | When |
|------------|------|
| `Model` directly | Simple create/update — caller just needs the model |
| `ActionResponse` | Caller needs structured feedback (message, redirect, error context) |
| `void` | Delete operations |
| `array` | Complex results that don't map to a single model |
| `Collection` | Read Action returning multiple results |
| `int` / `bool` | Simple counters or existence checks in Read Actions |

---

### 12. DTO Migration Path — Type Safety at Scale

Data Transfer Objects provide type safety for Action parameters. Instead of passing `array $data` around, a DTO gives named, typed parameters, IDE autocompletion, and compile-time safety. Mirrors the **Value Object** concept (Evans, DDD) — an object that describes a characteristic but has no conceptual identity.

#### Three-Phase Migration Path

- **Phase 1 — Rapid development:** accept `array`
- **Phase 2 — Migration:** accept both via union type, normalise internally
- **Phase 3 — Final:** DTO only

#### When to Introduce a DTO

- The Action has multiple parameters
- The Action has multiple callers
- The parameters have stabilised (no longer in rapid prototyping)
- The Action is part of a public API consumed by other modules

---

### 13. Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Command | `{Verb}{Entity}Action` | `CreateInternshipAction` |
| Read | `Read{Entity}Action` | `ReadInternshipAction` |
| Process | `Process{Entity}Action` | `ProcessInternshipEnrollmentAction` |

**Common Verbs:** `Create`, `Update`, `Delete`, `Activate`, `Deactivate`, `Finalize`, `Verify`, `Submit`, `Approve`, `Reject`, `Upload`, `Set`, `Reset`, `Generate`, `Validate`, `Provision`, `Setup`, `Install`, `Recover`, `Initialize`, `Toggle`, `Lock`, `Unlock`, `Score`, `Evaluate`, `Renew`, `Terminate`, `Batch`, `Bypass`, `Notify`.

**File Header Order:**
1. `declare(strict_types=1)`
2. Namespace
3. Use statements
4. Class declaration extending the appropriate base class
5. Constructor with `protected readonly` promotion
6. Single `execute()` method

**File Location:** `app/{Module}/{SubModule}/Actions/{ClassName}.php` or `app/{Module}/Actions/{ClassName}.php` for cross-submodule.

---

### 14. Testing Actions — Spec-Driven Verification

Tests are organized by module scope (`tests/{Module}/{SubModule}/{Name}Test.php`). An Action is tested when its behavior implements a spec requirement — the test is named after the requirement ID. One class → one test file for spec-defined behavior.

#### What to Test

| Concern | How |
|---------|-----|
| Happy path | Execute → assert model state / event / log |
| Business rule violation | Assert `RejectedException` is thrown |
| Validation failure | Assert `ValidationException` is thrown |
| Side effects | Assert `event()` dispatched, `log()` called |
| Partial failure (Process) | Test rollback when a composed Action fails |
| Policy enforcement | Test via feature test with authorised/unauthorised users |

#### Testing Conventions

- Use `LazilyRefreshDatabase` over `RefreshDatabase`
- Use `assertModelExists()` over `assertDatabaseHas()`
- Use Pest `test("{SpecID}-{ReqID}: description...")` with spec-traceable descriptions
- Mock SmartLogger in unit tests, use real SmartLogger in feature tests
- Do NOT test Eloquent relationships or model scopes through Actions

---

### 15. Action Extraction Workflow — From Inline to Action

**When to Extract** — you see:
- `Model::create()`, `Model::update()`, `Model::delete()` inside a component
- `DB::transaction()` call in a component
- `Mail::send()` or `Notification::send()` in a component (unless trivial)
- `if`/`switch` on record status or state in a component
- Any inline validation beyond simple required-field checks
- Business logic that you need to test independently

**Step-by-Step:** Identify → Create Action → Move validation → Wrap in `$this->transaction()` → Add `$this->log()` → Dispatch `event()` → Delegate to Entities (`ensureCan{Action}()`) → Inject into caller → Catch `RejectedException` → Write test.

**Extraction Checklist:**
- [ ] New Action class in correct module/submodule directory
- [ ] `declare(strict_types=1)` and proper namespace
- [ ] Extends the correct base class
- [ ] Single `execute()` method
- [ ] Constructor uses `protected readonly` promotion
- [ ] DB writes wrapped in `$this->transaction()`
- [ ] `$this->log()` called after mutation
- [ ] `event()` dispatched for significant state changes
- [ ] Business rules delegated to Entity methods
- [ ] `RejectedException` for rule violations (C8)
- [ ] Original caller injects Action via method parameter
- [ ] Policy check in calling layer precedes the Action call
- [ ] Test file created with happy path + edge cases
- [ ] DTO introduced (phase 2/3) if applicable

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `Model::create()` in Livewire component | `Create{Entity}Action::execute(DTO)` via DI | C1 — direct model mutation in UI layer |
| `DB::transaction()` in Livewire | `$this->transaction()` in Command Action | Unit of Work bypass, no logging |
| `if ($model->status === 'x')` in Blade/Livewire | `$model->as{Entity}()->ensureCan{Verb}()` in Action | Business logic leaked to UI layer |
| `throw new RuntimeException('business rule')` | `throw new RejectedException('business rule')` | C8 — wrong exception type |
| `execute(array $data)` with 5 parameters | `execute(SomeData $data)` DTO | C7 — raw array, no type safety |
| Action with `public function execute()` + `public function helper()` | Two separate Actions, one per operation | SRP violation, second public method |
| `$this->log()` in Read Action | Remove log — Read Actions are pure queries | Read Actions never log (CQRS query side) |
| `event(new SomeEvent())` outside `$this->transaction()` | Inside transaction callback or `$this->dispatchEvent()` | Events may fire before commit |
| `$request->all()` passed to Action | `$this->validate()` → DTO → Action | D5 — raw request, no validation |
| Action re-authorizes (`Gate::authorize()` inside `execute()`) | Authorization in calling layer (Livewire/Controller) | Redundant auth, violates layer boundary |
| No test for business rule violation | Test `RejectedException` is thrown | Missing spec-traceable test |
| `event(new SomeEvent())` preemptively (no listener exists) | Add event only when a listener exists | YAGNI — unnecessary event dispatch |

---

## Quick References

- `modular-pattern.md` §1.3 Action Triad, §1.6 SRP & Modularity Rules — project architecture contracts
- `exception-pattern.md` — `RejectedException` vs `RuntimeException` (C8)
- `data-pattern.md` — BaseData DTO contract, `fromArray()`, `from()`, `merge()`
- `event-pattern.md` — BaseEvent contract, dispatch patterns, listener registration
- `logging-pattern.md` — SmartLogger dual-channel API, PII masking
- `testing-pattern.md` — Scope isolation, layer-specific strategies, assertion preferences
- [Martin Fowler — CQRS](https://martinfowler.com/bliki/CQRS.html) — Command Query Responsibility Segregation, class-level separation
- [Microsoft — CQRS Pattern](https://learn.microsoft.com/en-us/azure/architecture/patterns/cqrs) — when to use, combining with Event Sourcing
- [Martin Fowler — PoEAA](https://martinfowler.com/eaaCatalog/) — Unit of Work, Transaction Script, Active Record, Service Layer
- [Gang of Four — Command Pattern](https://en.wikipedia.org/wiki/Command_pattern) — encapsulation of requests as objects
- [Robert C. Martin — SOLID Principles](https://blog.cleancoder.com/2014/10/the-packaging-dependency.html) — SRP, OCP, LSP, ISP, DIP
- [Laravel Daily — Action/Command Pattern](https://laraveldaily.com/lesson/design-patterns/action-command-pattern) — Laravel implementation of Command Pattern
- [Eric Evans — DDD](https://www.domainlanguage.com/ddd/) — Domain Events, Value Objects, Aggregate boundaries
