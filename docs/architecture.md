# Architecture
> Last updated: 2026-06-03
> Changes: restructure 23 domains into 16 — merge School+Setup→Academics, Partnership→Partners, Registration+Placement→Enrollment, Internship→Program+Reports, Mentee+Mentor+Guidance→Guidance, Attendance+Logbook+Schedule→Journals, Assessment+Evaluation→Assessment, Certificate+Document→Certification, Admin→Administration
> **Context:** ✅ All 16 domains defined in the [domain index](domain/domain-index.md).


## Philosophy

Internara organizes code by **business domain**, not by technical layer. Each business concept — User, Academics, Program, Assessment — owns its complete vertical slice: persistence, business rules, UI components, authorization, and HTTP interface.

This approach exists because flat layering (`app/Models/`, `app/Livewire/`, `app/Actions/`) scatters a single feature across 8+ directories, making it hard to reason about boundaries, impossible to enforce encapsulation, and expensive to refactor. Domain colocation solves this by ensuring everything related to "Enrollment" lives under `app/Domain/Enrollment/`.

Every architectural decision below serves three goals:
- **S1 - Secure**: Protect data integrity, enforce authorization, prevent leakage
- **S2 - Sustain**: Keep the codebase maintainable as it grows across 16 domains
- **S3 - Scalable**: Design for team expansion and feature accretion without rewrites

---

## Layered Architecture

The system is built in **12 layers**, bottom to top. Each layer depends only on layers below it.
The domain directories are vertical slices that cross all layers below Layer 11.

```
  Layer 12 ┌──────────────────────────────────────────────────────────┐
   Business│  16 Domains: User, Academics, Program, Enrollment...     │
   Domains │  Each domain is a vertical slice of layers 1–11          │
   (Domain)│  app/Domain/{Domain}/                                    │
           │  ├── {Aggregate}/  ← colocated Actions, Models, Policies │
           │  ├── Types/        ← shared enums, value objects         │
           │  └── (root files)  ← cross-aggregate Http, Console, ...  │
           └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 11 ┌──────────────────────────────────────────────────────────┐
  UI /    │  Livewire 4 components (88)    Blade templates           │
  Present.│  maryUI  +  DaisyUI  +  Alpine.js  +  Tailwind CSS v4   │
          │  resources/views/{domain}/     static assets             │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
 Layer 10 ┌──────────────────────────────────────────────────────────┐
  HTTP    │  Controllers / Middleware / Routes                       │
  Layer   │  16 domain route files → routes/web/{domain}.php        │
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
          │  spatie/laravel-permission auto-registers Gate::before  │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 7 ┌──────────────────────────────────────────────────────────┐
  Business│  Command Actions — mutations  (transaction + log)        │
  Ops     │  Read Actions     — queries   (lightweight, no tx)      │
          │  Process Actions  — multi-step orchestration             │
          │  app/Domain/*/Actions/  →  1 class = 1 use case         │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
   Layer 6 ┌──────────────────────────────────────────────────────────┐
   Domain  │  Enums  (35, LabelEnum, StatusEnum, ColorableEnum)      │
  Rules   │  Entities (27, final readonly, framework deps allowed)  │
           │  State entities (via BaseEntity) │
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
    Core    │  BaseAction  BaseEntity  BasePolicy                     │
   Base    │  BaseRecordManager  BaseController  FormRequest          │
   Classes │  Data (DTO)  HandlesActionErrors                        │
           │  SmartLogger  PiiMasker                                 │
          │  app/Domain/Core/{Actions,Models,Policies,etc}          │
          └──────────────────────────────────────────────────────────┘
                                         ▲ depends on
  Layer 3 ┌──────────────────────────────────────────────────────────┐
  Core    │  Contracts: LabelEnum, StatusEnum, ColorableEnum         │
  Contracts│  SendsNotifications                                     │
          │  Exception: AppException + DomainException (dual tree) │
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
          │  Spatie: activitylog v5, medialibrary v11, permission   │
          │  v8, model-status v1                                     │
          │  Livewire 4  +  Tailwind CSS 4  +  Alpine.js            │
          │  npm packages: Vite 8, flatpickr, marked                │
          └──────────────────────────────────────────────────────────┘
```

### Layer Dependency Rules

1. A layer may only depend on layers **below** it. Layer 12 depends on 1–11, Layer 7 depends on 1–6.
2. **Core** (layers 3–4) depends on nothing except Laravel/Spatie. No business domain imports Core.
3. **Sibling imports allowed.** `Academics` domain may import `Program` domain directly. Prefer events for loose coupling when side effects are involved, but direct imports are fine for straightforward cross-domain access.
4. **Persistence isolation.** Actions never call Eloquent directly — they delegate to Models.
5. **UI isolation.** Livewire components should not import other domains' Livewire components directly. Use events or redirects for UI communication.

### How Domain Directories Map to Layers

A domain directory `app/Domain/{Domain}/` combines multiple layers. Within each domain,
code is further organized by **DDD Aggregate** — a cluster of domain objects treated
as a single unit. Each aggregate directory is itself a vertical slice containing its own
Actions, Models, Policies, and optionally Livewire, Entities, Enums, and Notifications.

| Layer | Directory within Domain | Example |
|---|---|---|---|
| 12 | `app/Domain/{Domain}/` | The domain itself |
| 11 | `resources/views/{domain}/{aggregate}/` | Blade views (per aggregate) |
| 10 | `routes/web/{domain}.php` | Route definitions |
| 9 | `{Aggregate}/Listeners/`, `{Aggregate}/Notifications/`, `Console/` | Communication |
| 8 | `{Aggregate}/Policies/` | Authorization |
| 7 | `{Aggregate}/Actions/` | Business operations |
| 6 | `{Aggregate}/Entities/`, `{Aggregate}/Enums/`, `Types/` | Domain rules |
| 5 | `{Aggregate}/Models/` | Persistence |
| 4 | (uses Core's base classes: `app/Domain/Core/{Actions,Models,Policies,...}`) | |
| 3 | (uses Core's contracts) | |
| 2 | (uses database/config) | |
| 1 | (uses PHP/Laravel) | |

The mapping above uses `{Aggregate}/` as a placeholder for each aggregate directory
(e.g., `Program/Actions/`, `Enrollment/Policies/`). Cross-aggregate files
(shared Actions, Http, Console) live at the domain root, directly under
`app/Domain/{Domain}/` without an aggregate subdirectory.

---

## Action Triad: Command, Read, Process

This is the most important architectural decision in Internara. Actions are not monolithic — they split into three distinct categories, each with a specific base class and contract.

All three live under `app/Domain/{Domain}/{Aggregate}/Actions/` (or root `Actions/` for cross-aggregate actions) and follow the single `execute()` method convention.

### 1. Command Actions (Mutations)

**Purpose:** Every write to the system. Create, update, delete, transition state, send notifications, upload files.

**Base class:** `BaseAction` (provides `transaction()`, `log()`, `HandlesActionErrors`)

**Contract:**
- MUST wrap all database operations in `$this->transaction()`
- MUST call `$this->log()` after successful mutation
- MUST dispatch domain events for significant state changes
- MUST be preceded by a policy check in the calling layer (Livewire/Controller)
- MUST NOT return the model directly when a DTO or entity is more appropriate

**Example:**
```php
class ApproveReportAction extends BaseAction
{
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

            $this->log('report_approved', $report, [
                'score' => $data->score,
            ]);

            event(new ReportApproved($report, auth()->user()));

            return $report;
        });
    }
}
```

### 2. Read Actions (Queries)

**Purpose:** Complex read operations that involve aggregation, filtering, authorization, or cross-domain data assembly. Not for simple `Model::find()` or `Model::where()` — those stay in Livewire.

**Base class:** None required. A simple non-abstract class with constructor injection is sufficient. May extend `BaseAction` only if it benefits from `HandlesActionErrors` (but does NOT call `transaction()` or `log()`).

**Contract:**
- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()` from BaseAction
- SHOULD return typed objects or collections, never raw arrays
- MUST pass through authorization (unless the calling layer already authorized)

**Naming:** `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`

**Example:**
```php
class InternshipDashboardReader
{
    public function __construct(
        protected readonly Internship $model,
    ) {}

    public function activeCount(): int
    {
        return $this->model->whereIn('status', [
            InternshipStatus::PUBLISHED->value,
            InternshipStatus::ACTIVE->value,
        ])->count();
    }

    public function recentRegistrations(int $days = 7): Collection
    {
        return Registration::where('created_at', '>=', now()->subDays($days))
            ->with('mentee.user', 'internship')
            ->limit(20)
            ->get();
    }
}
```

### 3. Process Actions (Orchestration)

**Purpose:** Multi-step workflows that coordinate multiple Command and Read Actions. The "how" of complex business processes. Process Actions exist when a single use case requires multiple mutations, conditional branching, or external service calls.

**Base class:** `BaseAction` (same as Command, with transaction + logging at the process level).

**Contract:**
- MUST compose other Actions via constructor injection
- MUST handle partial failure — if step 3 of 5 fails, what happens to steps 1–2?
- SHOULD emit a single domain event representing the completed process
- MUST NOT duplicate business logic that already exists in Command Actions

**Naming:** `{Verb}{Entity}Process` — `RegisterStudentProcess`, `CloseInternshipProcess`

**Example:**
```php
class RegisterStudentProcess extends BaseAction
{
    public function __construct(
        protected readonly CreateRegistrationAction $createRegistration,
        protected readonly AssignPlacementAction $assignPlacement,
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

    public function execute(RegisterStudentData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->notifyMentor->execute($registration);

            $this->log('student_registered', $registration);

            event(new StudentRegistered($registration));

            return $registration;
        });
    }
}
```

### Action Category Decision Table

| Scenario | Pattern | Base Class | Transaction | Logging | Event |
|---|---|---|---|---|---|
| Create a record | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| Update a record | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| Delete a record | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| State transition | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |
| Send notification | Command | `BaseAction` | ✅ Required | ✅ Required | ❌ |
| Simple list query | Inline in Livewire | None | ❌ | ❌ | ❌ |
| Complex aggregated query | Read Action | None | ❌ | ❌ | ❌ |
| Dashboard statistics | Read Action | None | ❌ | ❌ | ❌ |
| Multi-step registration | Process | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |
| Internship close readiness | Process | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |

---

## Data Flow

### Mutation Flow (Writes)

Every write follows the same path through the layers:

```
Layer 10/11          Layer 7           Layer 5/6          Layer 2
Input → Livewire/Controller → Command Action → Model/Entity → Database
                                  │
                                  ├─ Policy check (Layer 8)
                                  ├─ Transaction wrap
                                  ├─ Log mutation
                                  └─ Dispatch event (Layer 9)
                                     ↓
                                  Listener(s)
                                  ├─ Notify users
                                  ├─ Invalidate cache
                                  └─ Write audit trail
```

Command Actions (Layer 7) are the **only** entry point for mutations. Livewire components (Layer 11) never call `Model::create()` directly. This is enforced through code review and PHPStan.

### Read Flow (Queries)

```
Simple query:
Livewire → Model::query() → Database
           │
           └─ Policy check (Layer 8)

Complex query:
Livewire → Read Action → Model::query() → Database
           │              │
           ├─ Policy check └─ Aggregate/filter/transform
           └─ Return typed result
```

Reads may skip Layers 6–7 for simple queries, but must still pass through authorization (Layer 8).

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

Events decouple side effects from core business logic. A Command Action's responsibility ends when it dispatches the event. Everything that happens next — notifications, cache invalidation, audit trails — belongs in listeners.

---

## Domain Structure

Every domain follows this directory layout. Within each domain, code is organized by
**DDD Aggregate** — a cluster of domain objects treated as a single unit. Each aggregate
has its own technical-layer subdirectories for high cohesion. Files that span multiple
aggregates (dashboards, shared utilities, console commands) live at the domain root.

```
app/Domain/{Domain}/
├── {Aggregate}/                    → One directory per aggregate root
│   ├── Actions/                    → Business operations (Command, Read, Process)
│   ├── Models/                     → Eloquent models belonging to this aggregate
│   ├── Policies/                   → Authorization gates
│   ├── Livewire/                   → UI components (optional)
│   │   └── Forms/                  → Form Objects (optional)
│   ├── Entities/                   → Pure business rules (optional)
│   ├── Enums/                      → Enum specific to this aggregate (optional)
│   ├── Events/                     → Domain events (optional)
│   ├── Listeners/                  → Event subscribers (optional)
│   └── Notifications/              → Multi-channel alerts (optional)
├── Types/                          → Shared value objects, flat enums, rules (optional)
├── Actions/                        → Cross-aggregate orchestration (optional)
├── Http/                           → Cross-aggregate controllers & middleware (optional)
│   ├── Controllers/
│   └── Middleware/
├── Console/                        → Cross-aggregate artisan commands (optional)
├── Livewire/                       → Cross-aggregate UI (dashboards, etc.) (optional)
│   └── Forms/                      → Form Objects (optional)
├── Notifications/                  → Cross-aggregate notifications (optional)
├── Events/                         → Cross-aggregate events (optional)
├── Listeners/                      → Cross-aggregate listeners (optional)
├── Support/                        → Shared domain utilities (optional)
└── Services/                       → Infrastructure services (optional)
```

Not every domain needs every directory. `Incidents` might only have `IncidentReport/` aggregate.
`Certification` adds `Http/` when downloads are needed. Tools and simple value objects
too small for their own aggregate live in `Types/`.

### Aggregate Mapping

Each domain contains the following aggregates:

| Domain | Aggregates | Cross-Aggregate Root Files |
|---|---|---|---|
| **Core** | — | (infrastructure + cross-domain utilities) |
| **User** | `Login/`, `Password/`, `ActivationToken/`, `AccountRecovery/`, `AccountStatus/`, `Profile/`, `Notification/` | Http, Livewire (login, recovery, dashboards, editors) |
| **Academics** | `School/`, `Department/`, `AcademicYear/`, `Setup/` | Console, Events, Listeners, Http, Livewire (wizard), Services, Support |
| **Partners** | `Company/`, `Partnership/` | — |
| **Program** | `Internship/`, `InternshipPhase/`, `InternshipGroup/`, `DocumentRequirement/` | Http, Events, Listeners, Notifications, Rules |
| **Enrollment** | `Registration/`, `AccountApplication/`, `RegistrationDocument/`, `Placement/`, `PlacementChangeRequest/` | — |
| **Guidance** | `Mentee/`, `Mentor/`, `SupervisionLog/`, `Handbook/`, `HandbookAcknowledgement/` | Http |
| **Journals** | `Attendance/`, `AbsenceRequest/`, `Logbook/`, `IndustryAssessment/`, `Schedule/` | Http |
| **Assignments** | `Assignment/`, `Submission/` | Http, Notifications |
| **Reports** | `Report/` | Http |
| **Assessment** | `Assessment/`, `Rubric/`, `Competency/`, `Indicator/`, `Presentation/` | — |
| **Evaluation** | `Evaluation/` | — |
| **Certification** | `Certificate/`, `CertificateTemplate/`, `Document/` | Http, Support |
| **Incidents** | `IncidentReport/` | — |
| **Settings** | `Setting/` | Http, Livewire (system settings), Support |
| **Administration** | `Announcement/`, `GdprDeletionLog/` | Console, Notifications, Livewire (audit, pulse), Services |

### Views Structure

Blade views mirror both the domain and aggregate structure:

```
resources/views/{domain}/
├── {aggregate}/                    → Views for a specific aggregate
│   ├── {component-name}.blade.php  → Livewire component view
│   └── components/                 → Sub-views (optional)
├── layouts/                        → Domain-specific layouts (optional, cross-cutting)
├── components/                     → Shared sub-views (optional, cross-cutting)
└── partials/                       → Reusable partials (optional, cross-cutting)
```

Cross-aggregate views (dashboards, global components) live directly in the domain
view directory without an aggregate subdirectory. The Livewire component alias follows
`{kebab-domain}.{kebab-aggregate}.{kebab-component-name}` for aggregate-specific
components, and `{kebab-domain}.{kebab-component-name}` for cross-aggregate
components.

---

## 16 Domains at a Glance

| Domain | Boundary | Key Concept |
|--------|----------|-------------|
| **Core** | Base classes, infrastructure, and cross-domain utilities everything depends on | Base model, base action, base entity, contracts, logging, exceptions, theme, CSV handler, environment detection, locale management |
| **User** | Identity, access, and profiles | Login, passwords, account lifecycle, recovery, RBAC, profile editing, notifications |
| **Academics** | Institution setup & configuration | School profile, departments, academic years, first-run wizard |
| **Partners** | External relationships | Companies, partnership agreements, MoU |
| **Program** | PKL program lifecycle | Program lifecycle, phases, groups, document requirements |
| **Enrollment** | Student registration & placement | Applications, wizard, document upload, verification, slot management, change requests |
| **Guidance** | Mentoring & supervision | Student role activation, supervision logs, handbooks, acknowledgements |
| **Journals** | Daily activities | Daily logbook, attendance, absence requests, scheduling |
| **Assignments** | Tasks & submissions | Task creation, grading workflow, revision loop |
| **Reports** | Student final reports | Report writing, revisions, supervisor review |
| **Assessment** | Competency evaluation | Rubrics, scoring, presentations |
| **Evaluation** | Program evaluation & feedback | Mentor evaluation, program feedback, user satisfaction |
| **Certification** | Credentialing | Certificate issuance, templates, document generation, PDF rendering |
| **Incidents** | Issue reporting | Report, investigation, resolution workflow |
| **Settings** | Runtime configuration | Key-value store, branding, localization |
| **Administration** | System oversight | User CRUD, announcements, GDPR compliance, audit logs |

---

## Base Class Mandate

Every layer has exactly one base class from Core. There is no alternative.

| You need... | Use this | Not this |
|---|---|---|
| A database table | `extends BaseModel` | `extends Model` |
| A business operation (mutation) | `extends BaseAction` | A custom service with multiple methods |
| A read operation (complex query) | A plain class | A service with mixed read/write methods |
| A multi-step process | `extends BaseAction` (Process pattern) | Inline orchestration in Livewire |
| Business rules | `extends BaseEntity` (final readonly) | A trait, a helper class, or inline in the model |
| State machine | `implements StatusEnum` | Custom status columns with if/else |
| Authorization | `extends BasePolicy` | `Gate::define()` with inline closures |
| A CRUD list page | `extends BaseRecordManager` | A Livewire component from scratch |
| A HTTP form request | `extends FormRequest` (Core's) | `extends Request` or inline validation |
| An enum | `implements LabelEnum` | A plain PHP enum or class constants |
| Logging | `SmartLogger` | `Log::` facade or `activity()` helper |
| Cache key registry | `CacheKeys` constants | Hardcoded strings everywhere |

These rules are enforced through code review and static analysis (PHPStan).

---

## Architectural Decisions

### Why Actions instead of Service classes?

Services accumulate unrelated methods over time (a `UserService` grows `createUser`, `sendWelcomeEmail`, `validateUsername`, etc.). Actions enforce **single responsibility by construction** — one class per use case. This makes them testable in isolation, discoverable by name, and composable.

### Why split Actions into Command, Read, and Process?

- **Commands** and **Reads** have fundamentally different contracts: commands need transactions + logging, reads do not. Mixing them in a single base class either forces overhead on reads or skips guarantees on writes.
- **Processes** solve the coordination problem. Without them, orchestration logic ends up in Livewire components (where it doesn't belong) or in a single Action that violates single responsibility.
- The split mirrors CQRS without the infrastructure cost. Same models, same database — different class contracts.

### Why Entities separate from Models?

Models couple business logic to the database. When `User::isSuspended()` calls `$this->status` and `$this->locked_at`, tests require a database, migrations, and factory state. Entities remove this coupling. `Apprentice::isSuspended()` receives its data via constructor — testable in one line, no database needed.

### Why UUID primary keys?

Auto-incrementing IDs leak information (user count, growth rate) and create merge conflicts in distributed workflows. UUIDs are globally unique, require no coordination, and work across SQLite, MySQL, and PostgreSQL identically.

### Why domain-split routes?

A single `routes/web.php` with 200+ lines creates merge conflicts and makes it hard to find routes for a feature. Splitting by domain means each team member owns their domain's route file without touching others.

### Why DTOs are optional (but recommended)?

During rapid development, `execute(array $data)` is faster to write and refactor. DTOs (via `App\Domain\Core\Data\Data`) add type safety, autocomplete, and documentation at the cost of boilerplate. The recommended approach is:

1. Start with `array $data` for speed
2. Migrate to typed DTOs when an Action's input stabilizes or grows beyond 3 parameters
3. Use `Data::fromArray()` for backward compatibility during migration

### Why events are optional (but encouraged)?

Every Command Action *can* dispatch events, but not every command *must*. The rule of thumb:
- If side effects exist (notifications, cache invalidation, audit beyond SmartLogger) → dispatch an event
- If the action only mutates database state and logs → no event needed
- If cross-domain coordination is needed → event is required

Events can be introduced incrementally. Start without them, add them when a second listener needs to react to the same occurrence.

---

## Cross-Domain Communication

Cross-domain imports are **allowed** — import Models, Actions, Policies, or Livewire components from other domains directly when needed. The following patterns provide guidance, not enforcement:

### 1. Direct Import (simplest)

```php
use App\Domain\Academics\Models\AcademicYear;

$year = AcademicYear::where('is_active', true)->first();
```

Use when straightforward access to another domain's data or logic is needed.

### 2. Core Contracts (Layer 3)

Shared interfaces in `App\Domain\Core\Contracts\` for abstractions used across many domains:

- `LabelEnum`, `StatusEnum`, `ColorableEnum` — enum contracts
- `SendsNotifications` — notification dispatch (bound to `SendNotificationAction`)

### 3. Domain Events (Layer 9)

Events decouple side effects from core business logic. Use events when the same occurrence triggers multiple downstream reactions, especially cross-domain:

```php
// Emitting domain
event(new InternshipCreated($internship, auth()->user()));

// Reacting domain
class NotifyAdminsInternshipCreated implements ShouldQueue
{
    public function handle(InternshipCreated $event): void
    {
        Notification::send($admins, new InternshipCreatedNotification(
            internshipName: $event->internship->name,
        ));
    }
}
```

### 4. Action Delegation

Any Action may call another domain's Action:

```php
class CloseInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly \App\Domain\Assessment\Actions\FinalizeAssessmentsAction $finalizeAssessments,
    ) {}
}
```

**Guideline:** Prefer direct imports for simplicity. Use events when the same event triggers 2+ independent side effects, or when you want to add new reactions without modifying existing code.

---

## Exceptions

Two separate exception hierarchies exist. Both use the `HasExceptionContext` trait for consistent hint, context, and CLI-friendly output.

### AppException hierarchy

`AppException` extends `RuntimeException`. All derive from it:

```
AppException (abstract)
├── ActionException (abstract) — business operation failed
│   ├── ConflictException — duplicate or conflicting state
│   └── ValidationFailedException — input validation failure
├── InfrastructureException (abstract) — external system failure
│   └── RateLimitException — rate limit exceeded
└── PresentationException (abstract) — HTTP-layer failure
    ├── NotFoundException — resource not found
    └── UnauthorizedException — access denied
```

### DomainException hierarchy (separate tree)

`DomainException` is deliberately **not** a child of `AppException`. This keeps domain catch blocks isolated from layered framework concerns:

```
DomainException (abstract, extends RuntimeException)
└── RejectedException — domain invariant violated (e.g., invalid state transition)
```

### When to use which

| Scenario | Exception | Hierarchy |
|---|---|---|
| Input validation failed | `ValidationFailedException` | AppException |
| Duplicate record | `ConflictException` | AppException |
| Permission denied (Layer 8) | `UnauthorizedException` | AppException |
| Resource not found | `NotFoundException` | AppException |
| External API timeout | `InfrastructureException` or `RateLimitException` | AppException |
| Invalid state transition | `RejectedException` | DomainException |
| Domain invariant violated | `RejectedException` | DomainException |

---

## Validation Strategy

Validation happens at the **outermost layer** possible. Livewire is the primary UI, so Form Objects are the primary validation mechanism.

### Livewire Form Objects (Primary)

Complex forms MUST extract validation into Form Objects under
`app/Domain/{Domain}/{Aggregate}/Livewire/Forms/{Name}Form.php` (or root
`Livewire/Forms/` for cross-aggregate forms):

```php
class AcademicYearForm extends Form
{
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';

    public function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name,'.($excludeId ?? 'NULL')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
```

**Rules:**
- Form Objects extend `Livewire\Form`
- All form state, validation rules, and `toArray()` logic live inside the Form Object
- Form Objects validate via explicit `$form->validate()` in the parent component
- Form Objects must NOT call Actions directly — they prepare data for the component to dispatch

### Shared Validation Rules (Gradual Adoption)

For consistency across Form Objects and Form Requests, validation rules can be centralized in Entities or DTOs:

```php
// In CreateInternshipData or Internship entity
public static function rules(?string $excludeId = null): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'start_date' => ['required', 'date'],
        'end_date' => ['required', 'date', 'after:start_date'],
        'status' => ['required', Rule::enum(InternshipStatus::class)],
    ];
}
```

Both Form Objects and Form Requests can reference the same rules.

### Form Requests (Secondary)

For the rare HTTP controller-based routes, `App\Domain\Core\Http\Requests\FormRequest` provides `ValidationFailedException` on failure instead of Laravel's default redirect.

---

## Caching Strategy

### Centralized Key Registry

Every cache key across the codebase MUST be defined in `App\Domain\Core\Support\CacheKeys` as a constant. This prevents key collisions and makes cache dependencies discoverable.

```php
final readonly class CacheKeys
{
    public const string SETUP_INSTALLED = 'setup.is_installed';
    public const string ADMIN_DASHBOARD_STATS = 'admin.dashboard.stats';
    public const string NOTIFICATION_UNREAD = 'notification.unread:';
    // ...
}
```

### Cache Invalidation

Cache invalidation follows the event-driven pattern. When data changes, the Command Action dispatches an event, and a listener flushes the affected cache keys.

```
Command Action → event({Entity}Updated) → CacheInvalidationListener → Cache::forget('key')
```

**Rules:**
- Every cached value has a documented invalidation trigger in `CacheKeys` (as a comment)
- Invalidation SHOULD happen in an event listener, not inline in the Action
- For frequently invalidated keys, prefer short TTLs over eager caching
- The `system:cache-warm` command pre-warms known cache keys after deployment

### What to cache

| Data | Cache Key | TTL | Invalidated By |
|---|---|---|---|
| Setup status | `setup.is_installed` | forever | FinalizeSetupAction, GenerateSetupTokenAction |
| Admin dashboard stats | `admin.dashboard.stats` | medium | User/Department/Internship CRUD actions |
| Theme CSS variables | `theme.css_variables` | long | Settings update (color change) |
| Unread notification count | `notification.unread:{userId}` | medium | MarkAsRead/MarkAllAsRead/SendNotification actions |
| Core integrity | `core.integrity_verified` | forever | composer.json changes (manual flush) |
| Core app name | `core.app_name` | forever | composer.json changes (manual flush) |
| App metadata | `appinfo.metadata` | forever | composer.json changes |
| Livewire component map | `domain.discovered_livewire` | static | Structural component changes |
| Policy map | `domain.discovered_policies` | static | Structural policy changes |
| View namespaces | `domain.discovered_views` | static | Structural view directory changes |
| Login failure count | `auth.login-failures:{userId}` | medium | Successful login |
| Health check | `health_check` | short | Each health check run |
| Recovery attempts | `recover_admin_attempts_{md5(email)}` | medium | Successful recovery |
| All settings | `settings.all` | forever | Settings::set(), Settings::forget() |
| Setting group | `settings.group.{name}` | forever | Settings::set(), Settings::forget() |
| Setting keys | `settings.keys` | forever | Settings::set(), Settings::forget() |
| Individual setting | `settings.{key}` | forever | Settings::set(), Settings::forget() |

---

## Dependency Rules

| Rule | Explanation | Violation Example |
|---|---|---|
| **Downward only** | Layer N may only use layers < N | A Controller (10) importing from another domain's Livewire (11) |
| **Core independence** | Core (Layers 3–4) must not import any business domain | Core importing an Academics model |
| **No sibling imports** | Domains at Layer 12 must not import each other | `Academics` importing `Program` models |
| **Aggregate encapsulation** | Aggregate directories MUST NOT import sibling aggregates. Cross-aggregate access goes through the domain root | `Profile/Actions/UpdateProfileAction.php` importing `Notification/Models/Notification.php` |
| **Root for cross-cutting** | Cross-aggregate code lives at the domain root, not in any single aggregate | A dashboard action in `User/Actions/` that queries both Profile and Notification |
| **Persistence isolation** | Entities (Layer 6) never import Models (Layer 5) | An Entity calling `User::find()` |
| **Action gate** | All mutations go through Command Actions (Layer 7). No `Model::save()` outside Actions | A Livewire component calling `$user->save()` |
| **Authorize first** | Every Action must be preceded by a policy check (Layer 8) | An Action that modifies data without calling `$this->authorize()` |

---

## Testing Strategy

Tests mirror the aggregate-based source structure:

```
tests/Feature/{Domain}/{Aggregate}/{Name}Test.php  → Integration tests
tests/Unit/{Domain}/{Aggregate}/{Name}Test.php     → Pure unit tests
tests/Unit/{Domain}/Types/{Name}Test.php           → Value objects, flat enums, rules
```

### Feature Tests

- Test Command Actions in isolation: factory → action execute → assert database/state
- Test Read Actions: setup data → reader method → assert returned structure
- Test Process Actions: test the complete workflow and partial failure scenarios
- Test Livewire components: render → interact → assert state/redirect
- Use `LazilyRefreshDatabase` for test isolation
- Do NOT test Eloquent relationships or model scopes directly — test through Actions

### Unit Tests

- Entities: construct with test data → assert business rule methods
- Enums: assert label(), transition rules, terminal states
- Data DTOs: construct via constructor or `fromArray()` → assert `toArray()`
- Policies: instantiate with mock user/model → assert boolean gate methods

### What NOT to test

- Eloquent model relationships (they are framework behavior, test through feature tests)
- Simple getters/setters on models
- Configuration loading
- Framework-provided functionality (UUID generation, pagination, etc.)

---

## Migration Paths

These patterns were introduced to address specific architectural gaps. Each has a clear migration strategy from the current simpler approach.

### Array to DTO Migration

```php
// Step 1 — current: raw array
public function execute(array $data): Report

// Step 2 — DTO created alongside, Action accepts both
public function execute(SubmitReportData|array $data): Report

// Step 3 — DTO only
public function execute(SubmitReportData $data): Report
```

### Inline Event to Listener Migration

```php
// Step 1 — current: side effects in Action
$admin->notify(new InternshipCreatedNotification(...));

// Step 2 — event dispatched
event(new InternshipCreated($internship, auth()->user()));

// Step 3 — listener created, side effects moved
class NotifyAdminsInternshipCreated implements ShouldQueue { ... }
```

### Inline Cache to Event-Driven Invalidation

```php
// Step 1 — current: manual forget in Action
Cache::forget(CacheKeys::ADMIN_DASHBOARD_STATS);

// Step 2 — event dispatched, listener flushes
class InvalidateDashboardCache
{
    public function handle(object $event): void
    {
        Cache::forget(CacheKeys::ADMIN_DASHBOARD_STATS);
    }
}
```

---

## Domain Invariants (DO NOT VIOLATE)

- **Super Admin name is ALWAYS `Administrator`** (from config `setup.defaults.admin_name`).
- **Super Admin username is ALWAYS `superadmin`** (from config `setup.defaults.admin_username`).
- These are canonical, non-customizable credentials enforced by `SetupSuperAdminAction`
  which only accepts `(string $email, string $password)` — no name/username parameters.
- Any code that calls `SetupSuperAdminAction::execute()` must NOT pass name or username.
- The `InitializeSuperAdminAction` (CLI recovery) must also use config defaults, NOT caller-provided values.
- `FinalizeSetupAction` must only extract `email` and `password` from `adminData` array.
- UUID primary keys on all models (via BaseModel/HasUuids). Foreign keys use `foreignUuid()->constrained()`.
- All enums are string-backed and implement `LabelEnum`. State machine enums also implement `StatusEnum`.
- All files must begin with `declare(strict_types=1)`.
