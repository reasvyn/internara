# Architecture Design: Internara (Enhanced Action-Oriented MVC)

## 1. Introduction

This document defines the architectural standards for Internara. The design separates **Business Rules (Stateful)** from **Application Logic (Stateless)** to achieve three quality dimensions: Secure, Sustainable, and Scalable.

The architecture is built to support extensibility — any institution or developer can adapt, extend, or integrate with the system without modifying core logic.

## 2. Quality Principles

### Secure
- **Input Validation**: Performed at the outermost layer (Form Requests) before entering any business logic
- **Explicit Failure**: Custom Exceptions handle business logic failures without leaking internal details
- **Protected Rules**: Business rules are centralized within Eloquent Models, preventing bypass via direct controller access
- **Authorization**: Policies enforce access control at the HTTP layer with additional checks in Actions

### Sustainable
- **Business Language**: Actions and Models use domain terminology (e.g., `ClockInAction`, not `SaveAttendance`)
- **Single Responsibility**: Each Action has one `execute()` method representing one use case
- **Maintainability**: Flat layer structure (no module nesting) simplifies onboarding and code navigation
- **Bounded Complexity**: Optional layers (Repositories, Events) are used only when they provide measurable value

### Scalable
- **Stateless Actions**: Application logic is reusable across web, API, and CLI entry points
- **Domain-Driven Grouping**: Actions, Models, and supporting layers are organized by business domain
- **Event-Driven Side Effects**: Notifications, emails, and audit logs are decoupled from core business logic

## 3. Layer Structure

```
app/
├── Actions/           # Stateless use cases (orchestration layer)
│   ├── Internship/
│   ├── Attendance/
│   └── ...
├── Models/            # Rich models with business rules
├── Http/
│   ├── Controllers/   # Thin controllers (API endpoints)
│   ├── Requests/      # Form Request validation classes
│   └── Middleware/
├── Livewire/          # Stateful UI components (Web only)
├── Policies/          # Authorization rules
├── Events/            # Domain events for side effects
├── Listeners/         # Event handlers (notifications, audit, emails)
├── Repositories/      # Optional: Complex query abstraction (only when needed)
├── Services/          # Infrastructure services (technical concerns)
├── Data/              # DTOs for data transfer
├── Enums/             # Fixed business statuses
└── Support/           # Cross-cutting concerns
```

### A. HTTP/UI Layer (Controllers & Livewire)
- **Responsibility**: Receive requests, validate input via Form Requests, invoke Actions, and return responses.
- **Constraint**: Must not contain business logic or complex database queries.
- **Controllers**: Handle API requests (stateless, return JSON).
- **Livewire**: Handle Web requests (stateful, return Blade views).
- **UI Components**: Base layout (`app.blade.php`), header with navbar (`header.blade.php`), footer with author credit (`app-signature.blade.php`).
- **Translations**: Indonesian & English for all pages (auth, dashboard, school, department, internship, company, setting, setup).

### B. Action Layer (Stateless Logic / Use Cases)
- **Location**: `app/Actions/{Domain}/`
- **Responsibility**: Orchestrate application workflows.
- **Properties**: 
    - Must be Stateless (no instance properties beyond constructor-injected dependencies).
    - Receives structured input (DTOs, Form Requests, or Models).
    - Invokes Business Rules in Models.
    - Performs side-effects via Events or direct calls (database writes, file uploads).
    - May use Repositories for complex data retrieval.
- **Current Domains**: AcademicYear, AccountLifecycle, Analytics, Assessment, Assignment, Attendance, Audit, Auth, Company, Department, Document, Guidance, Internship, Journal, Mentor, Notification, Permission, Profile, Report, Schedule, School, Setting, Setup, Supervision, Teacher.

### C. Domain Layer (Rich Models / Business Rules)
- **Location**: `app/Models/`
- **Responsibility**: Handle stateful business rules and data relationships.
- **Methods**: Contains logic for "Is it allowed?", "What is the status?", or internal calculations.
- **Constraint**: Models should not directly call external services or send notifications (use Events instead).
- **Key Models**: School (HasMedia), Department, Internship, InternshipCompany, InternshipPlacement, InternshipRegistration (HasStatuses), InternshipRequirement, RequirementSubmission, AttendanceLog, AbsenceRequest, JournalEntry, SupervisionLog (HasStatuses), MonitoringVisit, Assignment, AssignmentType, Submission, Assessment, Competency, StudentCompetencyLog, DepartmentCompetency, DocumentTemplate, OfficialDocument, Notification, Profile, Setting, Setup, User, AuditLog. See `docs/database.md` for full schema.
- **Enums**: `AbsenceReasonType`, `AbsenceRequestStatus`, `AccountStatus`, `AssignmentStatus`, `AttendanceStatus`, `BloodType`, `DocumentCategory`, `Gender`, `InternshipStatus`, `JournalEntryStatus`, `NotificationType`, `RequirementType`, `Role`, `SubmissionStatus`, `SupervisionLogStatus`, `SupervisionType`.

### D. Data Layer (DTOs & Enums)
- **Location**: `app/Data/` & `app/Enums/`
- **Responsibility**: Standardize data flow between layers and define fixed business statuses.
- **DTOs**: `CreateUserData`, `JournalEntryData`, `DirectPlacementData`, `InternshipRegistrationData`, `ClockInData`.
- **Enums**: See list in section C above.

### E. Job Layer (Queued Background Processing)
- **Location**: `app/Jobs/{Domain}/`
- **Current**: `GenerateReportJob` (asynchronous report generation).
- **When to Use**:
    - Long-running operations that should not block HTTP responses
    - PDF/report generation, file processing, bulk operations
    - Third-party API calls that may timeout
- **Properties**:
    - Implements `ShouldQueue` interface
    - Uses `Queueable` trait
    - Receives minimal data (IDs, not full models) for efficient serialization
    - Handles its own error states (success/failure tracking on related models)

### F. Repository Layer (Optional - Complex Queries Only)
- **Location**: `app/Repositories/{Domain}/`
- **Current**: `InternshipRepository`.
- **When to Use**:
    - Complex queries with multiple joins or conditions
    - Queries reused across multiple Actions
    - Need to swap data sources (e.g., Eloquent to API)
- **When NOT to Use**:
    - Simple CRUD operations (use Eloquent directly in Actions)
    - Queries specific to a single Action (keep in the Action)
- **Properties**:
    - Return Eloquent Collections or Models
    - Do not contain business logic
    - May use Query Scopes from Models

### G. Event/Listener Layer (Side Effects)
- **Location**: `app/Events/` & `app/Listeners/`
- **Current**: `InternshipCreated` event with `SendInternshipCreatedNotifications` listener.
- **Purpose**: Decouple side effects from core business logic.
- **When to Use**:
    - Sending notifications (email, in-app)
    - Audit logging (can also be done directly in Actions for simplicity)
    - Triggering external integrations
    - Multiple things need to happen after a business event
- **When NOT to Use**:
    - Single, simple side effect (do it directly in the Action)
    - When it reduces clarity without benefit

### G. Service Layer (Infrastructure Services)
- **Location**: `app/Services/`
- **Current**: `SetupService` (installation wizard orchestration, token management, lock file guard), `EnvAuditor` (pre-flight system checks).
- **Purpose**: Handle technical/infrastructure concerns.
- **Constraint**: Services should not contain business rules (those belong in Models).

## 4. Implementation Guidelines

### Use Case: Standard Action Pattern
```php
namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Events\InternshipCreated;
use App\Models\Internship;
use App\Http\Requests\CreateInternshipRequest;

class CreateInternshipAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}
    
    public function execute(CreateInternshipRequest $request): Internship
    {
        return DB::transaction(function () use ($request) {
            $internship = Internship::create($request->validated());
            
            // Option 1: Direct audit logging (simple cases)
            $this->logAudit->execute(
                action: 'internship_created',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: ['name' => $internship->name],
                module: 'Internship'
            );
            
            // Option 2: Event-driven side effects (complex cases)
            event(new InternshipCreated($internship, auth()->user()));
            
            return $internship;
        });
    }
}
```

### Business Rule in Model
Rules that depend on model data must reside within the model itself.
```php
namespace App\Models;

class Internship extends Model {
    public function canBeApproved(): bool {
        return $this->status === InternshipStatus::PENDING 
            && $this->documents->isComplete();
    }
    
    public function isAcceptingRegistrations(): bool {
        return $this->status?->isAcceptingRegistrations() ?? false;
    }
}
```

### Optional: Repository for Complex Queries
```php
namespace App\Repositories\Internship;

use App\Models\Internship;
use Illuminate\Database\Eloquent\Collection;

class InternshipRepository
{
    public function findAvailableForStudent(Student $student): Collection
    {
        return Internship::query()
            ->where('status', InternshipStatus::OPEN)
            ->whereDoesntHave('registrations', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->with(['company', 'requirements'])
            ->get();
    }
}
```

### Form Request for Validation
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInternshipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'description' => ['nullable', 'string'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'start_date.after' => 'The start date must be in the future.',
        ];
    }
}
```

## 5. Routing Strategy

### Web Routes (Livewire)
```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware(['role:student'])->group(function () {
        Route::livewire('/internships', InternshipBrowser::class);
    });
});
```

### API Routes (Controllers + Form Requests)
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/internships', [InternshipController::class, 'store']);
});
```

## 6. Sub-Systems

### Official Documents (Document Engine)
The system includes a centralized engine for managing institutional correspondence, certificates, and reports.
- **Templates**: Blade-based templates stored in `DocumentTemplate`.
- **Generation**: `GenerateDocumentAction` uses `dompdf` via `GeneratePdfAction` to convert templates into formal PDFs.
- **Storage**: All documents are attached via **Spatie Media Library** for consistent file handling and UUID protection.

## 7. Extension Points

Internara is designed to be extended without modifying core logic. The following patterns enable safe customization:

### Adding a New Domain
1. Create the domain folder: `app/Actions/{NewDomain}/`
2. Create the Action: `app/Actions/{NewDomain}/XxxAction.php` with `execute()` method
3. Create the Model: `app/Models/{NewDomain}.php` with `HasUuid` trait and business rules
4. Create the migration: `database/migrations/YYYY_MM_DD_create_{table}.php`
5. Create the Livewire component: `app/Livewire/Admin/{NewDomain}/XxxManager.php`
6. Create the view: `resources/views/livewire/admin/{new-domain}/index.blade.php`
7. Add routes in `routes/web.php` with role-based middleware

### Adding a New Report Type
1. Register the type in `ReportsManager::$reportTypes`
2. Create or reuse an Action that gathers the report data
3. The queued `GenerateReportJob` handles async generation and delivery automatically

### Adding a New Language
1. Create translation files in `lang/{locale}/` (e.g., `lang/ja/` for Japanese)
2. Use existing translation keys as reference from `lang/en/` and `lang/id/`
3. The language switcher automatically detects available locales from the `lang/` directory

### Adding a Custom Theme
1. Define a new `@plugin "daisyui/theme"` block in `resources/css/app.css`
2. Set the theme name, color palette, and radius tokens
3. Users can switch to the new theme via the theme switcher component

### Adding an API Endpoint
1. Create a Controller in `app/Http/Controllers/Api/`
2. Create a FormRequest for input validation
3. Call an existing Action (or create a new one) from the controller
4. Add the route in `routes/api.php` with appropriate middleware
5. The Action layer is API-agnostic — no changes needed to existing Actions

### Hooking into Events
1. Create an Event class: `app/Events/{Domain}Created.php`
2. Create a Listener: `app/Listeners/Handle{Domain}Created.php`
3. Register the Event → Listener mapping in `EventServiceProvider`
4. Dispatch the event from the relevant Action after the business operation

## 8. Legacy Modules (Reference Only)

> **Status**: The `modules/` directory contains code from a previous modular monolith structure. These modules are **disabled from autoloading** and are retained as reference material. All active domain implementations live in `app/`. Report, Handbook, Schedule, and AcademicYear are fully implemented in the current architecture.

## 9. Anti-Patterns to Avoid

### ❌ Don't Over-Engineer
- Don't create Repositories for simple CRUD (use Eloquent directly)
- Don't use Events for single, simple side effects
- Don't create Services for business logic (use Actions + Models)

### ❌ Don't Violate Layer Separation
- Controllers/Livewire must not contain business logic
- Models must not directly send notifications (use Events)
- Actions must not contain HTTP-specific code (use Form Requests)

### ✅ Do Keep It Simple
- `Repositories/`, `Events/`, `Listeners/`, `Services/` integrated into the request lifecycle
- Prefer direct calls over abstractions when clarity is better
- Document why you added a layer (see Decision Records)

## 10. Documentation & Sync
Every significant architectural change should be documented so future contributors understand the reasoning behind decisions. The code must always remain in sync with this documentation.

When adding lifecycle layers (Repositories, Events, etc.), document:
- What problem the layer solves
- Why simpler approaches weren't sufficient
- Which quality dimension it serves

## 11. Automated Verification
The architecture is enforced by automated tests in `tests/Arch/`:

### Architectural Tests
- `GlobalCodingStandardsTest.php` — Strict types, clean code
- `Layers/LayerSeparationTest.php` — Layer dependency rules
- `Models/ModelStandardsTest.php` — UUIDs, traits, no side effects
- `Actions/ActionStandardsTest.php` — Stateless, execute method
- `Controllers/ControllerStandardsTest.php` — Thin controllers
- `Repositories/`, `Events/`, `Listeners/`, `Services/` — standards for supplementary layers
- `Requests/RequestStandardsTest.php` — FormRequest validation
- `Services/ServiceStandardsTest.php` — Infrastructure only

### Quality Tests
`tests/Quality/` covers code stability, performance, and security.

### CI/CD Pipeline
- GitHub Actions workflow: `.github/workflows/ci.yml`
- Jobs: quality, architecture, tests, security
- All jobs must pass before merging

See `docs/infrastructure.md` for detailed CI/CD configuration and tooling setup.

## 12. Developer Quick Reference

| Change Type | Where to put it | Pattern |
|-------------|----------------|---------|
| New use case / workflow | `app/Actions/{Domain}/` | `XxxAction` with `execute()` method |
| New business rule | `app/Models/{Model}.php` | Method on the relevant model |
| New HTTP endpoint | `app/Http/Controllers/` (API) or `app/Livewire/` (Web) | Thin → delegate to Action |
| New validation | `app/Http/Requests/` | FormRequest class |
| New database table | `database/migrations/` | UUID primary key, `foreignUuid()` |
| New status/enum | `app/Enums/` | Backed enum, add to relevant model |
| New data transfer | `app/Data/` | DTO class with typed properties |
| New authorization | `app/Policies/` | Policy class + register in `AuthServiceProvider` |
| New scheduled job | `routes/console.php` or `app/Console/Commands/` | Console command or closure schedule |
| New notification | `app/Actions/Notification/SendNotificationAction` | In-app notification |
| New audit entry | `app/Actions/Audit/LogAuditAction` | Call from Action after state change |
| Complex query reuse | `app/Repositories/{Domain}/` | Only when used by 2+ Actions |
| Multiple side effects | `app/Events/` + `app/Listeners/` | Only when 2+ things happen after event |

**Layer call chain**: `Controller/Livewire` → `Action` → `Model` (business rules) → `Repository` (optional, read-only)

**Never**: Put business logic in Controllers, call external services from Models, or bypass FormRequest validation.
