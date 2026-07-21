# User Management — CRUD, Account Status, Profiles & Import/Export

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering user CRUD, role-based
> managers, account status lifecycle, profile editing, CSV import/export, account slips, and
> super admin protections

## Description

Complete specification of Internara's user management subsystem. Defines the four role-specific
Livewire managers (UserManager, StudentManager, TeacherManager, SupervisorManager), the
AccountStatus state machine with 8 states and transition guards, profile editing, CSV import/export,
account slip generation (PDF), password reset, account lock/unlock, batch operations, and super
admin protections. This subsystem spans the User and SysAdmin modules.

---

## 1. Problem Statements

### PS-1 — Multi-Role User Administration

Schools manage students, teachers, supervisors, and administrators — each with different data
requirements and operational needs. A single generic user management interface cannot efficiently
handle the distinct fields (student: department, NIS; teacher: NIP, subjects; supervisor: company)
without becoming unwieldy. Role-specific management interfaces are needed.

### PS-2 — Account Lifecycle Complexity

User accounts progress through 8 states (provisioned → activated → verified → ... → archived)
with strict transition rules. Without a state machine, administrators could put accounts in
inconsistent states (e.g., directly archiving a provisioned account). The state machine must
enforce valid transitions and prevent illegal operations.

### PS-3 — Bulk Operations at Scale

Schools may have 500+ students to onboard. Manual one-by-one creation is impractical. CSV import
must handle duplicates gracefully, generate credentials automatically, and provide clear feedback
(imported/skipped/failed counts). Batch lock/unlock and batch delete must protect critical accounts.

### PS-4 — Super Admin Protection

The super admin account is the system's break-glass access. If deleted or lockable, the school
could be permanently locked out. Multiple protection layers are needed: model observer, policy,
Livewire guard, and CLI recovery command.

### PS-5 — Account Slip Distribution

When accounts are created (especially via batch import), administrators need to distribute
credentials to users. Printed account slips (PDF) with username, temporary password, and setup
instructions are the standard distribution method in Indonesian schools.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide role-specific management interfaces (User, Student, Teacher, Supervisor, Admin) |
| G2  | Enforce AccountStatus state machine with 8 states and valid transition guards |
| G3  | Support CSV import with duplicate detection and automatic credential generation |
| G4  | Support CSV export with search and filter application |
| G5  | Generate PDF account slips for single and batch user creation |
| G6  | Protect super admin from deletion, lockout, and role removal |
| G7  | Provide batch operations: delete, lock, unlock, status change |
| G8  | Support profile editing with emergency contact information |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | User self-registration (admin-created accounts only) |
| NG2  | Role permission editing (handled by separate RBAC system) |
| NG3  | User activity monitoring beyond audit log |
| NG4  | Multi-tenant user isolation |
| NG5  | User impersonation / login-as |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Single User

**Actor:** Admin / Super Admin
**Preconditions:** Admin is authenticated with user management permission
**Flow:**
1. Admin navigates to User Management, clicks "Create User"
2. Fills in name, email, roles (optional: phone, address, bio, gender, DOB)
3. `CreateUserAction` validates (name, username uniqueness, email uniqueness, reserved names)
4. Generates username from email if not provided (collision resolution via alphanumeric suffix)
5. Generates random 12-char password if not provided
6. Creates User + Profile + syncs roles in a transaction
7. Sends activation token notification via `ActivationCodeNotification`
8. If password was auto-generated, sends `WelcomeNotification` with the plain password
9. Redirects to account slip page for credential distribution
**Postconditions:** User created, activation email sent, account slip ready for download

### UC-2 — Admin Imports Users via CSV

**Actor:** Admin
**Preconditions:** CSV file prepared with columns: name, email, phone
**Flow:**
1. Admin navigates to User Management, clicks "Import"
2. Uploads CSV file (max 2048KB, mimes: csv, txt)
3. `CsvHandler::import()` processes each row:
   - Empty name → skip
   - Email already exists → skip (CsvRowResult::SKIPPED)
   - Valid row → CreateUserAction → CsvRowResult::CREATED
4. Flash message shows: "Imported: X, Skipped: Y"
**Postconditions:** Users created from CSV, duplicates skipped, summary displayed

### UC-3 — Admin Locks Multiple Accounts

**Actor:** Admin
**Preconditions:** Users selected in the table
**Flow:**
1. Admin selects multiple users via checkboxes
2. Clicks "Lock" bulk action
3. `SetUserStatusAction` called for each selected user with `AccountStatus::SUSPENDED`
4. Transition guard validates: current status must allow transition to SUSPENDED
5. `UserStatusChanged` event dispatched for each locked user
6. `AccountStatusNotification` sent to each locked user
**Postconditions:** Selected accounts locked, users notified, invalid transitions rejected

### UC-4 — Admin Downloads Account Slip

**Actor:** Admin
**Preconditions:** User just created (or any existing user)
**Flow:**
1. After user creation, redirect to account slip page
2. Account slip displays: name, username, email, temporary password, school logo
3. Admin can download as PDF (DomPDF) or send via email
4. Batch slips available for multiple selected users
**Postconditions:** PDF generated with credentials, ready for distribution

### UC-5 — Super Admin Cannot Be Deleted

**Actor:** System (protection guard)
**Preconditions:** Someone attempts to delete the super admin account
**Flow:**
1. Delete action is called on super admin user
2. `UserObserver::deleting()` intercepts, checks `hasRole('super_admin')`
3. Throws `RejectedException` with message
4. Alternatively, `DeleteUserAction` has explicit guard: `$user->hasRole('super_admin')`
5. Livewire `UserManager::editUser()` blocks edit modal for super admin
**Postconditions:** Super admin deletion blocked at observer, action, and UI layers

---

## 4. Functional Requirements

### User CRUD

| ID   | Requirement |
| ---- | ----------- |
| FR-UC1 | `CreateUserAction` must validate name (required, max 255, not reserved), username (unique, system format, not reserved), email (unique, valid) |
| FR-UC2 | `CreateUserAction` must auto-generate username from email if not provided, with collision resolution |
| FR-UC3 | `CreateUserAction` must auto-generate 12-char random password if not provided |
| FR-UC4 | `CreateUserAction` must create User + Profile + sync roles in a single transaction |
| FR-UC5 | `CreateUserAction` must send `ActivationCodeNotification` (activation token) |
| FR-UC6 | `CreateUserAction` must send `WelcomeNotification` with plain password when auto-generated |
| FR-UC7 | `UpdateUserAction` must support atomic update of user data + profile + roles |
| FR-UC8 | `UpdateUserAction` must prevent role changes on super admin accounts |
| FR-UC9 | `DeleteUserAction` must prevent deletion of super admin and self |
| FR-UC10 | `BatchDeleteUserAction` must skip self and super_admin in batch operations |

### Account Status State Machine

| ID   | Requirement |
| ---- | ----------- |
| FR-AS1 | `AccountStatus` enum must implement `StatusEnum` and `ColorableEnum` contracts |
| FR-AS2 | Must define 8 states: PROVISIONED, ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, SUSPENDED, INACTIVE, ARCHIVED |
| FR-AS3 | `allowsLogin()` must return true for ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, INACTIVE |
| FR-AS4 | `isTerminal()` must return true for PROTECTED and ARCHIVED (no further transitions) |
| FR-AS5 | `validTransitions()` must define allowed transitions for each state |
| FR-AS6 | `SetUserStatusAction` must validate transitions via `canTransitionTo()` before applying |
| FR-AS7 | `ToggleUserStatusAction` must toggle between VERIFIED ↔ SUSPENDED only |
| FR-AS8 | Status changes must dispatch `UserStatusChanged` event |
| FR-AS9 | Status changes must send `AccountStatusNotification` to the affected user |

### Profile Management

| ID   | Requirement |
| ---- | ----------- |
| FR-PM1 | `Profile` model must belong to User, Department, and Company |
| FR-PM2 | `ProfileEditor` Livewire component must support editing: name, email, phone, address, bio, gender, DOB, POB, emergency contacts |
| FR-PM3 | `UpdateProfileAction` must validate and persist profile changes |
| FR-PM4 | Profile changes must dispatch `ProfileUpdated` event |
| FR-PM5 | `SendProfileChangedMail` listener must send email notification on profile change |
| FR-PM6 | Profile must be guarded by `ProfilePolicy` (admin or owner) |

### Livewire Managers

| ID   | Requirement |
| ---- | ----------- |
| FR-LM1 | `UserManager` must extend `BaseRecordManager` with full CRUD, search, filter, pagination |
| FR-LM2 | `UserManager` must support search across name, email, username, phone |
| FR-LM3 | `UserManager` must support filters: role, status, created_from, created_to |
| FR-LM4 | `UserManager` must display columns: name, email, phone, roles, status, actions |
| FR-LM5 | `StudentManager` must add department filter |
| FR-LM6 | `TeacherManager` must include id_number column |
| FR-LM7 | `SupervisorManager` must include company column |
| FR-LM8 | `AdminManager` must be restricted to admin-only access |
| FR-LM9 | All managers must enforce policy checks in `boot()` |

### Import / Export

| ID   | Requirement |
| ---- | ----------- |
| FR-IE1 | CSV import must accept files up to 2048KB (csv, txt) |
| FR-IE2 | Import must detect duplicate emails and skip them |
| FR-IE3 | Import must auto-generate credentials for each valid row |
| FR-IE4 | Import must display summary: created count, skipped count |
| FR-IE5 | CSV export must respect current search and filter state |
| FR-IE6 | Export columns: full_name, email, username, phone, address |
| FR-IE7 | `downloadTemplate()` must provide a CSV template with headers and placeholders |
| FR-IE8 | `exportSelected()` must export only selected rows |

### Account Slips

| ID   | Requirement |
| ---- | ----------- |
| FR-AS10 | `GenerateAccountSlipAction` must produce a PDF via DomPDF |
| FR-AS11 | Account slip must include: name, username, email, temporary password, school logo |
| FR-AS12 | `DownloadsAccountSlips` trait must provide `downloadSlip()` and `sendSlip()` methods |
| FR-AS13 | Batch slip generation must support multiple selected users |

### Super Admin Protection

| ID   | Requirement |
| ---- | ----------- |
| FR-SAP1 | `UserObserver` must block super admin deletion at model level |
| FR-SAP2 | `DeleteUserAction` must reject super admin deletion with explicit guard |
| FR-SAP3 | `UserManager::editUser()` must block edit modal for super admin |
| FR-SAP4 | `UserManager::saveUser()` must reject role changes on super admin |
| FR-SAP5 | `ReadUserManagerStatsAction` must exclude super_admin from admin count |

### Account Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-AL1 | `AutoInactivateAccounts` artisan command must mark 90-day inactive accounts |
| FR-AL2 | `ArchiveStudentAccountsAction` must mass-archive students via chunked query |
| FR-AL3 | `RevokeUserActivationTokensAction` must revoke all activation tokens for a user |
| FR-AL4 | `SaveRecoveryKeyAction` must store recovery key in private storage with 0600 permissions |
| FR-AL5 | `ReadRecoveryKeyAction` must read `.recovery-key` from private storage |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | User list query must use eager loading (roles, profile) to prevent N+1 |
| NFR-P2 | CSV import must process rows in chunks to prevent memory exhaustion |
| NFR-P3 | Account slip PDF generation must complete in < 3 seconds for a single slip |
| NFR-S1 | Super admin must be protected at observer, action, and UI layers |
| NFR-S2 | Password must never be stored in plain text (Hash::make required) |
| NFR-S3 | Recovery key file must have 0600 permissions (owner read/write only) |
| NFR-S4 | Account status transitions must be validated before application |
| NFR-R1 | Batch operations must skip invalid targets (self, super_admin) without failing the batch |
| NFR-R2 | CSV import must handle malformed rows gracefully (skip, report) |
| NFR-U1 | Flash messages must confirm success/failure for every operation |
| NFR-U2 | Account slip must display school branding (logo) |
| NFR-M1 | User CRUD must use Action classes, not inline Livewire logic |
| NFR-M2 | All form validation must use Form Objects, not inline rules |

---

## 6. API / Data Contracts

### 6.1 AccountStatus Enum

```php
// app/User/Enums/AccountStatus.php
enum AccountStatus: string implements ColorableEnum, StatusEnum
{
    case PROVISIONED = 'provisioned';   // color: warning, allowsLogin: false
    case ACTIVATED = 'activated';       // color: info,    allowsLogin: true
    case VERIFIED = 'verified';         // color: success, allowsLogin: true
    case PROTECTED = 'protected';       // color: primary, allowsLogin: true, terminal
    case RESTRICTED = 'restricted';     // color: warning, allowsLogin: true
    case SUSPENDED = 'suspended';       // color: error,   allowsLogin: false
    case INACTIVE = 'inactive';         // color: warning, allowsLogin: true
    case ARCHIVED = 'archived';         // color: error,   allowsLogin: false, terminal
}
```

### 6.2 CreateUserAction

```php
// app/User/UserManagement/Actions/CreateUserAction.php
final class CreateUserAction extends BaseCommandAction
{
    public function execute(
        array $userData,        // name, email, username?, password?, setup_required?
        array $profileData = [], // phone, address, bio, gender, pob, dob, emergency_*
        array $roles = [],
        bool $sendNotification = true,
    ): User;
    // Creates User + Profile + syncs roles in transaction
    // Sends ActivationCodeNotification + optional WelcomeNotification
}
```

### 6.3 UpdateUserAction

```php
// app/User/UserManagement/Actions/UpdateUserAction.php
final class UpdateUserAction extends BaseCommandAction
{
    public function execute(
        User $user,
        array $userData,       // name, email
        array $profileData = [],
        array $roles = [],
    ): User;
    // Atomic update with super_admin guard on role changes
}
```

### 6.4 SetUserStatusAction

```php
// app/User/UserManagement/Actions/SetUserStatusAction.php
final class SetUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, AccountStatus $targetStatus, ?string $reason = null): User;
    // Validates transition via canTransitionTo(), dispatches UserStatusChanged event
}
```

### 6.5 UserManager Livewire

```php
// app/User/UserManagement/Livewire/UserManager.php
class UserManager extends BaseRecordManager
{
    // CRUD: createUser(), editUser(), saveUser(), confirmAction()
    // Bulk: lockSelected(), unlockSelected(), askDeleteSelected()
    // Import/Export: import(), export(), exportSelected(), downloadTemplate()
    // Account Slips: trait DownloadsAccountSlips
    // Computed: roles(), statusOptions(), stats()
    // Search: name, email, username, phone
    // Filters: role, status, created_from, created_to
}
```

### 6.6 User Model

```php
// app/User/Models/User.php
class User extends BaseAuthenticatable implements HasMedia
{
    // Traits: HasRoles (Spatie), HasUuids, SuperAdminIntegrityRules, HasMedia
    // Entity bridges: asStudent(), asTeacher(), asSupervisor(), asApprentice()
    // Scopes: locked(), unlocked(), active(), roleType()
    // Role mapping: super_admin → superadmin (Spatie compatibility)
}
```

### 6.7 Profile Model

```php
// app/User/Profile/Models/Profile.php
class Profile extends BaseModel
{
    // #[Fillable]: user_id, department_id, company_id, phone, address, bio,
    //   gender, pob, dob, emergency_contact_name, emergency_contact_phone, emergency_contact_address
    // Belongs to: User, Department, Company
}
```

### 6.8 Routes

```php
// routes/web/sysadmin.php
Route::prefix('admin/users')->group(function () {
    // User management CRUD (UserManager Livewire)
    // Account slips: GET /admin/users/{user}/account-slip
    // Batch operations: POST /admin/users/batch/delete, lock, unlock
    // CSV: POST /admin/users/import, GET /admin/users/export
});
```

---

## 7. Design Decisions

### DD-1 — Role-Specific Managers Over Generic User Manager

**Decision:** Four separate Livewire managers (User, Student, Teacher, Supervisor) instead of one
generic manager with role tabs.
**Rationale:** Each role has distinct data requirements (student: department; teacher: NIP;
supervisor: company). Separate managers allow role-specific columns, filters, and form fields
without conditional logic cluttering a single component. The base `BaseRecordManager` provides
shared functionality (search, pagination, bulk actions).
**Trade-off:** More files to maintain (4 managers + 5 forms). Mitigated by shared traits and
consistent patterns.

### DD-2 — AccountStatus as State Machine Enum

**Decision:** Account status implemented as a PHP 8.1 enum with `validTransitions()` method.
**Rationale:** State machines prevent illegal transitions at the type level. An enum with transition
rules is self-documenting and testable — every state and transition can be verified in unit tests.
Database columns store the string value; the enum handles logic.
**Trade-off:** Adding new states requires updating the enum and all transition maps. Mitigated by
the enum being the single source of truth.

### DD-3 — Triple-Layer Super Admin Protection

**Decision:** Super admin protected at observer, action, and UI layers.
**Rationale:** Defense in depth — any single layer could be bypassed (observer via tinker, action
via direct call, UI via API). All three layers must be checked independently. The observer is the
final safety net.
**Trade-off:** Redundant checks. Intentional — the cost of accidentally losing super admin access
is system-wide lockout.

### DD-4 — PDF Account Slips via DomPDF

**Decision:** Account slips generated as PDF via `barryvdh/laravel-dompdf`, not HTML or email.
**Rationale:** Indonesian schools require printed credential distribution. PDF is the standard
format for printable documents. DomPDF runs server-side without external services.
**Trade-off:** DomPDF has limited CSS support. Mitigated by simple slip layout (no complex
positioning needed).

### DD-5 — CSV Import with Skip-on-Duplicate

**Decision:** CSV import skips rows with existing emails, does not update or merge.
**Rationale:** Import is for onboarding new users, not updating existing ones. Updating via CSV
would require conflict resolution logic (which fields to overwrite?) and audit trail complexity.
Skip-on-duplicate is predictable and safe.
**Trade-off:** Admin must manually update existing users. Acceptable because updates are infrequent
compared to initial onboarding.

---

## 8. Success Metrics

### 8.1 CRUD Operations

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| User creation | < 1s including notifications | CreateUserAction transaction time |
| Batch delete | Skips self + super_admin | BatchDeleteUserAction guards |
| Profile update | < 500ms | UpdateProfileAction transaction time |

### 8.2 Import/Export

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| CSV import 100 rows | < 30s | CsvHandler chunk processing |
| Duplicate detection | 100% skip existing emails | CsvRowResult::SKIPPED |
| Export respects filters | Search + filter applied | UserManager query builder |

### 8.3 Account Status

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Transition validation | 100% of illegal transitions rejected | `canTransitionTo()` unit tests |
| Terminal states | PROTECTED and ARCHIVED block all transitions | `isTerminal()` method |
| Notification delivery | Status change → user notified | AccountStatusNotification dispatch |

### 8.4 Super Admin Protection

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Triple protection | Observer + Action + UI guard | All three layers checked |
| Recovery | CLI recovery always works | `php artisan admin:recover` |

---

## Quick References

- `app/User/UserManagement/Actions/CreateUserAction.php` — user creation with notifications
- `app/User/UserManagement/Actions/UpdateUserAction.php` — atomic user update
- `app/User/UserManagement/Actions/DeleteUserAction.php` — single user deletion with guards
- `app/User/UserManagement/Actions/BatchDeleteUserAction.php` — batch deletion with skip logic
- `app/User/UserManagement/Actions/SetUserStatusAction.php` — status change with transition guard
- `app/User/UserManagement/Actions/ToggleUserStatusAction.php` — VERIFIED ↔ SUSPENDED toggle
- `app/User/UserManagement/Actions/GenerateAccountSlipAction.php` — PDF slip generation
- `app/User/UserManagement/Actions/ReadUserManagerStatsAction.php` — manager statistics
- `app/User/UserManagement/Actions/RevokeUserActivationTokensAction.php` — token revocation
- `app/User/UserManagement/Actions/SaveRecoveryKeyAction.php` — recovery key storage
- `app/User/UserManagement/Actions/ReadRecoveryKeyAction.php` — recovery key retrieval
- `app/User/UserManagement/Actions/ArchiveStudentAccountsAction.php` — mass student archival
- `app/User/UserManagement/Livewire/UserManager.php` — main user management component
- `app/User/UserManagement/Livewire/StudentManager.php` — student-specific manager
- `app/User/UserManagement/Livewire/TeacherManager.php` — teacher-specific manager
- `app/User/UserManagement/Livewire/SupervisorManager.php` — supervisor-specific manager
- `app/User/UserManagement/Livewire/AdminManager.php` — admin-only manager
- `app/User/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php` — PDF download/send trait
- `app/User/UserManagement/Events/UserCreated.php` — user creation event
- `app/User/UserManagement/Events/UserDeleted.php` — user deletion event
- `app/User/UserManagement/Events/UserStatusChanged.php` — status change event
- `app/User/UserManagement/Events/UserUpdated.php` — user update event
- `app/User/UserManagement/Notifications/ActivationCodeNotification.php` — activation email
- `app/User/UserManagement/Console/AutoInactivateAccounts.php` — 90-day inactivity command
- `app/User/AccountStatus/Actions/LockUserAccountAction.php` — account locking
- `app/User/AccountStatus/Actions/UnlockUserAccountAction.php` — account unlocking
- `app/User/AccountStatus/Actions/DetectUserAccountCloneAction.php` — clone detection
- `app/User/Profile/Actions/UpdateProfileAction.php` — profile editing
- `app/User/Profile/Actions/ReadProfileFormAction.php` — profile form data
- `app/User/Profile/Livewire/ProfileEditor.php` — profile editing component
- `app/User/Enums/AccountStatus.php` — 8-state status machine
- `app/User/Models/User.php` — user model with roles, media, entity bridges
- `app/User/Observers/UserObserver.php` — super admin deletion guard
- `app/User/Services/UserIdentifierGenerator.php` — username generation
- `docs/modules/user.md` — User module overview
- `docs/modules/user-reference.md` — User module technical reference
