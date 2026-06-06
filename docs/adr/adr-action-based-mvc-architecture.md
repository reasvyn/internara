# ADR-002: Action-based MVC Architecture
> Last updated: 2026-06-06
> Changes: Updated cross-module import policy to match architecture.md (sibling imports allowed), fixed module names in known violations table


## Status
Accepted

## Context

The application manages a complex business module — vocational fieldwork management — with 19 distinct modules, each owning its complete vertical slice under `app/{Module}/`. The 19 modules are:

| Module | Boundary | Key Concept |
|---|---|---|
| **Core** | Base classes and core interfaces everything depends on | `BaseModel`, `BaseAction`, `BaseEntity`, `BasePolicy`, `SmartLogger` |
| **Shared** | Cross-cutting helper traits, utilities, and global UI components | `CacheKeys`, `CsvHandler`, concrete exceptions, theme switcher |
| **User** | Identity, authentication, and profiles | Login, passwords, profile details (NISN/NIP), recovery |
| **SysAdmin** | System administration and user management | Announcements, GDPR audit logs, account lifecycle |
| **Settings** | System-wide configuration and branding | Key-value store, dynamic branding, color presets, mail config |
| **Setup** | First-run wizard and environment provisioning | SetupState, installation checks, database seeder triggers |
| **Academics** | Academic calendar and department mapping | Departments, academic years |
| **Partners** | Industrial relationship directories | Companies, partnership agreement contracts |
| **Program** | Internship program configurations | Program timelines, phase timelines (JSON), required templates lists (JSON) |
| **Enrollment** | Student registration and placement slots | Registrations, placements, application wizard |
| **Guidance** | Mentoring relationships and handbooks | Handbooks, handbook acknowledgements, supervision logs |
| **Journals** | Daily activity and attendance tracking | Presences, absence requests, schedules, logbooks |
| **Assignment** | Coursework tasks and submissions | Task creation, student submissions (including report document drafts) |
| **Assessment** | Competency grading templates | Rubrics (JSON structures), student assessments |
| **Evaluation** | Mentor feedback collection | Supervisor evaluations, feedback forms |
| **Reports** | Final student grade cards | Composite score aggregation (*Rapor PKL*), grade locking |
| **Certification** | Credential issuance | Digital certificates, serial numbers, QR hashes |
| **Incident** | Safety and disciplinary logging | Incident reports, severity, investigation workflows |
| **Document** | Official correspondence rendering | Permit letters, templates, PDF compiler driver |

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

A module directory `app/{Module}/` corresponds to Layer 12, with its subdirectories
crossing layers 5–11. Layers 1–4 are shared infrastructure provided by the **Core** module.

### Module Structure

```
app/{Module}/
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
| Livewire components | Scans `app/*/Livewire/`, registers as `{kebab-module}.{kebab-class}` | 24h |
| Policies | Scans `app/*/Policies/`, auto-links to model matching policy name | 24h |
| Blade namespaces | Scans `resources/views/*/`, registers as `x-{module}::` + `{module}::` | 24h |

Cross-module policies (e.g., `InternshipRegistrationPolicy` gating `Registration\Models\Registration`)
and event listeners are registered manually in `AppServiceProvider`.

### Cross-Module Communication Rules

Cross-module imports are **allowed**. Modules may import each other's Models, Actions, or Livewire
components directly. The following patterns provide guidance, not enforcement:

1. **Direct import** (simplest) — straightforward cross-module access when no decoupling is needed.
2. **Core contracts** (Layer 3) — shared interfaces like `SendsNotifications`, `LabelEnum`,
   `StatusEnum`. Any module implements them, any module consumes them through the container.
3. **Module events** (Layer 9) — a Command Action dispatches an event; listeners in any module
   react. Preferred for fire-and-forget side effects.
4. **Action delegation** — a Process Action may call another module's Action via its public
   `execute()` method. The called Action must accept primitives or DTOs, never Models.

### Known Cross-Module Patterns

The following cross-module imports exist in the current codebase:

| Pattern | Description |
|---|---|
| `Program\Models\Internship` imports `Academics\Models\AcademicYear` | Direct cross-module model access (allowed) |
| `Program\Policies\CompanyPolicy` gates `Partners\Models\Company` | Cross-module policy gating a model in another module (allowed) |
| `Program\Policies\InternshipRegistrationPolicy` gates `Enrollment\Models\Registration` | Cross-module policy gating a model in another module (allowed) |

### Enforcement Gap

Architecture tests that previously enforced module boundaries (`DomainBoundariesArchTest`,
`LayerSeparationArchTest`) were removed due to a `pest-plugin-arch` compatibility bug.
Until they are restored, boundary enforcement relies on code review and PHPStan analysis.

## Consequences

- **Positive**: A feature touches exactly one directory tree — high cohesion, low coupling.
- **Positive**: Module boundaries are explicit. Adding a new module is mechanical — create the
  directory, add subdirectories as needed, register routes.
- **Positive**: Refactoring a module (e.g., changing Registration's state machine) affects only
  `app/Registration/` and its consumers via defined interfaces.
- **Positive**: Each module can be developed, tested, and reasoned about independently.
  Team members can own entire modules without stepping on each other.
- **Negative**: Slightly more boilerplate than flat layering for very simple modules (e.g.,
  a single-model module still needs at minimum Models + Actions directories).
- **Negative**: Laravel's auto-discovery expects flat structures occasionally, requiring
  explicit registration in `AppServiceProvider`.
- **Negative**: Cross-module boundaries are not mechanically enforced until architecture tests
  are restored. Code review is the current gate.

## References

- `app/` (19 business module directories + infrastructure directories)
- `app/Core/` (base classes, contracts, exceptions, infrastructure)
- `app/Providers/AppServiceProvider.php` (auto-discovery, manual registrations)
- `docs/architecture.md` — 12-layer architecture, dependency rules, cross-module communication
- `docs/conventions.md` — coding conventions derived from this architecture
