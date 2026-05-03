# Codebase Structure

## Architecture: `app/{Layer}/{Domain}/`

This project follows the **Laravel-standard layered structure**, where the first-level directory
represents the architectural layer and the second-level directory groups code by business domain.

```
app/
├── Actions/{Domain}/          — Single-purpose action classes (execute one thing)
├── Casts/                     — Eloquent attribute casts
├── Console/Commands/{Domain}/ — Artisan CLI commands
├── Data/{Domain}/             — DTOs / Data Transfer Objects (spatie/laravel-data)
├── Enums/                     — PHP 8.1 backed enums
├── Events/{Domain}/           — Domain events (dispatchable)
├── Exceptions/                — Custom exception classes
├── Http/
│   ├── Controllers/           — HTTP request handlers
│   ├── Middleware/            — HTTP middleware
│   └── Requests/{Domain}/     — Form request validation
├── Jobs/{Domain}/             — Queueable jobs
├── Listeners/{Domain}/        — Event listeners
├── Livewire/{Domain}/         — Livewire components
├── Models/                    — Eloquent models (with Concerns/)
├── Notifications/{Domain}/    — Mail / notification classes
├── Policies/                  — Authorization policies
├── Providers/                 — Service providers
├── Repositories/{Domain}/     — Data access abstraction
├── Rules/                     — Custom validation rules
├── Services/{Domain}/         — Multi-step orchestration / stateful services
└── Support/                   — Framework-agnostic utilities & helpers
```

## Stakeholders

The system serves 5 distinct user roles with different responsibilities:

| Role | Definition | Responsibilities |
|------|------------|---------------|
| **SuperAdmin** | System owner with full access | Infrastructure management, system configuration, user lifecycle oversight |
| **Admin** | School-level manager | School profile, department management, mentor oversight, system settings |
| **Student** | Internship participant (mentee in domain model) | Daily attendance (clock-in/out), journal entries, assignment submissions, competency tracking |
| **Teacher** | School supervisor | Classroom management, monitoring visits, journal verification, academic assessment |
| **Supervisor** | Industry supervisor | Company-side oversight, technical evaluation, internship performance |

**Note**: In the domain model (code structure), Students are referred to as **Mentees** and Teachers/Supervisors are unified as **Mentors** with specializations (`teacher` or `supervisor`). One mentee can have multiple mentors with different specializations.

## Domain Taxonomy

Domains represent bounded business contexts within the application:

| Domain | Scope |
| --------- | ----- |
| **Core** | Framework traits and concerns (`Concerns\HasUuid`, `Concerns\HasMedia`) |
| **Support** | Framework-agnostic utilities, helpers (`AppInfo`, `Branding`) |
| **Shared** | Cross-cutting features (language/theme switching, base UI components) |
| **System** | Application installation, configuration (`Setup`, `Setting`, `EnvAuditor`) |
| **Audit** | Activity logging, recent activity tracking |
| **Auth** | Authentication, session management, roles, permissions |
| **Account** | User accounts, profile management |
| **Academic** | Academic years, curricula |
| **Assessment** | Assessment frameworks, grading rubrics, competency standards |
| **Schedule** | Academic schedules and calendar |
| **Registration** | Internship registrations, requirements, approvals |
| **Placement** | Internship placements and industry partners |
| **Attendance** | Clock-in/out, absence requests |
| **Journal** | Journal entries and verification |
| **Assignment** | Assignments and submissions |
| **Mentee** | Competency tracking, handbook (Student domain logic) |
| **Mentor** | Unified supervisor: `teacher` for academic supervision, `supervisor` for technical evaluation. One mentee can have multiple mentors with different specializations. |
| **Evaluation** | Internship evaluation and grading |
| **Department** | Academic department management |
| **School** | School profile and settings |
| **Company** | Internship company management |
| **Document** | Official documents, templates, PDF generation |
| **Report** | Report generation, queuing, downloads |

## Layer Definitions

| Layer | Purpose | Examples |
| ---|---|---|
| **Actions** | Single-responsibility classes that do one thing. Stateless. One `execute()` method. | `LoginAction`, `CreateSchoolAction` |
| **Services** | Multi-step orchestration, stateful operations, or complex business flows that span multiple actions. | `SetupService`, `EnvAuditor` |
| **Data** | Typed data transfer objects. Used for form input, API payloads, and inter-layer communication. | `ClockInData`, `CreateUserData` |
| **Events** | Domain events that broadcast state changes. Decouple side effects from primary operations. | `SetupFinalized`, `InternshipCreated` |
| **Listeners** | React to dispatched events. Keep actions focused by moving secondary concerns here. | `SendInternshipCreatedNotifications` |
| **Jobs** | Queueable units of work. Heavy/async operations. Use `Queueable` trait and `ShouldQueue` interface. | `GenerateReportJob` |
| **Models** | Eloquent entities. Contain domain business rules (e.g., `canBeApproved()`). Use Actions for application logic, Scopes for queries. | `User`, `Internship` |
| **Livewire** | Reactive UI components. Handle rendering, form state, and user interaction. | `Login`, `SetupWizard` |
| **Http/Controllers** | Traditional HTTP handlers. Use when RESTful API or non-Livewire routes are needed. | `DashboardController` |
| **Http/Requests** | Form request validation classes. Encapsulate authorization + validation rules. | `ClockInRequest` |
| **Http/Middleware** | HTTP request/response pipeline filters. | `ProtectSetupRoute`, `SetLocale` |
| **Policies** | Authorization gates per model. Follow Laravel naming: `{Model}Policy`. | `UserPolicy`, `InternshipPolicy` |
| **Notifications** | Outbound communications (mail, database, SMS). | `WelcomeNotification` |
| **Enums** | Type-safe enumerations. Backed enums with string/int values. | `Role`, `InternshipStatus` |
| **Exceptions** | Custom exception classes with factory methods and context. | `AuthException`, `SetupException` |
| **Services** | Stateful orchestration or complex multi-entity workflows. | `SetupService` |
| **Repositories** | Data access abstraction layer. Use when query complexity exceeds model scopes. | `InternshipRepository` |
| **Casts** | Eloquent attribute casts for custom type serialization. | `SettingValueCast` |
| **Rules** | Custom validation rule objects. | `SystemUsername` |
| **Support** | Framework-agnostic utilities, helpers, and shared logic. No Eloquent dependencies. | `AppInfo`, `Branding` |
| **Console/Commands** | Artisan CLI commands. Group by domain subdirectory. | `SetupInstallCommand` |
| **Providers** | Service providers for bootstrapping and binding. | `AppServiceProvider` |

## Naming Conventions

### Actions
- **Suffix**: `Action`
- **Verb-noun pattern**: `CreateUserAction`, `SubmitJournalEntryAction`
- **Method**: `execute()`
- **No state**: Always stateless; inject dependencies via constructor

### Services
- **Suffix**: `Service` (for orchestration) or no suffix (for specific roles like `EnvAuditor`)
- **May hold state**: Session, file state, or workflow tracking
- **Methods**: Descriptive verbs — `audit()`, `generateToken()`, `finalize()`

### Models
- **Singular noun**: `User`, `Internship`, `School`
- **Rich models**: Contain business rules (e.g., `canBeApproved()`). Use Actions for application logic, Scopes for queries
- **Traits**: Use `Concerns/HasUuid.php` for cross-cutting model behavior

### Livewire Components
- **Descriptive noun or role**: `Login`, `SetupWizard`, `Dashboard`
- **Manager suffix** for CRUD lists: `StudentManager`, `CompanyIndex`
- **Namespace** reflects user role or feature: `Livewire/Admin/`, `Livewire/Student/`

### Enums
- **Singular domain concept**: `Role`, `Gender`, `AttendanceStatus`
- **Backed enums**: Use `StringBackedEnum` or `IntegerBackedEnum` for database storage
- **No suffix** needed (already type-safe by nature)

### Exceptions
- **Suffix**: `Exception`
- **Factory methods**: Static named constructors — `AuthException::invalidCredentials()`
- **Renderers**: `{Type}ExceptionRenderer` for Livewire context handling

### DTOs / Data Objects
- **Suffix**: `Data`
- **Verb-noun or noun pattern**: `CreateUserData`, `ClockInData`
- **Immutable**: Prefer readonly properties

### Jobs
- **Suffix**: `Job`
- **Verb-noun pattern**: `GenerateReportJob`
- **Extend**: `App\Jobs\BaseJob`

### Events & Listeners
- **Events**: Past-tense or noun — `SetupFinalized`, `InternshipCreated`
- **Listeners**: Verb describing reaction — `SendInternshipCreatedNotifications`

### Requests
- **Suffix**: `Request`
- **Verb-noun pattern**: `ClockInRequest`, `CreateInternshipRequest`

### Policies
- **Suffix**: `Policy`
- **Model name prefix**: `UserPolicy`, `InternshipPolicy`

## Structural Rules

1. **Actions are the default for application logic.** Create an Action class unless the operation is
   trivial (1-2 lines) or requires state (use a Service).

2. **Livewire components handle UI state only.** Delegate mutations to Actions. Keep components
   focused on rendering and user interaction.

3. **Models contain business rules.** Application logic goes in Actions, domain rules go in Models. Use query
   scopes for read operations.

4. **Group by domain subdirectory** when a layer has >5 files belonging to the same domain.

5. **No circular dependencies.** Actions may call Services, but Services should not call Actions
   from a higher-level domain.

6. **Exceptions are domain-agnostic.** Place custom exceptions in `app/Exceptions/` with factory
   methods. Use renderers for framework-specific error display.

7. **Support utilities have no framework coupling.** Code in `app/Support/` must not depend on
   Eloquent, Livewire, or HTTP layer classes.

8. **Enums remain flat** in `app/Enums/` unless the count exceeds 20, then group by domain
   subdirectory.

9. **Tests mirror the app structure.** `tests/Feature/{Domain}/` and `tests/Unit/{Domain}/`
   correspond to `app/Actions/{Domain}/`, etc.
