# ADR-002: Module-First Architecture
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Status
Accepted

## Context

The application manages a complex business module — vocational fieldwork management — with 23 distinct

`app/Module/{Module}/` and owns its complete vertical slice. The 23 modules are:

| Module | Boundary | Key Concept |
|---|---|---|
| **Core** | Base classes, infrastructure, and cross-module utilities everything depends on | `BaseModel`, `BaseEntity`, `BaseAction`, `AppException`, `Integrity`, `Theme`, `CsvHandler`, `Environment`, `Locale` |
| **Auth** | Identity & access control | Login, passwords, account lifecycle, recovery |
| **User** | User profile & identity | Profile editing, dashboard routing |
| **School** | Institution configuration | Departments, academic years |
| **Settings** | Runtime configuration | Key-value store, branding, localization |
| **Setup** | First-run installation | Wizard, environment audit, provisioning |
| **Admin** | System administration | User CRUD, announcements, GDPR |
| **Partnership** | External relationships | Companies, partnership agreements |
| **Placement** | Slot management | Capacity, direct assignments, change requests |
| **Registration** | Student enrollment | Applications, wizard, document upload |
| **Internship** | Program execution | Reports, requirements, closure |
| **Mentor** | Mentoring & supervision | Logs, teacher/supervisor portals |
| **Mentee** | Student role | Dashboard, program participation |
| **Attendance** | Presence tracking | Clock-in/out, absence requests |
| **Logbook** | Daily journals | Student diary entries |
| **Schedule** | Event planning | Calendar management |
| **Guidance** | Handbooks | Versioned documents, acknowledgements |
| **Incident** | Issue reporting | Report, investigation, resolution |
| **Assignment** | Tasks & submissions | Creation, grading workflow |
| **Assessment** | Competency evaluation | Rubrics, scoring, presentations |
| **Evaluation** | Program & mentor quality | Multi-type feedback collection |
| **Document** | Template management | Rendering, report generation |
| **Certificate** | Credentialing | Issuance, templates, revocation |

### Layer Structure

The architecture is defined in 12 layers. A module directory combines multiple layers into a
single vertical slice:

```
Layer 12 — Business Modules
Layer 11 — UI / Presentation (Livewire, Blade)
Layer 10 — HTTP Layer (Controllers, Middleware, Routes)
Layer  9 — Communication (Events, Listeners, Notifications, Console)
Layer  8 — Authorization (Policies, RBAC)
Layer  7 — Business Operations (Command Actions, Read Actions, Process Actions)
Layer  6 — Module Rules (Enums, Entities, Data DTOs)
Layer  5 — Module Models (Eloquent)
Layer  4 — Core Base Classes
Layer  3 — Core Contracts
Layer  2 — Persistence (Database, Config, Cache, Queue, Files)
Layer  1 — Infrastructure (PHP, Laravel, Spatie packages)
```

A module directory `app/Module/{Module}/` corresponds to Layer 12, with its subdirectories
crossing layers 5–11. Layers 1–4 are shared infrastructure provided by the **Core** module.

### Module Structure

```
app/Module/{Module}/
├── Actions/         → Command, Read, Process — 1 class = 1 use case
├── Models/          → Eloquent persistence layer
├── Livewire/        → Reactive UI components
│   └── Forms/       → Form Objects for complex forms (optional)
├── Policies/        → Authorization gates
├── Enums/           → Constants with behavior (LabelEnum, StatusEnum)
├── Entities/        → Business rules without framework dependencies
├── Data/            → DTOs for typed input/output (optional, gradual)
├── Http/            → Controllers & middleware (optional, Livewire-first)
├── Notifications/   → Mail, database, broadcast alerts (optional)
├── Events/          → Module events emitted (optional, gradual)
├── Listeners/       → Event subscribers (optional, gradual)
├── Console/         → Artisan commands (optional)
├── Support/         → Module utilities (optional)
└── Contracts/       → Module interfaces (optional)
```

Not every module needs every directory. Simple modules like `Mentee` may only need
Models + Actions + Livewire. Complex modules like `Internship` add Events, Listeners,
Notifications, and Http layers.

### Auto-Discovery

`AppServiceProvider` automatically discovers and registers module artifacts:

| Artifact | Discovery Method | Cache |
|---|---|---|
| Livewire components | Scans `app/Module/*/Livewire/`, registers as `{kebab-module}.{kebab-class}` | 24h |
| Policies | Scans `app/Module/*/Policies/`, auto-links to model matching policy name | 24h |
| Blade namespaces | Scans `resources/views/*/`, registers as `x-{module}::` + `{module}::` | 24h |

Cross-module policies (e.g., `InternshipRegistrationPolicy` gating `Registration\Models\Registration`)
and event listeners are registered manually in `AppServiceProvider`.

### Cross-Module Communication Rules

No module may import another module's Models, Actions, or Livewire components directly.
Allowed communication paths:

1. **Core contracts** (Layer 3) — shared interfaces like `SendsNotifications`, `LabelEnum`,
   `StatusEnum`. Any module implements them, any module consumes them through the container.
2. **Module events** (Layer 9) — a Command Action dispatches an event; listeners in any module
   react. Preferred for fire-and-forget side effects.
3. **Action delegation** — a Process Action may call another module's Action via its public
   `execute()` method. The called Action must accept primitives or DTOs, never Models.

### Known Violations

The following cross-module imports exist in the current codebase and need remediation:

| Violation | Correct Approach |
|---|---|
| `Internship\Models\Internship` imports `School\Models\AcademicYear` | Use Core contract or query in School module |
| `Internship\Policies\CompanyPolicy` gates `Partnership\Models\Company` | Move policy to `Partnership\Policies` |
| `Internship\Policies\InternshipRegistrationPolicy` gates `Registration\Models\Registration` | Move policy to `Registration\Policies` |

### Enforcement Gap

Architecture tests that previously enforced module boundaries (`DomainBoundariesArchTest`,
`LayerSeparationArchTest`) were removed due to a `pest-plugin-arch` compatibility bug.
Until they are restored, boundary enforcement relies on code review and PHPStan analysis.

## Consequences

- **Positive**: A feature touches exactly one directory tree — high cohesion, low coupling.
- **Positive**: Module boundaries are explicit. Adding a new module is mechanical — create the
  directory, add subdirectories as needed, register routes.
- **Positive**: Refactoring a module (e.g., changing Registration's state machine) affects only
  `app/Module/Registration/` and its consumers via defined interfaces.
- **Positive**: Each module can be developed, tested, and reasoned about independently.
  Team members can own entire modules without stepping on each other.
- **Negative**: Slightly more boilerplate than flat layering for very simple modules (e.g.,
  a single-model module still needs at minimum Models + Actions directories).
- **Negative**: Laravel's auto-discovery expects flat structures occasionally, requiring
  explicit registration in `AppServiceProvider`.
- **Negative**: Cross-module boundaries are not mechanically enforced until architecture tests
  are restored. Code review is the current gate.

## References

- `app/Module/` (23 module directories; the former Shared module was merged into Core)
- `app/Core/` (base classes, contracts, exceptions, infrastructure)
- `app/Providers/AppServiceProvider.php` (auto-discovery, manual registrations)
- `docs/architecture.md` — 12-layer architecture, dependency rules, cross-module communication
- `docs/conventions.md` — coding conventions derived from this architecture
