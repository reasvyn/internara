# Architecture

## Philosophy

Internara organizes code by **business domain**, not by technical layer. Each business concept — Auth, School, Internship, Assessment — owns its complete vertical slice: persistence, business rules, UI components, authorization, and HTTP interface.

This approach exists because flat layering (`app/Models/`, `app/Livewire/`, `app/Actions/`) scatters a single feature across 8+ directories, making it hard to reason about boundaries, impossible to enforce encapsulation, and expensive to refactor. Domain colocation solves this by ensuring everything related to "Registration" lives under `app/Domain/Registration/`.

## Layered Architecture

The system is built in **12 layers**, bottom to top. Each layer depends only on layers below it.
The domain directories are vertical slices that cross all layers below Layer 11.

```
 Layer 12 ┌──────────────────────────────────────────────────────────┐
  Business│  24 Domains: Auth, School, Internship, Registration...   │
  Domains │  Each domain is a vertical slice of layers 1–11          │
          │  app/Domain/{Domain}/                                    │
          │  ├── Models/  ├── Actions/  ├── Livewire/  ├── Http/     │
          │  ├── Enums/   ├── Entities/ ├── Policies/ ├── ...        │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 11 ┌──────────────────────────────────────────────────────────┐
  UI /    │  Livewire 4 components (80)    Blade templates           │
  Present.│  maryUI  +  DaisyUI  +  Alpine.js  +  Tailwind CSS v4   │
          │  resources/views/{domain}/     static assets             │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
 Layer 10 ┌──────────────────────────────────────────────────────────┐
  HTTP    │  Controllers / Form Requests / Middleware / Routes       │
  Layer   │  24 domain route files → routes/web/{domain}.php        │
          │  SecurityHeaders, LogContext, CheckRole, SetLocale       │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 9 ┌──────────────────────────────────────────────────────────┐
  Comm.   │  Events + Listeners + Notifications + Console Commands  │
          │  Cross-domain communication via events                  │
          │  system:health, system:cleanup, system:cache-warm        │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 8 ┌──────────────────────────────────────────────────────────┐
  Author. │  Policies (36)  RBAC (5 roles)  Functional roles        │
          │  BasePolicy → AuthorizesRoles + AuthorizesOwnership    │
          │  spatie/laravel-permission.  Gate::before(super_admin)   │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 7 ┌──────────────────────────────────────────────────────────┐
  Business│  Actions (161)  → 1 class = 1 use case  →  execute()   │
  Ops     │  BaseAction → transaction() + log() + error handling    │
          │  app/Domain/*/Actions/ delegating all persistence       │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
   Layer 6 ┌──────────────────────────────────────────────────────────┐
   Domain  │  Enums  (35, LabelEnum, StatusEnum, ColorableEnum)      │
  Rules   │  Entities (27, final readonly, zero framework deps)    │
          │  Entity State Classes (InternshipState, PartnershipState)│
          │  Data DTOs (AuditCheck, AuditReport)                    │
          │  app/Domain/*/Enums/  Entities/  Data/                  │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 5 ┌──────────────────────────────────────────────────────────┐
  Domain  │  Eloquent Models (50)  →  extend BaseModel              │
  Models  │  UUID primary keys (HasUuids), HasFactory               │
          │  Relationships, Scopes, Accessors, Mutators             │
          │  app/Domain/*/Models/  +  factories + seeders           │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 4 ┌──────────────────────────────────────────────────────────┐
  Core    │  BaseModel  BaseAction  BaseEntity  BasePolicy           │
  Base    │  BaseRecordManager  BaseController  FormRequest          │
  Classes │  BaseState  Data (DTO)                                   │
          │  SmartLogger  PiiMasker  HandlesActionErrors             │
          │  app/Domain/Core/{Actions,Models,Policies,etc}          │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 3 ┌──────────────────────────────────────────────────────────┐
  Core    │  Contracts: LabelEnum, StatusEnum, ColorableEnum         │
  Contracts│  DomainEvent, Filterable, Searchable, Sortable          │
          │  Exception: AppException → Action/Presentation/...     │
          │  app/Domain/Core/{Contracts,Exceptions}                 │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 2 ┌──────────────────────────────────────────────────────────┐
  Persist.│  Database: SQLite/MySQL, 63 migrations                  │
          │  Config: .env, config/*.php, Runtime settings table     │
          │  Files: Spatie Media Library (polymorphic attachments)  │
          │  Cache: Laravel cache + queue (jobs) + session          │
          │  database/migrations/  config/  storage/                │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 1 ┌──────────────────────────────────────────────────────────┐
  Infra   │  PHP 8.4  +  Laravel 13  +  Composer packages           │
          │  Spatie: activitylog, medialibrary, permission, states  │
          │  Livewire 4  +  Tailwind CSS 4  +  Alpine.js            │
          │  npm packages: Vite, Reverb, Echo, flatpickr, marked    │
          └──────────────────────────────────────────────────────────┘
```

### Layer Dependency Rules

1. A layer may only depend on layers **below** it. Layer 12 depends on 1–11, Layer 7 depends on 1–6.
2. **Core** (layers 3–4) depends on nothing except Laravel/Spatie. No business domain imports Core.
3. **No sibling imports.** `School` domain must not import `Internship` domain. Cross-domain communication goes through Events (Layer 9) or shared contracts in Core.
4. **Persistence isolation.** Actions never call Eloquent directly — they delegate to Models. Entities never import Models.
5. **UI isolation.** Livewire components never import other domains' Livewire components. Communication uses events or redirects.

### How Domain Directories Map to Layers

A domain directory `app/Domain/{Domain}/` combines multiple layers:

| Layer | Directory within Domain | Example |
|---|---|---|
| 12 | `app/Domain/Registration/` | The domain itself |
| 11 | `resources/views/registration/` | Blade views |
| 10 | `routes/web/registration.php` | Route definitions |
| 9 | `Listeners/`, `Notifications/`, `Console/` | Communication |
| 8 | `Policies/` | Authorization |
| 7 | `Actions/` | Business operations |
| 6 | `Enums/`, `Entities/`, `States/`, `Data/` | Domain rules |
| 5 | `Models/` | Persistence |
| 4 | (uses Core's base classes) | |
| 3 | (uses Core's contracts) | |
| 2 | (uses database/config) | |
| 1 | (uses PHP/Laravel) | |

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
| **Core** | Base classes & infrastructure everything depends on | `BaseModel`, `BaseEntity`, `BaseAction`, `AppException`, `Integrity` |
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
| **Internship** | Program execution | Reports, requirements |
| **Mentor** | Mentoring & supervision | Logs, teacher/supervisor portals |
| **Mentee** | Student role | Dashboard, program participation |
| **Attendance** | Presence tracking | Clock-in/out, absence requests |
| **Logbook** | Daily journals | Student diary entries |
| **Schedule** | Event planning | Calendar management |
| **Guidance** | Handbooks | Versioned documents, acknowledgements |
| **Incident** | Issue reporting | Report, investigation, resolution |
| **Assignment** | Tasks & submissions | Creation, grading workflow |
| **Assessment** | Competency evaluation | Rubrics, scoring, presentations |
| **Evaluation** | Program & mentor quality | Multi-type feedback collection (program, company, facility, mentor, overall) |
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
| A state machine | `implements StatusEnum` | Spatie ModelStates, custom status columns with if/else |
| An enum | `implements LabelEnum` | A plain PHP enum or class constants |
| Logging | `SmartLogger` | `Log::` facade or `activity()` helper |

These rules are enforced through code review and static analysis (PHPStan).

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

All writes follow the same path through the layers:

```
Layer 10/11          Layer 7           Layer 5/6          Layer 2
Input → Livewire/Controller → Action → Model/Entity → Database
```

Actions (Layer 7) are the only entry point for mutations. Livewire components (Layer 11) never call `Model::create()` directly. This ensures consistency: every write is logged, validated, and wrapped in a transaction.

Reads may skip Layers 6–7 for simple queries (Livewire → Model directly), but must still pass through authorization (Layer 8).

## Dependency Rules

| Rule | Explanation | Violation Example |
|---|---|---|
| **Downward only** | Layer N may only use layers < N | A Controller (10) importing from another domain's Livewire (11) |
| **Core independence** | Core (Layers 3–4) must not import any business domain | Core importing a School model |
| **No sibling imports** | Domains at Layer 12 must not import each other | `School` importing `Internship` models |
| **Persistence isolation** | Entities (Layer 6) never import Models (Layer 5) | An Entity calling `User::find()` |
| **Action gate** | All mutations go through Actions (Layer 7). No `Model::save()` outside Actions | A Livewire component calling `$user->save()` |
| **Authorize first** | Every Action must be preceded by a policy check (Layer 8) | An Action that modifies data without calling `$this->authorize()` |

Cross-domain communication (when one domain needs data from another) must use:
1. **Events** (Layer 9) for fire-and-forget notifications
2. **Core contracts** (Layer 3) for shared interfaces
3. **Action delegation** — calling another domain's Action through its public `execute()`

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
```

Feature tests use `LazilyRefreshDatabase`. Unit tests for Entities instantiate objects directly.
