# Enrollment — Registration, Placement, Account Application & Change Requests

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering registration workflow,
> placement capacity management, placement change requests, guest-to-student account application
> pipeline, and document verification

## Description

Complete specification of the Internara Enrollment module: student registration into internship
programs, company placement slot management, mid-program placement change workflows, guest account
application with atomic user provisioning, and registration document submission/verification.
Defines the full lifecycle from guest intent through active enrollment with placement.

---

## 1. Problem Statements

### PS-1 — Registration Workflow Orchestration

Students need a guided, multi-step process to register for an internship program — selecting
a program, choosing or proposing a placement company, and uploading required documents. Without
a structured wizard, students may submit incomplete registrations, skip required steps, or
register for programs they are ineligible for. The system must enforce ordering constraints
(internship selected before placement, placement assigned before activation) and prevent
duplicate registrations for the same student-program pair.

### PS-2 — Placement Capacity Atomicity

Each company placement has a finite quota. When multiple students register simultaneously or
an admin performs direct placement at the same time a student self-registers, the system must
prevent overbooking. A naive check-then-act pattern (`if slots > 0 then increment`) leaves a
race window where two concurrent requests both see available slots and both succeed, exceeding
the quota. Capacity enforcement must be atomic within a single database transaction.

### PS-3 — Mid-Program Placement Change Requests

Students may need to change their assigned company during an internship due to workplace
conflicts, relocation, or supervisor issues. Without a formal request workflow, placement
changes are ad-hoc and error-prone — quota bookkeeping gets out of sync, old placements are
not freed, and there is no audit trail. A structured request → review → approve/reject
workflow with atomic quota transfer is required.

### PS-4 — Guest-to-Student Account Application Pipeline

Prospective students who are not yet system users need a way to express intent to participate
in an internship program. The school admin must review each application, and upon approval,
the system must atomically create a User account, Profile, and Registration in a single
transaction. Manual provisioning is error-prone (forgot to create Profile, Registration
left in wrong status) and does not scale.

### PS-5 — Registration Document Verification

Internship programs require specific documents from students (NIDN, vaccination certificate,
agreement letters, etc.). Without a structured submission and verification workflow, students
may not know which documents are required, administrators have no visibility into compliance
status, and document tracking relies on ad-hoc email or spreadsheet systems.

### PS-6 — Registration Status as Raw Strings

The Registration model uses raw string comparisons (`'pending'`, `'active'`) for status
instead of a backed enum. This means no compile-time type safety, no transition guard
methods, no `label()` for display, and inconsistent comparison logic scattered across
entities and actions. The `@todo` annotations in `RegistrationState` confirm this is known
technical debt.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a guided 2-step registration wizard: select internship → select/propose placement |
| G2  | Enforce placement capacity atomically within a single transaction (no overbooking) |
| G3  | Support a structured placement change request → admin review → approve/reject workflow |
| G4  | Atomically provision User + Profile + Registration on guest account application approval |
| G5  | Track per-registration document compliance with upload and admin verification status |
| G6  | Prevent duplicate registrations (same student + internship) and duplicate pending applications (same email) |
| G7  | Provide admin dashboard for pending registrations with available placement slots and mentor assignment |
| G8  | Gate registration availability on configurable registration period settings (start/end dates) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-tenant placement management (single-tenant only) |
| NG2  | Automated placement matching algorithms (manual selection by student/admin) |
| NG3  | Real-time placement slot notifications (WebSocket push when slots fill) |
| NG4  | Bulk/batch registration import from external government systems |
| NG5  | Student self-service placement swap (peer-to-peer without admin involvement) |

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

### UC-3 — Student Requests Placement Change

**Actor:** Student (role: student)
**Preconditions:** Student has an active registration with a placement assigned; no existing pending change request for this registration
**Flow:**
1. Student navigates to `/student/internships/placement-change` (StudentPlacementChangeRequest)
2. System shows current placement and available placements within the same internship (excluding current)
3. Student selects target placement and provides a reason
4. `RequestPlacementChangeAction` guards: no existing pending request for this registration
5. `PlacementChangeRequest` created with status `PENDING`
6. Admin reviews at `/admin/internships/placements/changes` (PlacementChangeManager)
7. Admin approves: `ApprovePlacementChangeAction` atomically decrements old placement `filled_quota`, increments new placement `filled_quota`, updates registration's `placement_id`
8. Or admin rejects: `RejectPlacementChangeAction` records rejection reason
**Postconditions:** Either placement swapped atomically (quota transferred) or request rejected with reason

### UC-4 — Guest Applies for Student Account

**Actor:** Guest (unauthenticated)
**Preconditions:** At least one published, active internship exists with non-full placements
**Flow:**
1. Guest navigates to `/apply` (ApplyPage, `guest` middleware)
2. System displays available internships and non-full placements
3. Guest fills application form: name, email, student_id_number, department, selected internship/placement, form_data
4. `ApplyAccountAction` guards: no duplicate pending/approved application by email; if previously rejected, re-activates the application
5. `AccountApplication` created with status `PENDING`
6. Admin reviews application
7. On approval: `ApproveAccountApplicationAction` atomically creates User (random password, `setup_required`, student role) + Profile + Registration (status `active`)
8. Account activation notification dispatched
**Postconditions:** User account created; Profile created; Registration active; applicant receives activation email

### UC-5 — Admin Approves Guest Account Application

**Actor:** Admin (role: super_admin or admin)
**Preconditions:** Pending account applications exist
**Flow:**
1. Admin reviews pending applications in admin panel
2. Admin verifies application details (student ID, department, proposed placement)
3. `ApproveAccountApplicationAction` executes within a single database transaction:
   a. Marks `AccountApplication` status as `APPROVED`
   b. Creates `User` with random password, `setup_required` flag, `student` role
   c. Creates `Profile` for the new user
   d. Creates `Registration` with status `active` linked to the selected internship/placement
   e. Dispatches `AccountApplicationApproved` event
4. Or admin rejects: `RejectAccountApplicationAction` records rejection reason
**Postconditions:** Full user provisioning complete in single transaction; activation email sent

---

## 4. Functional Requirements

### Registration — Availability & Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-R1 | `ReadRegistrationAvailabilityAction` must return status: `not_configured`, `open`, `upcoming`, or `closed` based on `registration_period_start` and `registration_period_end` settings |
| FR-R2 | Registration must be `open` only when current date falls within the configured registration period |
| FR-R3 | `RegistrationState` must provide `isActive()`, `isPending()`, `isCurrentlyOngoing()`, `hasEnded()`, `canBeApproved()` methods |
| FR-R4 | `canBeApproved()` must require both `isPending()` and `hasPlacement === true` |
| FR-R5 | `RegistrationState` must provide `daysRemaining()` and `totalDuration()` date calculations |
| FR-R6 | `RegistrationState` must support `phases[]` with `currentPhaseIndex()` and `currentPhase()` based on elapsed percentage of total duration |

### Registration — Creation & Constraints

| ID   | Requirement |
| ---- | ----------- |
| FR-R7 | `RegisterInternshipAction` must guard against duplicate registration (same student_id + internship_id where status is `active` or `pending`) |
| FR-R8 | Registration model must enforce unique constraint on `(student_id, internship_id)` at database level |
| FR-R9 | Registration model must use UUID primary key and cascade delete on `student_id` → `users` and `internship_id` → `internships` |
| FR-R10 | `placement_id` foreign key must be nullable with `set null` on delete |
| FR-R11 | `proposed_company_details` must be stored as JSON column for student-proposed companies |
| FR-R12 | Registration status must use raw strings `'pending'` and `'active'` (enum conversion deferred — see DD-1) |
| FR-R13 | `RegisterInternshipAction` must dispatch `StudentRegistered` event on successful creation |
| FR-R14 | `StudentRegistered` event listener must clear dashboard cache |

### Registration — Verification & Activation

| ID   | Requirement |
| ---- | ----------- |
| FR-R15 | `VerifyRegistrationAction` must guard: registration status must be `pending` |
| FR-R16 | `VerifyRegistrationAction` must guard: target placement must have available slots (`PlacementCapacity::hasAvailableSlots()`) |
| FR-R17 | `VerifyRegistrationAction` must atomically: assign `placement_id`, set `start_date`, set `end_date`, set status to `active`, increment placement `filled_quota` |
| FR-R18 | `RegistrationPolicy` must allow student to create/update own pending registrations |
| FR-R19 | `RegistrationPolicy` must allow admin to verify/approve registrations |

### Registration — Documents

| ID   | Requirement |
| ---- | ----------- |
| FR-D1 | `RegistrationDocument` model must track document submissions linked to a registration |
| FR-D2 | Document status must use `RegistrationDocumentStatus` enum: `PENDING`, `VERIFIED`, `REJECTED` |
| FR-D3 | `RegistrationDocumentStatus` must implement `LabelEnum` and `StatusEnum` contracts |
| FR-D4 | `UploadRegistrationDocumentAction` must handle file upload per required document type |
| FR-D5 | `RegistrationDocumentUpload` Livewire component must display required document IDs and current upload status |
| FR-D6 | `RegistrationDocumentPolicy` must govern upload and verification permissions |
| FR-D7 | Document verification transitions: `PENDING` → [`VERIFIED`, `REJECTED`]; terminal states: `VERIFIED`, `REJECTED` |

### Placement — CRUD & Capacity

| ID   | Requirement |
| ---- | ----------- |
| FR-P1 | Placement model must use UUID primary key with cascade delete on `company_id` → `companies` and `internship_id` → `internships` |
| FR-P2 | Placement must enforce unique constraint on `(company_id, internship_id)` |
| FR-P3 | Placement `quota` must default to 1; `filled_quota` must default to 0 |
| FR-P4 | `PlacementCapacity` entity must provide `isFull()`, `availableSlots()`, `hasAvailableSlots()` |
| FR-P5 | `PlacementState` entity must provide `registrationCount` and `canBeDeleted()` (only when `registrationCount === 0`) |
| FR-P6 | `DeletePlacementAction` must block deletion when registrations exist for the placement |
| FR-P7 | `CreatePlacementAction` and `UpdatePlacementAction` must handle standard CRUD with validation |
| FR-P8 | `PlacementIndex` Livewire component must display stats: total placements, total quota, filled slots, available slots |
| FR-P9 | `PlacementIndex` must support search and filter by company and internship |

### Placement — Direct Placement

| ID   | Requirement |
| ---- | ----------- |
| FR-P10 | `DirectPlacementAction` must atomically create registration + placement in a single transaction |
| FR-P11 | `DirectPlacementAction` must guard: target placement must have available slots |
| FR-P12 | `DirectPlacementManager` Livewire component must provide admin form: select student, select placement, assign mentors |

### Placement Change Requests — Workflow

| ID   | Requirement |
| ---- | ----------- |
| FR-C1 | `PlacementChangeStatus` enum must implement `LabelEnum` and `StatusEnum` contracts |
| FR-C2 | Valid transitions: `PENDING` → [`APPROVED`, `REJECTED`]; `APPROVED` and `REJECTED` are terminal |
| FR-C3 | `RequestPlacementChangeAction` must guard: no existing `PENDING` request for this registration |
| FR-C4 | `ApprovePlacementChangeAction` must guard: request is not terminal, target placement has available slots |
| FR-C5 | `ApprovePlacementChangeAction` must atomically: decrement old placement `filled_quota`, increment new placement `filled_quota`, update registration `placement_id` |
| FR-C6 | `RejectPlacementChangeAction` must record `rejection_reason` and transition to `REJECTED` |
| FR-C7 | `PlacementChangeManager` Livewire component must display pending requests for admin review |
| FR-C8 | `StudentPlacementChangeRequest` must display available placements within the same internship, excluding current placement |
| FR-C9 | `PlacementChangeRequestPolicy` must govern request creation and review permissions |

### Account Application — Guest Pipeline

| ID   | Requirement |
| ---- | ----------- |
| FR-A1 | `AccountApplicationStatus` enum must implement `LabelEnum` and `StatusEnum` contracts |
| FR-A2 | Valid transitions: `PENDING` → [`APPROVED`, `REJECTED`]; `APPROVED` and `REJECTED` are terminal |
| FR-A3 | `ApplyAccountAction` must guard: no duplicate `PENDING` or `APPROVED` application by email |
| FR-A4 | `ApplyAccountAction` must re-activate previously `REJECTED` applications on re-apply (set back to `PENDING`) |
| FR-A5 | `ApproveAccountApplicationAction` must execute within a single DB transaction: mark approved → create User (random password, `setup_required`, student role) → create Profile → create Registration (status `active`) |
| FR-A6 | `RejectAccountApplicationAction` must record `rejection_reason` and transition to `REJECTED` |
| FR-A7 | `ApplyPage` Livewire component must be accessible via `guest` middleware |
| FR-A8 | `ApplyPage` must display published/active internships and non-full placements |
| FR-A9 | `AccountApplication` model must store `form_data` as JSON column for flexible form fields |
| FR-A10 | `AccountApplicationPolicy` must govern guest submission and admin review permissions |

### Registration — Livewire Components & Routing

| ID   | Requirement |
| ---- | ----------- |
| FR-L1 | `RegistrationCenter` must display open internships at `/registration` with `auth` middleware |
| FR-L2 | `RegistrationWizard` must implement 2-step flow at `/register` with `auth` middleware |
| FR-L3 | `RegistrationVerification` must display pending registrations at `/admin/internships/registrations/pending` with `role:super_admin\|admin` middleware |
| FR-L4 | `PlacementIndex` must provide full CRUD at `/admin/internships/placements` with admin middleware |
| FR-L5 | `DirectPlacementManager` must be at `/admin/internships/placements/direct` with admin middleware |
| FR-L6 | `PlacementChangeManager` must be at `/admin/internships/placements/changes` with admin middleware |
| FR-L7 | `StudentPlacementChangeRequest` must be at `/student/internships/placement-change` with `role:student` middleware |
| FR-L8 | `ApplyPage` must be at `/apply` with `guest` middleware |
| FR-L9 | `RegistrationDocumentUpload` must be at `/registration/documents` with `auth` middleware |

### Registration — DTO

| ID   | Requirement |
| ---- | ----------- |
| FR-DTO1 | `RegistrationData` must extend `BaseData` with required `internshipId` |
| FR-DTO2 | `RegistrationData` must accept nullable: `placementId`, `academicYear`, `startDate`, `endDate`, `proposedCompanyName`, `proposedCompanyAddress` |
| FR-DTO3 | All form objects must use Laravel Form Objects for validation (RegistrationWizardForm, PlacementForm, DirectPlacementForm, AccountApplicationForm, PlacementChangeForm) |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Registration wizard must complete in < 500ms (network-excluded) for submission step |
| NFR-P2 | Placement capacity check and increment must execute within a single DB transaction (< 100ms) |
| NFR-P3 | `RegistrationVerification` page must load pending registrations in < 1s for up to 500 records |
| NFR-S1 | Guest application endpoint (`/apply`) must be rate-limited to prevent spam submissions |
| NFR-S2 | File uploads in `RegistrationDocumentUpload` must validate file type, size, and scan for malicious content |
| NFR-S3 | `AccountApplication` `form_data` JSON must be sanitized before storage (prevent XSS via stored JSON) |
| NFR-S4 | Account provisioning must use random passwords — never allow password choice during application |
| NFR-R1 | Guest-to-student provisioning must be atomic — if User creation fails, entire transaction rolls back (no orphaned Registration) |
| NFR-R2 | Placement quota must never go negative — `filled_quota` decrement must be guarded |
| NFR-R3 | Concurrent placement registrations must not exceed quota — atomic check-and-increment required |
| NFR-U1 | Registration wizard must clearly show which step the student is on (1 of 2, 2 of 2) |
| NFR-U2 | `PlacementChangeManager` must display request reason and both source/target placements for informed admin review |
| NFR-U3 | `ApplyPage` must work without JavaScript for basic form submission (progressive enhancement) |
| NFR-M1 | All enrollment Actions must extend appropriate base classes (BaseCommandAction, BaseReadAction) |
| NFR-M2 | Registration status must be migrated to a backed enum before production launch (see DD-1 @todo) |
| NFR-A1 | All enrollment UI (wizard, verification, placement management) must meet WCAG 2.1 Level AA |
| NFR-A2 | Registration wizard step indicators must be keyboard-accessible and announced to screen readers |
| NFR-A3 | Form inputs in wizard and placement forms must have associated labels |
| NFR-A4 | Dynamic content updates (step transitions, validation errors) must use `aria-live` regions |
| NFR-A5 | Color contrast must meet 4.5:1 minimum for all enrollment UI text |
| NFR-L1 | All user-facing strings in enrollment UI must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Registration status labels must use `LabelEnum::label()` (calls `__()` internally) |

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
    // Checks: config('enrollment.registration_period_start'), config('enrollment.registration_period_end')
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

### 6.5 Placement Model

```php
// app/Enrollment/Placement/Models/Placement.php
// Table: placements
// PK: id (uuid, cascade)
// FK: company_id → companies (cascade delete)
// FK: internship_id → internships (cascade delete)
// Fillable: company_id, internship_id, name, address, quota (default 1), filled_quota (default 0), description
// Unique: (company_id, internship_id)
```

### 6.6 Placement Entities

```php
// app/Enrollment/Placement/Entities/PlacementCapacity.php
final readonly class PlacementCapacity extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function isFull(): bool;           // filledQuota >= quota
    public function availableSlots(): int;    // max(0, quota - filledQuota)
    public function hasAvailableSlots(): bool; // availableSlots() > 0
}

// app/Enrollment/Placement/Entities/PlacementState.php
final readonly class PlacementState extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function canBeDeleted(): bool;     // registrationCount === 0
}
```

### 6.7 Placement Actions

```php
// app/Enrollment/Placement/Actions/CreatePlacementAction.php
// app/Enrollment/Placement/Actions/UpdatePlacementAction.php
// app/Enrollment/Placement/Actions/DeletePlacementAction.php
//   Guards: canBeDeleted() — blocks if registrations exist

// app/Enrollment/Placement/Actions/DirectPlacementAction.php
final class DirectPlacementAction extends BaseCommandAction
{
    public function execute(User $student, Placement $placement, array $mentors = []): Registration;
    // Atomic: create Registration + increment placement filled_quota
    // Guards: placement must have available slots
}
```

### 6.8 PlacementChangeRequest Model

```php
// app/Enrollment/Placement/Models/PlacementChangeRequest.php
// Table: placement_change_requests
// Fillable: registration_id, from_placement_id, to_placement_id, reason, requested_by, status, processed_by, processed_at, rejection_reason
```

### 6.9 PlacementChangeStatus Enum

```php
// app/Enrollment/Placement/Enums/PlacementChangeStatus.php
enum PlacementChangeStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    // Transitions: PENDING → [APPROVED, REJECTED]
    // Terminal: APPROVED, REJECTED
}
```

### 6.10 Placement Change Actions

```php
// app/Enrollment/Placement/Actions/RequestPlacementChangeAction.php
// Guards: no existing PENDING request for this registration

// app/Enrollment/Placement/Actions/ApprovePlacementChangeAction.php
// Guards: not terminal, target has available slots
// Atomic: decrement old filled_quota, increment new filled_quota, update registration placement_id

// app/Enrollment/Placement/Actions/RejectPlacementChangeAction.php
// Records rejection_reason
```

### 6.11 AccountApplication Model

```php
// app/Enrollment/AccountApplication/Models/AccountApplication.php
// Table: account_applications
// Fillable: name, email, student_id_number, department_id, form_data (json), status, processed_by, processed_at, rejection_reason
```

### 6.12 AccountApplicationStatus Enum

```php
// app/Enrollment/AccountApplication/Enums/AccountApplicationStatus.php
enum AccountApplicationStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    // Transitions: PENDING → [APPROVED, REJECTED]
    // Terminal: APPROVED, REJECTED
    // Special: rejected applications can be re-activated on re-apply
}
```

### 6.13 Account Application Actions

```php
// app/Enrollment/AccountApplication/Actions/ApplyAccountAction.php
// Guards: no duplicate PENDING/APPROVED by email; re-activates REJECTED on re-apply

// app/Enrollment/AccountApplication/Actions/ApproveAccountApplicationAction.php
// Atomic pipeline (single transaction):
//   1. Mark AccountApplication as APPROVED
//   2. Create User (random password, setup_required, student role)
//   3. Create Profile
//   4. Create Registration (status 'active')
//   5. Dispatch AccountApplicationApproved event

// app/Enrollment/AccountApplication/Actions/RejectAccountApplicationAction.php
// Records rejection_reason
```

### 6.14 RegistrationDocumentStatus Enum

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

### 6.15 Events

```php
// app/Enrollment/Registration/Events/StudentRegistered.php
// Dispatched by: RegisterInternshipAction
// Listener: ClearDashboardOnRegistration (clears dashboard cache)

// app/Enrollment/AccountApplication/Events/AccountApplicationApproved.php
// Dispatched by: ApproveAccountApplicationAction

// app/Enrollment/AccountApplication/Events/AccountApplicationRejected.php
// Dispatched by: RejectAccountApplicationAction
```

### 6.16 Routes

```php
// routes/web/enrollment.php

// Guest
Route::middleware('guest')->group(function () {
    Route::livewire('/apply', ApplyPage::class)->name('apply');
});

// Authenticated
Route::middleware('auth')->group(function () {
    Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
    Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
    Route::livewire('/registration/documents', RegistrationDocumentUpload::class)->name('registration.documents');
});

// Student
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::livewire('/internships/placement-change', StudentPlacementChangeRequest::class)->name('internships.placement-change');
});

// Admin
Route::prefix('admin')->name('enrollment.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
    Route::livewire('/internships/placements', PlacementIndex::class)->name('internships.placements');
    Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
    Route::livewire('/internships/placements/changes', PlacementChangeManager::class)->name('internships.placements.changes');
});
```

### 6.17 Database Migrations

| Migration | Table |
| --------- | ----- |
| `2026_01_04_000002_create_placements_table.php` | `placements` |
| `2026_01_04_000003_create_registrations_table.php` | `registrations` |
| `2026_01_04_000004_create_account_applications_table.php` | `account_applications` |
| `2026_01_05_000002_create_registration_documents_table.php` | `registration_documents` |
| `2026_01_05_000003_create_placement_change_requests_table.php` | `placement_change_requests` |

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
**Future:** Migrate to `RegistrationStatus` enum implementing `LabelEnum` + `StatusEnum` contracts,
matching the pattern used by all other enrollment status enums.

### DD-2 — Atomic Capacity Enforcement in Application Transaction

**Decision:** Placement capacity (`filled_quota`) is checked and incremented within the same
PHP transaction using `DB::transaction()`, not at the database level with `SELECT ... FOR UPDATE`.
**Rationale:** SQLite (development database) has limited locking support. Application-level
transaction wrapping is sufficient for single-tenant, low-concurrency deployment (one school
at a time). The `filled_quota` column uses `default(0)` to prevent null issues.
**Trade-off:** Under extreme concurrent load (hundreds of simultaneous registrations), the
application-level check could theoretically race. Mitigated by the single-tenant deployment
model — one school's registration period generates low concurrency. If multi-school scale is
ever needed, upgrade to `DB::select('SELECT ... FOR UPDATE')` pattern.

### DD-3 — Single-Transaction Guest-to-Student Provisioning

**Decision:** `ApproveAccountApplicationAction` creates User + Profile + Registration within
a single `DB::transaction()` call. If any step fails, all changes roll back.
**Rationale:** An approved application that creates a User but not a Registration (or vice versa)
leaves the system in an inconsistent state. Atomic provisioning ensures the student account is
fully functional immediately upon approval, or not created at all.
**Trade-off:** Longer transaction hold time during User provisioning (password hashing is CPU-
intensive). Mitigated by using random passwords (no bcrypt for user-chosen password) and the
operation being admin-triggered (not user-facing latency).

### DD-4 — Placement Change as Separate Model

**Decision:** Placement changes use a dedicated `PlacementChangeRequest` model with its own
status enum (`PlacementChangeStatus`), rather than modifying the Registration's `placement_id`
directly.
**Rationale:** A separate model provides a complete audit trail (who requested, when, reason,
who approved, when). It enables the request → review → approve/reject workflow with proper
authorization checks. Direct modification would bypass admin review and lose the reason for
the change.
**Trade-off:** Additional model, migration, and enum to maintain. Mitigated by the clear
separation of concerns — Registration tracks current state, PlacementChangeRequest tracks
the change workflow.

### DD-5 — Registration Availability via System Settings

**Decision:** Registration window is controlled by `registration_period_start` and
`registration_period_end` system settings, checked by `ReadRegistrationAvailabilityAction`.
**Rationale:** Settings are admin-configurable without code changes. The action returns
semantic statuses (`not_configured`, `open`, `upcoming`, `closed`) that the UI uses to
display appropriate messaging (e.g., "Registration opens on {date}" for `upcoming`).
**Trade-off:** Settings must be correctly configured by admin. Mitigated by the
`not_configured` status providing clear feedback when settings are missing.

### DD-6 — RegistrationDocument as Separate Model

**Decision:** Document submissions use a dedicated `RegistrationDocument` model with
`RegistrationDocumentStatus` enum, separate from the Registration model itself.
**Rationale:** A registration may require multiple documents, each with independent
verification status. Embedding documents in the Registration model would create a
complex nested structure. Separate model allows independent queries ("show all pending
documents across all registrations") and per-document verification workflows.
**Trade-off:** Additional model and query complexity. Mitigated by the clear 1-to-many
relationship (Registration has many RegistrationDocuments) and the dedicated
`RegistrationDocumentUpload` Livewire component handling the UI.

---

## 8. Success Metrics

### 8.1 Registration Completeness

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate registration prevention | 0 duplicates (same student + internship) | Unique constraint + `RegisterInternshipAction` guard |
| Registration availability accuracy | Correct status 100% of the time | `ReadRegistrationAvailabilityAction` against settings |
| Step completion rate | > 95% complete 2-step wizard | `RegistrationWizard` Livewire analytics |

### 8.2 Capacity Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Overbooking incidents | 0 (quota never exceeded) | Atomic `DB::transaction()` in VerifyRegistrationAction and ApprovePlacementChangeAction |
| Quota accuracy | `filled_quota` matches actual registrations | Periodic audit: `count(registrations where placement_id=X)` vs `placements.filled_quota` |
| Concurrent safety | No race condition under normal load | Transaction isolation within single-tenant deployment |

### 8.3 Guest Pipeline Efficiency

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Provisioning atomicity | 0 orphaned Users/Registrations | `DB::transaction()` in `ApproveAccountApplicationAction` |
| Duplicate application prevention | 0 duplicate pending/approved per email | `ApplyAccountAction` guard |
| Application-to-activation time | < 5 minutes (admin review time excluded) | Transaction execution time |

### 8.4 Placement Change Workflow

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Quota transfer accuracy | `filled_quota` correct on both old and new placement after approval | Atomic decrement+increment in `ApprovePlacementChangeAction` |
| Orphan request prevention | 0 pending requests for already-processed registrations | `RequestPlacementChangeAction` guard |
| Admin review completeness | All pending requests reviewed within 48 hours | `PlacementChangeManager` admin workflow tracking |

---

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
- `app/Enrollment/Placement/Models/Placement.php` — Placement model (UUID PK, quota/filled_quota)
- `app/Enrollment/Placement/Entities/PlacementCapacity.php` — Capacity entity (isFull, availableSlots)
- `app/Enrollment/Placement/Entities/PlacementState.php` — Placement state (canBeDeleted)
- `app/Enrollment/Placement/Actions/CreatePlacementAction.php` — Placement CRUD
- `app/Enrollment/Placement/Actions/UpdatePlacementAction.php` — Placement update
- `app/Enrollment/Placement/Actions/DeletePlacementAction.php` — Placement deletion with guard
- `app/Enrollment/Placement/Actions/DirectPlacementAction.php` — Atomic registration+placement creation
- `app/Enrollment/Placement/Policies/PlacementPolicy.php` — Placement authorization
- `app/Enrollment/Placement/Livewire/PlacementIndex.php` — Placement CRUD with stats
- `app/Enrollment/Placement/Livewire/DirectPlacementManager.php` — Admin direct placement form
- `app/Enrollment/Placement/Livewire/Forms/PlacementForm.php` — Placement form validation
- `app/Enrollment/Placement/Livewire/Forms/DirectPlacementForm.php` — Direct placement form validation
- `app/Enrollment/Placement/Models/PlacementChangeRequest.php` — Change request model
- `app/Enrollment/Placement/Enums/PlacementChangeStatus.php` — Change status enum (PENDING/APPROVED/REJECTED)
- `app/Enrollment/Placement/Actions/RequestPlacementChangeAction.php` — Student change request
- `app/Enrollment/Placement/Actions/ApprovePlacementChangeAction.php` — Atomic quota swap on approval
- `app/Enrollment/Placement/Actions/RejectPlacementChangeAction.php` — Change rejection
- `app/Enrollment/Placement/Policies/PlacementChangeRequestPolicy.php` — Change request authorization
- `app/Enrollment/Placement/Livewire/PlacementChangeManager.php` — Admin change review
- `app/Enrollment/Placement/Livewire/StudentPlacementChangeRequest.php` — Student change request form
- `app/Enrollment/Placement/Livewire/Forms/PlacementChangeForm.php` — Change form validation
- `app/Enrollment/AccountApplication/Models/AccountApplication.php` — Application model
- `app/Enrollment/AccountApplication/Enums/AccountApplicationStatus.php` — Application status enum
- `app/Enrollment/AccountApplication/Actions/ApplyAccountAction.php` — Guest application with re-activation
- `app/Enrollment/AccountApplication/Actions/ApproveAccountApplicationAction.php` — Atomic user provisioning
- `app/Enrollment/AccountApplication/Actions/RejectAccountApplicationAction.php` — Application rejection
- `app/Enrollment/AccountApplication/Policies/AccountApplicationPolicy.php` — Application authorization
- `app/Enrollment/AccountApplication/Events/AccountApplicationApproved.php` — Approval event
- `app/Enrollment/AccountApplication/Events/AccountApplicationRejected.php` — Rejection event
- `app/Enrollment/AccountApplication/Livewire/ApplyPage.php` — Guest application form
- `app/Enrollment/AccountApplication/Livewire/Forms/AccountApplicationForm.php` — Application form validation
- `routes/web/enrollment.php` — All enrollment route definitions
- `database/migrations/2026_01_04_000002_create_placements_table.php` — Placements migration
- `database/migrations/2026_01_04_000003_create_registrations_table.php` — Registrations migration
- `database/migrations/2026_01_04_000004_create_account_applications_table.php` — Applications migration
- `database/migrations/2026_01_05_000002_create_registration_documents_table.php` — Documents migration
- `database/migrations/2026_01_05_000003_create_placement_change_requests_table.php` — Change requests migration
- `docs/modules/enrollment.md` — Enrollment module overview
