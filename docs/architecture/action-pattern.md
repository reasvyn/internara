# Action Triad Pattern Reference

> **Last updated:** 2026-06-10
>
> Comprehensive deep-dive on the Command/Read/Process Action Triad — the single most important
> architectural decision in Internara. This document covers every facet of the Action pattern:
> contracts, mechanics, conventions, and migration workflows.
>
> For the high-level overview (including the decision table and basic examples), see
> [Modular Pattern Reference](modular-pattern.md) §4. For the ADR that formalised this pattern,
> see [ADR-003](../adr/adr-action-pattern-over-services.md).
>
> **Key references:**
> - `app/Core/Actions/BaseAction.php` — base class for Command and Process Actions
> - `app/Core/Data/ActionResponse.php` — standardised return envelope
> - `app/Core/Data/BaseData.php` — DTO base class
> - `app/Core/Support/HandlesActionErrors.php` — error-handling trait
> - `.agents/skills/action-refactoring/` — skill with workflow rules
> - `.agents/skills/feature-building/rules/01-action-pattern.md` — pattern enforcement guide

---

## Table of Contents

1. [Action Triad Overview](#1-action-triad-overview)
2. [Command Actions](#2-command-actions)
3. [Read Actions](#3-read-actions)
4. [Process Actions](#4-process-actions)
5. [Decision Table](#5-decision-table)
6. [Data Flow](#6-data-flow)
7. [Transaction Safety](#7-transaction-safety)
8. [Logging Protocol](#8-logging-protocol)
9. [Event Dispatch](#9-event-dispatch)
10. [Error Handling](#10-error-handling)
11. [Validation Strategy](#11-validation-strategy)
12. [ActionResponse Contract](#12-actionresponse-contract)
13. [DTO Migration Path](#13-dto-migration-path)
14. [Naming Conventions](#14-naming-conventions)
15. [Testing Actions](#15-testing-actions)
16. [Action Extraction Workflow](#16-action-extraction-workflow)

---

## 1. Action Triad Overview

### Intent

Replace traditional Service classes (god objects with multiple public methods) with three distinct
action types, each owning exactly one business operation. The triad mirrors CQRS at the class level
— separate mutation paths from read paths — without the infrastructure cost of separate databases or
event sourcing.

### The Three Types

| Type | Purpose | Base Class | Transaction | Logging |
|------|---------|-----------|-------------|---------|
| **Command** | Every write — create, update, delete, state transitions | `BaseAction` | Required | Required |
| **Read** | Complex queries, aggregations, dashboard assembly | None (plain class) | Never | Never |
| **Process** | Multi-step orchestration composing Command/Read Actions | `BaseAction` | Required | Required |

### Clean Code Rationale

Service classes with `register()`, `approve()`, `reject()` methods share one file and one `__construct`.
They accumulate mixed responsibilities, shared mutable state, and branching conditionals. Testing a
single method means loading the entire service. Actions invert this: one class per operation, testable
in isolation, discoverable by name alone.

The triad refines this further. Not all operations need transactions. Not all need logging. Forcing
every operation into the same mould adds unnecessary ceremony to reads. The triad gives each operation
type the contract it actually needs.

### Where to Find It

- **Base class:** `app/Core/Actions/BaseAction.php`
- **Error trait:** `app/Core/Support/HandlesActionErrors.php`
- **Architecture doc:** `docs/architecture.md` §3
- **Conventions:** `docs/conventions.md` §6
- **ADR:** `docs/adr/adr-action-pattern-over-services.md`
- **Skill rules:** `.agents/skills/action-refactoring/rules/`

---

## 2. Command Actions

### Intent

The sole entry point for every mutation in the system. If data changes in the database, a Command
Action did it.

### Contract

- MUST extend `BaseAction`
- MUST wrap all database operations in `$this->transaction()`
- MUST call `$this->log()` after successful mutation
- MUST be preceded by a policy check in the calling layer
- MUST NOT contain inline `canX()` checks — delegate to Entity methods and throw `RejectedException`
- MUST throw `RejectedException` for business rule violations, never `RuntimeException`
- MUST have exactly one public method: `execute()`
- SHOULD dispatch a module event for significant state changes

### Structure

```php
declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Registration\Data\ApproveRegistrationData;
use App\Enrollment\Registration\Models\Registration;

class ApproveRegistrationAction extends BaseAction
{
    public function __construct(
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

    public function execute(Registration $registration, ApproveRegistrationData $data): Registration
    {
        $registration->asRegistrationState()->ensureCanBeApproved();

        return $this->transaction(function () use ($registration, $data) {
            $registration->update([
                'status' => RegistrationStatus::APPROVED->value,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->notifyMentor->execute($registration);
            $this->log('registration_approved', $registration, [
                'registration_id' => $registration->id,
            ]);
            event(new RegistrationApproved($registration));

            return $registration;
        });
    }
}
```

### Return Type Conventions

- Create: returns the created Model
- Update: returns the updated Model
- Delete: returns `void` or an `ActionResponse`
- State transition: returns the Model
- Complex operations: return an array, DTO, or `ActionResponse`

### Where to Find Examples

- `app/User/Profile/Actions/UpdateProfileAction.php`
- `app/Enrollment/Registration/Actions/ApproveRegistrationAction.php`
- `app/Program/Internship/Actions/CreateInternshipAction.php`
- `app/Academics/AcademicYear/Actions/CreateAcademicYearAction.php`

---

## 3. Read Actions

### Intent

Encapsulate complex read operations — aggregation, filtering, cross-module data assembly, dashboard
statistics — that are too heavy for inline `Model::query()` in a Livewire component.

### Contract

- Plain class with constructor injection — no base class required
- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()` from `BaseAction`
- SHOULD return typed objects or collections, never raw arrays
- MAY use `HandlesActionErrors` from `BaseAction` but does not extend it
- MUST pass through authorization unless the calling layer already authorized

### When to Use vs. Inline Queries

| Scenario | Approach |
|----------|----------|
| `Model::find($id)` | Inline in Livewire |
| `Model::where('x', $y)->get()` | Inline in Livewire |
| Aggregation with multiple conditions | Read Action |
| Cross-module data assembly | Read Action |
| Dashboard with charts and stats | Read Action |
| Query with complex authorization rules | Read Action |

### Structure

```php
declare(strict_types=1);

namespace App\Program\Internship\Actions;

use App\Program\Internship\Models\Internship;
use App\Program\Internship\Enums\InternshipStatus;
use Illuminate\Support\Collection;

class InternshipDashboardReader
{
    public function __construct(
        protected readonly Internship $model,
    ) {}

    public function activeCount(): int
    {
        return $this->model
            ->whereIn('status', [
                InternshipStatus::PUBLISHED->value,
                InternshipStatus::ACTIVE->value,
            ])
            ->count();
    }

    public function recentRegistrations(int $days = 7): Collection
    {
        return $this->model->registrations()
            ->where('created_at', '>=', now()->subDays($days))
            ->with('mentee.user', 'internship')
            ->limit(20)
            ->get();
    }

    public function completionStats(Internship $program): array
    {
        $total = $program->registrations()->count();
        $completed = $program->registrations()
            ->whereHas('certificates')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0
                ? round(($completed / $total) * 100, 1)
                : 0,
        ];
    }
}
```

### Naming Options

- `{Context}Reader` — `InternshipDashboardReader`, `RegistrationReportReader`
- `Get{Dashboard}Data` — `GetStudentStatsData` (note: this is a Read Action, not a DTO)
- `{Entity}Query` — `RegistrationQuery`, `ActiveInternshipQuery`

### Where to Find Examples

- `app/Program/Internship/Actions/InternshipDashboardReader.php`
- `app/User/Dashboard/Actions/GetStudentStatsData.php`

---

## 4. Process Actions

### Intent

Orchestrate multi-step workflows that coordinate multiple Command and Read Actions. The "how" of
complex business processes — registration, finalisation, closure.

### Contract

- MUST extend `BaseAction` (transaction + logging at the process level)
- MUST compose other Actions via constructor injection
- MUST handle partial failure — if step 3 of 5 fails, what happens to steps 1–2?
- SHOULD emit a single module event representing the completed process
- MUST NOT duplicate business logic that already exists in Command Actions

### Structure

```php
declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Registration\Data\RegisterStudentData;
use App\Enrollment\Registration\Models\Registration;

class RegisterStudentProcess extends BaseAction
{
    public function __construct(
        protected readonly CreateRegistrationAction $createRegistration,
        protected readonly AssignPlacementAction $assignPlacement,
        protected readonly NotifyMentorAction $notifyMentor,
        protected readonly NotifyStudentAction $notifyStudent,
    ) {}

    public function execute(RegisterStudentData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->notifyMentor->execute($registration);
            $this->notifyStudent->execute($registration);

            $this->log('student_registered', $registration);
            event(new StudentRegistered($registration));

            return $registration;
        });
    }
}
```

### Partial Failure Handling

Every Process Action must consider what happens when a composed Action fails. There is no
one-size-fits-all answer — the business decides:

- **All-or-nothing:** The transaction rolls back everything. The caller retries after fixing the
  issue. This is the most common approach.
- **Compensating action:** Step 3 fails after steps 1–2 committed (e.g., an API call that can't be
  rolled back). Execute a compensating action (e.g., `CancelRegistrationAction`) to undo.
- **Flag-and-continue:** Mark the process as partially complete, log the failure, and let an admin
  resolve it manually.

The default approach is **all-or-nothing** via `$this->transaction()`. Compensating actions and
flag-and-continue are documented in the Process Action's docblock.

### Where to Find Examples

- `app/Enrollment/Registration/Actions/RegisterStudentProcess.php`
- `app/Program/Internship/Actions/CloseInternshipProcess.php`

---

## 5. Decision Table

| Scenario | Pattern | Base Class | Transaction | Logging | Event |
|----------|---------|-----------|-------------|---------|-------|
| Create a record | Command | `BaseAction` | Required | Required | Recommended |
| Update a record | Command | `BaseAction` | Required | Required | Recommended |
| Delete a record | Command | `BaseAction` | Required | Required | Recommended |
| State transition | Command | `BaseAction` | Required | Required | Required |
| Send notification | Command | `BaseAction` | Required | Required | Not needed |
| File upload | Command | `BaseAction` | Required | Required | Recommended |
| Import / batch operation | Command | `BaseAction` | Required | Required | Recommended |
| Simple list query | Inline in Livewire | None | Never | Never | Never |
| Single record fetch | Inline in Livewire | None | Never | Never | Never |
| Complex aggregated query | Read Action | None (plain class) | Never | Never | Never |
| Dashboard statistics | Read Action | None (plain class) | Never | Never | Never |
| Cross-module data assembly | Read Action | None (plain class) | Never | Never | Never |
| Multi-step orchestration | Process | `BaseAction` | Required | Required | Required |
| Batch workflow | Process | `BaseAction` | Required | Required | Required |

**Invalid combinations (enforced in code review):**

| What you might try | Why it's wrong |
|--------------------|----------------|
| Command Action without `$this->transaction()` | Partial writes corrupt data |
| Read Action extending `BaseAction` | Unnecessary ceremony, risk of accidental logging |
| Read Action calling `transaction()` or `log()` | Violates CQRS separation |
| Process Action with logic duplicated from a Command Action | Dual maintenance burden |

---

## 6. Data Flow

### Mutation Flow (Writes)

Every write follows the same path through the layers:

```
Layer 10/11          Layer 7            Layer 5/6           Layer 2
Input → Livewire/Controller → Command Action → Model/Entity → Database
                                  │
                                  ├─ Policy check (Layer 8)
                                  ├─ Entity rule check (Layer 6)
                                  ├─ Transaction wrap
                                  ├─ Validation re-check
                                  ├─ Log mutation (SmartLogger)
                                  └─ Dispatch event (Layer 9)
                                     ↓
                                  Listener(s)
                                  ├─ Notify users
                                  ├─ Invalidate cache
                                  └─ Write audit trail
```

Command Actions (Layer 7) are the **only** entry point for mutations. Livewire components (Layer 11)
never call `Model::create()` directly. This invariant is enforced through code review.

### Read Flow (Queries)

```
Simple query:
Livewire → Model::query() → Database
           │
           └─ Policy check (Layer 8)

Complex query:
Livewire → Read Action → Model::query() → Database
           │              │
           ├─ Policy check └─ Filter/transform/aggregate
           └─ Return typed result
```

Reads may skip Layer 7 for simple queries but must still pass through authorization (Layer 8).

### Process Flow

```
Livewire → Process Action
           │
           ├─ $this->transaction()
           │   ├─ Command Action 1 ──► Model ──► DB
           │   ├─ Command Action 2 ──► Model ──► DB
           │   └─ Command Action 3 ──► Model ──► DB
           ├─ $this->log()
           └─ event(ProcessCompleted)
              ↓
           Listeners
```

---

## 7. Transaction Safety

### BaseAction::transaction() Mechanics

The `transaction()` method in `BaseAction` (`app/Core/Actions/BaseAction.php`) handles three
critical concerns:

**1. Nested transaction detection:**
When a Process Action calls `$this->transaction()` which calls a Command Action that also calls
`$this->transaction()`, the inner call detects it is already inside a transaction via
`DB::transactionLevel() > 0` and executes the callback directly without wrapping. This prevents
Laravel's `DB::transaction()` from creating a savepoint or committing prematurely.

```php
protected function transaction(callable $callback, int $attempts = 3): mixed
{
    $this->beforeExecute();

    if (DB::transactionLevel() > 0) {
        $result = $callback();
        $this->dispatchPendingEvents();
        $this->afterExecute($result);
        return $result;
    }

    $result = DB::transaction(function () use ($callback) {
        $result = $callback();
        $this->dispatchPendingEvents();
        return $result;
    }, $attempts);

    $this->afterExecute($result);
    return $result;
}
```

**2. Deferred event dispatch:**
Events are collected via `$this->dispatchEvent()` into a `$pendingEvents` array and dispatched
only after the transaction commits (via `dispatchPendingEvents()`). This prevents listeners from
seeing uncommitted data.

**3. Deadlock retry:**
The outer `DB::transaction()` retries up to 3 times by default on serialisation failures. This is
important for high-concurrency workflows (registrations, submissions).

### Lifecycle Hooks

```php
protected function beforeExecute(): void {}  // Called before every transaction
protected function afterExecute(mixed $result): void {}  // Called after every transaction
```

Override these in Command/Process Actions to set up context or clean up resources. Most Actions
do not need them.

---

## 8. Logging Protocol

### The log() Method

Every Command and Process Action MUST call `$this->log()` after a successful mutation. The method
writes to both the system log and Spatie activity log:

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

### Log Action Keys

Use `snake_case` action keys that describe what happened:

- `user_created`, `user_updated`, `user_deleted`
- `registration_approved`, `registration_rejected`
- `logbook_submitted`, `logbook_verified`
- `internship_closed`, `internship_cancelled`

### What to Log

| Data Point | Included? | Notes |
|------------|-----------|-------|
| Action identifier | Always | e.g., `'registration_approved'` |
| Subject model | Always | The affected entity |
| Context payload | Recommended | IDs, status values, relevant metadata |
| PII | Masked | `withPiiMasking()` handles this |

### Where NOT to Log

Read Actions must NEVER call `log()`. If you need to log a read operation (e.g., for analytics),
use an explicit SmartLogger call outside the Action — never via `$this->log()`.

---

## 9. Event Dispatch

### Pattern

Command and Process Actions dispatch module events for significant state changes. The pattern is:

```php
$this->transaction(function () use ($data) {
    // ... mutation ...
    $this->log('registration_approved', $registration);
    event(new RegistrationApproved($registration));
    return $registration;
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

### SmartLogger Integration

`BaseEvent` integrates with SmartLogger. When using SmartLogger directly:

```php
SmartLogger::success('User registered')
    ->event(new UserRegistered($user))
    ->for($admin)
    ->save();
```

When a `BaseEvent` is passed, the log key is derived from `$event->eventName()`, payload merges
from `$event->toPayload()`, and `event($baseEvent)` is dispatched inside `save()`.

---

## 10. Error Handling

### Three Failure Modes

The error-handling strategy distinguishes three distinct failure modes:

| Failure Mode | Exception | Handled By | User Experience |
|-------------|-----------|------------|-----------------|
| Format/invalid input | `ValidationException` | Livewire error bag | Inline field errors |
| Business rule violation | `RejectedException` | Component try/catch | Flash error message |
| Infrastructure failure | `RuntimeException` (rethrown) | Component try/catch | Generic error message |

### RejectedException for Business Rules

When an Entity check fails, throw `RejectedException`:

```php
$registration->asRegistrationState()->ensureCanBeApproved();
// If not, throws RejectedException('Registration cannot be approved in its current state.')
```

The calling Livewire component catches it:

```php
public function approve(int $id, ApproveRegistrationAction $action): void
{
    try {
        $registration = Registration::findOrFail($id);
        $this->authorize('approve', $registration);
        $action->execute($registration, $this->form->toArray());
        flash()->success(__('registration.approved'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

### HandlesActionErrors Trait

The `HandlesActionErrors` trait (`app/Core/Support/HandlesActionErrors.php`) wraps unexpected
infrastructure failures:

```php
protected function withErrorHandling(callable $callback, string $context): mixed
{
    try {
        return $callback();
    } catch (RuntimeException|AppException|ModuleException|ValidationException|AuthorizationException|ModelNotFoundException|NotFoundHttpException $e) {
        throw $e;  // Known types pass through
    } catch (\Throwable $e) {
        SmartLogger::error($context)
            ->withPayload(['error' => $e->getMessage(), ...])
            ->systemOnly()
            ->save();
        throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
    }
}
```

Known exception types pass through unmodified. Unknown `Throwable` is logged to the system log
(with full context) and rethrown as a generic `RuntimeException`. The trait is used by `BaseAction`
and is available to any class that needs it.

### Error Handling Rules

1. Business rule violations → `RejectedException` (never bare `RuntimeException`)
2. Format validation → `Validator::validate()` → `ValidationException` (automatic)
3. Infrastructure failure → `HandlesActionErrors` logs + rethrows as `RuntimeException`
4. `RejectedException` is ONLY for business rules — do not use it for validation or infrastructure errors

### Where to Find It

- `app/Core/Support/HandlesActionErrors.php` — the trait
- `app/Core/Exceptions/RejectedException.php` — business rule exception
- `app/Core/Exceptions/ModuleException.php` — abstract base for module exceptions
- `.agents/skills/action-refactoring/rules/05-error-handling.md` — skill rule

---

## 11. Validation Strategy

### Two Layers of Validation

| Layer | Purpose | Mechanism | Authoritative? |
|-------|---------|-----------|----------------|
| Livewire component | User experience — inline errors, button state | `$this->validate()` | No (UX only) |
| Action | Data integrity — last gate before persistence | `Validator::make()->validate()` | Yes |

### Why Validate in Both Layers

Livewire validation runs in the browser context and can be bypassed — accidentally (JavaScript
disabled) or intentionally (crafted requests). The Action runs server-side and cannot be circumvented
because it's the last validation gate before persistence. This is defence in depth.

### How Actions Validate

```php
class CreateUserAction extends BaseAction
{
    public function execute(array $data): User
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        return $this->transaction(function () use ($validated) {
            $user = User::create($validated);
            $this->log('user_created', $user, ['email' => $user->email]);
            event(new UserCreated($user));
            return $user;
        });
    }
}
```

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

### Where to Find It

- `docs/architecture.md` §11 — Validation Strategy
- `.agents/skills/action-refactoring/rules/02-validation.md` — skill rule

---

## 12. ActionResponse Contract

### Intent

Standardise the return envelope from Actions so every caller — Livewire, Controller, Artisan command
— handles results the same way. Not every Action must return an `ActionResponse`; simple create/
update/delete operations may return the model directly. Use `ActionResponse` when the caller needs
structured feedback beyond the model.

### Factory Methods

```php
ActionResponse::ok($data, 'Operation completed');          // Generic success
ActionResponse::created($model, 'User created');           // Resource created
ActionResponse::updated($model, 'Profile updated');        // Resource updated
ActionResponse::deleted('User removed');                   // Resource deleted
ActionResponse::error('Something went wrong', $errors);    // Failure
```

All factory methods accept an optional message. `created()`, `updated()`, and `deleted()` have
sensible defaults via `__()` translation keys.

### WithRedirect

```php
return ActionResponse::created($registration)
    ->withRedirect(route('registrations.show', $registration));
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
values:

```php
public function jsonSerialize(): array
{
    return array_filter([
        'success' => $this->success,
        'data' => $this->data instanceof Model ? $this->data->toArray() : $this->data,
        'message' => $this->message,
        'redirect' => $this->redirect,
        'errors' => $this->errors,
    ], fn (mixed $v) => $v !== null && $v !== []);
}
```

### When to Use ActionResponse vs. Direct Return

| Return Type | When |
|-------------|------|
| `Model` directly | Simple create/update — caller just needs the model |
| `ActionResponse` | Caller needs structured feedback (message, redirect, error context) |
| `void` | Delete operations |
| `array` | Complex results that don't map to a single model |
| `Collection` | Read Action returning multiple results |
| `int` / `bool` | Simple counters or existence checks in Read Actions |

### Where to Find It

- `app/Core/Data/ActionResponse.php` — the class

---

## 13. DTO Migration Path

### Intent

Data Transfer Objects (DTOs) provide type safety for Action parameters. Instead of passing `array $data`
around, a DTO gives you named, typed parameters, IDE autocompletion, and compile-time safety.

### BaseData

All DTOs extend `App\Core\Data\BaseData` (`app/Core/Data/BaseData.php`):

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

### DTO Example

```php
final readonly class ApproveRegistrationData extends BaseData
{
    public function __construct(
        public string $status,
        public ?string $feedback,
        public ?string $approvedBy,
    ) {}
}
```

### Three-Phase Migration Path

```php
// Phase 1 — Rapid development: accept array
public function execute(array $data): Model { ... }

// Phase 2 — Migration: accept both (union type)
public function execute(array|ApproveRegistrationData $data): Model
{
    $data = $data instanceof ApproveRegistrationData
        ? $data
        : ApproveRegistrationData::fromArray($data);
    // ... use $data ...
}

// Phase 3 — Final: DTO only
public function execute(ApproveRegistrationData $data): Model { ... }
```

### When to Introduce a DTO

- The Action has 3+ parameters
- The Action has multiple callers
- The parameters have stabilised (no longer in rapid prototyping)
- The Action is part of a public API consumed by other modules

### fromArray() and from()

`BaseData::fromArray()` maps `snake_case` array keys to `camelCase` constructor parameters:

```php
$data = ApproveRegistrationData::fromArray([
    'status' => 'approved',
    'feedback' => 'All requirements met',
    'approved_by' => $userId,  // snake_case mapped to approvedBy
]);
```

`BaseData::from()` accepts arrays or objects with `toArray()`:

```php
$data = ApproveRegistrationData::from($request->validated());
$data = ApproveRegistrationData::from($this->form->toArray());
```

### Where to Find It

- `app/Core/Data/BaseData.php` — base class
- `app/Enrollment/Registration/Data/ApproveRegistrationData.php`
- `docs/conventions.md` §11 — Data / DTOs

---

## 14. Naming Conventions

### Action Names

| Type | Pattern | Examples |
|------|---------|---------|
| Command | `{Verb}{Entity}Action` | `CreateUserAction`, `ApproveRegistrationAction`, `FinalizeLogbookAction` |
| Read | `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query` | `InternshipDashboardReader`, `GetStudentStatsData`, `ActiveRegistrationQuery` |
| Process | `{Verb}{Entity}Process` | `RegisterStudentProcess`, `CloseInternshipProcess` |

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
3. Use statements (BaseAction, RejectedException, Model, Validator, dependencies)
4. Class declaration extending `BaseAction` (Command/Process) or plain class (Read)
5. Constructor with `protected readonly` promotion for injected dependencies
6. Single `execute()` method

### Class Name Rule

The class name must never be repeated in the path:

- ✅ `app/User/Models/User.php` (namespace `App\User\Models`)
- ✅ `app/Academics/AcademicYear/Actions/CreateAcademicYearAction.php`
- ❌ `app/Academics/AcademicYear/AcademicYear/Actions/CreateAcademicYearAction.php`

---

## 15. Testing Actions

### Scope Isolation

Every Action has its own test file. This is a critical rule — do not group multiple Action tests
into a single file. One class → one test file.

### File Structure

```
tests/Feature/{Module}/{SubModule}/{Name}Test.php
```

Examples:
- `tests/Feature/User/Profile/UpdateProfileActionTest.php` → mirrors `app/User/Profile/Actions/UpdateProfileAction.php`
- `tests/Feature/Enrollment/Registration/ApproveRegistrationActionTest.php`
- `tests/Feature/Program/Internship/CreateInternshipActionTest.php`

### Test Structure

```php
describe('ApproveRegistrationAction', function () {
    it('approves a pending registration', function () {
        $registration = Registration::factory()->pending()->create();
        $data = ApproveRegistrationData::from(['status' => 'approved', 'feedback' => null]);

        $result = app(ApproveRegistrationAction::class)->execute($registration, $data);

        expect($result->status)->toBe(RegistrationStatus::APPROVED->value);
        expect($result->approved_at)->not->toBeNull();
    });

    it('throws RejectedException for already-approved registration', function () {
        $registration = Registration::factory()->approved()->create();
        $data = ApproveRegistrationData::from(['status' => 'approved', 'feedback' => null]);

        app(ApproveRegistrationAction::class)
            ->execute($registration, $data);
    })->throws(RejectedException::class);

    it('logs the approval action', function () {
        $registration = Registration::factory()->pending()->create();
        $data = ApproveRegistrationData::from(['status' => 'approved', 'feedback' => null]);

        SmartLogger::shouldReceive('info')
            ->with('registration_approved')
            ->once();

        app(ApproveRegistrationAction::class)->execute($registration, $data);
    });
});
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

### Where to Find It

- `docs/architecture/testing-pattern.md` — complete testing reference
- `docs/conventions.md` §22 — testing conventions
- `.agents/skills/pest-testing/SKILL.md` — Pest testing skill

---

## 16. Action Extraction Workflow

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

```
app/{Module}/{SubModule}/Actions/{Verb}{Entity}Action.php
```

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

```php
// Before
public function save(): void
{
    $this->validate();
    User::create($this->form->toArray());
    flash()->success(__('user.created'));
}

// After
public function save(CreateUserAction $action): void
{
    $this->validate();
    $action->execute($this->form->toArray());
    flash()->success(__('user.created'));
}
```

**9. Catch RejectedException.**

```php
public function save(CreateUserAction $action): void
{
    try {
        $this->validate();
        $action->execute($this->form->toArray());
        flash()->success(__('user.created'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

**10. Write the test.**

Create `tests/Feature/{Module}/{SubModule}/{Name}Test.php` covering happy path, rule violations,
validation failures, and side effects.

### Extraction Checklist

- [ ] New Action class in correct module/submodule directory
- [ ] `declare(strict_types=1)` and proper namespace
- [ ] Extends `BaseAction` (or plain class for Read)
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

### Where to Find It

- `.agents/skills/action-refactoring/SKILL.md` — full extraction workflow
- `.agents/skills/action-refactoring/rules/` — detailed rules: single responsibility (01),
  validation (02), side effects (03), entity delegation (04), error handling (05), naming (06)
