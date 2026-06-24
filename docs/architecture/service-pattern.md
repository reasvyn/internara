# Service Pattern

> **Last updated:** 2026-06-24
> **Changes:** add note about app/Core/Services layer establishment; clarify when to use vs Actions
> This document explains when and why Services exist despite the deliberate architectural
> choice of Actions over traditional Service classes. It is not an endorsement of the Service pattern
> — it is a boundary document that prevents Service scope creep.

---

## 1. Why Actions, Not Services

The decision to prefer Actions over Services is codified in [ADR-003](../adr/adr-action-pattern-over-services.md).

Service classes are the conventional Laravel pattern — a single class with multiple public methods representing related operations. Over time they become:

- **God classes**: a 3-method service becomes 20 methods with mixed responsibilities.
- **Hard to test**: one test file covers all methods; testing a single method requires understanding the entire service.
- **Hard to decorate**: cross-cutting concerns (transactions, logging, error handling) apply to the whole class, making per-method variation awkward.
- **Mutable state encouraged**: instance properties shared across method calls create hidden dependencies.

The Action Triad (Command, Read, Process) solves this by splitting business operations into types, each with a single `execute()` method, a tailored contract, and 1:1 test-to-class mapping.

---

## 2. When Services Are Appropriate

Services are the **exception**, not the rule. They are colocated in `app/Core/Services/` as a dedicated layer for infrastructure-aware utilities that do not fit the Action model. 

**Existing services:**
- `ModuleDiscoverService` — module discovery and registration during boot

A class belongs in `Services/` only when all of the following are true:

1. **It is infrastructure code**, not business logic. The class interacts with the framework, container, config, facades, or system environment — not with domain models or business rules.
2. **It does not fit a single Action**. The class provides multiple loosely related capabilities where extracting each into its own Action would create more surface area than value.
3. **It is NOT a mutation**. If the class writes to the database, it must be a Command Action.
4. **It is NOT a complex query**. If the class reads and transforms data from multiple models, it must be a Read Action.
5. **Constructor injection is used**. No `app()` make, no `resolve()` inside methods — dependencies are explicit.

---

## 3. Services vs Support Convention

The `Services/` and `Support/` directories serve different purposes. `Support/` contains pure utility classes with no Eloquent or framework dependencies — they can be unit-tested without booting Laravel. `Services/` contains framework-aware infrastructure code that depends on the Laravel container, config, or facades.

**Rule of thumb:** If you can unit-test the class without booting Laravel, it belongs in `Support/`. If it needs `config()`, `app()`, or a framework service container, it belongs in `Services/`.

---

## 4. Service Patterns

### Constructor Injection

Services use constructor property promotion for framework dependencies, same as Actions. When a Service needs explicit dependencies, they are injected via promoted constructor properties.

### Single Method vs Multiple Methods

Unlike Actions (which enforce exactly one `execute()` method), Services may expose multiple public methods. This is permissible only when:

1. The methods are loosely related (not steps of the same workflow).
2. The methods do not share mutable state.
3. Each method is independently testable.

### Static Methods

Static methods in Services are the exception, not the rule. They are only acceptable when a framework hook (e.g., an authorization callable) requires a static callable. New Services should use instance methods with constructor injection.

---

## 5. How Services Differ from Actions

| Concern | Service | Action |
| --- | --- | --- |
| **Base class** | None | `BaseAction` (Command/Process) |
| **Transaction** | Not available | Required (Command/Process) |
| **Logging** | Not available | Required (Command/Process) |
| **Public methods** | One or more | Exactly one (`execute()`) |
| **State mutation** | Never | Always (Command) |
| **Business logic** | Never | Primary owner |

### Key Boundary

Services must **never**:

- Call `DB::transaction()` or wrap operations in database transactions.
- Write to the database (create, update, delete).
- Log to the activity log.
- Throw module exceptions.
- Dispatch events.

If you need any of the above, you need an Action.

---

## 6. Anti-Patterns to Avoid

- **Adding a New Service Without Review** — every new Service file must be reviewed against the five criteria above. If the class writes to the database, performs a complex query, or orchestrates a workflow, it must be an Action instead.

- **Convenience Service** — a single Service with create, update, delete, list, and export methods. This is a god class in the making. Each method should be its own Action.

- **Service That Calls Actions** — if a class composes multiple Actions, it IS an Action (specifically a Process Action). The Service layer adds nothing here.

- **Service With Transaction Logic** — transaction management is the responsibility of Command/Process Actions. A Service must never manage database transactions.

- **Static Helper Service** — stateless formatting utilities belong in `Support/`, not `Services/`. Services imply framework awareness; pure transformations do not qualify.

- **Service Injecting HTTP Dependencies** — Services must never depend on Livewire components, request instances, or session state. Move the logic into the Livewire component itself or into an Action that receives HTTP-scoped data as parameters.

---

> If a Service method grows beyond its infrastructure scope, extract it into the appropriate Action type (Command, Read, or Process).
