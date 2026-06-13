# Action-based MVC Architecture

> **Last updated:** 2026-06-13
> **Changes:** sync — migrate exact counts to ranges (models 42→40+, Livewire 107→100+, policies 29→25+), fix route files (18→17), fix broken links, fix shared component paths
>
> Complete architectural foundation of Internara. Covers the 12-layer architecture, Action Triad pattern, data flow, cross-module communication, exception handling, validation, caching, testing strategy, and invariant rules. Every decision here serves three goals:
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
5. [Module Structure](#module-structure)
6. [19 Modules at a Glance](#19-modules-at-a-glance)
7. [Base Class Mandate](#base-class-mandate)
8. [Architectural Decisions](#architectural-decisions)
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

Internara organizes code by **business module**, not by technical layer. Each business concept — User, Academics, Program, Assessment — owns its complete vertical slice: persistence, business rules, UI components, authorization, and HTTP interface.

Flat layering (`app/Models/`, `app/Livewire/`, `app/Actions/`) scatters a single feature across eight or more directories, making it hard to reason about boundaries, impossible to enforce encapsulation, and expensive to refactor. Module colocation solves this by ensuring everything related to "Enrollment" lives under `app/Enrollment/`.

The architecture draws inspiration from Domain-Driven Design (strategic design, bounded contexts) and CQRS (command/query separation) without the operational overhead of separate databases or event sourcing. Actions replace traditional Service classes to enforce single responsibility by construction.

---

## Layered Architecture

The system is built in **12 layers** from infrastructure at the bottom to business modules at the top. Each layer depends only on layers below it. The 19 module directories are vertical slices that cross all layers below Layer 11.

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
  Author. │  Policies (25+)  RBAC (5 roles)  Functional roles (2)       │
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
  Module  │  Eloquent Models (40+)  →  extend BaseModel                 │
 Models  │  UUID primary keys (HasUuids)  HasFactory                   │
         │  Relationships, Scopes, Accessors, Mutators                 │
          │  app/{Module}/**/Models/ (40+)  + factories + seeders      │
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
          │  database/migrations/ (48 files)  config/  storage/        │
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

1. **Downward only**: A layer may only depend on layers **below** it. Layer 12 depends on 1–11, Layer 7 depends on 1–6, and so forth.
2. **Core independence**: Core (layers 3–4) depends on nothing except Laravel and Spatie packages. No business module may be imported by Core.
3. **Sibling imports allowed**: A business module at Layer 12 may import another module directly. Prefer events when side effects are involved, but direct imports are perfectly acceptable for straightforward access.
4. **Persistence isolation**: Actions never call Eloquent directly — they delegate to Models via the Action's injected dependencies.
5. **UI isolation**: Livewire components should not import other modules' Livewire components directly. Use events or redirects for cross-module UI communication.

### How Module Directories Map to Layers

| Layer | Directory within Module                                              | Example                       |
| ----- | -------------------------------------------------------------------- | ----------------------------- |
| 12    | `app/{Module}/`                                                      | The module itself             |
| 11    | `resources/views/{module}/{submodule}/` or `resources/views/{module}/` | Blade views (per submodule or module-root) |
| 10    | `routes/web/{module}.php`                                            | Route definitions (17 files, no Core route file, Evaluation pending) |
| 9     | `{SubModule}/Listeners/`, `{SubModule}/Notifications/`, `Console/`   | Communication                 |
| 8     | `{SubModule}/Policies/`                                              | Authorization                 |
| 7     | `{SubModule}/Actions/`                                               | Business operations           |
| 6     | `{SubModule}/Entities/`, `{SubModule}/Enums/`, `Types/`              | Domain rules                  |
| 5     | `{SubModule}/Models/`                                                | Persistence                   |
| 4     | Uses Core's base classes: `app/Core/{Actions,Models,Policies,...}`   | Base classes                  |
| 3     | Uses Core's contracts and exceptions                                 | Contracts                     |
| 2     | Uses database, config, filesystem                                    | Persistence infrastructure    |
| 1     | Uses PHP, Laravel, Composer packages                                 | Foundation                    |

Cross-submodule files (shared Actions, Http, Console) live at the module root, directly under `app/{Module}/` without a submodule subdirectory.

---

## Action Triad: Command, Read, Process

This is the single most important architectural decision in Internara. Actions are not monolithic — they split into three distinct categories, each with a specific contract. All three live under `app/{Module}/{SubModule}/Actions/` (or root `Actions/` for cross-submodule actions) and follow the single `execute()` method convention.

### 1. Command Actions (Mutations)

**Purpose:** Every write to the system. Create, update, delete, transition state, send notifications, upload files.

**Base class:** `BaseCommandAction` (extends `BaseAction`, provides `transaction()`, `log()`, `HandlesActionErrors`)

**Contract:**

- MUST wrap all database operations in `$this->transaction()`
- MUST call `$this->log()` after successful mutation
- MUST dispatch module events for significant state changes
- MUST be preceded by a policy check in the calling layer (Livewire/Controller)
- MUST NOT return the model directly when a DTO or entity is more appropriate
- Single public `execute()` method — never add a second public method

**Naming:** `{Verb}{Entity}Action` — `CreateUserAction`, `ApproveRegistrationAction`

```php
class ApproveReportAction extends BaseCommandAction
{
    public function __construct(
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

    public function execute(Report $report, ApproveReportData $data): Report
    {
        return $this->transaction(function () use ($report, $data) {
            $report->update([
                'status' => ReportStatus::APPROVED->value,
                'score' => $data->score,
                'feedback' => $data->feedback,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            $this->log('report_approved', $report, ['score' => $data->score]);

            event(new ReportApproved($report, auth()->user()));

            return $report;
        });
    }
}
```

### 2. Read Actions (Queries)

**Purpose:** Complex read operations that involve aggregation, filtering, authorization, or cross-module data assembly. Not for simple `Model::find()` or `Model::where()` — those stay in Livewire.

**Base class:** `BaseReadAction` (plain class with `HandlesActionErrors` trait — NO `transaction()` or `log()`).

**Contract:**

- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()`
- SHOULD return typed objects or collections, never raw arrays
- MUST pass through authorization (unless the calling layer already authorized)

**Naming:** `Read{Entity}Action` — `ReadTeacherDashboardAction`, `ReadActivityLogAction`

```php
class ReadTeacherDashboardAction extends BaseReadAction
{
    public function __construct(protected readonly Internship $model) {}

    public function execute(): array
    {
        return [
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
        ];
    }
}
```

### 3. Process Actions (Orchestration)

**Purpose:** Multi-step workflows that coordinate multiple Command and Read Actions. The "how" of complex business processes.

**Base class:** `BaseProcessAction` (extends `BaseAction` — transaction + logging at the process level).

**Contract:**

- MUST compose other Actions via constructor injection
- MUST handle partial failure — if step 3 of 5 fails, what happens to steps 1–2?
- SHOULD emit a single module event representing the completed process
- MUST NOT duplicate business logic that already exists in Command Actions

**Naming:** `Process{Entity}Action` — `ProcessRegistrationAction`, `ProcessReportFinalizationAction`

```php
class ProcessRegistrationAction extends BaseProcessAction
{
    public function __construct(
        protected readonly CreateRegistrationAction $createRegistration,
        protected readonly AssignPlacementAction $assignPlacement,
        protected readonly NotifyStudentAction $notifyStudent,
    ) {}

    public function execute(RegisterStudentData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->notifyStudent->execute($registration);

            $this->log('student_registered', $registration);
            event(new StudentRegistered($registration));

            return $registration;
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

### Mutation Flow (Writes)

Every write follows the same path through the layers:

```
Layer 10/11          Layer 7            Layer 5/6           Layer 2
Input → Livewire/Controller → Command Action → Model/Entity → Database
                                  │
                                  ├─ Policy check (Layer 8)
                                  ├─ Transaction wrap
                                  ├─ Log mutation (SmartLogger)
                                  └─ Dispatch event (Layer 9)
                                     ↓
                                  Listener(s)
                                  ├─ Notify users
                                  ├─ Invalidate cache
                                  └─ Write audit trail
```

Command Actions (Layer 7) are the **only** entry point for mutations. Livewire components (Layer 11) never call `Model::create()` directly.

### Read Flow (Queries)

```
Simple query:
Livewire → Model::query() → Database
           │
           └─ Policy check (Layer 8)

Complex query:
Livewire → Read Action → Model::query() → Database
           │              │
           ├─ Policy check └─ Filter/transform/aggregate
           └─ Return typed result
```

Reads may skip Layer 7 for simple queries but must still pass through authorization (Layer 8).

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

Events decouple side effects from core business logic. A Command Action's responsibility ends when it dispatches the event.

---

## Module Structure

Every module follows a consistent directory layout. Within each module, code is organized by **submodule** — a cluster of module objects treated as a single unit.

> [!NOTE]
> For cross-cutting or system-wide modules (such as Settings, Enrollment, or Assessment), a **flat structure** directly under the module root is permitted. This places component directories (e.g. `Actions/`, `Models/`, `Policies/`) without a submodule grouping layer, avoiding redundant namespace segments.

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
├── Support/                        → Shared module utilities (optional)
└── Services/                       → Infrastructure services (optional)
```

### Path Convention

| Scope                 | Pattern                                                           | Example                                                  |
| --------------------- | ----------------------------------------------------------------- | -------------------------------------------------------- |
| Module-specific       | `app/{Module}/{Submodule}/{Component}/{ClassName}.php`            | `app/User/Profile/Actions/UpdateProfileAction.php`       |
| Shared (cross-module) | `app/{Component}/{ClassName}.php`                                 | `app/Core/Data/AuditCheck.php`                           |
| Module views          | `resources/views/{module}/{submodule}/{component-name}.blade.php` | `resources/views/user/profile/profile-editor.blade.php`  |
| Shared views          | `resources/views/{component}/{component-name}.blade.php`          | `resources/views/livewire/lang-switcher.blade.php`       |
| Module tests          | `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php`        | `tests/Feature/User/Profile/UpdateProfileActionTest.php` |
| Shared tests          | `tests/{Feature,Unit}/{Component}/{Name}Test.php`                 | `tests/Unit/Data/AuditDtoTest.php`                       |

**No redundant namespace segments.** The class name must never be repeated in the path. This applies to both PHP classes and view files.

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
| **Auth**          | `Permissions/`, `SuperAdmin/`, `Login/`, `Account/`, `ApiTokens/`, `AccountRecovery/`, `Password/`       | Enums, Http/Middleware, Livewire (login, recovery, activation, password)                 |
| **User**          | `AccountStatus/`, `Profile/`, `Notifications/`, `Dashboard/`                                             | Http, Livewire (dashboards, editors), Actions, Enums, Entities, Rules, Support, Services |
| **SysAdmin**      | `Announcement/`, `Observability/`                                                                        | Actions, Console, Livewire (audit, pulse), Recorders, Services                           |
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

The view directory must exactly mirror the `app/` module structure. Every submodule directory under
`app/{Module}/{SubModule}/` has a corresponding `resources/views/{module}/{submodule}/` directory.
No spurious view directories may exist without a matching `app/` submodule.

```
resources/views/{module}/
├── {submodule}/                    → Matches app/{Module}/{SubModule}/
│   ├── {component-name}.blade.php  → Livewire component view
│   └── components/                 → Sub-views / partials (optional)
├── {component-name}.blade.php      → Cross-submodule component view
├── layouts/                        → Module-specific layouts (optional)
├── components/                     → Shared sub-views (optional)
├── livewire/                       → Module-root Livewire views (optional)
└── partials/                       → Reusable partials (optional)
```

**View naming rules:**

1. **Mirror app/ structure** — Each `app/{Module}/{SubModule}/` has a corresponding
   `resources/views/{module}/{submodule}/`. Views at the module root (no submodule) go into
   `resources/views/{module}/` directly.

2. **Avoid redundant name nesting** — If a submodule's primary view filename matches the submodule
   name, flatten to `{module}.{submodule}` instead of `{module}.{submodule}.{submodule}`.
   - ✅ `resources/views/auth/login.blade.php` → `view('auth.login')`
   - ❌ `resources/views/auth/login/login.blade.php` → `view('auth.login.login')`
   - ✅ `resources/views/auth/password/confirm-password.blade.php` → `view('auth.password.confirm-password')`

3. **View reference uses dot notation** — `{module}.{submodule}.{component-name}` maps directly to
   `resources/views/{module}/{submodule}/{component-name}.blade.php`. For module-root views:
   `{module}.{component-name}` → `resources/views/{module}/{component-name}.blade.php`.

4. **No orphan directories** — View subdirectories that do not correspond to an `app/` submodule
   must not exist. For example, `assessment/core/` and `evaluation/core/` are invalid because
   there is no `app/Assessment/Core/` or `app/Evaluation/Core/`.

5. **Include references** — `@include()` directives must use the full dot notation path matching
   the file location, the same as `view()` calls.

**Examples:**

| app path | view path | view() call |
|---|---|---|
| `app/Auth/Login/Livewire/Login.php` | `resources/views/auth/login.blade.php` | `view('auth.login')` |
| `app/Auth/AccountRecovery/Livewire/AccountRecovery.php` | `resources/views/auth/account-recovery/account-recovery.blade.php` | `view('auth.account-recovery.account-recovery')` |
| `app/User/Profile/Livewire/ProfileEditor.php` | `resources/views/user/profile/profile-editor.blade.php` | `view('user.profile.profile-editor')` |
| `app/Enrollment/Placement/Livewire/PlacementIndex.php` | `resources/views/enrollment/placement/placement-index.blade.php` | `view('enrollment.placement.placement-index')` |
| `app/User/UserManagement/Livewire/UserManager.php` | `resources/views/user/user-management/user-manager.blade.php` | `view('user.user-management.user-manager')` |
| `app/Assessment/Livewire/AssessmentGrading.php` | `resources/views/assessment/assessment-grading.blade.php` | `view('assessment.assessment-grading')` |

Shared cross-module views live directly under `resources/views/{component}/`:

```
resources/views/livewire/
├── lang-switcher.blade.php          → app/Settings/Livewire/LangSwitcher.php
└── theme-switcher.blade.php         → app/Settings/Livewire/ThemeSwitcher.php
```

### Livewire Component Alias Conventions

| Scope             | Pattern                                               | Example                             |
| ----------------- | ----------------------------------------------------- | ----------------------------------- |
| Submodule         | `{kebab-module}.{kebab-submodule}.{kebab-name}`       | `admin.user.user-manager`           |
| Cross-submodule   | `{kebab-module}.{kebab-name}`                         | `user.profile-editor`               |
| Shared            | `{kebab-component-name}`                              | `livewire.lang-switcher`            |

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
| 10 | **Assessment**    | Competency evaluation framework                                                | Rubrics (JSON structures), assessment grading, finalization, dual mentor fallback                        |
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
| A value object / DTO             | `extends BaseData` (final readonly)    | An array passed around                         |
| An event                         | `extends BaseEvent`                    | Implements `ShouldDispatch` manually           |
| An enum                          | `implements LabelEnum`                 | A plain PHP enum                               |
| A state machine enum             | `implements StatusEnum` (+ LabelEnum)  | A boolean field on the model                   |
| An exception                     | `extends AppException` or `ModuleException` | `extends \Exception`                      |

**Notes:**

- `User` model is the sole exception — extends `Authenticatable` directly but applies `HasUuids` manually for UUID consistency.
- Notifications extend `Illuminate\Notifications\Notification` directly (no shortcut provided).
- Cache keys go into `config/cache-keys.php`, not inline strings.

---

## Cross-Module Communication

Cross-module imports are **allowed** — import Models, Actions, Policies, or other classes from sibling modules directly when needed. Four patterns are available, used as guidance not enforcement:

| Pattern            | When to Use                                                                 | Example                                      |
| ------------------ | --------------------------------------------------------------------------- | -------------------------------------------- |
| **Direct import**  | Straightforward access with no side effects                                 | `use App\Academics\Models\AcademicYear;`     |
| **Action call**    | Cross-module business operation                                             | `$this->createUser->execute($data);`         |
| **Module event**   | Fire-and-forget side effects (notifications, cache invalidation)            | `event(new InternshipCreated($internship));` |
| **Core contract**  | Abstraction used broadly across modules                                      | `LabelEnum`, `SendsNotifications`            |

Use events when you want to add new reactions without modifying the caller. Use direct imports for everything else.

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

`ModuleException` is deliberately NOT a child of `AppException`. This allows catch blocks to target module failures independently from infrastructure failures:

- `catch (ModuleException $e)` → user-facing error messages
- `catch (InfrastructureException $e)` → operations/technical errors

---

## Validation Strategy

| Layer       | Mechanism                | Purpose                        |
| ----------- | ------------------------ | ------------------------------ |
| Livewire    | `$this->validate()`      | Form-level validation          |
| Form Object | `rules()` method         | Complex form field validation  |
| Form Request| `rules()` method         | Controller request validation  |
| Action      | `Validator::make()`      | Business rule pre-conditions   |
| Entity      | `rules()` static methods | Shared domain validation rules |

Actions call form requests/Form Objects for input validation before executing business logic. Entities expose reusable `rules()` for validation logic shared across forms.

---

## Caching Strategy

- **Centralized key registry**: Every cache key declared in `config/cache-keys.php`
- **Naming**: `{module}.{purpose}[.{qualifier}]` — e.g. `setup.is_installed`, `theme.css_variables`
- **Invalidation**: Command Action dispatches event → Listener → `Cache::forget(key)`
- **TTL classes**: Short (<5min), Medium (5min-1h), Long (1h-24h), Forever
- **Cache drivers**: `file` or `database` (Tier 1), `redis` (Tier 2+)

---

## Testing Strategy

- **Feature tests**: Test Command/Process Actions end-to-end with database
- **Unit tests**: Test Entities (no DB), Enums, DTOs, Policies in isolation
- **Every Action has its own test file** — scope isolation is critical
- **LazilyRefreshDatabase** preferred over `RefreshDatabase` for test speed
- **Entities testable without database** — `final readonly` with zero framework dependencies
- **TDD workflow**: Entity → Enum → Command Action → Read Action → Process Action → Livewire → Policy → Console Command
- Tests mirror source structure: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`

---

## Module Invariants (Do Not Violate)

- **Super Admin name is ALWAYS `Administrator`** (from config `setup.defaults.admin_name`).
- **Super Admin username is ALWAYS `superadmin`** (from config `setup.defaults.admin_username`).
- These are canonical, non-customizable credentials enforced by `SetupSuperAdminAction` which only accepts `(string $email, string $password)` — no name/username parameters.
- Any code that calls `SetupSuperAdminAction::execute()` must NOT pass name or username.
- The `InitializeSuperAdminAction` (CLI recovery) must also use config defaults, NOT caller-provided values.
- `FinalizeSetupAction` must only extract `email` and `password` from `adminData` array.
