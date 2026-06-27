# Service Pattern

> **Last updated:** 2026-06-27
> **Changes:** fix: Services can exist at Core/Module/SubModule level (not only Core); add all 4 existing services; update Support vs Service distinction
> This document explains when and why Services exist despite the
> deliberate architectural choice of Actions over traditional Service classes. It is not an
> endorsement of the Service pattern — it is a boundary document that prevents Service scope creep.

---

## 1. Why Actions, Not Services

The decision to prefer Actions over Services is codified in
[ADR-003](../adr/adr-action-pattern-over-services.md).

Service classes are the conventional Laravel pattern — a single class with multiple public methods
representing related operations. Over time they become:

- **God classes**: a 3-method service becomes 20 methods with mixed responsibilities.
- **Hard to test**: one test file covers all methods; testing a single method requires understanding
  the entire service.
- **Hard to decorate**: cross-cutting concerns (transactions, logging, error handling) apply to the
  whole class, making per-method variation awkward.
- **Mutable state encouraged**: instance properties shared across method calls create hidden
  dependencies.

The Action Triad (Command, Read, Process) solves this by splitting business operations into types,
each with a single `execute()` method, a tailored contract, and 1:1 test-to-class mapping.

---

## 2. When Services Are Appropriate

Services are the **exception**, not the rule. They live at the same scope as their owning module —
**not** limited to `app/Core/Services/`.

| Scope | Path | Example |
|-------|------|---------|
| Global (cross-module infrastructure) | `app/Core/Services/` | `ModuleDiscoverService` |
| Module-level | `app/{Module}/Services/` | `User/Services/DashboardService` |
| Submodule-level | `app/{Module}/{SubModule}/Services/` | `SysAdmin/Observability/Services/PulseGuard` |

**Existing services:**

| File | Class | Scope | Purpose |
|------|-------|-------|---------|
| `Core/Services/ModuleDiscoverService.php` | `ModuleDiscoverService` | Global | Auto-discovers Livewire components, Gate policies, and Blade namespaces across all modules during boot |
| `User/Services/DashboardService.php` | `DashboardService` | Module | Resolves dashboard route name and shared stats by user role |
| `SysAdmin/Observability/Services/EnvironmentAuditor.php` | `EnvironmentAuditor` | Submodule | Audits PHP version, extensions, permissions, DB, terminal, frontend assets |
| `SysAdmin/Observability/Services/PulseGuard.php` | `PulseGuard` | Submodule | Pulse dashboard access guard (admin-only) |

A class belongs in `Services/` only when all of the following are true:

1. **It is infrastructure code**, not business logic. The class interacts with the framework,
   container, config, facades, or system environment — not with domain models or business rules.
2. **It does not fit a single Action**. The class provides multiple loosely related capabilities
   where extracting each into its own Action would create more surface area than value.
3. **It is NOT a mutation**. If the class writes to the database, it must be a Command Action.
4. **It is NOT a complex query**. If the class reads and transforms data from multiple models, it
   must be a Read Action.
5. **Constructor injection is used**. No `app()` make, no `resolve()` inside methods — dependencies
   are explicit. Static methods are acceptable only for framework hooks that require callables.

---

## 3. Services vs Support Convention

The `Services/` and `Support/` directories serve different purposes. See the
[Support Pattern](support-pattern.md) for the complete Support reference. In summary:

| Concern | Service | Support |
|---------|---------|---------|
| **Nature** | Infrastructure code | Utility/helper code |
| **Framework dependency** | Required (config, container, facades) | Optional |
| **Scope** | Service scope defines its reach | Module or submodule |
| **Multiple methods** | Allowed but should be minimal | Allowed |
| **Business logic** | Never | Never |
| **Database writes** | Never | Never |
| **Example** | `ModuleDiscoverService` (framework scanning) | `PiiMasker` (data transformation) |

**Rule of thumb:**

- **Service** when the class operates framework infrastructure (container, facades, config) at any
  scope — Core, Module, or SubModule.
- **Support** when the class provides pure utilities, transformations, or renderers that could
  theoretically work without the framework (even if they currently use it).
- When in doubt, prefer `Support/` over `Services/`. A Support class is easier to promote to a
  Service later than to downgrade a Service that accumulated Action-like responsibilities.

---

## 4. Service Patterns

### Constructor Injection

Services use constructor property promotion for framework dependencies, same as Actions. When a
Service needs explicit dependencies, they are injected via promoted constructor properties.

### Single Method vs Multiple Methods

Unlike Actions (which enforce exactly one `execute()` method), Services may expose multiple public
methods. This is permissible only when:

1. The methods are loosely related (not steps of the same workflow).
2. The methods do not share mutable state.
3. Each method is independently testable.

### Static Methods

Static methods in Services are the exception, not the rule. They are only acceptable when a
framework hook (e.g., an authorization callable `PulseGuard::viewPulse()`) requires a static
callable. New Services should use instance methods with constructor injection.

---

## 5. How Services Differ from Actions

| Concern | Service | Action |
| ------- | ------- | ------ |
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

- **Adding a New Service Without Review** — every new Service file must be reviewed against the five
  criteria above. If the class writes to the database, performs a complex query, or orchestrates a
  workflow, it must be an Action instead.

- **Convenience Service** — a single Service with create, update, delete, list, and export methods.
  This is a god class in the making. Each method should be its own Action.

- **Service That Calls Actions** — if a class composes multiple Actions, it IS an Action
  (specifically a Process Action). The Service layer adds nothing here.

- **Service With Transaction Logic** — transaction management is the responsibility of
  Command/Process Actions. A Service must never manage database transactions.

- **Static Helper Service** — stateless formatting utilities belong in `Support/`, not `Services/`.
  Services imply framework awareness; pure transformations do not qualify.

- **Service Injecting HTTP Dependencies** — Services must never depend on Livewire components,
  request instances, or session state. Move the logic into the Livewire component itself or into an
  Action that receives HTTP-scoped data as parameters.

- **Service as Action Wrapper** — never create a Service that calls Actions "to simplify the
  interface". Callers should inject Actions directly. If orchestration is needed, create a Process
  Action.

---

> If a Service method grows beyond its infrastructure scope, extract it into the appropriate Action
> type (Command, Read, or Process).
