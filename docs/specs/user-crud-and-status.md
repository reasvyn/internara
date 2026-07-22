# User CRUD & Account Status — Multi-Role Management & State Machine

> **Last updated:** 2026-07-22 **Changes:** feat — split from `user-management.md` covering user
> CRUD operations, AccountStatus state machine with 8 states, profile editing, role-specific
> Livewire managers, super admin protection, batch operations, password reset, account lifecycle
> auto-inactivation, and recovery key management

## Description

Focused specification of Internara's user CRUD and account status subsystem. Defines the
role-specific Livewire managers (UserManager, StudentManager, TeacherManager, SupervisorManager,
AdminManager), the AccountStatus state machine with 8 states and transition guards, user
creation/update/delete lifecycle, profile editing, batch operations (lock/unlock/delete),
password reset via token revocation, account lifecycle automation (auto-inactivate, mass archive),
and the triple-layer super admin protection system. Extracted from the broader user management
spec to isolate the core user administration and state machine concerns.

---

## 1. Problem Statements

### PS-1 — Multi-Role User Administration

Schools manage students, teachers, supervisors, and administrators — each with different data
requirements and operational needs. A single generic user management interface cannot efficiently
handle the distinct fields (student: department, NIS; teacher: NIP, subjects; supervisor:
company) without becoming unwieldy. Role-specific management interfaces with tailored columns,
filters, and form fields are needed.

### PS-2 — Account Lifecycle Complexity

User accounts progress through 8 states (provisioned → activated → verified → ... → archived)
with strict transition rules. Without a state machine, administrators could put accounts in
inconsistent states (e.g., directly archiving a provisioned account or restoring an archived
account). The state machine must enforce valid transitions, define terminal states, and prevent
illegal operations at every layer.

### PS-3 — Bulk Operations at Scale

Schools may have 500+ students to onboard or manage. Manual one-by-one creation and status
changes are impractical. Batch lock/unlock, batch delete, and CSV import must handle duplicates
gracefully, generate credentials automatically, skip protected accounts (self, super admin),
and provide clear feedback (imported/skipped/failed counts) without exhausting memory.

### PS-4 — Super Admin Protection

The super admin account is the system's break-glass access. If deleted or locked, the school
could be permanently locked out. A single protection layer is insufficient — observers can be
bypassed via tinker, actions via direct calls, UIs via API. Multiple independent protection
layers are needed: model observer, action guard, Livewire UI guard, and CLI recovery command.

### PS-5 — Account Lifecycle Automation

Without automated lifecycle management, inactive accounts accumulate indefinitely, consuming
resources and creating security surface area. The system must periodically transition long-unused
accounts to inactive status and support mass archival of completed program cohorts while
preserving the ability to recover access via recovery keys.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide role-specific management interfaces (User, Student, Teacher, Supervisor, Admin) via Livewire |
| G2  | Enforce AccountStatus state machine with 8 states and valid transition guards |
| G3  | Support batch operations: delete, lock, unlock with skip-on-protected logic |
| G4  | Support profile editing with personal, emergency, and role-specific fields |
| G5  | Protect super admin from deletion, lockout, name change, and username change |
| G6  | Auto-inactivate accounts after configurable inactivity period |
| G7  | Mass-archive student accounts via chunked queries |
| G8  | Store and retrieve recovery keys for super admin account recovery |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | User self-registration (admin-created accounts only) |
| NG2  | Role permission editing (handled by separate RBAC system via Spatie) |
| NG3  | CSV import/export (covered in [csv-import-export.md](csv-import-export.md)) |
| NG4  | Account slip PDF generation (covered in [account-slips.md](account-slips.md)) |
| NG5  | User activity monitoring beyond audit log |
| NG6  | Multi-tenant user isolation |
| NG7  | User impersonation / login-as |

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
6. Creates User + Profile + syncs roles in a database transaction
7. Sends activation token notification via `ActivationCodeNotification`
8. If password was auto-generated, sends `WelcomeNotification` with the plain password
9. Redirects to account slip page for credential distribution
**Postconditions:** User created, activation email sent, account slip ready for download

### UC-2 — Admin Updates User Details

**Actor:** Admin / Super Admin
**Preconditions:** Target user exists, admin is authenticated
**Flow:**
1. Admin clicks edit on a user row in the manager table
2. `editUser()` loads user data into the form modal (blocked for super admin)
3. Admin modifies name, email, profile fields, roles
4. `UpdateUserAction` validates uniqueness (excluding current user), persists atomically
5. `UserUpdated` event dispatched
**Postconditions:** User and profile updated, event dispatched

### UC-3 — Admin Locks Multiple Accounts

**Actor:** Admin
**Preconditions:** Users selected in the table, admin is authenticated
**Flow:**
1. Admin selects multiple users via checkboxes
2. Clicks "Lock" bulk action
3. `SetUserStatusAction` called for each selected user with `AccountStatus::SUSPENDED`
4. Transition guard validates: current status must allow transition to SUSPENDED
5. Self-locking is rejected (cannot change own status)
6. Super admin locking is rejected (integrity rules)
7. `UserStatusChanged` event dispatched for each locked user
8. `AccountStatusNotification` sent to each locked user
**Postconditions:** Selected accounts locked, users notified, invalid transitions rejected

### UC-4 — Super Admin Cannot Be Deleted or Modified

**Actor:** System (protection guard)
**Preconditions:** Someone attempts to delete or modify the super admin account
**Flow:**
1. Delete action is called on super admin user
2. `UserObserver::deleting()` intercepts, checks `hasRole('superadmin')`
3. Throws `RejectedException` — deletion blocked at model level
4. `DeleteUserAction` has explicit guard: `$user->hasRole('super_admin')` — blocked at action level
5. `UserManager::editUser()` blocks edit modal for super admin — blocked at UI level
6. `UpdateUserAction` rejects name and username changes via `SuperAdminIntegrityRules`
**Postconditions:** Super admin protected at observer, action, and UI layers

### UC-5 — Admin Resets User Password

**Actor:** Admin
**Preconditions:** Target user exists, admin is authenticated
**Flow:**
1. Admin clicks "Reset Password" on a user row
2. `RevokeUserActivationTokensAction` revokes all activation tokens for the user
3. Flash message confirms password reset
4. User must go through activation flow again to set new password
**Postconditions:** Activation tokens revoked, user locked out until re-activation

### UC-6 — System Auto-Inactivates Dormant Accounts

**Actor:** System (scheduled artisan command)
**Preconditions:** `accounts:auto-inactivate` command is run (scheduled daily)
**Flow:**
1. Command queries users with `VERIFIED` status and `last_activity_at` older than threshold (default 90 days)
2. Excludes super admin accounts
3. `SetUserStatusAction` transitions each to `INACTIVE` with reason
4. Progress bar displayed, summary reported
5. `--dry-run` flag available for preview without changes
**Postconditions:** Dormant accounts transitioned to INACTIVE, super admin excluded

### UC-7 — Admin Mass-Archives Student Accounts

**Actor:** Admin / Super Admin
**Preconditions:** Student cohort completed, admin is authenticated
**Flow:**
1. Admin triggers mass archive from Student Manager
2. `ArchiveStudentAccountsAction` receives a query builder
3. Processes users in chunks of 100 to prevent memory exhaustion
4. Skips super admin accounts
5. Sets each user status to `ARCHIVED` via `setStatus()`
**Postconditions:** Student accounts archived, super admin skipped, count returned

---

## 4. Functional Requirements

### User CRUD

| ID    | Requirement |
| ----- | ----------- |
| FR-UC1 | `CreateUserAction` must validate name (required, max 255, not reserved), username (unique, system format, not reserved), email (unique, valid) |
| FR-UC2 | `CreateUserAction` must auto-generate username from email if not provided, with collision resolution via `UserIdentifierGenerator` |
| FR-UC3 | `CreateUserAction` must auto-generate 12-char random password if not provided |
| FR-UC4 | `CreateUserAction` must create User + Profile + sync roles in a single database transaction |
| FR-UC5 | `CreateUserAction` must send `ActivationCodeNotification` with activation token via `AccessToken::generateFor()` |
| FR-UC6 | `CreateUserAction` must send `WelcomeNotification` with plain password when password was auto-generated |
| FR-UC7 | `UpdateUserAction` must support atomic update of user data + profile (via `updateOrCreate`) + roles (via `syncRoles`) |
| FR-UC8 | `UpdateUserAction` must prevent name and username changes on super admin via `SuperAdminIntegrityRules` |
| FR-UC9 | `DeleteUserAction` must prevent deletion of super admin (`hasRole('super_admin')`) and self (`Auth::id()`) |
| FR-UC10 | `BatchDeleteUserAction` must skip self and super_admin in batch operations, returning `['deleted' => int, 'skipped' => int]` |
| FR-UC11 | All CRUD actions must extend `BaseCommandAction` and use `$this->transaction()` for atomic operations |
| FR-UC12 | All CRUD actions must dispatch domain events (`UserCreated`, `UserUpdated`, `UserDeleted`) after successful operations |

### Account Status State Machine

| ID     | Requirement |
| ------ | ----------- |
| FR-AS1 | `AccountStatus` enum must implement `StatusEnum` and `ColorableEnum` contracts |
| FR-AS2 | Must define 8 states: `PROVISIONED`, `ACTIVATED`, `VERIFIED`, `PROTECTED`, `RESTRICTED`, `SUSPENDED`, `INACTIVE`, `ARCHIVED` |
| FR-AS3 | `allowsLogin()` must return `true` for ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, INACTIVE and `false` for PROVISIONED, SUSPENDED, ARCHIVED |
| FR-AS4 | `isTerminal()` must return `true` for PROTECTED and ARCHIVED — no further transitions allowed |
| FR-AS5 | `validTransitions()` must define: PROVISIONED→{ACTIVATED,SUSPENDED}, ACTIVATED→{VERIFIED,SUSPENDED,ARCHIVED}, VERIFIED→{RESTRICTED,SUSPENDED,ARCHIVED,INACTIVE}, PROTECTED→{}, RESTRICTED→{VERIFIED,SUSPENDED,ARCHIVED}, SUSPENDED→{ACTIVATED,VERIFIED,ARCHIVED}, INACTIVE→{VERIFIED,ARCHIVED,SUSPENDED}, ARCHIVED→{} |
| FR-AS6 | `canTransitionTo()` must return `false` for all transitions from terminal states |
| FR-AS7 | `SetUserStatusAction` must validate transitions via `canTransitionTo()` before applying status change |
| FR-AS8 | `SetUserStatusAction` must reject self-status-change (`auth()->id() === $user->id`) and super admin status change |
| FR-AS9 | `ToggleUserStatusAction` must toggle between VERIFIED ↔ SUSPENDED only |
| FR-AS10 | Status changes must dispatch `UserStatusChanged` event |
| FR-AS11 | Status changes must send `AccountStatusNotification` to the affected user |
| FR-AS12 | `color()` must return: PROVISIONED→warning, ACTIVATED→info, VERIFIED→success, PROTECTED→primary, RESTRICTED→warning, SUSPENDED→error, INACTIVE→warning, ARCHIVED→error |
| FR-AS13 | `label()` must return translated string via `__('account_status.status.'.$this->value)` |

### Profile Management

| ID     | Requirement |
| ------ | ----------- |
| FR-PM1 | `Profile` model must belong to User, Department, and Company via `BelongsTo` relationships |
| FR-PM2 | `Profile` must cast `gender` to `Gender` enum, `blood_type` to `BloodType` enum, `dob` to date, `emergency_contact` to JSON |
| FR-PM3 | `Profile` must use `#[Fillable]` attribute with: user_id, phone, address, bio, gender, blood_type, pob, dob, emergency_contact, id_number, national_id_number, competence_field, employment_status, job_title, internal_notes, department_id, company_id |
| FR-PM4 | `UpdateProfileAction` must validate and persist profile changes with upsert semantics (`updateOrCreate`) |
| FR-PM5 | Profile changes must dispatch `ProfileUpdated` event |
| FR-PM6 | Profile must be guarded by `ProfilePolicy` (admin or owner access) |

### Livewire Managers

| ID     | Requirement |
| ------ | ----------- |
| FR-LM1 | `UserManager` must extend `BaseRecordManager` with full CRUD, search, filter, and pagination |
| FR-LM2 | `UserManager` must support search across name, email, username, and phone (via profile relation) |
| FR-LM3 | `UserManager` must support filters: role, status, created_from, created_to |
| FR-LM4 | `UserManager` must display columns: name, email, profile.phone, roles_list, status, actions |
| FR-LM5 | `UserManager` must eagerly load `roles` and `profile` to prevent N+1 queries |
| FR-LM6 | `UserManager` must provide computed `roles()` excluding super_admin and admin roles |
| FR-LM7 | `UserManager` must provide computed `statusOptions()` excluding PROTECTED and ARCHIVED |
| FR-LM8 | `StudentManager` must add department filter |
| FR-LM9 | `TeacherManager` must include id_number column |
| FR-LM10 | `SupervisorManager` must include company column |
| FR-LM11 | `AdminManager` must be restricted to admin-only access |
| FR-LM12 | All managers must enforce policy checks in `boot()` via `$this->authorize('viewAny', User::class)` |

### Super Admin Protection

| ID     | Requirement |
| ------ | ----------- |
| FR-SAP1 | `UserObserver::deleting()` must throw `RejectedException` when deleting a user with `superadmin` role |
| FR-SAP2 | `User::delete()` must call `asSuperAdminIntegrityRules()->canBeDeleted()` and throw `RejectedException` if false |
| FR-SAP3 | `DeleteUserAction` must check `$user->hasRole('super_admin')` before deletion |
| FR-SAP4 | `UserManager::editUser()` must block edit modal with flash error for super admin |
| FR-SAP5 | `UserManager::confirmAction()` must check super admin role before deletion confirmation |
| FR-SAP6 | `UpdateUserAction` must reject name changes via `integrity->canChangeName()` and username changes via `integrity->canChangeUsername()` |
| FR-SAP7 | `SetUserStatusAction` must reject status changes on super admin via `integrity->canBeLocked()` |
| FR-SAP8 | `ToggleUserStatusAction` must reject toggle on super admin via `integrity->canBeLocked()` |
| FR-SAP9 | `BatchDeleteUserAction` must skip super admin in batch iterations |

### Account Lifecycle

| ID     | Requirement |
| ------ | ----------- |
| FR-AL1 | `AutoInactivateAccounts` artisan command (`accounts:auto-inactivate`) must transition VERIFIED accounts to INACTIVE after configurable threshold (default 90 days) based on `last_activity_at` |
| FR-AL2 | `AutoInactivateAccounts` must exclude super admin accounts and support `--dry-run` flag |
| FR-AL3 | `ArchiveStudentAccountsAction` must mass-archive users via chunked query (chunk size 100) |
| FR-AL4 | `ArchiveStudentAccountsAction` must skip super admin accounts |
| FR-AL5 | `RevokeUserActivationTokensAction` must revoke all activation tokens for a user via `AccessToken::revokeFor()` |
| FR-AL6 | `SaveRecoveryKeyAction` must store recovery key in `storage/app/private/.recovery-key` with `0600` file permissions |
| FR-AL7 | `ReadRecoveryKeyAction` must read `.recovery-key` from private storage, skipping comment lines (starting with `#`) and empty lines |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-P1 | User list query must use eager loading (`roles`, `profile`) to prevent N+1 — enforced in `UserManager::query()` |
| NFR-P2 | `ArchiveStudentAccountsAction` must process rows in chunks of 100 to prevent memory exhaustion |
| NFR-P3 | Batch operations must skip invalid targets (self, super_admin) without failing the entire batch |
| NFR-S1 | Super admin must be protected at three independent layers: model observer, action guard, and Livewire UI guard |
| NFR-S2 | Password must never be stored in plain text — `Hash::make()` required in all creation/update paths |
| NFR-S3 | Recovery key file must have `0600` permissions (owner read/write only) |
| NFR-S4 | Account status transitions must be validated via `canTransitionTo()` before application |
| NFR-S5 | Self-status-change must be rejected in `SetUserStatusAction` and `ToggleUserStatusAction` |
| NFR-S6 | `SetUserStatusAction` must support `skipAuthCheck` parameter for system-initiated transitions (e.g., auto-inactivate) |
| NFR-R1 | `BatchDeleteUserAction` must return `['deleted' => int, 'skipped' => int]` — partial success is acceptable |
| NFR-R2 | `CreateUserAction` notification failures must be caught and logged, not propagated to caller |
| NFR-U1 | Flash messages must confirm success/failure for every CRUD operation via `flash()->success()` / `flash()->error()` |
| NFR-U2 | `UserManager` status badges must include translated text labels alongside color indicators via `LabelEnum::label()` |
| NFR-U3 | Confirmation modals for delete/lock/unlock must be presented before destructive actions |
| NFR-M1 | User CRUD must use Action classes, not inline Livewire logic — `UserManager` delegates to `CreateUserAction`, `UpdateUserAction`, `DeleteUserAction` |
| NFR-M2 | All form validation must use Form Objects (`UserForm`), not inline rules |
| NFR-M3 | All actions must extend `BaseCommandAction` (write) or `BaseReadAction` (read) and use `$this->log()` for audit |
| NFR-A1 | All user management UI must meet WCAG 2.1 Level AA |
| NFR-A2 | Account status badges must include visible text labels, not color alone |
| NFR-A3 | Bulk action confirmation modals must trap focus and be keyboard-navigable |
| NFR-L1 | All user-facing strings in user management UI must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Account status labels must use `LabelEnum::label()` which calls `__()` internally |

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
    case PROTECTED = 'protected';       // color: primary, allowsLogin: true,  terminal
    case RESTRICTED = 'restricted';     // color: warning, allowsLogin: true
    case SUSPENDED = 'suspended';       // color: error,   allowsLogin: false
    case INACTIVE = 'inactive';         // color: warning, allowsLogin: true
    case ARCHIVED = 'archived';         // color: error,   allowsLogin: false, terminal

    // Transition map:
    // PROVISIONED → {ACTIVATED, SUSPENDED}
    // ACTIVATED   → {VERIFIED, SUSPENDED, ARCHIVED}
    // VERIFIED    → {RESTRICTED, SUSPENDED, ARCHIVED, INACTIVE}
    // PROTECTED   → {} (terminal)
    // RESTRICTED  → {VERIFIED, SUSPENDED, ARCHIVED}
    // SUSPENDED   → {ACTIVATED, VERIFIED, ARCHIVED}
    // INACTIVE    → {VERIFIED, ARCHIVED, SUSPENDED}
    // ARCHIVED    → {} (terminal)

    public function color(): string { /* ... */ }
    public function label(): string { /* __('account_status.status.'.$this->value) */ }
    public function allowsLogin(): bool { /* ... */ }
    public function isTerminal(): bool { /* ARCHIVED, PROTECTED → true */ }
    public function validTransitions(): array { /* ... */ }
    public function canTransitionTo(StatusEnum $target): bool { /* ... */ }
}
```

### 6.2 CreateUserAction

```php
// app/User/UserManagement/Actions/CreateUserAction.php
final class CreateUserAction extends BaseCommandAction
{
    public function execute(
        array $userData,           // name, email, username?, password?, setup_required?
        array $profileData = [],   // phone, address, bio, gender, pob, dob, emergency_*
        array $roles = [],
        bool $sendNotification = true,
    ): User;
    // Creates User + Profile + syncs roles in transaction
    // Sends ActivationCodeNotification + optional WelcomeNotification
    // Dispatches UserCreated event
}
```

### 6.3 UpdateUserAction

```php
// app/User/UserManagement/Actions/UpdateUserAction.php
final class UpdateUserAction extends BaseCommandAction
{
    public function execute(
        User $user,
        array $userData,          // name, email, username?, password?, setup_required?, locked_at?, locked_reason?
        ?array $profileData = null,
        ?array $roles = null,
    ): User;
    // Atomic update with super_admin guard on name/username changes
    // Uses profile()->updateOrCreate() for profile data
    // Dispatches UserUpdated event
}
```

### 6.4 DeleteUserAction

```php
// app/User/UserManagement/Actions/DeleteUserAction.php
final class DeleteUserAction extends BaseCommandAction
{
    public function execute(User $user): void;
    // Guards: super_admin check, self-delete check
    // Dispatches UserDeleted event
}
```

### 6.5 BatchDeleteUserAction

```php
// app/User/UserManagement/Actions/BatchDeleteUserAction.php
final class BatchDeleteUserAction extends BaseCommandAction
{
    public function __construct(protected readonly DeleteUserAction $deleteAction) {}

    /** @return array{deleted: int, skipped: int} */
    public function execute(array $ids): array;
    // Skips: self (auth()->id()), super_admin, not-found users
    // Delegates each deletion to DeleteUserAction
}
```

### 6.6 SetUserStatusAction

```php
// app/User/UserManagement/Actions/SetUserStatusAction.php
final class SetUserStatusAction extends BaseCommandAction
{
    public function execute(
        User $user,
        AccountStatus $newStatus,
        ?string $reason = null,
        bool $skipAuthCheck = false,
    ): User;
    // Guards: self-change rejection, super admin integrity, transition validation
    // Sets status via $user->setStatus(), dispatches UserStatusChanged, sends notification
}
```

### 6.7 ToggleUserStatusAction

```php
// app/User/UserManagement/Actions/ToggleUserStatusAction.php
final class ToggleUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, ?string $reason = null): User;
    // Toggles: VERIFIED ↔ SUSPENDED only
    // Guards: self-change rejection, super admin integrity
}
```

### 6.8 UserManager Livewire

```php
// app/User/UserManagement/Livewire/UserManager.php
class UserManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips, WithFileUploads;

    // Properties: userModal, showConfirm, confirmActionType, confirmTarget,
    //   showStatusModal, statusTarget, selectedStatus, statusReason, importFile
    // Form: UserForm $form

    // Lifecycle: boot() — authorize('viewAny', User::class)
    // Table: headers() — name, email, profile.phone, roles_list, status, actions
    // Query: query() — User::with(['roles', 'profile'])
    // Search: applySearch() — name, email, username, profile.phone
    // Filters: applyFilters() — role, status, created_from, created_to
    // Computed: roles(), statusOptions(), stats()

    // CRUD: createUser(), editUser(id), saveUser(CreateUserAction, UpdateUserAction)
    // Delete: askDeleteUser(id), askDeleteSelected(), confirmAction(DeleteUserAction, BatchDeleteUserAction)
    // Status: lockSelected(SetUserStatusAction), unlockSelected(SetUserStatusAction)
    // Password: resetPassword(id, RevokeUserActivationTokensAction)
    // Import/Export: import(), export(), exportSelected(), downloadTemplate()
    // Slips: trait DownloadsAccountSlips
}
```

### 6.9 StudentManager Livewire

```php
// app/User/UserManagement/Livewire/StudentManager.php
// Extends UserManager pattern with department-specific columns and filters
// Adds department filter, student-specific form fields
```

### 6.10 TeacherManager Livewire

```php
// app/User/UserManagement/Livewire/TeacherManager.php
// Extends UserManager pattern with teacher-specific columns
// Includes id_number column (NIP)
```

### 6.11 SupervisorManager Livewire

```php
// app/User/UserManagement/Livewire/SupervisorManager.php
// Extends UserManager pattern with company-specific columns
// Includes company column and company filter
```

### 6.12 AdminManager Livewire

```php
// app/User/UserManagement/Livewire/AdminManager.php
// Restricted to admin-only access
// Shows admin-specific columns
```

### 6.13 User Model

```php
// app/User/Models/User.php
class User extends BaseAuthenticatable implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, Notifiable;
    use HasRoles { /* overridden: hasRole, hasAnyRole, hasAllRoles, assignRole,
                       removeRole, syncRoles, scopeRole, scopeWithoutRole */ }

    // #[Fillable]: name, email, username, password, setup_required, first_login_at,
    //   locked_at, locked_reason, status, is_active
    // #[Hidden]: password, remember_token

    // Casts: email_verified_at → datetime, locked_at → datetime, first_login_at → datetime,
    //   password → hashed, setup_required → boolean, status → AccountStatus, is_active → boolean

    // Relations: profile() → HasOne, registrations() → HasMany
    // Media: avatar collection (singleFile), thumb conversion (200x200 webp)
    // Entity bridges: asStudent(), asTeacher(), asSupervisor(), asAdmin(), asApprentice(),
    //   asAccountActivation(), asSuperAdminIntegrityRules()

    // Scopes: scopeLocked(), scopeUnlocked(), scopeActive(), scopeRoleType()
    // Delete guard: asSuperAdminIntegrityRules()->canBeDeleted()
}
```

### 6.14 Profile Model

```php
// app/User/Profile/Models/Profile.php
class Profile extends BaseModel
{
    // #[Fillable]: user_id, phone, address, bio, gender, blood_type, pob, dob,
    //   emergency_contact, id_number, national_id_number, competence_field,
    //   employment_status, job_title, internal_notes, department_id, company_id

    // Casts: gender → Gender, blood_type → BloodType, dob → date, emergency_contact → json
    // Relations: user() → BelongsTo User, department() → BelongsTo Department,
    //   company() → BelongsTo Company
}
```

### 6.15 Lifecycle Actions

```php
// app/User/UserManagement/Actions/RevokeUserActivationTokensAction.php
final class RevokeUserActivationTokensAction extends BaseCommandAction
{
    public function execute(User $user): void;
    // Revokes all activation tokens via AccessToken::revokeFor($user, 'activation')
}

// app/User/UserManagement/Actions/ArchiveStudentAccountsAction.php
final class ArchiveStudentAccountsAction extends BaseCommandAction
{
    public function execute(Builder $query): int;
    // Chunks users (100), skips super_admin, sets ARCHIVED status, returns count
}

// app/User/UserManagement/Actions/SaveRecoveryKeyAction.php
final class SaveRecoveryKeyAction extends BaseCommandAction
{
    public function execute(string $plaintext): string;
    // Writes to storage/app/private/.recovery-key with 0600 permissions, returns path
}

// app/User/UserManagement/Actions/ReadRecoveryKeyAction.php
final class ReadRecoveryKeyAction extends BaseReadAction
{
    public function execute(): ?string;
    // Reads .recovery-key from private storage, strips comments/blanks, returns key or null
}
```

### 6.16 AutoInactivateAccounts Command

```php
// app/User/UserManagement/Console/Commands/AutoInactivateAccounts.php
class AutoInactivateAccounts extends Command
{
    protected $signature = 'accounts:auto-inactivate
        {--days=90 : Number of days since last activity before marking inactive}
        {--dry-run : List accounts that would be inactivated without making changes}';

    protected $description = 'Transition VERIFIED accounts to INACTIVE after extended inactivity';

    // Queries: VERIFIED status, last_activity_at < threshold, excludes super_admin
    // Uses SetUserStatusAction with skipAuthCheck: true
}
```

### 6.17 UserObserver

```php
// app/User/Observers/UserObserver.php
class UserObserver
{
    public function deleting(User $user): void
    {
        // Checks $user->hasRole('superadmin') — throws RejectedException if true
    }
}
```

### 6.18 Database Schema — `users` Table

```sql
CREATE TABLE users (
    id              UUID PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    username        VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at  TIMESTAMP NULL,
    password        VARCHAR(255) NOT NULL,
    remember_token  VARCHAR(100) NULL,
    setup_required  BOOLEAN DEFAULT FALSE,
    first_login_at  TIMESTAMP NULL,
    locked_at       TIMESTAMP NULL,
    locked_reason   VARCHAR(255) NULL,
    status          VARCHAR(20) DEFAULT 'provisioned',
    is_active       BOOLEAN DEFAULT TRUE,
    last_activity_at TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_locked_at (locked_at),
    INDEX idx_setup_required (setup_required),
    INDEX idx_is_active (is_active)
);
```

### 6.19 Database Schema — `profiles` Table

```sql
CREATE TABLE profiles (
    id                  UUID PRIMARY KEY,
    user_id             UUID NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    phone               VARCHAR(255) NULL,
    address             TEXT NULL,
    bio                 TEXT NULL,
    gender              VARCHAR(20) NULL,
    blood_type          VARCHAR(10) NULL,
    pob                 VARCHAR(255) NULL,      -- Place of Birth
    dob                 DATE NULL,              -- Date of Birth
    emergency_contact   JSON NULL,
    id_number           VARCHAR(50) NULL,       -- NISN/NIP/industry registration
    national_id_number  VARCHAR(20) NULL,       -- NISN lifelong number
    competence_field    VARCHAR(255) NULL,
    employment_status   VARCHAR(30) NULL,
    job_title           VARCHAR(255) NULL,
    internal_notes      TEXT NULL,
    department_id       UUID NULL REFERENCES departments(id) ON DELETE SET NULL,
    company_id          UUID NULL REFERENCES companies(id) ON DELETE SET NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_department_id (department_id),
    INDEX idx_company_id (company_id)
);
```

---

## 7. Design Decisions

### DD-1 — Role-Specific Managers Over Generic User Manager

**Decision:** Four separate Livewire managers (User, Student, Teacher, Supervisor) plus an Admin
manager instead of one generic manager with role tabs.
**Rationale:** Each role has distinct data requirements (student: department; teacher: NIP/ID
number; supervisor: company). Separate managers allow role-specific columns, filters, and form
fields without conditional logic cluttering a single component. The base `BaseRecordManager`
provides shared functionality (search, pagination, bulk actions, selection). Shared traits
(`DownloadsAccountSlips`) eliminate duplication for cross-cutting concerns.
**Trade-off:** More files to maintain (5 managers + forms). Mitigated by shared base class and
consistent patterns — each manager typically adds only 10-30 lines of role-specific code.

### DD-2 — AccountStatus as State Machine Enum

**Decision:** Account status implemented as a PHP 8.1 backed enum with `validTransitions()`
method returning allowed target states for each source state.
**Rationale:** State machines prevent illegal transitions at the type level. An enum with explicit
transition rules is self-documenting and testable — every state and transition can be verified in
unit tests. Database columns store the string value; the enum handles all logic. Terminal states
(PROTECTED, ARCHIVED) return empty transition arrays and `canTransitionTo()` always returns false.
**Trade-off:** Adding new states requires updating the enum, all transition maps, and color/label
methods. Mitigated by the enum being the single source of truth — there is exactly one place to
update.

### DD-3 — Triple-Layer Super Admin Protection

**Decision:** Super admin protected at observer (`UserObserver::deleting`), action (`DeleteUserAction`,
`UpdateUserAction`, `SetUserStatusAction`), and UI (`UserManager::editUser`, `UserManager::confirmAction`)
layers, plus model-level guard (`User::delete()` calling `canBeDeleted()`).
**Rationale:** Defense in depth — any single layer could be bypassed (observer via tinker, action
via direct call, UI via API). All layers must be checked independently. The observer is the final
safety net. `SuperAdminIntegrityRules` entity encapsulates all super admin checks (canBeDeleted,
canBeLocked, canChangeName, canChangeUsername) in a single testable contract.
**Trade-off:** Redundant checks across 4 layers. Intentional — the cost of accidentally losing
super admin access is system-wide lockout. Recovery requires CLI command (`php artisan admin:recover`).

### DD-4 — CSV Import with Skip-on-Duplicate

**Decision:** CSV import skips rows with existing emails, does not update or merge.
**Rationale:** Import is for onboarding new users, not updating existing ones. Updating via CSV
would require conflict resolution logic (which fields to overwrite?) and audit trail complexity.
Skip-on-duplicate is predictable, safe, and provides clear feedback via `CsvRowResult::SKIPPED`.
**Trade-off:** Admin must manually update existing users. Acceptable because updates are infrequent
compared to initial onboarding.

### DD-5 — Chunked Processing for Mass Operations

**Decision:** `ArchiveStudentAccountsAction` uses `chunk(100)` instead of loading all records.
**Rationale:** Schools may archive 500+ student accounts at once. Loading all into memory risks
OOM. Chunked processing processes 100 users at a time, freeing memory between chunks. The same
pattern applies to CSV import via `CsvHandler`.
**Trade-off:** Slightly more complex code than a single `->get()->each()`. Necessary for
reliability at scale.

---

## 8. Success Metrics

### 8.1 CRUD Operations

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| User creation | < 1s including notifications | `CreateUserAction` transaction time |
| User update | < 500ms including profile upsert | `UpdateUserAction` transaction time |
| User deletion | < 200ms per user | `DeleteUserAction` transaction time |
| Batch delete (100 users) | < 10s, skips self + super_admin | `BatchDeleteUserAction` execution time |

### 8.2 Account Status Transitions

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Transition validation | 100% of illegal transitions rejected | `canTransitionTo()` unit tests |
| Terminal states | PROTECTED and ARCHIVED block all transitions | `isTerminal()` method tests |
| Self-change rejection | 100% of self-status-change attempts rejected | `SetUserStatusAction` tests |
| Notification delivery | Status change → user notified | `AccountStatusNotification` dispatch verification |

### 8.3 Super Admin Protection

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Triple protection | Observer + Action + UI + Model guard | All four layers verified |
| Name/username immutability | Super admin name/username changes rejected | `UpdateUserAction` tests |
| Status immutability | Super admin status changes rejected | `SetUserStatusAction` tests |
| Recovery | CLI recovery always works | `php artisan admin:recover` command test |

### 8.4 Account Lifecycle

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Auto-inactivate accuracy | Only VERIFIED accounts with expired activity | `AutoInactivateAccounts --dry-run` output |
| Mass archive memory | < 50MB peak for 500 users | `ArchiveStudentAccountsAction` chunk test |
| Recovery key storage | File exists with 0600 permissions | `ReadRecoveryKeyAction` test |
| Super admin exclusion | Lifecycle commands never touch super admin | Exclusion filter in queries |

---

## Quick References

- `app/User/Enums/AccountStatus.php` — 8-state status machine enum
- `app/User/Models/User.php` — user model with roles, media, entity bridges, delete guard
- `app/User/Profile/Models/Profile.php` — profile model with fillable fields, casts, relations
- `app/User/Observers/UserObserver.php` — super admin deletion guard at model level
- `app/User/UserManagement/Actions/CreateUserAction.php` — user creation with notifications
- `app/User/UserManagement/Actions/UpdateUserAction.php` — atomic user update with super admin guard
- `app/User/UserManagement/Actions/DeleteUserAction.php` — single user deletion with guards
- `app/User/UserManagement/Actions/BatchDeleteUserAction.php` — batch deletion with skip logic
- `app/User/UserManagement/Actions/SetUserStatusAction.php` — status change with transition guard
- `app/User/UserManagement/Actions/ToggleUserStatusAction.php` — VERIFIED ↔ SUSPENDED toggle
- `app/User/UserManagement/Actions/RevokeUserActivationTokensAction.php` — token revocation
- `app/User/UserManagement/Actions/ArchiveStudentAccountsAction.php` — mass student archival
- `app/User/UserManagement/Actions/SaveRecoveryKeyAction.php` — recovery key storage (0600)
- `app/User/UserManagement/Actions/ReadRecoveryKeyAction.php` — recovery key retrieval
- `app/User/UserManagement/Actions/ReadUserManagerStatsAction.php` — manager statistics
- `app/User/UserManagement/Livewire/UserManager.php` — main user management component
- `app/User/UserManagement/Livewire/StudentManager.php` — student-specific manager
- `app/User/UserManagement/Livewire/TeacherManager.php` — teacher-specific manager
- `app/User/UserManagement/Livewire/SupervisorManager.php` — supervisor-specific manager
- `app/User/UserManagement/Livewire/AdminManager.php` — admin-only manager
- `app/User/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php` — PDF download/send trait
- `app/User/UserManagement/Livewire/Forms/UserForm.php` — form object for user CRUD
- `app/User/UserManagement/Console/Commands/AutoInactivateAccounts.php` — 90-day inactivity command
- `app/User/UserManagement/Events/UserCreated.php` — user creation event
- `app/User/UserManagement/Events/UserUpdated.php` — user update event
- `app/User/UserManagement/Events/UserDeleted.php` — user deletion event
- `app/User/UserManagement/Events/UserStatusChanged.php` — status change event
- `app/User/UserManagement/Notifications/ActivationCodeNotification.php` — activation email
- `app/User/AccountStatus/Notifications/AccountStatusNotification.php` — status change notification
- `app/User/Profile/Actions/UpdateProfileAction.php` — profile editing
- `app/User/Profile/Actions/ReadProfileFormAction.php` — profile form data
- `app/User/Profile/Livewire/ProfileEditor.php` — profile editing component
- `app/User/Services/UserIdentifierGenerator.php` — username generation
- `database/migrations/2026_01_02_000001_create_users_table.php` — users table schema
- `database/migrations/2026_01_02_000006_create_profiles_table.php` — profiles table schema
- `docs/modules/user.md` — User module overview
- `docs/modules/user-reference.md` — User module technical reference
