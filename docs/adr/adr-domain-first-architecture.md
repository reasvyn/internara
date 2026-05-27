# ADR-002: Domain-First Architecture

## Status
Accepted

## Context

The application manages a complex business domain — vocational fieldwork management — with 24 distinct
business concepts spanning the full program lifecycle: from institutional setup and partnership
management through student registration, daily operations (attendance, logbooks, assignments),
competency assessment, certification, and finally program closure and archival.

Two organizational approaches were considered:

1. **Flat layering** (Laravel defaults): `app/Models/`, `app/Http/Controllers/`,
   `app/Livewire/`, `app/Policies/` — all models in one directory, all controllers in another,
   etc. This is the conventional Laravel structure, suitable for simple CRUD applications with
   few business concepts.

2. **Domain-first**: Each business concept owns its complete vertical slice in one directory —
   `app/Domain/{Domain}/` containing Models, Actions, Livewire, Policies, Entities, Enums,
   etc.

Flat layering scatters a single feature (e.g., "submit an assignment") across 8+ directories:
the Model is in `app/Models/`, the form request in `app/Http/Requests/`, the policy in
`app/Policies/`, the Livewire component in `app/Livewire/`, the notification in
`app/Notifications/`, the event in `app/Events/`, the listener in `app/Listeners/`, and the
view in `resources/views/`. This makes it difficult to:

- Reason about feature boundaries and encapsulation — related code is physically separated
- Enforce architectural rules — nothing prevents a notification from importing a Livewire component
- Refactor a domain without touching unrelated code — changes ripple across 8+ directories
- Onboard new developers — the cognitive overhead of navigating 8+ directories per feature

## Decision

Code is organized by business domain, not by technical layer. Every domain lives under
`app/Domain/{Domain}/` and owns its complete vertical slice. The 24 domains are:

| Domain | Boundary | Key Concept |
|---|---|---|
| **Core** | Base classes & infrastructure everything depends on | `BaseModel`, `BaseEntity`, `BaseAction`, `BaseState`, `AppException`, `Integrity` |
| **Shared** | Utilities shared across domains, no business logic | `Theme`, `CsvHandler`, `Environment`, `Locale` |
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

The architecture is defined in 12 layers. A domain directory combines multiple layers into a
single vertical slice:

```
Layer 12 — Business Domains
Layer 11 — UI / Presentation (Livewire, Blade)
Layer 10 — HTTP Layer (Controllers, Middleware, Routes)
Layer  9 — Communication (Events, Listeners, Notifications, Console)
Layer  8 — Authorization (Policies, RBAC)
Layer  7 — Business Operations (Command Actions, Read Actions, Process Actions)
Layer  6 — Domain Rules (Enums, Entities, Data DTOs)
Layer  5 — Domain Models (Eloquent)
Layer  4 — Core Base Classes
Layer  3 — Core Contracts
Layer  2 — Persistence (Database, Config, Cache, Queue, Files)
Layer  1 — Infrastructure (PHP, Laravel, Spatie packages)
```

A domain directory `app/Domain/{Domain}/` corresponds to Layer 12, with its subdirectories
crossing layers 5–11. Layers 1–4 are shared infrastructure provided by the **Core** domain.

### Domain Structure

```
app/Domain/{Domain}/
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
├── Events/          → Domain events emitted (optional, gradual)
├── Listeners/       → Event subscribers (optional, gradual)
├── Console/         → Artisan commands (optional)
├── Support/         → Domain utilities (optional)
└── Contracts/       → Domain interfaces (optional)
```

Not every domain needs every directory. Simple domains like `Mentee` may only need
Models + Actions + Livewire. Complex domains like `Internship` add Events, Listeners,
Notifications, and Http layers.

### Auto-Discovery

`DomainServiceProvider` automatically discovers and registers domain artifacts:

| Artifact | Discovery Method | Cache |
|---|---|---|
| Livewire components | Scans `app/Domain/*/Livewire/`, registers as `{kebab-domain}.{kebab-class}` | 24h |
| Policies | Scans `app/Domain/*/Policies/`, auto-links to model matching policy name | 24h |
| Blade namespaces | Scans `resources/views/*/`, registers as `x-{domain}::` + `{domain}::` | 24h |

Cross-domain policies (e.g., `InternshipRegistrationPolicy` gating `Registration\Models\Registration`)
and event listeners are registered manually in `DomainServiceProvider`.

### Cross-Domain Communication Rules

No domain may import another domain's Models, Actions, or Livewire components directly.
Allowed communication paths:

1. **Core contracts** (Layer 3) — shared interfaces like `SendsNotifications`, `LabelEnum`,
   `StatusEnum`. Any domain implements them, any domain consumes them through the container.
2. **Domain events** (Layer 9) — a Command Action dispatches an event; listeners in any domain
   react. Preferred for fire-and-forget side effects.
3. **Action delegation** — a Process Action may call another domain's Action via its public
   `execute()` method. The called Action must accept primitives or DTOs, never Models.

### Known Violations

The following cross-domain imports exist in the current codebase and need remediation:

| Violation | Correct Approach |
|---|---|
| `Internship\Models\Internship` imports `School\Models\AcademicYear` | Use Core contract or query in School domain |
| `Internship\Policies\CompanyPolicy` gates `Partnership\Models\Company` | Move policy to `Partnership\Policies` |
| `Internship\Policies\InternshipRegistrationPolicy` gates `Registration\Models\Registration` | Move policy to `Registration\Policies` |

### Enforcement Gap

Architecture tests that previously enforced domain boundaries (`DomainBoundariesArchTest`,
`LayerSeparationArchTest`) were removed due to a `pest-plugin-arch` compatibility bug.
Until they are restored, boundary enforcement relies on code review and PHPStan analysis.

## Consequences

- **Positive**: A feature touches exactly one directory tree — high cohesion, low coupling.
- **Positive**: Domain boundaries are explicit. Adding a new domain is mechanical — create the
  directory, add subdirectories as needed, register routes.
- **Positive**: Refactoring a domain (e.g., changing Registration's state machine) affects only
  `app/Domain/Registration/` and its consumers via defined interfaces.
- **Positive**: Each domain can be developed, tested, and reasoned about independently.
  Team members can own entire domains without stepping on each other.
- **Negative**: Slightly more boilerplate than flat layering for very simple domains (e.g.,
  a single-model domain still needs at minimum Models + Actions directories).
- **Negative**: Laravel's auto-discovery expects flat structures occasionally, requiring
  explicit registration in `DomainServiceProvider`.
- **Negative**: Cross-domain boundaries are not mechanically enforced until architecture tests
  are restored. Code review is the current gate.

## References

- `app/Domain/` (24 domain directories)
- `app/Domain/Core/` (base classes, contracts, exceptions, infrastructure)
- `app/Providers/DomainServiceProvider.php` (auto-discovery, manual registrations)
- `docs/architecture.md` — 12-layer architecture, dependency rules, cross-domain communication
- `docs/conventions.md` — coding conventions derived from this architecture
