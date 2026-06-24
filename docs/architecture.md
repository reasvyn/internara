# Action-based MVC Architecture

> **Last updated:** 2026-06-24
> **Changes:** complete rewrite — new 4-layer data flow with DTO boundaries to prevent circular
> dependencies; consolidated Action Triad, Validation Strategy, and Dependency Rules; added
> Layer Interaction Flow diagram with explicit boundary contracts
>
> Complete architectural foundation of Internara. Covers the 12-layer architecture, Action Triad
> pattern, DTO-boundary data flow, cross-module communication, exception handling, validation
> strategy, caching, dependency rules, testing strategy, and invariant rules. Every decision here
> serves three goals:
>
> - **S1 — Secure**: Protect data integrity, enforce authorization, prevent leakage
> - **S2 — Sustain**: Keep the codebase maintainable as it grows across 19 modules
> - **S3 — Scalable**: Design for team expansion and feature accretion without rewrites
>
> For a complete catalog of all design patterns, conventions, and workflow patterns used across the
> codebase, see [Modular Pattern Reference](architecture/modular-pattern.md).
> For focused deep-dives into specific pattern domains, see:
> [Action](architecture/action-pattern.md) · [Entity](architecture/entity-pattern.md) ·
> [Model](architecture/model-pattern.md) · [Data](architecture/data-pattern.md) ·
> [Event](architecture/event-pattern.md) · [Enum](architecture/enum-pattern.md) ·
> [Livewire](architecture/livewire-pattern.md) · [Exception](architecture/exception-pattern.md) ·
> [Policy](architecture/policy-pattern.md) · [Logging](architecture/logging-pattern.md) ·
> [Cache](architecture/cache-pattern.md) · [Service](architecture/service-pattern.md) ·
> [Repository](architecture/repository-pattern.md) · [Testing](architecture/testing-pattern.md)

---

## Table of Contents

1. [Philosophy](#philosophy)
2. [Layered Architecture](#layered-architecture)
3. [Action Triad: Command, Read, Process](#action-triad-command-read-process)
4. [Data Flow](#data-flow)
5. [Circular Dependency Prevention](#circular-dependency-prevention)
6. [Module Structure](#module-structure)
7. [19 Modules at a Glance](#19-modules-at-a-glance)
8. [Base Class Mandate](#base-class-mandate)
9. [Cross-Module Communication](#cross-module-communication)
10. [Exceptions](#exceptions)
11. [Validation Strategy](#validation-strategy)
12. [Caching Strategy](#caching-strategy)
13. [Dependency Rules](#dependency-rules)
14. [Testing Strategy](#testing-strategy)
15. [Migration Paths](#migration-paths)
16. [Module Invariants (Do Not Violate)](#module-invariants-do-not-violate)

---

## Philosophy

Internara organizes code by **business module**, not by technical layer. Each business concept —
User, Academics, Program, Assessment — owns its complete vertical slice: persistence, business
rules, UI components, authorization, and HTTP interface.

Flat layering (`app/Models/`, `app/Livewire/`, `app/Actions/`) scatters a single feature across
eight or more directories, making it hard to reason about boundaries, impossible to enforce
encapsulation, and expensive to refactor. Module colocation solves this by ensuring everything
related to "Enrollment" lives under `app/Enrollment/`.

The architecture draws inspiration from Domain-Driven Design (strategic design, bounded contexts)
and CQRS (command/query separation) without the operational overhead of separate databases or event
sourcing. Actions replace traditional Service classes to enforce single responsibility by
construction. DTOs act as immutable boundary objects that prevent layer coupling and circular
dependencies.

---

## Layered Architecture

### Two-Dimensional Structure

The system is organized along two axes:

**Vertical axis — 12 infrastructure layers** (bottom to top): Framework, persistence, contracts,
base classes, models, domain rules, business ops, authorization, communication, HTTP, UI, and
business modules at the top. Each layer depends only on layers below it.

**Horizontal axis — 4-layer data flow** (left to right): UI Layer → Business Logic Layer → Domain
Rules Layer → Data Layer. Data crosses each boundary exclusively through DTOs, preventing circular
dependencies by ensuring no layer ever reaches into a layer above it.

### The 12 Infrastructure Layers

```
Layer 12 ┌──────────────────────────────────────────────────────────────┐
  Business│  19 Modules: Core, Auth, User, SysAdmin, Setup,              │
  Modules │  Settings, Academics, Program, Enrollment, Assessment,       │
          │  Evaluation, Assignment, Journals, Guidance, Incident,       │
          │  Partners, Certification, Reports, Document                  │
          │  Each module is a vertical slice through layers 1–11         │
          │  app/{Module}/                                               │
          │  ├── {SubModule}/  ← colocated Actions, Models, Policies    │
          │  ├── Types/        ← shared enums, value objects            │
          │  └── (root files)  ← cross-submodule Http, Console, Livewire│
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
  Layer 11 ┌──────────────────────────────────────────────────────────────┐
   UI /    │  Livewire 4 components (100+)  Blade templates              │
  Present.│  maryUI + DaisyUI + Alpine.js + Tailwind CSS v4             │
          │  resources/views/{module}/     static assets                 │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 10 ┌──────────────────────────────────────────────────────────────┐
  HTTP    │  Controllers / Middleware / Routes                           │
   Layer   │  17 module route files → routes/web/{module}.php            │
          │  SecurityHeaders, LogContext, CheckRole, SetLocale           │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 9 ┌──────────────────────────────────────────────────────────────┐
  Comm.   │  Events + Listeners + Notifications + Console Commands      │
          │  Cross-module communication via events                      │
          │  system:health, system:cleanup, system:cache-warm, pulse:*  │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
  Layer 8 ┌──────────────────────────────────────────────────────────────┐
   Author. │  Policies (28+)  RBAC (5 roles)  Functional roles (2)       │
          │  BasePolicy → AuthorizesRoles + AuthorizesOwnership         │
          │  spatie/laravel-permission auto-registers Gate::before      │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 7 ┌──────────────────────────────────────────────────────────────┐
  Business│  Command Actions — mutations  (transaction + log)           │
  Ops    │  Read Actions     — queries   (lightweight, no transaction)  │
  Utility│  Process Actions  — multi-step orchestration                │
          │  app/{Module}/**/Actions/  →  1 class = 1 use case         │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 6 ┌──────────────────────────────────────────────────────────────┐
  Domain  │  Entities (final readonly)  DTOs (BaseData)  Custom Enums   │
  Rules   │  app/{Module}/**/Entities/  app/{Module}/**/Enums/         │
           │  app/Core/Data/             app/Core/Exceptions/            │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
  Layer 5 ┌──────────────────────────────────────────────────────────────┐
  Module  │  Eloquent Models (44+)  →  extend BaseModel                  │
  Models  │  UUID primary keys (HasUuids)  HasFactory                   │
          │  Relationships, Scopes, Accessors, Mutators                 │
           │  app/{Module}/**/Models/ (44+)  + factories + seeders       │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 4 ┌──────────────────────────────────────────────────────────────┐
  Core    │  BaseAction  BaseEntity  BasePolicy  BaseRecordManager      │
  Base    │  BaseController  BaseFormRequest  BaseData  BaseEvent       │
  Classes │  app/Core/  (Actions, Entities, Policies, Livewire,         │
          │            Http/Requests, Data, Events)                     │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 3 ┌──────────────────────────────────────────────────────────────┐
  Core    │  Contracts: LabelEnum, StatusEnum, ColorableEnum            │
  Contracts│  SendsNotifications, SettingsStore                          │
          │  Exceptions: AppException + ModuleException (dual hierarchy)│
          │  app/Core/Contracts/   app/Core/Exceptions/                │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 2 ┌──────────────────────────────────────────────────────────────┐
  Persist.│  Database: SQLite (default) / MySQL / PostgreSQL            │
          │  Config: .env, config/*.php, Runtime settings table        │
          │  Files: Spatie Media Library (polymorphic attachments)     │
          │  Cache: Laravel cache + queue (jobs) + session             │
             │  database/migrations/ (51 files)  config/  storage/        │
          └──────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 1 ┌──────────────────────────────────────────────────────────────┐
  Infra   │  PHP 8.4 + Laravel 13 + Composer packages                   │
          │  Spatie: activitylog v5, medialibrary v11, permission v5,  │
          │  model-status v1                                            │
          │  Livewire 4 + Tailwind CSS v4 + Alpine.js                  │
          │  npm packages: Vite, flatpickr, marked                     │
          └──────────────────────────────────────────────────────────────┘
```

### Layer Dependency Rules

1. **Downward only**: A layer may only depend on layers **below** it. Layer 12 depends on 1–11,
   Layer 7 depends on 1–6, and so forth.

2. **Core independence**: Core (layers 3–4) depends on nothing except Laravel and Spatie packages.
   No business module may be imported by Core.

3. **Sibling imports allowed**: A business module at Layer 12 may import another module directly.
   Prefer events when side effects are involved, but direct imports are perfectly acceptable for
   straightforward access.

4. **Persistence isolation**: Actions never call Eloquent directly — they delegate to Models via
   injected dependencies. Models live in Layer 5; Actions in Layer 7 access them through injected
   Model classes.

5. **UI isolation**: Livewire components should not import other modules' Livewire components
   directly. Use events or redirects for cross-module UI communication.

6. **Entity purity**: Entities (Layer 6) must never import Actions (Layer 7), Livewire (Layer 11),
   or any HTTP layer. Entities are pure domain logic with zero framework dependencies beyond
   `Carbon\Carbon` for date math.

7. **DTO ownership**: DTOs (Layer 6) are owned by the consuming Action. A DTO is defined in the
   module where its Action lives, not in a shared location. This prevents dependency inversion.

### The 4-Layer Horizontal Data Flow

The 12 infrastructure layers group into 4 logical layers that govern **data flow direction**:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    4-LAYER DATA FLOW (left → right)                     │
│                                                                         │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌────┐ │
│  │     UI       │     │  BUSINESS    │     │   DOMAIN     │     │DATA│ │
│  │   LAYER      │ ──► │  LOGIC      │ ──► │   RULES      │ ──► │    │ │
│  │              │     │  LAYER       │     │   LAYER      │     │LAY │ │
│  │ Livewire     │     │ Actions      │     │ Entities     │     │    │ │
│  │ Controllers  │     │ Services     │     │ Events       │     │Mod │ │
│  │ Console      │     │ Support      │     │              │     │els │ │
│  └──────┬───────┘     └──────┬───────┘     └──────┬───────┘     └─▲──┘ │
│         │                    │                    │              │     │
│         │  DTO/FormReq      │      DTO           │  Model        │     │
│         │  /LivewireForm    │      (input)       │  Record       │     │
│         └──────────────────►└──────────────────►└───────────────┘     │
│                                                                         │
│  LAYER BOUNDARIES:                                                      │
│  ─────────────────                                                      │
│  UI → Business:  ALWAYS through a DTO (or FormRequest/LivewireForm)   │
│  Business → Domain: ALWAYS through a model record (Model instance)     │
│  Domain → Data:    ALWAYS through Eloquent (Model::create/update)      │
│                                                                         │
│  🚫  NEVER: UI → Entity directly, UI → Model::create() directly       │
│  🚫  NEVER: Business → UI classes                                      │
│  🚫  NEVER: Entity → Action or Service                                 │
└─────────────────────────────────────────────────────────────────────────┘
```

**Boundary contract summary:**

| Boundary | What crosses | What NEVER crosses |
|----------|-------------|-------------------|
| **UI → Business** | DTO (`BaseData`), FormRequest, LivewireForm (validated) | Raw `Request`, raw arrays, Eloquent Models, Entities |
| **Business → Domain** | Model record (for Entity construction), explicit scalar values | DTOs (already consumed), raw arrays, UI classes |
| **Domain → Data** | Eloquent query results, Model instances | DTOs, Entities (they are readonly snapshots) |
| **Event → Listener** | Event object (extends `BaseEvent`) | UI classes, HTTP context |

### How Module Directories Map to Layers

| Layer | Directory within Module                                              | Example                       |
| ----- | -------------------------------------------------------------------- | ----------------------------- |
| 12    | `app/{Module}/`                                                      | The module itself             |
| 11    | `resources/views/{module}/{submodule}/` or `resources/views/{module}/` | Blade views (per submodule or module-root) |
| 10    | `routes/web/{module}.php`                                            | Route definitions (17 files) |
| 9     | `{SubModule}/Listeners/`, `{SubModule}/Notifications/`, `Console/`   | Communication                 |
| 8     | `{SubModule}/Policies/`                                              | Authorization                 |
| 7     | `{SubModule}/Actions/`                                               | Business operations           |
| 6     | `{SubModule}/Entities/`, `{SubModule}/Enums/`, `Types/`, `Data/`     | Domain rules + DTOs           |
| 5     | `{SubModule}/Models/`                                                | Persistence                   |
| 4     | Uses Core's base classes: `app/Core/{Actions,Models,Policies,...}`   | Base classes                  |
| 3     | Uses Core's contracts and exceptions                                 | Contracts                     |
| 2     | Uses database, config, filesystem                                    | Persistence infrastructure    |
| 1     | Uses PHP, Laravel, Composer packages                                 | Foundation                    |

Cross-submodule files (shared Actions, Http, Console) live at the module root, directly under
`app/{Module}/` without a submodule subdirectory.

### Data Flow Layer Map

Here is how the 4-layer horizontal flow maps to the 12 infrastructure layers:

| Horizontal Layer | Infrastructure Layers | Key Directories                    |
|------------------|----------------------|------------------------------------|
| **UI Layer**     | 12, 11, 10, 9, 8    | `app/{Module}/**/Livewire/`, HTTP/ |
| **Business Logic** | 7, 4, 3            | `app/{Module}/**/Actions/`, `app/Core/Services/`, `app/{Module}/Support/` |
| **Domain Rules** | 6, 4, 3             | `app/{Module}/**/Entities/`, `app/{Module}/**/Enums/` |
| **Data Layer**   | 5, 2, 1             | `app/{Module}/**/Models/`          |

Authorization (Layer 8) and Communication (Layer 9) span both UI and Business layers, as policies
are checked before Action execution and events are dispatched after.

---

## Action Triad: Command, Read, Process

This is the single most important architectural decision in Internara. Actions are not monolithic —
they split into three distinct categories, each with a specific contract. All three live under
`app/{Module}/{SubModule}/Actions/` (or root `Actions/` for cross-submodule actions) and follow the
single `execute()` method convention.

### 1. Command Actions (Mutations)

**Purpose:** Every write to the system. Create, update, delete, transition state, send notifications,
upload files.

**Base class:** `BaseCommandAction` (extends `BaseAction`, provides `transaction()`, `log()`,
`respond()`, `validate()`, `authorize()`, `HandlesActionErrors`)

**Contract:**

- MUST extend `BaseCommandAction`
- MUST wrap all database operations in `$this->transaction()`
- MUST call `$this->log()` after successful mutation
- MUST be preceded by a policy check in the calling layer (Livewire/Controller)
- **MUST accept a DTO (`BaseData`) as the primary parameter** — never raw `array`, never
  `Illuminate\Http\Request`
- MAY accept a Model instance as a second parameter for update/delete operations (to identify the
  record), but the mutation data itself MUST be a DTO
- MUST delegate business rule checks to Entity methods and throw `RejectedException` on violation
- MUST throw `RejectedException` for business rule violations, never `RuntimeException`
- **MUST return `ActionResponse`** — never return the Model directly
- MUST have exactly one public method: `execute()`
- SHOULD dispatch a module event for significant state changes via `event()` or
  `$this->dispatchEvent()`

**Naming:** `{Verb}{Entity}Action` — `CreateCompanyAction`, `ApproveRegistrationAction`

```php
final class CreateCompanyAction extends BaseCommandAction
{
    public function execute(CompanyData $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $company = Company::create($data->toArray());

            $this->log('company_created', $company, [
                'name' => $company->name,
            ]);

            event(new CompanyCreated($company));

            return $this->respondCreated($company);
        });
    }
}
```

```php
final class UpdateCompanyAction extends BaseCommandAction
{
    public function execute(Company $company, CompanyData $data): ActionResponse
    {
        $state = $company->asCompanyState();

        if (! $state->canBeModified()) {
            $this->fail('Cannot modify a company with active placements.');
        }

        return $this->transaction(function () use ($company, $data) {
            $company->update($data->toArray());

            $this->log('company_updated', $company, [
                'name' => $company->name,
            ]);

            event(new CompanyUpdated($company));

            return $this->respondUpdated($company);
        });
    }
}
```

### 2. Read Actions (Queries)

**Purpose:** Complex read operations that involve aggregation, filtering, authorization, or
cross-module data assembly. Not for simple `Model::find()` or `Model::where()` — those stay in
Livewire.

**Base class:** `BaseReadAction` (plain class with `HandlesActionErrors` trait — NO `transaction()`
or `log()`)

**Contract:**

- MUST extend `BaseReadAction`
- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()`
- MUST accept a DTO or explicit typed parameters — never raw `array`
- SHOULD return typed objects, collections, or `ActionResponse` — never raw arrays
- MUST pass through authorization (unless the calling layer already authorized)
- Single public `execute()` method — never add a second public method

**Naming:** `Read{Entity}Action` — `ReadTeacherDashboardAction`, `ReadActivityLogAction`

```php
final class ReadTeacherDashboardAction extends BaseReadAction
{
    public function __construct(
        protected readonly Internship $model,
    ) {}

    public function execute(ReadDashboardData $data): array
    {
        return $this->remember(
            $this->cacheKey('dashboard', $data->teacherId),
            fn () => [
                'activeCount' => $this->model
                    ->whereIn('status', [
                        InternshipStatus::PUBLISHED->value,
                        InternshipStatus::ACTIVE->value,
                    ])
                    ->count(),
                'recentRegistrations' => Registration::where('created_at', '>=', now()->subDays(7))
                    ->with('mentee.user', 'internship')
                    ->limit(20)
                    ->get(),
            ],
        );
    }
}
```

### 3. Process Actions (Orchestration)

**Purpose:** Multi-step workflows that coordinate multiple Command and Read Actions. The "how" of
complex business processes.

**Base class:** `BaseProcessAction` (extends `BaseAction` — transaction + logging at the process
level).

**Contract:**

- MUST extend `BaseProcessAction`
- MUST compose other Actions via constructor injection
- MUST handle partial failure — if step 3 of 5 fails, what happens to steps 1–2?
- **MUST accept a DTO as the primary parameter** — same rule as Command Actions
- SHOULD emit a single module event representing the completed process
- MUST NOT duplicate business logic that already exists in Command Actions

**Naming:** `Process{Entity}Action` — `ProcessRegistrationAction`,
`ProcessReportFinalizationAction`

```php
final class ProcessRegistrationAction extends BaseProcessAction
{
    public function __construct(
        protected readonly CreateRegistrationAction $createRegistration,
        protected readonly AssignPlacementAction $assignPlacement,
        protected readonly NotifyStudentAction $notifyStudent,
    ) {}

    public function execute(RegistrationData $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->notifyStudent->execute($registration);

            $this->log('student_registered', $registration);
            event(new StudentRegistered($registration));

            return $this->respondCreated($registration);
        });
    }
}
```

### Action Category Decision Table

| Scenario                 | Pattern            | Base Class           | Transaction | Logging     | Event          |
| ------------------------ | ------------------ | -------------------- | ----------- | ----------- | -------------- |
| Create a record          | Command            | `BaseCommandAction`  | ✅ Required | ✅ Required | ✅ Recommended |
| Update a record          | Command            | `BaseCommandAction`  | ✅ Required | ✅ Required | ✅ Recommended |
| Delete a record          | Command            | `BaseCommandAction`  | ✅ Required | ✅ Required | ✅ Recommended |
| State transition         | Command            | `BaseCommandAction`  | ✅ Required | ✅ Required | ✅ Required    |
| Send notification        | Command            | `BaseCommandAction`  | ✅ Required | ✅ Required | ❌             |
| Simple list query        | Inline in Livewire | None                 | ❌          | ❌          | ❌             |
| Complex aggregated query | Read Action        | `BaseReadAction`     | ❌          | ❌          | ❌             |
| Dashboard statistics     | Read Action        | `BaseReadAction`     | ❌          | ❌          | ❌             |
| Multi-step orchestration | Process            | `BaseProcessAction`  | ✅ Required | ✅ Required | ✅ Required    |

---

## Data Flow

### Core Principle: DTOs as Layer Boundaries

The defining architectural rule of Internara: **every layer boundary is crossed with a DTO.** No
layer ever receives raw request input, and no layer ever passes internal state as a raw array. This
prevents circular dependencies by making dependency direction explicit at compile time.

### Mutation Flow (Writes)

Every write follows the exact same path through the layers:

```
                    ╔═══════════════════════════════════════════╗
                    ║          1. UI LAYER                      ║
                    ║  Livewire / Controller / Console          ║
                    ║                                           ║
                    ║  1. Receive input (validated)             ║
                    ║  2. Check authorization (Policy)          ║
                    ║  3. Build DTO from validated data         ║
                    ║  4. Call Action with DTO only             ║
                    ╚═══════════════════════════════════════════╝
                                     │
                            ┌────────┴────────┐
                            │   DTO crosses   │
                            │  layer boundary │
                            └────────┬────────┘
                                     ▼
                    ╔═══════════════════════════════════════════╗
                    ║       2. BUSINESS LOGIC LAYER             ║
                    ║  Action / Service / Support               ║
                    ║                                           ║
                    ║  1. Receive DTO via execute(DTO $data)   ║
                    ║  2. Validate business preconditions       ║
                    ║  3. Create Entity from Model record       ║
                    ║  4. Check business rules via Entity       ║
                    ║  5. Wrap in transaction                   ║
                    ║  6. Persist via Model                     ║
                    ║  7. Log mutation                          ║
                    ║  8. Dispatch event                        ║
                    ║  9. Return ActionResponse                 ║
                    ╚═══════════════════════════════════════════╝
                                     │
                            ┌────────┴────────┐
                            │  ActionResponse │
                            │  crosses back   │
                            └────────┬────────┘
                                     ▼
                    ╔═══════════════════════════════════════════╗
                    ║           3. UI LAYER (return)            ║
                    ║  Livewire / Controller / Console          ║
                    ║                                           ║
                    ║  1. Receive ActionResponse                ║
                    ║  2. Flash success/error message           ║
                    ║  3. Redirect or update UI state           ║
                    ╚═══════════════════════════════════════════╝
```

#### Detailed Step-by-Step: Create Company

```php
// ─── UI LAYER (app/Partners/Company/Livewire/CompanyManager.php) ───

class CompanyManager extends Component
{
    public CreateCompanyForm $form;  // Livewire\Form

    public function save(CreateCompanyAction $action): void
    {
        $this->authorize('create', Company::class);

        // Validate form-level rules (UX layer)
        $this->form->validate();

        // Build DTO — this is the ONLY thing that crosses the boundary
        $dto = CompanyData::from($this->form->toArray());

        // Call Action with DTO only — never pass $this->form or $this
        $result = $action->execute($dto);

        // Handle the ActionResponse (success or failure)
        if ($result->failed()) {
            flash()->error($result->message);
            return;
        }

        flash()->success($result->message);
        $this->redirectRoute('companies.show', $result->data);
    }
}

// ─── BUSINESS LOGIC LAYER (app/Partners/Company/Actions/CreateCompanyAction.php) ───

final class CreateCompanyAction extends BaseCommandAction
{
    public function execute(CompanyData $data): ActionResponse
    {
        // Business precondition — Entity-based rule check
        // (e.g., company limit per admin, duplicate name detection)
        // ...

        return $this->transaction(function () use ($data) {
            $company = Company::create($data->toArray());

            $this->log('company_created', $company, [
                'name' => $company->name,
            ]);

            event(new CompanyCreated($company));

            return $this->respondCreated($company);
        });
    }
}
```

#### Detailed Step-by-Step: Approve Registration

```php
// ─── UI LAYER ───

class RegistrationReview extends Component
{
    public function approve(
        string $id,
        ApproveRegistrationAction $action,
    ): void {
        $registration = Registration::findOrFail($id);
        $this->authorize('approve', $registration);

        try {
            // If no complex input, a simple DTO or even no DTO is fine
            // For state transitions with notes/reason, use a DTO
            $result = $action->execute(
                $registration,
                new ApproveRegistrationData(
                    approvedBy: auth()->id(),
                    notes: $this->approvalNotes,
                ),
            );

            flash()->success($result->message);
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }
}

// ─── BUSINESS LOGIC LAYER ───

final class ApproveRegistrationAction extends BaseCommandAction
{
    public function execute(
        Registration $registration,  // Model identity (pre-existing record)
        ApproveRegistrationData $data,  // Mutation payload (DTO)
    ): ActionResponse {
        // Entity-based business rule check
        $state = $registration->asRegistrationState();

        if (! $state->canBeApproved()) {
            $this->fail(
                'Registration cannot be approved in its current state.',
            );
        }

        return $this->transaction(function () use ($registration, $data) {
            $registration->update([
                'status' => RegistrationStatus::APPROVED->value,
                'approved_by' => $data->approvedBy,
                'approved_at' => now(),
                'approval_notes' => $data->notes,
            ]);

            $this->log('registration_approved', $registration);
            event(new RegistrationApproved($registration));

            return $this->respondUpdated($registration);
        });
    }
}
```

### Read Flow (Queries)

```
Simple query:
Livewire → Model::query() → Database
           │
           └─ Policy check (Layer 8)

Complex query:
Livewire ──► Read Action ──► Model::query() ──► Database
  │            │
  │            ├─ Accepts DTO (filter/sort params)
  │            ├─ Uses cache (remember)
  │            ├─ Applies authorization
  │            └─ Returns typed result or ActionResponse
  │
  └─── Build DTO from component state, pass to action
```

**Simple query** (stays in Livewire — no Read Action needed):

```php
public function rows(): LengthAwarePaginator
{
    return Company::query()
        ->where('name', 'like', "%{$this->search}%")
        ->withCount('placements')
        ->paginate($this->perPage);
}
```

**Complex query** (requires a Read Action):

```php
// UI Layer
#[Computed]
public function dashboardStats(): array
{
    $dto = new ReadDashboardData(
        teacherId: auth()->id(),
        dateRange: $this->selectedRange,
    );

    return app(ReadTeacherDashboardAction::class)->execute($dto);
}

// Business Logic Layer
final class ReadTeacherDashboardAction extends BaseReadAction
{
    public function execute(ReadDashboardData $data): array
    {
        return $this->remember(
            $this->cacheKey('dashboard', $data->teacherId),
            fn () => [
                'activeCount' => Internship::query()
                    ->whereIn('status', ['published', 'active'])
                    ->count(),
                // ...more aggregation...
            ],
            ttl: 300,
        );
    }
}
```

### Event Flow

```
Command Action            Event              Listener(s)
execute() ──────────► dispatch() ──────► ┌─────────────────┐
  │                                       │ Notify users     │
  │                                       │ Invalidate cache │
  │                                       │ Write audit      │
  │                                       │ Trigger next cmd │
  │                                       └─────────────────┘
```

Events decouple side effects from core business logic. A Command Action's responsibility ends when
it dispatches the event. Events are dispatched **inside** the transaction callback, and
`BaseAction::transaction()` defers delivery until after the commit. This ensures listeners never see
uncommitted data.

**Key rule:** Events are dispatched from Actions only. Livewire components and Controllers must
never call `event()` directly for domain events. UI-triggered events (e.g., `RefreshList`) are the
only exception.

### DTO Lifecycle

```
                    ┌──────────────────┐
                    │  Livewire Form   │
                    │  (validated)     │
                    └────────┬─────────┘
                             │ $this->form->toArray()
                             ▼
                    ┌──────────────────┐
                    │  DTO (BaseData)  │ ← immutable, typed
                    │  CompanyData     │
                    └────────┬─────────┘
                             │ crosses layer boundary
                             ▼
                    ┌──────────────────┐
                    │  Action          │
                    │  execute($dto)   │
                    └────────┬─────────┘
                             │ $dto->toArray()
                             ▼
                    ┌──────────────────┐
                    │  Model::create() │
                    └──────────────────┘

  Key properties:
  • DTO is validated before construction (in Form/FormRequest)
  • DTO is immutable after construction
  • DTO carries ONLY scalar, enum, and Carbon values — never Models
  • DTO is consumed by one Action and never passed further down
```

### What Crosses Each Boundary (Reference Table)

| Boundary | Direction | What Crosses | Example |
|----------|-----------|-------------|---------|
| **UI → Business** | Input | `BaseData` (DTO), LivewireForm validated values → DTO | `CompanyData`, `ApproveRegistrationData` |
| **Business → UI** | Output | `ActionResponse` | `respondCreated($company)` |
| **Business → Domain** | In-process | Model record → Entity factory | `Company::find($id)` → `CompanyState::fromModel($company)` |
| **Business → Data** | Persistence | DTO values → Model attributes | `$data->toArray()` → `Company::create(...)` |
| **Business → Comm.** | After commit | Event object | `event(new CompanyCreated($company))` |
| **Domain → Business** | In-process | Boolean/Enum answers | `$state->canBeDeleted()`, `$period->isAcceptingRegistrations()` |

### What MUST NOT Cross Each Boundary

| Boundary | NEVER crosses | Why |
|----------|--------------|-----|
| **UI → Business** | `Eloquent Model` | Breaks layer isolation; couples UI to persistence |
| **UI → Business** | `Request` object | Action becomes untestable without HTTP |
| **UI → Business** | Raw `array` | No type safety, no documentation |
| **UI → Domain** | Entity directly | Entity creation is the Action's responsibility |
| **Business → UI** | `Eloquent Model` directly | UI would depend on Model; use `ActionResponse` |
| **Business → UI** | `RejectedException` as control flow | Use `ActionResponse` for expected failures |
| **Domain → Business** | DTO (already consumed) | DTOs are input-only, consumed once |
| **Domain → Data** | Entity (it is readonly) | Entities are snapshots, never persisted |

---

## Circular Dependency Prevention

### How Circular Dependencies Occur

Circular dependencies happen when two classes depend on each other (directly or transitively). In
Laravel applications, the most common forms are:

1. **Model ↔ Action**: A Model imports an Action (e.g., calling an Action from an accessor) while
   the Action imports the Model.

2. **Entity ↔ Action**: An Entity references an Action while the Action references the Entity.

3. **Livewire ↔ Model**: A Livewire component calls `Model::create()` directly AND the Model has an
   event listener that references the Livewire component.

4. **Cross-module import cycles**: Module A imports Module B's Action for a business rule, while
   Module B imports Module A's Model.

### Prevention Strategy

The architecture prevents circular dependencies through four structural mechanisms:

#### 1. Strict Layer Direction

Dependencies flow in ONE direction only:

```
UI Layer (Livewire/Controller/Console)
    ↓
Business Logic Layer (Actions/Services/Support)
    ↓
Domain Rules Layer (Entities/Enums)
    ↓
Data Layer (Models)
```

A layer may depend on any layer below it but NEVER on a layer above it.

| Layer | May Depend On | Must NOT Depend On |
|-------|---------------|-------------------|
| UI | Business, Domain, Data | Nothing above UI |
| Business | Domain, Data | UI classes |
| Domain | Data only | Business layer (Actions), UI |
| Data | Nothing (except Core base) | Any module layer |

#### 2. DTOs as Boundary Objects

DTOs (`BaseData` subclasses) are the **only** objects that cross layer boundaries as input. Because
DTOs contain only scalar values, enums, and Carbon instances — never Models, never Actions, never
UI classes — they carry zero transitive dependencies.

```
✅  Livewire → DTO (CompanyData) → Action → Model
    DTO has zero references to Livewire, Action, or Model classes.

🚫  Livewire → Request → Action → Model
    Request carries HTTP context, making Actions untestable and coupled.

🚫  Livewire → array → Action → Model
    No type safety; caller and Action must agree on keys by convention only.
```

#### 3. Entity-Only Business Checks

Business rules are enforced through Entities (final readonly classes with zero framework
dependencies). Actions create entities from Model records and ask them business questions. Entities
never reference Actions, Livewire, or Services. This eliminates the most common circular dependency:
a Model calling an Action from a business rule.

```
✅  Action → $model->asEntity() → Entity::canX() → bool
    Entity has zero dependencies on Actions or UI.

🚫  Model → asEntity() → Entity::canX() → Action::execute()
    Entity must never import or call Actions.
```

#### 4. Event-Based Decoupling for Side Effects

When an operation in Module A needs to trigger logic in Module B, it dispatches a **module event**
(extends `BaseEvent`). The event carries data, not behavior. Module B's listener picks it up
asynchronously.

```
✅  Module A: Action → event(new EntityCreated($entity))
    Module B: Listener → cache invalidation / notification / follow-up Action

    Event class references nothing (just BaseEvent + primitives).
    Listener in Module B can reference Module A's Models (downward dependency).

🚫  Module A: Action → call Module B::Action directly (tight coupling)
    Module B: Action → call Module A::Model (creates cycle potential)
```

### Circular Dependency Detection

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| PHP fatal error: Class not found | Autoloader can't resolve because of cycle | Break the cycle with an event or DTO |
| Laravel container resolution fails | Service provider order creates cycle | Extract shared dependency into Core |
| `Cannot use X — not found` during deployment | Composer autoloader order | Use event-based decoupling |
| Slow autoloading | Many interdependent classes | Consolidate cross-refs into DTOs/Events |

### Seven Rules for Dependency Safety

1. **DTOs must be leaf classes.** A DTO may extend `BaseData` but must never reference an Action,
   Model, Entity, Livewire component, or Service.

2. **Entities must be leaf classes.** An Entity (or `BaseEntity`) must never reference an Action,
   Service, Livewire component, or any class from Layer 7 or above.

3. **Models must only depend on Core.** Models extend `BaseModel` and use Core contracts. A Model
   must never import an Action, Service, Entity (except in its `asEntity()` bridge), or any
   Livewire/Controller class.

4. **Actions may depend on Models and Entities, but not on other Actions' internals.** An Action
   may call another Action via constructor injection (composition), but it must never rely on the
   internal implementation details of that Action.

5. **Livewire components must never call `Model::create()` or `Model::update()` or
   `Model::delete()` directly.** All persistence goes through a Command Action.

6. **Livewire components must never access Entities directly.** Entity creation is the
   responsibility of Actions. If a Livewire component needs to check a business rule, it calls an
   Action, which uses an Entity internally.

7. **Services must never call Actions.** If a Service needs business logic, it must be refactored
   into an Action. Services are infrastructure code and must remain stateless and action-unaware.

---

## Module Structure

Every module follows a consistent directory layout. Within each module, code is organized by
**submodule** — a cluster of module objects treated as a single unit.

> [!NOTE]
> For cross-cutting or system-wide modules (such as Settings, Enrollment, or Assessment), a **flat
> structure** directly under the module root is permitted. This places component directories (e.g.
> `Actions/`, `Models/`, `Policies/`) without a submodule grouping layer, avoiding redundant
> namespace segments.

```
app/{Module}/
├── {SubModule}/                    → One directory per submodule root
│   ├── Actions/                    → Business operations (Command, Read, Process)
│   ├── Models/                     → Eloquent models belonging to this submodule
│   ├── Policies/                   → Authorization gates
│   ├── Livewire/                   → UI components (optional)
│   │   └── Forms/                  → Form Objects (optional)
│   ├── Entities/                   → Pure business rules (optional)
│   ├── Enums/                      → Enum specific to this submodule (optional)
│   ├── Events/                     → Module events (optional)
│   ├── Listeners/                  → Event subscribers (optional)
│   ├── Notifications/              → Multi-channel alerts (optional)
│   ├── Data/                       → DTOs (BaseData subclasses) (optional)
│   └── Http/                       → HTTP layer (optional)
│       ├── Controllers/
│       ├── Middleware/
│       └── Requests/
├── Types/                          → Shared value objects, flat enums, rules (optional)
├── Actions/                        → Cross-submodule orchestration (optional)
├── Http/                           → Cross-submodule controllers & middleware (optional)
│   ├── Controllers/
│   └── Middleware/
├── Console/                        → Cross-submodule artisan commands (optional)
├── Livewire/                       → Cross-submodule UI (dashboards, etc.) (optional)
│   └── Forms/                      → Form Objects (optional)
├── Notifications/                  → Cross-submodule notifications (optional)
├── Events/                         → Cross-submodule events (optional)
├── Listeners/                      → Cross-submodule listeners (optional)
├── Data/                           → Shared module DTOs (optional)
├── Support/                        → Shared module utilities (optional)
└── Services/                       → Infrastructure services (optional)
```

### Path Convention

| Scope                 | Pattern                                                           | Example                                                  |
| --------------------- | ----------------------------------------------------------------- | -------------------------------------------------------- |
| Module-specific       | `app/{Module}/{Submodule}/{Component}/{ClassName}.php`            | `app/User/Profile/Actions/UpdateProfileAction.php`       |
| Shared (cross-module) | `app/{Component}/{ClassName}.php`                                 | `app/Core/Data/AuditCheck.php`                           |
| Module views          | `resources/views/{module}/{submodule}/{component-name}.blade.php` | `resources/views/user/profile/profile-editor.blade.php`  |
| Module-root views     | `resources/views/{module}/{component-name}.blade.php`             | `resources/views/user/activity-feed.blade.php`             |
| Module tests          | `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php`        | `tests/Feature/User/Profile/UpdateProfileActionTest.php` |
| Shared tests          | `tests/{Feature,Unit}/{Component}/{Name}Test.php`                 | `tests/Unit/Data/AuditDtoTest.php`                       |

**No redundant namespace segments.** The class name must never be repeated in the path. This applies
to both PHP classes and view files.

- ✅ `app/User/Models/User.php` (namespace `App\User\Models`)
- ❌ `app/User/User/Models/User.php` — `User` is repeated
- ✅ `app/Program/Internship/Models/Internship.php` (namespace `App\Program\Internship\Models`)
- ❌ `app/Program/Internship/Internship/Models/Internship.php`
- ✅ `resources/views/auth/login.blade.php` → `view('auth.login')`
- ❌ `resources/views/auth/login/login/login.blade.php` → `view('auth.login.login.login')` — triple nested
- ❌ `resources/views/auth/login/login.blade.php` → `view('auth.login.login')` — submodule name repeated as filename

### Submodule Mapping

| Module            | Submodules                                                                                               | Cross-Submodule Root Files                                                               |
| ----------------- | -------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| **Core**          | —                                                                                                        | Infrastructure + cross-module utilities                                                  |
| **Auth**          | `Permissions/`, `SuperAdmin/`, `Login/`, `Account/`, `AccessTokens/`, `AccountRecovery/`, `Password/`       | Enums, Http/Middleware, Livewire (login, recovery, activation, password)                 |
| **User**          | `AccountStatus/`, `Profile/`, `Notifications/`, `Dashboard/`                                             | Http, Livewire (dashboards, editors), Actions, Enums, Entities, Rules, Support, Services |
| **SysAdmin**      | `Announcement/`, `Observability/`, `Backups/`                                                             | Actions, Console, Livewire (audit, pulse, backups), Recorders, Services                  |
| **Setup**         | `Installation/`, `SetupWizard/`                                                                          | Entities, Data                                                                            |
| **Settings**      | `Branding/`, `Theme/`, `Locale/`                                                                         | Actions, Casts, Data, Enums, Events, Http/Middleware, Listeners, Livewire, Models, Policies, Rules, Support |
| **Academics**     | `Department/`, `AcademicYear/`, `School/`                                                                | Actions, Console, Http, Livewire, Services, Support                                      |
| **Program**       | `Internship/`, `InternshipGroup/`                                                                         | Http, Events, Listeners, Notifications, Rules                                            |
| **Enrollment**    | `AccountApplication/`, `Placement/`, `Registration/`                                                     | Http, Notifications                                                                      |
| **Assessment**    | `Assessment/`, `Rubric/`                                                                                 | Actions, Entities, Enums, Livewire, Models, Policies                                     |
| **Evaluation**    | — (flat structure, under construction)                                                                  | Enums, Models                                                                             |
| **Assignment**    | `Assignment/`, `Submission/`                                                                             | Actions, Entities, Enums, Http, Livewire, Models, Notifications, Policies                |
| **Journals**      | `Logbook/`, `Attendance/`, `AbsenceRequest/`                                                            | Http                                                                                     |
| **Guidance**      | `SupervisionLog/`                                                                                        | Actions, Entities, Enums, Livewire, Models, Policies                                     |
| **Incident**      | `IncidentReport/`                                                                                        | Actions, Entities, Enums, Livewire, Models, Policies                                     |
| **Partners**      | `Company/`, `Partnership/`                                                                               | Actions, Data, Entities, Enums, Livewire, Models, Policies                               |
| **Certification** | `Certificate/`                                                                                           | Actions, Entities, Enums, Livewire, Models, Policies, Support                            |
| **Reports**       | `Report/`                                                                                                | Actions, Entities, Livewire, Models, Policies                                            |
| **Document**      | `OfficialDocument/`                                                                                      | Actions, Enums, Models, Policies, Support                                                |

### Views Structure

Views remain unchanged from the existing convention. See [View Structure Documentation](architecture/modular-pattern.md#views-structure) for details.

### Livewire Component Alias Conventions

| Scope             | Pattern                                               | Example                             |
| ----------------- | ----------------------------------------------------- | ----------------------------------- |
| Submodule         | `{kebab-module}.{kebab-submodule}.{kebab-name}`       | `admin.user.user-manager`           |
| Module-root       | `{kebab-module}.{kebab-name}`                         | `user.profile-editor`               |

---

## 19 Modules at a Glance

| #  | Module            | Boundary                                                                       | Key Concept                                                                                              |
| -- | ----------------- | ------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------- |
| 1  | **Core**          | Base classes, contracts, infrastructure, and cross-module utilities            | BaseModel, BaseAction, BaseEntity, SmartLogger, exception hierarchy, contracts, CSV handler              |
| 2  | **Auth**          | Authentication, authorization, and access control                              | Login, password management, account activation, recovery, RBAC, super admin integrity                    |
| 3  | **User**          | Identity, profiles, and personal dashboards                                    | Profile editing, avatar upload, role-based dashboards, notification center, account state machine        |
| 4  | **SysAdmin**      | System administration and user management                                      | User CRUD, announcements, audit logs, Pulse monitoring, account clone detection, GDPR compliance        |
| 5  | **Setup**         | One-time installation and provisioning                                         | 6-step wizard, environment audit, setup token, super admin creation, CLI recovery                        |
| 6  | **Settings**      | System-wide configuration and branding                                         | Key-value store, dynamic branding, color presets, mail config, feature flags, cached resolution chain    |
| 7  | **Academics**     | Institution structure and academic foundation                                  | School profile, departments, academic years                                                              |
| 8  | **Program**       | Internship program lifecycle                                                   | Program lifecycle, phases, groups, document requirements, closure readiness                             |
| 9  | **Enrollment**    | Student registration and placement                                             | Applications, registration wizard, document upload, slot management, placement change requests           |
| 10 | **Assessment**    | Competency evaluation framework                                                | Rubrics (JSON structures), assessment grading, finalization, cross-role proxy                        |
| 11 | **Evaluation**    | Generic feedback collection                                                                             | Google Forms-like forms, sections, weighted questions, polymorphic targeting, auto-scored responses |
| 12 | **Assignment**    | Tasks and submissions                                                          | Task creation, grading workflow, revision loop, deadline management, version history                     |
| 13 | **Journals**      | Daily activity tracking                                                        | Logbook entries, attendance with clock-in/out, absence requests, scheduling, calendar views              |
| 14 | **Guidance**      | Mentoring and supervision                                                      | Supervision logs, mentoring assignments, handbook acknowledgements                                      |
| 15 | **Incident**      | Issue reporting and resolution                                                 | Incident forms, severity classification, investigation workflow, resolution outcomes                     |
| 16 | **Partners**      | External relationships management                                              | Company profiles, partnership agreements, MoU documents, expiry detection                                |
| 17 | **Certification** | Credentialing and certificate management                                       | Certificate templates, single/batch issuance, revocation, serial number management, QR verification      |
| 18 | **Reports**       | Student final grade card                                                       | Grade aggregation, grade card review, coordinator sign-off, immutable lock                              |
| 19 | **Document**      | Official correspondence and template rendering                                 | Document templates, PDF rendering, handbooks, acknowledgement system, template versioning                |

---

## Base Class Mandate

Every layer has exactly one base class from Core. There is no alternative.

| You need...                      | Use this                               | Not this                                        |
| -------------------------------- | -------------------------------------- | ----------------------------------------------- |
| A database table                 | `extends BaseModel`                    | `extends Model`                                 |
| A business operation (mutation)  | `extends BaseCommandAction`            | A custom service with multiple methods          |
| A business operation (query)     | `extends BaseReadAction`              | Using `transaction()` or `log()`               |
| A multi-step orchestration       | `extends BaseProcessAction`           | Duplicating logic from Command Actions          |
| A pure business rule             | `extends BaseEntity` (final readonly)  | Inline in a model or controller                 |
| An authorization gate            | `extends BasePolicy`                   | A custom closure or array of strings            |
| A CRUD table UI (Livewire)       | `extends BaseRecordManager`            | A bespoke Livewire component with inline search |
| An HTTP controller               | `extends BaseController` (or Laravel's)| A custom router closure                        |
| A form request                   | `extends BaseFormRequest`              | `extends FormRequest` (Laravel's)              |
| A value object / DTO             | `extends BaseData` (final readonly)    | An array passed around                          |
| An event                         | `extends BaseEvent`                    | Implements `ShouldDispatch` manually           |
| An enum                          | `implements LabelEnum`                 | A plain PHP enum                               |
| A state machine enum             | `implements StatusEnum` (+ LabelEnum)  | A boolean field on the model                   |
| An exception                     | `extends AppException` or `ModuleException` | `extends \Exception`                      |

**Notes:**

- `User` model is the sole exception — extends `Authenticatable` directly but applies `HasUuids`
  manually for UUID consistency.
- Notifications extend `Illuminate\Notifications\Notification` directly (no shortcut provided).
- Cache keys go into `config/cache-keys.php`, not inline strings.

---

## Cross-Module Communication

Cross-module imports are **allowed** — import Models, Actions, Policies, or other classes from
sibling modules directly when needed. Four patterns are available, used as guidance not enforcement:

| Pattern            | When to Use                                                                 | Example                                      |
| ------------------ | --------------------------------------------------------------------------- | -------------------------------------------- |
| **Direct import**  | Straightforward access with no side effects                                 | `use App\Academics\Models\AcademicYear;`     |
| **Action call**    | Cross-module business operation                                             | `$this->createUser->execute($data);`         |
| **Module event**   | Fire-and-forget side effects (notifications, cache invalidation)            | `event(new InternshipCreated($internship));` |
| **Core contract**  | Abstraction used broadly across modules                                      | `LabelEnum`, `SendsNotifications`            |

Use events when you want to add new reactions without modifying the caller. Use direct imports for
everything else.

---

## Exceptions

Two separate exception hierarchies exist, both using `HasExceptionContext` trait:

```
RuntimeException
├── AppException (abstract)           ← Framework & infrastructure failures
│   ├── ActionException
│   │   ├── ValidationFailedException  ← HTTP 422 / validation errors
│   │   └── ConflictException          ← Duplicate / conflict state
│   ├── InfrastructureException
│   │   └── RateLimitException         ← HTTP 429 / rate limited
│   └── PresentationException
│       ├── NotFoundException          ← HTTP 404 / resource missing
│       └── UnauthorizedException      ← HTTP 403 / permission denied
│
└── ModuleException (abstract)        ← Business rule violations
    └── RejectedException             ← Domain invariant violated
```

`ModuleException` is deliberately NOT a child of `AppException`. This allows catch blocks to target
module failures independently from infrastructure failures:

- `catch (ModuleException $e)` → user-facing error messages
- `catch (InfrastructureException $e)` → operations/technical errors

### Exception Handling in the Layered Architecture

| Layer | Exception Type | Handler | User Experience |
|-------|---------------|---------|-----------------|
| **UI (Livewire/Controller)** | `RejectedException` | `try/catch` in component/controller | Flash error with user-friendly message |
| **Business (Action)** | `ValidationException` | `Validator::validate()` → automatic | Inline field errors |
| **Business (Action)** | `RejectedException` | Explicit `$this->fail()` | Business rule violation message |
| **Domain (Entity)** | `RejectedException` | Entity methods throw on violation | Caught by Action, rethrown to UI |
| **Infrastructure** | `AppException` subclasses | `HandlesActionErrors` trait | Generic error, logged with context |

---

## Validation Strategy

### Two-Layer Validation

| Layer | Mechanism | Purpose | Authoritative? |
|-------|-----------|---------|----------------|
| **UI** | `$this->validate()` (Livewire) or FormRequest rules | UX — inline error messages, button state | No (UX only) |
| **Business** | `Validator::make()->validate()` in Action | Data integrity — last gate before persistence | **Yes** |
| **Domain** | Entity static `rules()` methods | Shared business validation rules | Yes |

### Why Validate in Both Layers

Livewire validation runs in the browser context and can be bypassed — accidentally (JavaScript
disabled) or intentionally (crafted requests). The Action runs server-side and cannot be
circumvented because it is the last validation gate before persistence. This is defence in depth.

### DTO Validation

DTOs themselves do not validate — they are pure data carriers. Validation happens at two points:

1. **Before DTO construction**: The Livewire Form Object or FormRequest validates input and produces
   clean, typed data.

2. **Inside the Action**: The Action may call `$this->validate($data->toArray(), $rules)` for
   additional business rule validation that cannot be expressed in form-level rules.

```php
// In Action:
public function execute(RegisterStudentData $data): ActionResponse
{
    $this->validate($data->toArray(), [
        'studentId' => [
            'required',
            'exists:users,id',
            Rule::unique('registrations', 'student_id')
                ->where('internship_id', $data->internshipId),
        ],
    ]);

    // ...
}
```

### Types of Validation

| Concern | Tool | Exception |
|---------|------|-----------|
| Format (required, email, length) | `Validator::validate()` | `ValidationException` |
| Uniqueness constraints | `Validator` with `unique:` rule | `ValidationException` |
| State-based business rules | Entity method + `RejectedException` | `RejectedException` |
| Authorization | Policy `Gate` check | `AuthorizationException` |

### Where Rules Live

- **Shared validation rules** across multiple Actions → Entity static `rules()` methods
- **Action-specific rules** → inline `Validator::make()` in the Action
- **Form-level rules** → Form Object `rules()` method (for UX, re-validated in Action)
- **HTTP-level rules** → FormRequest `rules()` method (for controller endpoints)

---

## Caching Strategy

- **Centralized key registry**: Every cache key declared in `config/cache-keys.php`
- **Naming**: `{module}.{purpose}[.{qualifier}]` — e.g. `setup.is_installed`, `theme.css_variables`
- **Invalidation**: Command Action dispatches event → Listener → `Cache::forget(key)`
- **TTL classes**: Short (<5min), Medium (5min-1h), Long (1h-24h), Forever
- **Cache drivers**: `file` or `database` (Tier 1), `redis` (Tier 2+)

---

## Dependency Rules

### Package-Level (Composer)

- Core package must not depend on any module package
- Module packages may depend on Core
- Module packages may depend on other module packages (sibling imports)

### Namespace-Level

- `App\Core\*` must not import `App\{Module}\*`
- `App\{Module}\*\Entities\*` must not import `App\{Module}\*\Actions\*` or
  `App\{Module}\*\Livewire\*`
- `App\{Module}\*\Models\*` must not import `App\{Module}\*\Actions\*` (exception: entity bridge
  `fromModel()` methods in Entities)
- `App\{Module}\*\Livewire\*` must not import `App\{OtherModule}\*\Livewire\*` (use events)

### Data Flow Rules

These rules encode the 4-layer data flow. Violations are structural and must be corrected before
merge.

| # | Rule | Violation Example |
|---|------|-------------------|
| D1 | A DTO must carry only scalar, enum, and Carbon types. Never Models, never Actions, never Entities. | `CompanyData` with a `Company` property (should be scalar) |
| D2 | A DTO's only base class is `BaseData`. | Extending `BaseData` with a custom base |
| D3 | An Entity must never import an Action, Service, Livewire, or Controller. | `RegistrationState` importing `ApproveRegistrationAction` |
| D4 | A Model must never import a non-Core Action. | `User` model calling `CreateProfileAction` in an accessor |
| D5 | A Command/Process Action must return `ActionResponse`, not a Model directly. | `return $company` instead of `return $this->respondCreated($company)` |
| D6 | A Command/Process Action's `execute()` must accept a DTO as its primary parameter. | `execute(array $data)` (should be `execute(CompanyData $data)`) |
| D7 | A Livewire component must not call `Model::create()`, `Model::update()`, or `Model::delete()`. | `Company::create($this->form->toArray())` in Livewire |
| D8 | A Livewire component must not access Entity methods directly. Must delegate to an Action. | `$company->asCompanyState()->canBeDeleted()` in Livewire |
| D9 | A Service must not call an Action. | `ModuleDiscoverService` calling `CreateCompanyAction` |
| D10 | A Listener must not call UI methods directly. Should call Actions for side-effect logic. | Listener calling `redirect()` or `flash()` |
| D11 | An Entity method must not perform I/O (DB queries, HTTP, file writes, event dispatch). | Entity calling `Cache::get()` or `DB::select()` |
| D12 | An Event must not carry HTTP context, request instances, or Livewire references. | Event carrying a `Request` object |

### Dependency Graph (Allowed)

```
Livewire Component
    │
    ├──→ Policy (authorization gate)
    ├──→ Action (via method injection)
    │       │
    │       ├──→ DTO (input boundary)
    │       ├──→ Model (persistence)
    │       │       └──→ Entity (via asEntity() bridge, not Model-owned)
    │       ├──→ Entity (from Model record)
    │       ├──→ Event (dispatch)
    │       └──→ Another Action (constructor injection)
    │
    ├──→ Model (read only — simple queries)
    └──→ Blade view (render)
```

---

## Testing Strategy

- **Feature tests**: Test Command/Process Actions end-to-end with database. Actions receive DTOs,
  return `ActionResponse`. Assert model state, events dispatched, logs written.

- **Unit tests**: Test Entities (no DB), Enums, DTOs, Policies in isolation. DTOs are tested by
  constructing them, serializing them, and verifying field access — zero framework dependencies.

- **Every Action has its own test file** — scope isolation is critical.

- **LazilyRefreshDatabase** preferred over `RefreshDatabase` for test speed.

- **Entities testable without database** — `final readonly` with zero framework dependencies.
  `fromModel()` can be tested separately from business logic methods by constructing entities
  directly via `fromArray()` or constructor.

- **DTOs testable without database** — pure data objects. No migrations, no factories, no HTTP.

- **TDD workflow**: Enum → Entity → DTO → Command Action → Read Action → Process Action →
  Livewire → Policy → Console Command

- Tests mirror source structure: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`

---

## Migration Paths

### Array → DTO Migration

Existing Actions that accept `array $data` should be migrated to DTOs in three phases:

| Phase | Signature | Status |
|-------|-----------|--------|
| **1 — Array** | `execute(array $data)` | Current (many Actions still here) |
| **2 — Union** | `execute(Data|array $data)` | Transitional — both paths work |
| **3 — DTO only** | `execute(Data $data)` | Target state |

### Model → ActionResponse Return Migration

Actions that currently return a Model directly should be migrated to return `ActionResponse`:

| Phase | Return Type | Status |
|-------|-------------|--------|
| **1 — Model** | `execute(): Model` | Current (some Actions still here) |
| **2 — ActionResponse** | `execute(): ActionResponse` | Target state |

### Inline Entity → Action Migration

Livewire components that access Entities directly should be migrated to delegate to Actions:

| Phase | Location | Status |
|-------|----------|--------|
| **1 — In Livewire** | `$model->asEntity()->canX()` in Livewire | Current (some components still here) |
| **2 — In Action** | `$model->asEntity()->canX()` inside Action | Target state |

---

## Module Invariants (Do Not Violate)

- **Super Admin name is ALWAYS `Administrator`** (from config `setup.defaults.admin_name`).
- **Super Admin username is ALWAYS `superadmin`** (from config `setup.defaults.admin_username`).
- These are canonical, non-customizable credentials enforced by `SetupSuperAdminAction` which only
  accepts `(string $email, string $password)` — no name/username parameters.
- Any code that calls `SetupSuperAdminAction::execute()` must NOT pass name or username.
- The `InitializeSuperAdminAction` (CLI recovery) must also use config defaults, NOT caller-provided
  values.
- `FinalizeSetupAction` must only extract `email` and `password` from `adminData` array.
