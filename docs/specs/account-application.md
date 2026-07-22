# Account Application — Guest-to-Student Pipeline

> **Last updated:** 2026-07-22 **Changes:** feat — expanded DDs, API contracts, NFRs; full action
> signatures, policy authorization, form validation rules, and provisioning edge cases

## Description

Specification of the Internara Enrollment module's account application initiative: the
guest-to-student account application pipeline with atomic user provisioning, application
lifecycle, and admin review workflow. Registration and placement management are separate
initiatives — see [registration.md](registration.md) and [placement.md](placement.md).

---

## 1. Problem Statements

### PS-1 — Guest-to-Student Account Application Pipeline

Prospective students who are not yet system users need a way to express intent to participate
in an internship program. The school admin must review each application, and upon approval,
the system must atomically create a User account, Profile, and Registration in a single
transaction. Manual provisioning is error-prone (forgot to create Profile, Registration
left in wrong status) and does not scale.

### PS-2 — Duplicate Application Prevention

Multiple students may share the same email during application, or a student may re-apply
after rejection. The system must prevent duplicate pending/approved applications per email
while allowing rejected applications to be re-activated on re-apply, preserving the audit
trail without cluttering the database with redundant records.

### PS-3 — Atomic Provisioning Failure Modes

The approval pipeline creates 4 records (Application status, User, Profile, Registration)
in a single transaction. Any failure midway — User creation fails (duplicate email), Profile
FK violation (invalid department), Registration FK violation (invalid internship) — must
roll back cleanly with no orphaned records. The error must surface as a user-friendly message
to the admin, not a stack trace.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Atomically provision User + Profile + Registration on guest account application approval |
| G2  | Prevent duplicate pending applications (same email) |
| G3  | Provide guest-accessible application page without authentication |
| G4  | Support application re-activation for previously rejected applications |
| G5  | Record admin attribution (processed_by, processed_at) on approval/rejection |
| G6  | Support both placement-based and proposed-company application modes |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Registration workflow (see [registration.md](registration.md)) |
| NG2  | Placement management (see [placement.md](placement.md)) |
| NG3  | Bulk/batch registration import from external government systems |
| NG4  | Student self-service placement swap (peer-to-peer without admin) |

---

## 3. User Stories / Use Cases

### UC-1 — Guest Applies for Student Account

**Actor:** Guest (unauthenticated)
**Preconditions:** At least one published, active internship exists with non-full placements
**Flow:**
1. Guest navigates to `/apply` (ApplyPage, `guest` middleware)
2. System displays available internships (status: published/active) and non-full placements
3. Guest fills application form: name, email, phone, address, national_id_number, student_id_number, department, class_name, entry_year, academic_year, selected internship/placement
4. Guest chooses mode: "Select placement" (existing slot) or "Propose company" (manual entry)
5. If "Propose company": guest enters proposed_company_name and proposed_company_address
6. `ApplyAccountAction` guards: no duplicate PENDING/APPROVED application by email; if previously REJECTED, re-activates
7. `AccountApplication` created with status `PENDING`
8. Admin reviews application
9. On approval: `ApproveAccountApplicationAction` atomically creates User (random password, `setup_required`, student role) + Profile + Registration (status `active`)
10. Account activation notification dispatched
**Postconditions:** User account created; Profile created; Registration active; applicant receives activation email

### UC-2 — Admin Approves Guest Account Application

**Actor:** Admin (role: super_admin or admin)
**Preconditions:** Pending account applications exist
**Flow:**
1. Admin reviews pending applications in admin panel
2. Admin verifies application details (student ID, department, proposed placement)
3. `ApproveAccountApplicationAction` executes within a single database transaction:
   a. Validates application is still PENDING (guard against concurrent approval)
   b. Marks `AccountApplication` status as `APPROVED`, sets processed_by and processed_at
   c. Creates `User` with random 32-char password, `setup_required` flag, `student` role
   d. Creates `Profile` for the new user (phone, address, id_number, department_id)
   e. Validates form_data contains internship_id (throws RejectedException if missing)
   f. Creates `Registration` with status `active` linked to the selected internship/placement
   g. Dispatches `AccountApplicationApproved` event
4. Or admin rejects: `RejectAccountApplicationAction` records rejection reason
**Postconditions:** Full user provisioning complete in single transaction; activation email sent

### UC-3 — Guest Re-Apply After Rejection

**Actor:** Guest (unauthenticated)
**Preconditions:** Guest previously applied and was rejected
**Flow:**
1. Guest navigates to `/apply` again
2. Guest fills form with same email
3. `ApplyAccountAction` detects REJECTED application by email
4. Inside transaction: re-activates existing record (sets status back to PENDING, updates form_data)
5. Returns the re-activated application (not a new record)
**Postconditions:** Application re-activated, previous rejection reason preserved in audit log

---

## 4. Functional Requirements

### Account Application — Guest Pipeline

| ID   | Requirement |
| ---- | ----------- |
| FR-A1 | `AccountApplicationStatus` enum must implement `LabelEnum` and `StatusEnum` contracts |
| FR-A2 | Valid transitions: `PENDING` → [`APPROVED`, `REJECTED`]; `APPROVED` and `REJECTED` are terminal |
| FR-A3 | `ApplyAccountAction` must guard: no duplicate `PENDING` or `APPROVED` application by email |
| FR-A4 | `ApplyAccountAction` must re-activate previously `REJECTED` applications on re-apply (set back to `PENDING`) |
| FR-A5 | `ApplyAccountAction` must execute within a transaction for both new and re-activation paths |
| FR-A6 | `ApproveAccountApplicationAction` must execute within a single DB transaction: mark approved → create User (random 32-char password, `setup_required`, student role) → create Profile → create Registration (status `active`) |
| FR-A7 | `ApproveAccountApplicationAction` must validate application is still PENDING (concurrent guard) |
| FR-A8 | `ApproveAccountApplicationAction` must validate form_data contains internship_id (throw RejectedException if missing) |
| FR-A9 | `RejectAccountApplicationAction` must record `rejection_reason` and transition to `REJECTED` |
| FR-A10 | `RejectAccountApplicationAction` must validate application is still PENDING |
| FR-A11 | `AccountApplication` model must store `form_data` as JSON column for flexible form fields |
| FR-A12 | `AccountApplication` must have `belongsTo` Department and `belongsTo` User (processed_by) |
| FR-A13 | `AccountApplicationPolicy` must allow: create → all users (guest); viewAny/view → admin; update/delete → admin |

### Account Application — Livewire Components & Routing

| ID   | Requirement |
| ---- | ----------- |
| FR-L1 | `ApplyPage` must be at `/apply` with `guest` middleware |
| FR-L2 | `ApplyPage` must extend `BaseFormView` and use `AccountApplicationForm` Form Object |
| FR-L3 | `ApplyPage` must filter internships to published/active status |
| FR-L4 | `ApplyPage` must filter placements by internship and exclude full slots (via PlacementCapacity) |
| FR-L5 | `ApplyPage` must support mode toggle: placement-based vs proposed-company |
| FR-L6 | `ApplyPage` must clear placement_id and proposed fields when mode toggles |
| FR-L7 | `AccountApplicationForm` must validate: name (required, string, max:255), email (required, email, unique:account_applications,email, unique:users,email), internship_id (required, exists, OpenForRegistration rule), academic_year (required, string, max:20) |
| FR-L8 | `AccountApplicationForm` must conditionally validate: placement_id (required if use_placement) OR proposed_company_name + proposed_company_address (required if not use_placement) |
| FR-L9 | `AccountApplicationForm::toArray()` must return all form fields as flat array for Action consumption |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | Guest application endpoint (`/apply`) must be rate-limited to prevent spam submissions |
| NFR-S2 | `AccountApplication` `form_data` JSON must be sanitized before storage (prevent XSS via stored JSON) |
| NFR-S3 | Account provisioning must use random 32-char passwords — never allow password choice during application |
| NFR-S4 | Email uniqueness must be checked against both `account_applications` and `users` tables (prevent creating application for existing user) |
| NFR-R1 | Guest-to-student provisioning must be atomic — if User creation fails, entire transaction rolls back (no orphaned Registration) |
| NFR-R2 | Application re-activation must preserve original rejection reason in activity log |
| NFR-U1 | `ApplyPage` must work without JavaScript for basic form submission (progressive enhancement) |
| NFR-U2 | Mode toggle (placement vs proposed company) must dynamically show/hide relevant fields |
| NFR-U3 | Success message after submission must not reveal whether an existing application was re-activated |
| NFR-M1 | All enrollment Actions must extend appropriate base classes (BaseCommandAction, BaseReadAction) |
| NFR-A1 | All enrollment UI (apply page) must meet WCAG 2.1 Level AA |
| NFR-A2 | Form inputs in apply form must have associated labels |
| NFR-A3 | Color contrast must meet 4.5:1 minimum for all enrollment UI text |
| NFR-L1 | All user-facing strings in enrollment UI must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### 6.1 AccountApplication Model

```php
// app/Enrollment/AccountApplication/Models/AccountApplication.php
// Table: account_applications
// Fillable: name, email, student_id_number, department_id, form_data (json),
//           status, processed_by, processed_at, rejection_reason
// Casts: form_data → json, processed_at → datetime, status → AccountApplicationStatus
// Relations: belongsTo Department, belongsTo User (processed_by)
```

### 6.2 AccountApplicationStatus Enum

```php
// app/Enrollment/AccountApplication/Enums/AccountApplicationStatus.php
enum AccountApplicationStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string;            // __('registration.status.{value}')
    public function isTerminal(): bool;         // true for APPROVED, REJECTED
    public function validTransitions(): array;  // PENDING → [APPROVED, REJECTED]
    public function canTransitionTo(StatusEnum $target): bool;
}
```

### 6.3 Action Signatures

```php
// app/Enrollment/AccountApplication/Actions/ApplyAccountAction.php
final class ApplyAccountAction extends BaseCommandAction
{
    public function execute(array $data): AccountApplication;
    // Guards: no duplicate PENDING/APPROVED by email
    // Re-activates REJECTED on re-apply (updates status + form_data)
    // Returns new or re-activated application
}

// app/Enrollment/AccountApplication/Actions/ApproveAccountApplicationAction.php
final class ApproveAccountApplicationAction extends BaseCommandAction
{
    public function execute(string $applicationId, User $admin): Registration;
    // Validates: application must be PENDING
    // Atomic pipeline (single transaction):
    //   1. Mark AccountApplication as APPROVED (processed_by, processed_at)
    //   2. Create User (random 32-char password, setup_required, student role)
    //   3. Create Profile (phone, address, id_number, department_id)
    //   4. Validate form_data contains internship_id
    //   5. Create Registration (status 'active', internship/placement/propose_company)
    //   6. Dispatch AccountApplicationApproved event
    // Returns: Registration
}

// app/Enrollment/AccountApplication/Actions/RejectAccountApplicationAction.php
final class RejectAccountApplicationAction extends BaseCommandAction
{
    public function execute(string $applicationId, User $admin, string $reason): void;
    // Validates: application must be PENDING
    // Updates: status → REJECTED, processed_by, processed_at, rejection_reason
    // Dispatches AccountApplicationRejected event
}
```

### 6.4 Policy

```php
// app/Enrollment/AccountApplication/Policies/AccountApplicationPolicy.php
class AccountApplicationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool;   // admin only
    public function view(User $user, AccountApplication $application): bool;  // admin or owner (email match)
    public function create(User $user): bool;    // all users (guest endpoint)
    public function update(User $user, AccountApplication $application): bool; // admin only
    public function delete(User $user, AccountApplication $application): bool; // admin only
}
```

### 6.5 Form Object

```php
// app/Enrollment/AccountApplication/Livewire/Forms/AccountApplicationForm.php
class AccountApplicationForm extends Form
{
    // Fields: id, name, email, phone, address, national_id_number, student_id_number,
    //         department_id, class_name, entry_year, internship_id, placement_id,
    //         academic_year, proposed_company_name, proposed_company_address, use_placement

    public function rules(): array;
    // Base: name (required|string|max:255), email (required|email|max:255|unique:account_applications,email|unique:users,email),
    //       internship_id (required|exists:internships,id|OpenForRegistration), academic_year (required|string|max:20)
    // Conditional: placement_id (required_if:use_placement,true) OR
    //              proposed_company_name (required_if:use_placement,false) + proposed_company_address (required_if:use_placement,false)

    public function toArray(): array;
    // Returns flat array for Action consumption
}
```

### 6.6 Events

```php
// app/Enrollment/AccountApplication/Events/AccountApplicationApproved.php
// Dispatched by: ApproveAccountApplicationAction
// Payload: AccountApplication model

// app/Enrollment/AccountApplication/Events/AccountApplicationRejected.php
// Dispatched by: RejectAccountApplicationAction
// Payload: AccountApplication model
```

### 6.7 Routes

```php
// routes/web/enrollment.php (account application portion)
Route::middleware('guest')->group(function () {
    Route::livewire('/apply', ApplyPage::class)->name('apply');
});

// Admin routes (in admin route group)
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/admin/account-applications', AccountApplicationManager::class)->name('account-applications.index');
});
```

### 6.8 Database Migrations

| Migration | Table | Key Columns |
| --------- | ----- | ----------- |
| `2026_01_04_000004_create_account_applications_table.php` | `account_applications` | id (uuid), name, email, student_id_number, department_id (FK→departments), form_data (json), status (enum), processed_by (FK→users), processed_at (datetime), rejection_reason (text), timestamps |

---

## 7. Design Decisions

### DD-1 — Single-Transaction Guest-to-Student Provisioning

**Decision:** `ApproveAccountApplicationAction` creates User + Profile + Registration within
a single `DB::transaction()` call. If any step fails, all changes roll back.
**Rationale:** An approved application that creates a User but not a Registration (or vice versa)
leaves the system in an inconsistent state. Atomic provisioning ensures the student account is
fully functional immediately upon approval, or not created at all.
**Trade-off:** Longer transaction hold time during User provisioning (password hashing is CPU-
intensive). Mitigated by using random passwords and the operation being admin-triggered.

### DD-2 — Re-Activation of Rejected Applications

**Decision:** `ApplyAccountAction` re-activates previously rejected applications by setting
status back to `PENDING` instead of creating a new record.
**Rationale:** Prevents duplicate applications from the same email accumulating in the database.
The original rejection reason and timestamp are preserved in the audit trail (the status
change is logged).
**Trade-off:** Loses the original rejection reason on re-activation. If admins need rejection
history, the activity log provides this.

### DD-3 — Random Passwords for Provisioned Accounts

**Decision:** `ApproveAccountApplicationAction` generates a random 32-character password for
the new User, with `setup_required = true`. The student must set their own password on first
login via the setup wizard.
**Rationale:** Prevents the admin from knowing or choosing the student's password. Random
passwords eliminate the risk of weak/guessable initial credentials. The setup_required flag
forces password change before the student can access the system.
**Trade-off:** Extra step for students (must set password on first login). Mitigated by the
setup wizard being a streamlined flow.

### DD-4 — Dual Application Modes (Placement vs Proposed Company)

**Decision:** The `AccountApplicationForm` supports two modes: selecting an existing placement
or proposing a company manually. The `use_placement` boolean flag controls which fields are
required.
**Rationale:** Some students have a specific placement slot; others need to propose a company
for the admin to evaluate. Supporting both modes in a single form (with conditional validation)
avoids separate application flows.
**Trade-off:** Form validation becomes conditional. Mitigated by Livewire's dynamic validation
rules and clear mode toggle UI.

### DD-5 — Email Uniqueness Across Tables

**Decision:** Email uniqueness is checked against both `account_applications` and `users` tables
at the form validation level.
**Rationale:** A student who already has an account (created via another flow) should not be able
to submit a duplicate application. Checking both tables prevents creating applications for
existing users.
**Trade-off:** Slightly stricter validation may reject legitimate re-applications if the user
already has an account. Mitigated by the admin being able to manually create registrations for
existing users.

---

## 8. Success Metrics

### 8.1 Guest Pipeline Efficiency

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Provisioning atomicity | 0 orphaned Users/Registrations | `DB::transaction()` in `ApproveAccountApplicationAction` |
| Duplicate application prevention | 0 duplicate pending/approved per email | `ApplyAccountAction` guard |
| Application-to-activation time | < 5 minutes (admin review time excluded) | Transaction execution time |
| Re-activation preserves audit | 0 lost rejection records | Activity log entries |

### 8.2 Form Validation

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Email dual-table check | 100% of duplicate emails caught | Form validation rules |
| Conditional field enforcement | 0 incomplete applications submitted | Form validation tests |
| OpenForRegistration rule | 0 applications for closed internships | Rule validation tests |

---

## Quick References

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
- `app/Program/Internship/Rules/OpenForRegistration.php` — internship must be published/active
- `routes/web/enrollment.php` — All enrollment route definitions
- `database/migrations/2026_01_04_000004_create_account_applications_table.php` — Applications migration
- `docs/modules/enrollment.md` — Enrollment module overview
- **Related specs:** [registration.md](registration.md) — Registration workflow & documents
- **Related specs:** [placement.md](placement.md) — Placement CRUD & capacity management
