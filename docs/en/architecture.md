# Architecture

## Philosophy

Internara organizes code by **business domain**, not by technical layer. Each business concept — Auth, School, Internship, Assessment — owns its complete vertical slice: persistence, business rules, UI components, authorization, and HTTP interface.

This approach exists because flat layering (`app/Models/`, `app/Livewire/`, `app/Actions/`) scatters a single feature across 8+ directories, making it hard to reason about boundaries, impossible to enforce encapsulation, and expensive to refactor. Domain colocation solves this by ensuring everything related to "Registration" lives under `app/Domain/Registration/`.

## Domain Structure

```
app/Domain/{Domain}/
├── Actions/        → Business operations (1 class = 1 use case)
├── Models/         → Eloquent persistence layer
├── Livewire/       → Reactive UI components
├── Policies/       → Authorization gates
├── Enums/          → Constants with behavior (LabelEnum, StatusEnum)
├── Entities/       → Business rules without framework dependencies
├── Http/           → Controllers, middleware, form requests (optional)
├── Notifications/  → Mail, database, broadcast alerts (optional)
├── Events/         → Domain events emitted (optional)
├── Listeners/      → Event subscribers (optional)
├── Console/        → Artisan commands (optional)
├── Support/        → Domain utilities (optional)
├── Contracts/      → Domain interfaces (optional)
└── Data/           → DTOs (optional)
```

Not every domain needs every layer. `Mentee` might only need Models + Livewire + Actions. `Certificate` adds Http when downloads are needed.

## 24 Domains at a Glance

| Domain | Boundary | Key Concept |
|--------|----------|-------------|
| **Core** | Base classes & infrastructure everything depends on | `BaseModel`, `BaseEntity`, `BaseAction`, `AppException` |
| **Shared** | Utilities shared across domains, no business logic | `Integrity`, `Theme`, `CsvHandler` |
| **Auth** | Identity & access control | Login, passwords, account lifecycle, recovery |
| **User** | User profile & identity | Profile editing, dashboard routing |
| **School** | Institution configuration | Departments, academic years |
| **Settings** | Runtime configuration | Key-value store, branding, localization |
| **Setup** | First-run installation | Wizard, environment audit, provisioning |
| **Admin** | System administration | User CRUD, announcements, GDPR |
| **Partnership** | External relationships | Companies, partnership agreements |
| **Placement** | Slot management | Capacity, direct assignments, change requests |
| **Registration** | Student enrollment | Applications, wizard, document upload |
| **Internship** | Program execution | Briefings, reports, requirements |
| **Mentor** | Mentoring & supervision | Logs, teacher/supervisor portals |
| **Mentee** | Student role | Dashboard, program participation |
| **Attendance** | Presence tracking | Clock-in/out, absence requests |
| **Logbook** | Daily journals | Student diary entries |
| **Schedule** | Event planning | Calendar management |
| **Guidance** | Handbooks | Versioned documents, acknowledgements |
| **Incident** | Issue reporting | Report, investigation, resolution |
| **Assignment** | Tasks & submissions | Creation, grading workflow |
| **Assessment** | Competency evaluation | Rubrics, scoring, presentations |
| **Evaluation** | Mentor quality | Feedback collection |
| **Document** | Template management | Rendering, report generation |
| **Certificate** | Credentialing | Issuance, templates, revocation |

## Base Class Mandate

Every layer has exactly one base class from Core. There is no alternative. Building a new feature means:

| You need... | Use this | Not this |
|---|---|---|
| A database table | `extends BaseModel` | `extends Model` |
| A business operation | `extends BaseAction` | A custom service with multiple methods |
| Business rules | `extends BaseEntity` (final readonly) | A trait, a helper class, or inline in the model |
| Authorization | `extends BasePolicy` | `Gate::define()` with inline closures |
| A CRUD list page | `extends BaseRecordManager` | A Livewire component from scratch |
| A form request | `extends FormRequest` (Core's) | `extends Request` or inline validation |
| A state machine | `extends BaseState` | Custom status columns with if/else |
| An enum | `implements LabelEnum` | A plain PHP enum or class constants |
| Logging | `SmartLogger` | `Log::` facade or `activity()` helper |

Architecture tests enforce these rules. Violations cause test failures in CI.

## Architectural Decisions

### Why Actions instead of Service classes?

Services accumulate unrelated methods over time (a `UserService` grows `createUser`, `sendWelcomeEmail`, `validateUsername`, etc.). Actions enforce **single responsibility by construction** — one class per use case. This makes them testable in isolation, discoverable by name, and composable.

### Why Entities separate from Models?

Models couple business logic to the database. When `User::isSuspended()` calls `$this->status` and `$this->locked_at`, tests require a database, migrations, and factory state. Entities remove this coupling. `Apprentice::isSuspended()` receives its data via constructor — testable in one line, no database needed.

### Why UUID primary keys?

Auto-incrementing IDs leak information (user count, growth rate) and create merge conflicts in distributed workflows. UUIDs are globally unique, require no coordination, and work across SQLite, MySQL, and PostgreSQL identically.

### Why domain-split routes?

A single `routes/web.php` with 200+ lines creates merge conflicts and makes it hard to find routes for a feature. Splitting by domain means each team member owns their domain's route file without touching others.

## Data Flow

All writes follow the same path:

```
Input → Livewire/Controller → Action → Model/Entity → Database
```

Actions are the only entry point for mutations. Livewire components never call `Model::create()` directly. This ensures consistency: every write is logged, validated, and wrapped in a transaction.

## Dependency Rules

- **Core depends on nothing.** Everything depends on Core.
- **No sibling imports.** `School` must not import `Internship`. Cross-domain communication goes through Events.
- **Persistence isolation.** Models are never imported by Entities. Livewire may query Models for reads but never writes.
- **UI isolation.** Livewire never imports other domains' Livewire components. Communication uses events or redirects.

## Exceptions

All application exceptions derive from `App\Domain\Core\Exceptions\AppException`. The hierarchy distinguishes four categories:

- **ActionException** — business operation failed (e.g., duplicate entry)
- **DomainException** — domain invariant violated (e.g., invalid state transition)
- **InfrastructureException** — external system failed (e.g., mail server down)
- **PresentationException** — HTTP-layer failure (e.g., not found, unauthorized)

Each carries a user-facing hint, debug context, and CLI-friendly output.

## Testing Strategy

Tests mirror source structure:

```
tests/Feature/{Domain}/{Name}Test.php    → Integration tests
tests/Unit/{Domain}/{Layer}/{Name}Test.php → Pure unit tests
tests/Arch/*ArchTest.php                 → Structural enforcement
```

Feature tests use `LazilyRefreshDatabase`. Unit tests for Entities instantiate objects directly. Architecture tests enforce the rules in this document.
