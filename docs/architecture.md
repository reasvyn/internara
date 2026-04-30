# Architecture Design: Internara (Enhanced Action-Oriented MVC)

## 1. Introduction
This document defines the architectural standards for the `internara` project following its transformation from a modular monolith to a modern Laravel MVC architecture. This design prioritizes the separation of **Business Rules (Stateful)** and **Application Logic (Stateless)** to achieve 3S quality standards (Secure, Sustain, Scalable).

## 2. The 3S Doctrine Alignment

### S1 - Secure (Security of Code, System, and Data)
- **Input Validation**: Validation is performed at the outermost layer (Form Requests) before entering any business logic.
- **Explicit Failure**: Custom Exceptions are used to handle business logic failures explicitly without leaking internal system details.
- **Protected Rules**: Business rules are centralized within Eloquent Models, ensuring they cannot be bypassed by direct database access in Controllers.
- **Authorization**: Policies enforce authorization at the HTTP layer with additional checks in Actions.

### S2 - Sustain (Sustainability)
- **Clarity & Project Language**: Action and Model naming follows business terminology (e.g., `ClockInAction` instead of `SaveAttendance`).
- **Single Responsibility**: Each Action has a single `execute()` method representing one specific Use Case.
- **Maintainability**: Removes the overhead of module management (`nwidart/laravel-modules`) to accelerate development and simplify onboarding.
- **Bounded Complexity**: Optional layers (Repositories, Events) are used only when they provide measurable value.

### S3 - Scalable (Enterprise Scalability)
- **Stateless Actions**: Application logic is stateless, allowing for reusability across different entry points (Web, API, CLI).
- **Domain-Driven Grouping**: `Actions/`, `Models/`, and optional `Repositories/` folders are grouped by business domain.
- **Event-Driven Side Effects**: Decouples notifications, emails, and audit logging from core business logic.

## 3. Enhanced Layered Architecture

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

### E. Repository Layer (Optional - Complex Queries Only)
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

### F. Event/Listener Layer (Side Effects)
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
- **Current**: `SetupService` (installation wizard orchestration, token management, lock file guard), `InstallationAuditor` (pre-flight system checks).
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
        Route::get('/internships', InternshipBrowser::class);
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

## 7. Legacy Modules (Reference Only)

> **Status**: The `modules/` directory contains legacy code from the pre-MVC modular monolith (29 modules, ~1,142 PHP files). These modules are **disabled from autoloading** and are retained solely as reference material during the ongoing MVC migration. Module scaffolding for 8 new domains (Report, Handbook, Schedule, AcademicYear, AccountLifecycle, ActivityFeed, MentorEvaluation, TeacherDashboard) has been created in `app/` to support incremental migration.

## 8. Anti-Patterns to Avoid

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

## 9. Documentation & Sync
Every change to this architecture must be recorded in **Decision Records** according to the `AGENTS.md` standards. The code must always remain in sync with this documentation.

When adding lifecycle layers (Repositories, Events, etc.), create a Decision Record explaining:
- What problem the layer solves
- Why simpler approaches weren't sufficient
- Which 3S dimension it serves (usually S3 - Scalable)

## 9. Infrastructure Support
The architecture is enforced by automated testing in `tests/Arch/`:

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
- All jobs must pass before merging to `main`/`develop`

See `docs/infrastructure.md` for detailed CI/CD configuration and tooling setup.

## 10. AI Quick Reference

> For AI agents: Use this section to quickly locate where to implement changes.

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
