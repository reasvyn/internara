# Support Pattern

> **Last updated:** 2026-06-27
> **Changes:** initial — document Support utility pattern, clarify Action vs Service vs Support boundaries

Defines the Support utility layer — module-level helpers, formatters, generators, and renderers that
do not fit the Action model but are not infrastructure-wide enough for Core Services.

---

## 1. What Support Is

The `Support/` directory holds utility classes that:

- Serve a **specific module or submodule** (not cross-cutting infrastructure).
- Provide **one or more loosely related helper methods** — not a single business operation.
- May depend on **framework components** (Cache, Config, Facades, models from the same module) — but
  ideally are pure where possible.
- Are **action-unaware** — they never call Actions, never dispatch events, never manage transactions.

**Existing support classes by category:**

| Category | Examples | Location |
|----------|----------|----------|
| **Pure helpers** | `Color`, `PiiMasker`, `PasswordRules`, `CsvHandler` | `app/Core/Support/` |
| **Module utilities** | `Settings`, `Brand`, `Theme`, `Locale` | `app/Settings/*/Support/` |
| **Generators** | `UserIdentifierGenerator` | `app/User/Support/` |
| **Renderers** | `DocumentRenderer`, `CertificateRenderer` | `app/*/Support/` |
| **Orchestrators** | `SystemProvisioner`, `BackupRunner` | `app/*/Support/` |
| **Global helpers** | `helpers.php` (module-level `__()` wrappers) | `app/{Module}/Support/` |
| **Info/Integrity** | `AppInfo`, `AppIntegrity`, `Environment` | `app/Core/Support/` |
| **Logging** | `SmartLogger`, `LangChecker` | `app/Core/Support/` |

---

## 2. Where Support Lives

Support classes are colocated with their owning module:

| Scope | Path | Example |
|-------|------|---------|
| Shared (any module) | `app/Core/Support/` | `app/Core/Support/PiiMasker.php` |
| Module-level | `app/{Module}/Support/` | `app/User/Support/UserIdentifierGenerator.php` |
| Submodule-level | `app/{Module}/{SubModule}/Support/` | `app/Settings/Theme/Support/Theme.php` |

**Rules for placement:**

- **Module-specific** (used by one module only) → `app/{Module}/Support/`
- **Submodule-specific** → `app/{Module}/{SubModule}/Support/`
- **Cross-module** (used by 3+ modules) → `app/Core/Support/`
- **Framework-level infrastructure** with container dependency → `app/Core/Services/` instead

A Support class may be promoted to `app/Core/Support/` when it grows to serve multiple modules. It
may be demoted to a submodule scope when it is only used there.

---

## 3. Support vs Actions

| Concern | Support | Action |
|---------|---------|--------|
| **Base class** | None | `BaseAction` (Command/Read/Process) |
| **Entry point** | Any public method(s) | Exactly one `execute()` |
| **Transaction** | Never | Required (Command/Process) |
| **Logging** | Not required | Required (Command/Process) |
| **Event dispatch** | Never | Recommended (Command) |
| **Business rules** | Delegates to caller | Direct owner |
| **Database writes** | Never | Always (Command) |
| **Framework dependency** | Optional | Uses injected deps |
| **Test style** | Unit or integration | Feature (database) |

### When to Choose Action Over Support

- The class performs a single business operation (create, update, delete, complex read, orchestrate).
- The class needs transaction safety or logging.
- The class dispatches events.

If any of these are true, use an Action. A Support class is never a downgrade path for an Action.

---

## 4. Support vs Services

| Concern | Support | Service |
|---------|---------|---------|
| **Scope** | Module or submodule | Global (`app/Core/Services/`) |
| **Framework dependency** | Optional (may use Cache, Config, Facades) | Required (container, config, facades) |
| **Framework injection** | Possible but optional | Required (constructor injection) |
| **Number of classes** | 20+ across modules | 1 (`ModuleDiscoverService`) |
| **Unit-testable without Laravel** | Ideally yes, but not required | No |
| **Business logic** | Utility only | Infrastructure only |

**Rule of thumb:** A class belongs in `Services/` only when it serves the entire application (not a
single module) AND depends on the framework container or service provider system. Everything else
goes in `Support/`.

### Migration Path: Support → Service

When a Support class grows to:
1. Serve 3+ modules (becoming cross-cutting), AND
2. Depend on framework container or provider registration

...it may be promoted to `app/Core/Services/`. No Support class currently meets this threshold.

---

## 5. When to Create a Support Class

Create a Support utility when:

1. **The logic is not a single business operation.** If it has multiple loosely related methods
   (e.g., `export()`, `import()`, `validate()`), Support is likely the right home. A single-method
   utility with no side effects is still fine as Support.
2. **The logic is module-specific and reusable within that module.** If two or more classes within
   the same module need the same helper, extract it to `app/{Module}/Support/`.
3. **The logic is a pure transformation with no side effects.** Formatters, maskers, validators,
   generators without database interaction belong in Support.
4. **The logic bridges framework components for a module.** A class that reads cached settings and
   provides typed accessors (like `Brand`, `Theme`) is Support — it serves one module and has no
   container-level dependency.

### Do NOT Create a Support Class For

- **Business operations** — create an Action instead.
- **Cross-cutting infrastructure** — use `app/Core/Services/` if framework-aware, or add to
  `app/Core/Support/` if pure.
- **Single-use inline logic** — keep it in the calling Action/Livewire until a second caller
  appears.
- **Eloquent scopes or accessors** — use model traits or local scopes instead.

---

## 6. Anti-Patterns

- **Support calling Actions** — a Support class must never call an Action. If orchestration is
  needed, create a Process Action that uses both the Support utility and the necessary Command
  Actions.
- **Support with database writes** — persistence always goes through a Command Action. A Support
  class reading from the database (via Model) is acceptable for read-only queries; writing is not.
- **Support as a dumping ground** — a `Support/` directory with unrelated classes (some formatting,
  some business logic, some database queries) indicates missing Action boundaries. Extract business
  operations into Actions.
- **Static everything** — static methods in Support are acceptable for pure transformations, but
  Support classes that depend on framework state should use instance methods with injected
  dependencies.
- **Support duplicating Action logic** — if a Support method does the same thing as an Action
  (validates, logs, dispatches), refactor to use the Action instead.

---

## 7. Relationship Summary

```
┌──────────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                          │
│                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────────────────┐ │
│  │       ACTION         │  │          SUPPORT                 │ │
│  │  (single operation)  │  │  (module utilities, helpers)     │ │
│  │                      │  │                                  │ │
│  │  • execute()         │  │  • any public method(s)          │ │
│  │  • transaction       │  │  • no transaction                │ │
│  │  • logging           │  │  • no logging mandate            │ │
│  │  • event dispatch    │  │  • no events                     │ │
│  │  • business rules    │  │  • no business decisions          │ │
│  └──────────────────────┘  └──────────────────────────────────┘ │
│              ▲                             ▲                     │
│              │                             │                     │
│              └────────── uses ─────────────┘                     │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │                    SERVICE (global infra)                 │    │
│  │  • framework-aware, container-dependent                  │    │
│  │  • never calls Actions, never writes DB                  │    │
│  │  • exactly 1 class (ModuleDiscoverService)               │    │
│  └──────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```

**Key boundaries:**

- **Action** → owns business operations. Transactions, logging, events.
- **Support** → owns module utilities. Stateless or stateful, no business decisions.
- **Service** → owns global infrastructure. Framework-aware, application-wide.

An Action may use a Support utility. A Support utility may use a Service. A Service must never
call an Action.

---

> For the distinction between Support and Services in more detail, see the
> [Service Pattern](service-pattern.md) (§3 Services vs Support Convention).
