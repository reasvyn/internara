# Service Pattern

> **Last updated:** 2026-06-10
>
> This document explains when and why Services exist in Internara despite the deliberate architectural
> choice of Actions over traditional Service classes. It is not an endorsement of the Service pattern
> — it is a boundary document that prevents Service scope creep.

---

## Table of Contents

1. [Why Actions, Not Services](#1-why-actions-not-services)
2. [When Services Are Appropriate](#2-when-services-are-appropriate)
3. [Services vs Support Convention](#3-services-vs-support-convention)
4. [Service Patterns](#4-service-patterns)
5. [How Services Differ from Actions](#5-how-services-differ-from-actions)
6. [Migration Path: Service to Action Extraction](#6-migration-path-service-to-action-extraction)
7. [Anti-Patterns to Avoid](#7-anti-patterns-to-avoid)

---

## 1. Why Actions, Not Services

The decision to prefer Actions over Services is codified in [ADR-003](../adr/adr-action-pattern-over-services.md).
The core rationale:

### The Service Problem

Service classes are the conventional Laravel pattern — a single class with multiple public methods
representing related operations (e.g., `RegistrationService` with `register()`, `approve()`,
`reject()`). Over time they become:

- **God classes**: a 3-method service becomes 20 methods with mixed responsibilities.
- **Hard to test**: one test file covers all methods; testing a single method requires understanding
  the entire service.
- **Hard to decorate**: cross-cutting concerns (transactions, logging, error handling) apply to the
  whole class, making per-method variation awkward.
- **Mutable state encouraged**: instance properties shared across method calls create hidden
  dependencies.

### The Action Triad Solution

The system splits business operations into three types, each with a tailored contract:

| Type | Purpose | Base Class | Transaction | Logging |
| --- | --- | --- | --- | --- |
| **Command** | Mutations (create, update, delete) | `BaseAction` | Required | Required |
| **Read** | Complex queries | None | Never | Never |
| **Process** | Multi-step orchestration | `BaseAction` | Required | Required |

Every Action has a single `execute()` method. Test files map 1:1 with Action classes. Transaction
and logging are opt-in at the type level, not the method level.

### Decision Table

| Scenario | Pattern | Why Not a Service |
| --- | --- | --- |
| Create/update/delete | Command Action | Single responsibility, automatic transaction |
| State transition | Command Action | Single responsibility, required audit log |
| Simple list query | Inline in Livewire | No abstraction overhead for trivial reads |
| Complex aggregated query | Read Action | Lightweight, no unnecessary base class |
| Multi-step workflow | Process Action | Composes Actions, handles partial failure |
| Loose toolkit of related functions | **Service** | Only when no single Action can own the method |

---

## 2. When Services Are Appropriate

Services are the **exception**, not the rule. A class belongs in `app/{Module}/Services/` only when
all of the following are true:

1. **It is infrastructure code**, not business logic. The class interacts with the framework,
   container, config, facades, or system environment — not with domain models or business rules.
2. **It does not fit a single Action**. The class provides multiple loosely related capabilities
   where extracting each into its own Action would create more surface area than value.
3. **It is NOT a mutation**. If the class writes to the database, it must be a Command Action.
4. **It is NOT a complex query**. If the class reads and transforms data from multiple models, it
   must be a Read Action.
5. **Constructor injection is used**. No `app()` make, no `resolve()` inside methods — dependencies
   are explicit.

### Legitimate Service Use Cases

| Use Case | Example | Why Service |
| --- | --- | --- |
| Environment auditing | `EnvironmentAuditor` | Infrastructure introspection; multiple check methods; no single "verb+entity" fits |
| Dashboard routing | `DashboardService` | Stateless routing logic; returns a string route name; no database mutation |
| Third-party guard | `PulseGuard` | One-off static guard for an external package; not a policy |

### When to Reconsider

If you are adding a Service and find yourself writing:

- A `transaction()` call → this should be a Command Action.
- A query that joins three models and returns typed DTOs → this should be a Read Action.
- A multi-step workflow that calls other Services → this should be a Process Action.

---

## 3. Services vs Support Convention

The directory `Support/` and `Services/` serve different purposes. See
[conventions.md §Services vs Support](../conventions.md#services-vs-support) for the authoritative
reference.

| Directory | Purpose | Example | Depends On |
| --- | --- | --- | --- |
| `Support/` | Pure utility classes, no Eloquent, no framework dependencies | `Theme`, `CsvHandler`, `PiiMasker` | Nothing outside PHP |
| `Services/` | Framework-aware infrastructure code | `EnvironmentAuditor`, `PulseGuard` | Laravel container, config, facades |

**Rule of thumb:** If you can unit-test the class without booting Laravel, it belongs in `Support/`.
If it needs `config()`, `app()`, or a framework service container, it belongs in `Services/`.

---

## 4. Service Patterns

### Constructor Injection

Services use constructor property promotion for framework dependencies, same as Actions:

```php
class EnvironmentAuditor
{
    // No constructor needed — all dependencies come from facades/config
}
```

```php
class DashboardService
{
    // No constructor needed — uses auth() facade internally
}
```

When a Service does need explicit dependencies, they are injected via promoted constructor
properties:

```php
class SomeService
{
    public function __construct(
        protected readonly SomeDependency $dependency,
    ) {}
}
```

### Single Method vs Multiple Methods

Unlike Actions (which enforce exactly one `execute()` method), Services may expose multiple public
methods. This is permissible only when:

1. The methods are loosely related (not steps of the same workflow).
2. The methods do not share mutable state.
3. Each method is independently testable.

`EnvironmentAuditor` has one public method (`audit()`) and seven private helpers — it follows the
Action spirit. `DashboardService` has two public methods (`getDashboardForUser()` and
`getSharedStats()`) that share no state and serve different callers.

### Static Methods

Static methods in Services are the exception, not the rule. `PulseGuard::viewPulse()` is static
because Laravel Pulse's authorization hook expects a callable — it cannot be an instance method.
New Services should use instance methods with constructor injection.

---

## 5. How Services Differ from Actions

| Concern | Service | Command/Process Action | Read Action |
| --- | --- | --- | --- |
| **Base class** | None | `BaseAction` | None |
| **`transaction()`** | Not available | Required | Never |
| **`log()`** | Not available | Required | Never |
| **`HandlesActionErrors`** | Not available | Available | Available (opt-in) |
| **Public methods** | One or more | Exactly one (`execute()`) | One or more |
| **Naming convention** | `{Name}Service` | `{Verb}{Entity}Action` | `{Context}Reader` / `{Entity}Query` |
| **Test file scope** | All methods in one test | Single `execute()` per test file | Single method per test file |
| **State mutation** | Never | Always | Never |
| **Business logic** | Never | Primary owner | Query logic only |

### Key Boundary

Services must **never**:

- Call `DB::transaction()` or wrap operations in database transactions.
- Write to the database (create, update, delete).
- Log to the activity log (`SmartLogger`).
- Throw module exceptions (`ModuleException`, `RejectedException`).
- Dispatch events.

If you need any of the above, you need an Action.

---

## 6. Migration Path: Service to Action Extraction

When a Service method grows beyond its infrastructure scope, extract it into the appropriate Action
type. The general process:

### Step 1: Identify the Extraction Candidate

Look for methods that:

- Accept a Model parameter and perform business logic on it.
- Query the database across multiple models.
- Perform a state transition or mutation.
- Are called from a Livewire component and contain logic that should be testable in isolation.

### Step 2: Choose the Action Type

| Service Method Behavior | Target Action Type |
| --- | --- |
| Validates data and writes to the database | Command Action |
| Queries and transforms data from multiple models | Read Action |
| Writes to the database and dispatches notifications | Process Action |

### Step 3: Extract and Replace

1. Create the Action class in the appropriate `Actions/` directory.
2. Move the method body into `execute()`, converting thrown exceptions as needed.
3. Add `$this->transaction()` and `$this->log()` for Command/Process types.
4. Replace all calls to `$service->method(...)` with `$action->execute(...)`.
5. Remove the original Service method once no callers remain.
6. If the Service becomes empty after extraction, delete it entirely.

### Example: Migrating `DashboardService`

`DashboardService::getDashboardForUser()` returns a route name string based on role — this is
trivial routing logic that can stay. But `getSharedStats()` accesses `auth()` and returns an array;
if it ever grows to include database queries, extract it as `GetDashboardSharedData` Read Action.

---

## 7. Anti-Patterns to Avoid

### Adding a New Service Without Review

Every new Service file must be reviewed against the criteria in §2. If the class writes to the
database, performs a complex query, or orchestrates a workflow, it must be an Action instead. A new
Service should be as notable as a new architectural exception.

### The "Convenience" Service

```php
// ❌ ANTI-PATTERN
class UserService
{
    public function create(array $data): User { ... }
    public function update(User $user, array $data): User { ... }
    public function delete(User $user): void { ... }
    public function list(array $filters): Collection { ... }
    public function activate(User $user): void { ... }
    public function deactivate(User $user): void { ... }
    public function exportCsv(): string { ... }
}
```

This is a god class in the making. Each method should be its own Action (`CreateUserAction`,
`UpdateUserAction`, `DeleteUserAction`, etc.), and the CSV export belongs in `Support/` or a
dedicated Read Action.

### Service That Calls Actions

```php
// ❌ ANTI-PATTERN
class RegistrationService
{
    public function __construct(
        protected CreateRegistrationAction $createRegistration,
        protected AssignPlacementAction $assignPlacement,
    ) {}

    public function register(RegisterStudentData $data): void
    {
        $this->createRegistration->execute($data);
        $this->assignPlacement->execute($data);
    }
}
```

If a class composes multiple Actions, it IS an Action — specifically a Process Action. The Service
layer adds nothing here.

### Service With Transaction Logic

```php
// ❌ ANTI-PATTERN
class ProfileService
{
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // ...
        });
    }
}
```

Transaction management is the responsibility of Command/Process Actions via `$this->transaction()`.
A Service must never manage database transactions.

### Static Helper Service

```php
// ❌ ANTI-PATTERN — use Support/ instead
class FormattingService
{
    public static function phone(string $number): string { ... }
    public static function currency(float $amount): string { ... }
}
```

Stateless formatting utilities belong in `Support/`, not `Services/`. Services imply framework
awareness; pure transformations do not qualify.

### Service Injecting Livewire or HTTP Dependencies

Services must never depend on Livewire components, request instances, or session state. If a class
imports a Livewire component or `Illuminate\Http\Request`, it has crossed a layer boundary. Move the
logic into the Livewire component itself or into an Action that receives HTTP-scoped data as
parameters.

---

## References

- [ADR-003: Action Pattern over Services](../adr/adr-action-pattern-over-services.md) — full rationale
- [Architecture: Action Triad](../architecture.md#action-triad-command-read-process) — Command/Read/Process contracts
- [Conventions: Services vs Support](../conventions.md#services-vs-support) — directory purpose table
- [Action Pattern](action-pattern.md) — detailed Action implementation guide
