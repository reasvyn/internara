# Registration — Internship Registration, Verification & Document Verification

> **Last updated:** 2026-07-22 **Changes:** feat — split from enrollment.md; registration,
> verification, documents

## Description

Specification of the Internara Enrollment module's registration initiative: student registration
into internship programs, registration availability checking, admin verification with placement
assignment, registration document submission/verification, and the registration status lifecycle.
Placement management and guest account applications are separate initiatives — see
[placement.md](placement.md) and [account-application.md](account-application.md).

---

## 1. Problem Statements

### PS-1 — Registration Workflow Orchestration

Students need a guided, multi-step process to register for an internship program — selecting
a program, choosing or proposing a placement company, and uploading required documents. Without
a structured wizard, students may submit incomplete registrations, skip required steps, or
register for programs they are ineligible for. The system must enforce ordering constraints
(internship selected before placement, placement assigned before activation) and prevent
duplicate registrations for the same student-program pair.

### PS-2 — Registration Document Verification

Internship programs require specific documents from students (NIDN, vaccination certificate,
agreement letters, etc.). Without a structured submission and verification workflow, students
may not know which documents are required, administrators have no visibility into compliance
status, and document tracking relies on ad-hoc email or spreadsheet systems.

### PS-3 — Registration Status as Raw Strings

The Registration model uses raw string comparisons (`'pending'`, `'active'`) for status
instead of a backed enum. This means no compile-time type safety, no transition guard
methods, no `label()` for display, and inconsistent comparison logic scattered across
entities and actions. The `@todo` annotations in `RegistrationState` confirm this is known
technical debt.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Provide a guided 2-step registration wizard: select internship → select/propose placement |
| G2  | Track per-registration document compliance with upload and admin verification status |
| G3  | Prevent duplicate registrations (same student + internship)         |
| G4  | Provide admin dashboard for pending registrations with available placement slots and mentor assignment |
| G5  | Gate registration availability on configurable registration period settings (start/end dates) |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Placement capacity management (see [placement.md](placement.md)) |
| NG2  | Guest-to-student account application (see [account-application.md](account-application.md)) |
| NG3  | Multi-tenant placement management (single-tenant only)           |
| NG4  | Automated placement matching algorithms (manual selection)       |
| NG5  | Bulk/batch registration import from external government systems  |

---

## 3. User Stories / Use Cases

### UC-1 — Student Registers for an Internship Program

**Actor:** Student (authenticated, role: student)
**Preconditions:** Student is logged in; registration period is open; student has no existing active/pending registration for the target internship
**Flow:**
1. Student navigates to `/registration` (RegistrationCenter)
2. System displays open internships via `ReadRegistrationAvailabilityAction`
3. Student clicks register, redirected to `/register` (RegistrationWizard)
4. Step 1: Student selects an internship program
5. Step 2: Student selects an existing placement (with available slots) or proposes a new company (name + address)
6. `RegisterInternshipAction` validates: no duplicate active/pending registration for this student+internship
7. Registration created with status `pending`, `proposed_company_details` stored as JSON if applicable
8. `StudentRegistered` event dispatched, dashboard cache cleared
9. Student uploads required documents via `/registration/documents` (RegistrationDocumentUpload)
**Postconditions:** Registration in `pending` state; documents uploaded; awaiting admin placement verification

### UC-2 — Admin Verifies Registration and Assigns Placement

**Actor:** Admin (role: super_admin or admin)
**Preconditions:** Pending registrations exist; placement slots are available
**Flow:**
1. Admin navigates to `/admin/internships/registrations/pending` (RegistrationVerification)
2. System displays pending registrations with student info, proposed company details, and available placements
3. Admin selects a placement for the student (from available slots, or creates new)
4. `VerifyRegistrationAction` guards: registration must be pending, placement must have available slots
5. Action atomically: assigns placement_id, sets start_date/end_date, transitions status to `active`, increments `filled_quota` on placement
6. Admin may also assign mentors during verification
**Postconditions:** Registration is `active`; placement quota incremented; student gains access to journals, assignments, assessments

---

## 4. Functional Requirements

### Registration — Availability & Lifecycle

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-R1 | `ReadRegistrationAvailabilityAction` must return status: `not_configured`, `open`, `upcoming`, or `closed` based on `registration_period_start` and `registration_period_end` settings |
| FR-R2 | Registration must be `open` only when current date falls within the configured registration period |
| FR-R3 | `RegistrationState` must provide `isActive()`, `isPending()`, `isCurrentlyOngoing()`, `hasEnded()`, `canBeApproved()` methods |
| FR-R4 | `canBeApproved()` must require both `isPending()` and `hasPlacement === true`        |
| FR-R5 | `RegistrationState` must provide `daysRemaining()` and `totalDuration()` date calculations |
| FR-R6 | `RegistrationState` must support `phases[]` with `currentPhaseIndex()` and `currentPhase()` based on elapsed percentage of total duration |

### Registration — Creation & Constraints

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-R7 | `RegisterInternshipAction` must guard against duplicate registration (same student_id + internship_id where status is `active` or `pending`) |
| FR-R8 | Registration model must enforce unique constraint on `(student_id, internship_id)` at database level |
| FR-R9 | Registration model must use UUID primary key and cascade delete on `student_id` → `users` and `internship_id` → `internships` |
| FR-R10 | `placement_id` foreign key must be nullable with `set null` on delete                 |
| FR-R11 | `proposed_company_details` must be stored as JSON column for student-proposed companies |
| FR-R12 | Registration status must use raw strings `'pending'` and `'active'` (enum conversion deferred — see DD-1) |
| FR-R13 | `RegisterInternshipAction` must dispatch `StudentRegistered` event on successful creation |
| FR-R14 | `StudentRegistered` event listener must clear dashboard cache                         |

### Registration — Verification & Activation

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-R15 | `VerifyRegistrationAction` must guard: registration status must be `pending`          |
| FR-R16 | `VerifyRegistrationAction` must guard: target placement must have available slots (`PlacementCapacity::hasAvailableSlots()`) |
| FR-R17 | `VerifyRegistrationAction` must atomically: assign `placement_id`, set `start_date`, set `end_date`, set status to `active`, increment placement `filled_quota` |
| FR-R18 | `RegistrationPolicy` must allow student to create/update own pending registrations    |
| FR-R19 | `RegistrationPolicy` must allow admin to verify/approve registrations                |

### Registration — Documents

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-D1 | `RegistrationDocument` model must track document submissions linked to a registration |
| FR-D2 | Document status must use `RegistrationDocumentStatus` enum: `PENDING`, `VERIFIED`, `REJECTED` |
| FR-D3 | `RegistrationDocumentStatus` must implement `LabelEnum` and `StatusEnum` contracts    |
| FR-D4 | `UploadRegistrationDocumentAction` must handle file upload per required document type |
| FR-D5 | `RegistrationDocumentUpload` Livewire component must display required document IDs and current upload status |
| FR-D6 | `RegistrationDocumentPolicy` must govern upload and verification permissions          |
| FR-D7 | Document verification transitions: `PENDING` → [`VERIFIED`, `REJECTED`]; terminal states: `VERIFIED`, `REJECTED` |

### Registration — Livewire Components & Routing

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-L1 | `RegistrationCenter` must display open internships at `/registration` with `auth` middleware |
| FR-L2 | `RegistrationWizard` must implement 2-step flow at `/register` with `auth` middleware |
| FR-L3 | `RegistrationVerification` must display pending registrations at `/admin/internships/registrations/pending` with `role:super_admin\|admin` middleware |
| FR-L4 | `RegistrationDocumentUpload` must be at `/registration/documents` with `auth` middleware |

### Registration — DTO

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-DTO1 | `RegistrationData` must extend `BaseData` with required `internshipId`              |
| FR-DTO2 | `RegistrationData` must accept nullable: `placementId`, `academicYear`, `startDate`, `endDate`, `proposedCompanyName`, `proposedCompanyAddress` |
| FR-DTO3 | `RegistrationWizardForm` must use Laravel Form Object for validation                 |

---

## 5. Non-Functional Requirements

| ID    | Requirement                                                                          |
| ----- | ------------------------------------------------------------------------------------ |
| NFR-P1 | Registration wizard must complete in < 500ms (network-excluded) for submission step  |
| NFR-P3 | `RegistrationVerification` page must load pending registrations in < 1s for up to 500 records |
| NFR-R1 | Registration creation must be wrapped in a database transaction                       |
| NFR-U1 | Registration wizard must clearly show which step the student is on (1 of 2, 2 of 2)  |
| NFR-M1 | All enrollment Actions must extend appropriate base classes (BaseCommandAction, BaseReadAction) |
| NFR-M2 | Registration status must be migrated to a backed enum before production launch (see DD-1 @todo) |
| NFR-A1 | All enrollment UI (wizard, verification) must meet WCAG 2.1 Level AA                 |
| NFR-A2 | Registration wizard step indicators must be keyboard-accessible and announced to screen readers |
| NFR-A3 | Form inputs in wizard must have associated labels                                    |
| NFR-A4 | Dynamic content updates (step transitions, validation errors) must use `aria-live` regions |
| NFR-A5 | Color contrast must meet 4.5:1 minimum for all enrollment UI text                   |
| NFR-L1 | All user-facing strings in enrollment UI must use `__()` translation helper          |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files          |
| NFR-L3 | Registration status labels must use `LabelEnum::label()` (calls `__()` internally)   |

---

## 6. API / Data Contracts

### 6.1 Registration Model

```php
// app/Enrollment/Registration/Models/Registration.php
// Table: registrations
// PK: id (uuid, cascade)
// FK: student_id → users (cascade delete)
// FK: internship_id → internships (cascade delete)
// FK: placement_id → placements (nullable, set null on delete)
// Fillable: student_id, internship_id, placement_id, start_date, end_date, status, proposed_company_details (json)
// Unique: (student_id, internship_id)
// Status: raw strings 'pending' | 'active' (@todo: backed enum)
```

### 6.2 RegistrationState Entity

```php
// app/Enrollment/Registration/Entities/RegistrationState.php
final readonly class RegistrationState extends BaseEntity
{
    public static function fromModel(Model $model): static;

    public function isActive(): bool;       // status === 'active'
    public function isPending(): bool;      // status === 'pending'
    public function isCurrentlyOngoing(?Carbon $today = null): bool;
    public function hasEnded(?Carbon $today = null): bool;
    public function canBeApproved(): bool;  // isPending() && hasPlacement
    public function daysRemaining(?Carbon $today = null): int;
    public function totalDuration(): int;
    public function withPhases(array $phases): static;
    public function phases(): array;
    public function currentPhaseIndex(?Carbon $now = null): ?int;
    public function currentPhase(?Carbon $now = null): ?string;
}
```

### 6.3 RegistrationData DTO

```php
// app/Enrollment/Registration/Data/RegistrationData.php
final readonly class RegistrationData extends BaseData
{
    public function __construct(
        public string $internshipId,              // required
        public ?string $placementId = null,
        public ?string $academicYear = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $proposedCompanyName = null,
        public ?string $proposedCompanyAddress = null,
    ) {}
}
```

### 6.4 Registration Actions

```php
// app/Enrollment/Registration/Actions/ReadRegistrationAvailabilityAction.php
final class ReadRegistrationAvailabilityAction extends BaseReadAction
{
    public function execute(): array;
    // Returns: ['status' => 'not_configured'|'open'|'upcoming'|'closed']
}

// app/Enrollment/Registration/Actions/RegisterInternshipAction.php
final class RegisterInternshipAction extends BaseCommandAction
{
    public function execute(RegistrationData $data, User $student): Registration;
    // Guards: no duplicate active/pending registration for student+internship
    // Creates: Registration with status 'pending', stores proposed_company_details as JSON
    // Dispatches: StudentRegistered event
}

// app/Enrollment/Registration/Actions/VerifyRegistrationAction.php
final class VerifyRegistrationAction extends BaseCommandAction
{
    public function execute(Registration $registration, Placement $placement, array $mentors = []): Registration;
    // Guards: must be pending, placement must have available slots
    // Atomic: assign placement_id, set dates, set status 'active', increment filled_quota
}

// app/Enrollment/Registration/Actions/UploadRegistrationDocumentAction.php
final class UploadRegistrationDocumentAction extends BaseCommandAction
{
    public function execute(Registration $registration, string $requiredDocumentId, UploadedFile $file): RegistrationDocument;
}
```

### 6.5 RegistrationDocumentStatus Enum

```php
// app/Enrollment/Registration/Enums/RegistrationDocumentStatus.php
enum RegistrationDocumentStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    // Transitions: PENDING → [VERIFIED, REJECTED]
    // Terminal: VERIFIED, REJECTED
}
```

### 6.6 Events

```php
// app/Enrollment/Registration/Events/StudentRegistered.php
// Dispatched by: RegisterInternshipAction
// Listener: ClearDashboardOnRegistration (clears dashboard cache)
```

### 6.7 Routes

```php
// routes/web/enrollment.php (registration portion)

// Authenticated
Route::middleware('auth')->group(function () {
    Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
    Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
    Route::livewire('/registration/documents', RegistrationDocumentUpload::class)->name('registration.documents');
});

// Admin
Route::prefix('admin')->name('enrollment.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
});
```

### 6.8 Database Migrations

| Migration | Table |
| --------- | ----- |
| `2026_01_04_000003_create_registrations_table.php` | `registrations` |
| `2026_01_05_000002_create_registration_documents_table.php` | `registration_documents` |

---

## 7. Design Decisions

### DD-1 — Registration Status as Raw Strings with @todo Enum Migration

**Decision:** Registration status uses raw string comparisons (`'pending'`, `'active'`) instead
of a backed enum. A `@todo` annotation marks the future migration to a `RegistrationStatus` enum.
**Rationale:** The Registration model was implemented before the `StatusEnum` contract pattern was
established (used by `PlacementChangeStatus`, `AccountApplicationStatus`, `RegistrationDocumentStatus`).
Retrofitting the enum requires updating all string comparisons in `RegistrationState`, actions, and
Livewire components simultaneously.
**Trade-off:** No compile-time type safety, no transition guard methods, no `label()` for display.
Mitigated by consistent string usage and `RegistrationState` encapsulating comparison logic.
**Future:** Migrate to `RegistrationStatus` enum implementing `LabelEnum` + `StatusEnum` contracts.

### DD-2 — Registration Availability via System Settings

**Decision:** Registration window is controlled by `registration_period_start` and
`registration_period_end` system settings, checked by `ReadRegistrationAvailabilityAction`.
**Rationale:** Settings are admin-configurable without code changes. The action returns
semantic statuses (`not_configured`, `open`, `upcoming`, `closed`) that the UI uses to
display appropriate messaging.
**Trade-off:** Settings must be correctly configured by admin. Mitigated by the
`not_configured` status providing clear feedback when settings are missing.

### DD-3 — RegistrationDocument as Separate Model

**Decision:** Document submissions use a dedicated `RegistrationDocument` model with
`RegistrationDocumentStatus` enum, separate from the Registration model itself.
**Rationale:** A registration may require multiple documents, each with independent
verification status. Embedding documents in the Registration model would create a
complex nested structure. Separate model allows independent queries.
**Trade-off:** Additional model and query complexity. Mitigated by the clear 1-to-many
relationship (Registration has many RegistrationDocuments).

---

## 8. Success Metrics

### 8.1 Registration Completeness

| Metric                          | Target      | Measurement                                       |
| ------------------------------- | ----------- | ------------------------------------------------- |
| Duplicate registration prevention | 0 duplicates (same student + internship) | Unique constraint + `RegisterInternshipAction` guard |
| Registration availability accuracy | Correct status 100% of the time | `ReadRegistrationAvailabilityAction` against settings |
| Step completion rate            | > 95% complete 2-step wizard | `RegistrationWizard` Livewire analytics           |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [internship-lifecycle.md](internship-lifecycle.md) | Program entities — registration enrolls students into programs |
| [internship-groups.md](internship-groups.md) | Group entities — enrolled students are assigned to groups |

### Build Guide
After implementing this spec, students can register for internship programs and be assigned to groups. Registration creates enrollment records with status tracking (pending, approved, rejected). The next step is to build placement, which matches enrolled students to companies via partnerships.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [placement.md](placement.md) | Placement reads enrollment records from this spec and matches with companies from #20 partnership-management |
| 2 | [account-application.md](account-application.md) | New students self-register through this flow before reaching registration |

## Quick References

- `app/Enrollment/Registration/Models/Registration.php` — Registration model (UUID PK, status strings)
- `app/Enrollment/Registration/Entities/RegistrationState.php` — Registration state entity with lifecycle methods
- `app/Enrollment/Registration/Data/RegistrationData.php` — Registration DTO
- `app/Enrollment/Registration/Actions/ReadRegistrationAvailabilityAction.php` — Registration window status
- `app/Enrollment/Registration/Actions/RegisterInternshipAction.php` — Registration creation with duplicate guard
- `app/Enrollment/Registration/Actions/VerifyRegistrationAction.php` — Admin verification with atomic placement assignment
- `app/Enrollment/Registration/Actions/UploadRegistrationDocumentAction.php` — Document upload handler
- `app/Enrollment/Registration/Enums/RegistrationDocumentStatus.php` — Document status enum (PENDING/VERIFIED/REJECTED)
- `app/Enrollment/Registration/Models/RegistrationDocument.php` — Document submission model
- `app/Enrollment/Registration/Policies/RegistrationPolicy.php` — Registration authorization
- `app/Enrollment/Registration/Policies/RegistrationDocumentPolicy.php` — Document authorization
- `app/Enrollment/Registration/Events/StudentRegistered.php` — Registration creation event
- `app/Enrollment/Registration/Listeners/ClearDashboardOnRegistration.php` — Dashboard cache invalidation
- `app/Enrollment/Registration/Livewire/RegistrationCenter.php` — Open internships display
- `app/Enrollment/Registration/Livewire/RegistrationWizard.php` — 2-step registration wizard
- `app/Enrollment/Registration/Livewire/RegistrationVerification.php` — Admin pending registration review
- `app/Enrollment/Registration/Livewire/RegistrationDocumentUpload.php` — Student document upload
- `app/Enrollment/Registration/Livewire/Forms/RegistrationWizardForm.php` — Wizard form validation
- `routes/web/enrollment.php` — All enrollment route definitions
- `docs/modules/enrollment.md` — Enrollment module overview
- **Related specs:** [placement.md](placement.md) — Placement CRUD & capacity management
- **Related specs:** [account-application.md](account-application.md) — Guest-to-student account pipeline
