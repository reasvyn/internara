# Service Pattern — Infrastructure Logic, Scope & Constructor Injection

> **Last updated:** 2026-06-27
> **Changes:** rewrite — clarify "domain business logic" vs infrastructure logic; Service = instance methods with constructor injection; Support = static utilities only
## Description

Boundaries between Services (infrastructure logic), Support (static utilities), and Actions (domain business logic), with scope and placement rules.

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

| File | Class | Scope | Method style | Purpose |
|------|-------|-------|-------------|---------|
| `Core/Services/ModuleDiscoverService.php` | `ModuleDiscoverService` | Global | Instance + constructor injection | Auto-discovers Livewire, policies, Blade namespaces across modules |
| `User/Services/DashboardService.php` | `DashboardService` | Module | Instance (`auth()` facade) | Resolves dashboard route name by user role |
| `SysAdmin/Observability/Services/EnvironmentAuditor.php` | `EnvironmentAuditor` | Submodule | Instance (`config()`, `base_path()`) | Audits PHP, extensions, permissions, DB, terminal, assets |
| `SysAdmin/Observability/Services/PulseGuard.php` | `PulseGuard` | Submodule | Static (framework hook) | Pulse dashboard access guard |

A class belongs in `Services/` only when all of the following are true:

1. **It is infrastructure code**, not **domain** business logic. The class may contain
   **infrastructure logic** (decisions about framework, environment, routing, or system state) but
   must **never** contain **domain business logic** (rules about internships, students, grades,
   enrollments, or other business concepts).
2. **It uses instance methods with constructor injection.** Static methods are acceptable only for
   framework hooks that require static callables (e.g., `PulseGuard::viewPulse()`).
3. **It does not fit a single Action.** Multiple loosely related capabilities where extracting each
   into its own Action would create more surface area than value.
4. **It is NOT a mutation.** If it writes to the database, it must be a Command Action.
5. **It is NOT a complex query.** If it reads from multiple models with business aggregation, it
   must be a Read Action.

---

## 3. The Two Kinds of "Logic"

This distinction is critical to understanding when a Service is appropriate:

| Type of logic | Definition | Example | Belongs in |
|--------------|------------|---------|------------|
| **Domain business logic** | Rules about the business domain: students, internships, grades, enrollments, assessments, certificates | "A student must have an active registration to submit a logbook" | Action + Entity |
| **Infrastructure logic** | Rules about the framework, environment, UI routing, system state | "Scan PHP files and register them as Livewire components" | Service |

**What this means in practice:**

- `DashboardService.getDashboardForUser()` maps roles to dashboard names — this is **infrastructure
  logic** (UI routing concern), NOT domain business logic. ✅ Correct in Service.
- `EnvironmentAuditor.audit()` checks PHP extensions and permissions — pure infrastructure. ✅
- `PulseGuard.viewPulse()` checks admin role — infrastructure access guard. ✅
- A class that decides "can this registration be approved" contains **domain business logic** and
  must be an Action + Entity instead. ❌ Not Service.

### Key Boundary

Services must **never**:

- Call `DB::transaction()` or wrap operations in database transactions.
- Write to the database (create, update, delete).
- Log to the activity log.
- Throw module exceptions (`RejectedException`, etc.).
- Dispatch events.
- Call Actions.
- Contain **domain** business logic.

If you need any of the above, you need an Action.

---

## 4. Services vs Support Convention

The `Services/` and `Support/` directories serve fundamentally different purposes:

| Concern | Service | Support |
|---------|---------|---------|
| **Method style** | Instance methods only (constructor injection) | **`public static` methods only** |
| **Constructor injection** | Required | **Never permitted** |
| **Framework dependency** | Required (config, container, facades) | Minimal to none. Static `config()` calls OK; instance deps are not. |
| **Scope** | Core, Module, or SubModule | Module or submodule |
| **Multiple methods** | Allowed but should be minimal | Allowed (all static) |
| **Domain business logic** | Never | Never |
| **Database writes** | Never | Never |
| **Typical purpose** | Framework operations, environment checks, UI routing | Pure transformations, string manipulation, color math, simple generators |

**Rule of thumb:**

- If the class needs **constructor injection** → it is a **Service**.
- If the class can be written with **only `public static` methods** and no constructor injection →
  it is **Support**.
- If you're tempted to add constructor injection to a Support class → it should probably be a
  Service instead.

See the [Support Pattern](support-pattern.md) for the complete Support reference.

---

## 5. Service Patterns

### Constructor Injection

Services use constructor property promotion for framework dependencies:

```php
final readonly class ModuleDiscoverService
{
    public function __construct(
        private Cache $cache,
    ) {}
}
```

### Instance Methods

All public methods are instance methods. Static methods are the exception, permitted only when a
framework hook requires a static callable (e.g., a Gate callback or middleware resolve).

### Single Method vs Multiple Methods

Unlike Actions (which enforce exactly one `execute()` method), Services may expose multiple public
methods. This is permissible only when:

1. The methods are loosely related (not steps of the same workflow).
2. The methods do not share mutable state.
3. Each method is independently testable.

---

## 6. Existing Service Audit

These are the current Service classes and their verification against the rules:

| Class | Instance methods? | Constructor injection? | Domain business logic? | Verdict |
|-------|------------------|----------------------|----------------------|---------|
| `ModuleDiscoverService` | ✅ Yes | ✅ Yes (`Cache`) | ❌ No — pure infra | ✅ Correct |
| `EnvironmentAuditor` | ✅ Yes | ❌ No (uses `config()`, `base_path()` directly) | ❌ No — pure infra | ✅ Correct (borderline: could add injection) |
| `PulseGuard` | ❌ Static | ❌ No | ❌ No — infra guard | ✅ Correct (static required by framework hook) |
| `DashboardService` | ✅ Yes | ❌ No (uses `auth()` facade) | ❌ No — UI routing only | ✅ Correct (could add injection for testability) |

**Coming from Support (should migrate to Service):**

| File | Current location | Why |
|------|----------------|-----|
| `SmartLogger` | `Core/Support/` | Instance methods, constructor injection, framework facades, DB writes, events |
| `CsvHandler` | `Core/Support/` | Instance methods, framework deps (`StreamedResponse`, `Collection`) |
| `Settings` | `Settings/Support/` | Instance methods, framework deps (`Cache`, `Config`) |
| `Brand` | `Settings/Support/` | Instance methods, framework deps (`Cache`) |
| `Theme` | `Settings/Theme/Support/` | Instance methods, framework deps (`Cache`, `SettingsStore`) |
| `Locale` | `Settings/Locale/Support/` | Instance methods, framework deps (`App`, `Cookie`) |
| `DocumentRenderer` | `Document/Support/` | Instance via constructor injection (`Pdf`, `Blade`, `Storage`) |
| `CertificateRenderer` | `Certification/Certificate/Support/` | Instance via constructor injection (`Pdf`, `Blade`, `Storage`) |
| `AppInfo` | `Core/Support/` | Static but uses `Cache`, `Config`, `File` facades — framework-aware infra |
| `SmartLogger` | `Core/Support/` | Heavy framework deps, DB writes, events — already flagged |

---

## 7. How Services Differ from Actions

| Concern | Service | Action |
| ------- | ------- | ------ |
| **Base class** | None | `BaseAction` (Command/Process) |
| **Transaction** | Not available | Required (Command/Process) |
| **Logging** | Not available | Required (Command/Process) |
| **Public methods** | One or more (instance) | Exactly one (`execute()`) |
| **State mutation** | Never | Always (Command) |
| **Domain business logic** | Never | Primary owner |

---

## 8. Anti-Patterns to Avoid

- **Adding a New Service Without Review** — every new Service file must be reviewed against the five
  criteria above.

- **Convenience Service** — a single Service with create, update, delete, list, and export methods.
  Each method should be its own Command/Read Action.

- **Service That Calls Actions** — if a class composes multiple Actions, it IS a Process Action.
  The Service layer adds nothing here.

- **Service With Transaction Logic** — transaction management belongs in Command/Process Actions.

- **Static Helper Service** — pure static utilities belong in `Support/`, not `Services/`. Services
  use instance methods; Support uses static methods.

- **Service Injecting HTTP Dependencies** — Services must never depend on Livewire components,
  request instances, or session state.

- **Service as Action Wrapper** — never create a Service that calls Actions to "simplify the
  interface". Callers should inject Actions directly.

- **Domain Logic in Service** — if a Service method evaluates a business rule (e.g., "can this
  student graduate?"), extract it into an Entity + Action.

---

> If a Service accumulates domain business logic, extract it into the appropriate Action type
> (Command, Read, or Process).
