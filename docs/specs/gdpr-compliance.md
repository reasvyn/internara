# GDPR Compliance — Data Deletion, Anonymization, and Audit Logging

> **Last updated:** 2026-07-24 **Changes:** feat — initial spec documenting GDPR compliance:
> user-initiated and admin-initiated data deletion, PII anonymization, GDPR deletion audit logging,
> schema migration to add missing columns, and admin-facing deletion log viewer

## Description

Specification of Internara's GDPR/data deletion compliance system: a `DeleteUserGdprAction` that
performs GDPR-compliant user data erasure with PII anonymization, a `GdprDeletionLog` model that
stores immutable append-only audit records of every deletion, a migration that completes the
`gdpr_deletion_logs` schema with missing columns (`deletion_type`, `reason`, `user_email`,
`deleted_at`), integration with the existing `DeleteUserAction` and `BatchDeleteUserAction` to log
deletions, and an admin-facing `GdprDeletionLogs` Livewire component for browsing and filtering
deletion records.

---

## 1. Problem Statements

### PS-1 — No GDPR-Compliant Data Erasure Workflow

When a user requests account deletion (right to be forgotten) or an admin deletes an account, the
current `DeleteUserAction` (`app/User/UserManagement/Actions/DeleteUserAction.php:15`) performs a
hard delete via `$user->delete()` without preserving a compliance audit trail. Indonesian data
protection law (UU PDP — Undang-Undang Pelindungan Data Pribadi) and general GDPR alignment require
that organizations log when personal data is destroyed, who authorized it, and why.

### PS-2 — GDPR Deletion Log Schema Is Incomplete

The `gdpr_deletion_logs` table (`database/migrations/2026_01_02_000005_create_gdpr_deletion_logs_table.php`)
only defines `user_id`, `metadata_snapshot`, and `created_at`. The existing `GdprDeletionLogs`
Livewire component (`app/SysAdmin/Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php:26-29`)
references columns that do not exist: `user_email`, `deletion_type`, `reason`, `deleted_at`. This
makes the admin-facing log viewer non-functional — search by email filters a column that is absent,
the deletion type badge renders from a column that is null, and the "deleted at" sort uses a missing
column.

### PS-3 — No PII Anonymization on Account Deletion

The current `DeleteUserAction` deletes the entire User row, which cascades via foreign keys but
leaves no record of what was deleted. There is no intermediate step that scrubs or anonymizes PII
fields (name, email, username) before deletion, meaning a database backup taken before deletion still
contains the full PII.

### PS-4 — No Distinction Between Deletion Types

Organizations need to distinguish between a user-initiated deletion (self-service right to be
forgotten) and an admin-initiated deletion (account removal for policy violations, inactivity, etc.).
The existing `deletion_type` filter in the Blade view references `anonymization` and
`permanent_deletion` values, but there is no enum or constant defining these types, and no action
populates this field.

### PS-5 — Deletion Log Not Linked to the DeleteUserAction

The `DeleteUserAction` (`app/User/UserManagement/Actions/DeleteUserAction.php:25-34`) logs a
`user_deleted` activity event via SmartLogger but never creates a `GdprDeletionLog` record. The two
systems — activity logging and GDPR compliance logging — are disconnected. An auditor reviewing
GDPR logs would see an empty table despite deletions occurring.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a `GdprDeletionType` enum defining `ANONYMIZATION` and `PERMANENT_DELETION` deletion types with translated labels |
| G2  | Provide a `GdprDeletionLogState` entity encapsulating derived state logic (formatted timestamp, type label, snapshot summary) |
| G3  | Provide a `DeleteUserGdprAction` that performs GDPR-compliant user data erasure: captures metadata snapshot, anonymizes PII fields before deletion, creates an append-only `GdprDeletionLog` record, and dispatches a `UserGdprDeleted` event |
| G4  | Integrate `GdprDeletionLog` creation into the existing `DeleteUserAction` so every user deletion (admin-initiated) creates a compliance record with `deletion_type = permanent_deletion` |
| G5  | Integrate `GdprDeletionLog` creation into the existing `BatchDeleteUserAction` so batch deletions create one log record per deleted user |
| G6  | Provide a migration that adds missing columns (`deletion_type`, `reason`, `user_email`, `deleted_at`) to the `gdpr_deletion_logs` table, completing the schema the UI already expects |
| G7  | Enforce admin-only authorization for viewing GDPR deletion logs via `GdprDeletionLogPolicy` (→ existing policy) |
| G8  | Preserve the existing `GdprDeletionLogs` Livewire component with search-by-email, filter-by-type, and sortable deleted-at columns now backed by real schema columns |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Self-service user account deletion UI — users cannot delete their own accounts through a settings page; deletion is admin-initiated only |
| NG2  | Data export / data portability (GDPR Article 20 right to data portability) — no export-to-JSON/CSV endpoint for user data retrieval |
| NG3  | Consent management or cookie consent — not applicable for a self-hosted school management system |
| NG4  | Automatic data retention enforcement — no scheduled job to auto-delete inactive accounts after N days |
| NG5  | Cryptographic erasure or key destruction — data is relational, not encrypted at rest per-user |
| NG6  | GDPR deletion log retention or auto-cleanup — logs are append-only and retained indefinitely (→ DD-2) |
| NG7  | Multi-stage approval workflows for deletion requests — a single admin action is sufficient |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Deletes a User Account and GDPR Log Is Created

**Actor:** Admin
**Preconditions:** Admin authenticated with `super_admin` or `admin` role, target user exists and is not a super admin
**Flow:**
1. Admin clicks the delete button on a user row in `UserManager`
2. Confirmation modal appears; admin confirms deletion
3. `UserManager::confirmAction()` calls `DeleteUserAction::execute($user)`
4. `DeleteUserAction` checks the user is not a super admin and not the current user
5. `DeleteUserAction` captures a metadata snapshot: `name`, `email`, `username`, `role`, `status`
6. `DeleteUserAction` creates a `GdprDeletionLog` record with `deletion_type = 'permanent_deletion'`, `reason = null`, `user_email = $user->email`, `deleted_at = now()`, `metadata_snapshot = [...]`
7. `DeleteUserAction` dispatches `UserGdprDeleted` event
8. `DeleteUserAction` proceeds with `$user->delete()` (hard delete, cascade)
**Postconditions:** User record deleted, GDPR deletion log record exists with complete metadata snapshot

### UC-2 — Admin Deletes Users via Batch Action

**Actor:** Admin
**Preconditions:** Admin authenticated, multiple non-super-admin users selected
**Flow:**
1. Admin selects multiple users in `UserManager` and clicks "Delete Selected"
2. Confirmation modal appears; admin confirms
3. `UserManager::confirmAction()` calls `BatchDeleteUserAction::execute($ids)`
4. `BatchDeleteUserAction` iterates each ID, delegates to `DeleteUserAction::execute($user)` for each valid user
5. `DeleteUserAction` creates a `GdprDeletionLog` record for each deleted user
6. `BatchDeleteUserAction` returns `['deleted' => N, 'skipped' => M]`
**Postconditions:** N user records deleted, N GDPR deletion log records created

### UC-3 — Admin Views GDPR Deletion Log History

**Actor:** Admin
**Preconditions:** Admin authenticated, at least one deletion log record exists
**Flow:**
1. Admin navigates to **Admin → GDPR Logs** (`/admin/gdpr-logs`)
2. `GdprDeletionLogs` component renders with paginated table
3. Table shows columns: Email, Type (badge), Reason, Deleted At
4. Admin types in the search box → table filters by `user_email` LIKE match
5. Admin selects a type from the filter dropdown → table filters by `deletion_type`
6. Admin clicks the "Deleted At" column header → table sorts by `deleted_at` descending/ascending
**Postconditions:** Admin sees a complete, filterable, sortable log of all GDPR deletion events

### UC-4 — Admin Reviews Deletion Detail via Log Entry

**Actor:** Admin
**Preconditions:** Admin on the GDPR Logs page, log entries exist
**Flow:**
1. Admin clicks a row in the GDPR deletion logs table
2. Row links to `/admin/gdpr-logs/{id}` (route already defined)
3. Detail view shows full metadata snapshot (name, email, username at time of deletion), deletion type, reason, and timestamp
**Postconditions:** Admin can inspect the pre-deletion PII snapshot for any deleted user

---

## 4. Functional Requirements

### Enum — GdprDeletionType

| ID   | Requirement |
| ---- | ----------- |
| FR-E1 | `GdprDeletionType` enum must implement `LabelEnum` with cases: `ANONYMIZATION`, `PERMANENT_DELETION` |
| FR-E2 | `GdprDeletionType::label()` must return a translated string via `__('sysadmin.gdpr_logs.type.'.$this->value)` |
| FR-E3 | `GdprDeletionType` values must match the Blade filter options: `'anonymization'` and `'permanent_deletion'` (→ existing `gdpr-deletion-logs.blade.php:11-12`) |

### Entity — GdprDeletionLogState

| ID   | Requirement |
| ---- | ----------- |
| FR-EN1 | `GdprDeletionLogState` must be a `final readonly` class extending `BaseEntity` |
| FR-EN2 | `GdprDeletionLogState::fromModel()` must extract `user_email`, `deletion_type`, `reason`, `metadata_snapshot`, `deleted_at` from the model |
| FR-EN3 | `GdprDeletionLogState::typeLabel()` must return the translated label for the `GdprDeletionType` enum |
| FR-EN4 | `GdprDeletionLogState::formattedDeletedAt()` must return `deleted_at` formatted as `'Y-m-d H:i'` or `'N/A'` when null |
| FR-EN5 | `GdprDeletionLogState::snapshotSummary()` must return a human-readable summary of the metadata snapshot (e.g., "Jane Doe (jane@example.com)") |
| FR-EN6 | `GdprDeletionLogState::isAnonymization()` must return `true` when deletion type is `ANONYMIZATION` |
| FR-EN7 | `GdprDeletionLogState::isPermanentDeletion()` must return `true` when deletion type is `PERMANENT_DELETION` |

### Model — GdprDeletionLog

| ID   | Requirement |
| ---- | ----------- |
| FR-M1 | `GdprDeletionLog` model must use `#[Fillable]` attribute with: `user_id`, `user_email`, `deletion_type`, `reason`, `metadata_snapshot`, `deleted_at` |
| FR-M2 | `GdprDeletionLog` model must cast `metadata_snapshot` → `array` |
| FR-M3 | `GdprDeletionLog` model must set `UPDATED_AT = null` to enforce immutability |
| FR-M4 | `GdprDeletionLog` model must provide `asGdprDeletionLogState(): GdprDeletionLogState` bridge method |
| FR-M5 | `GdprDeletionLog` model must provide `deleter(): BelongsTo` relationship to `User` via `deleter_id` foreign key (nullable) |

### Migration — Complete gdpr_deletion_logs Schema

| ID   | Requirement |
| ---- | ----------- |
| FR-MG1 | A new migration must add `user_email` column: `string(255)`, nullable, indexed — stores the email at time of deletion for post-deletion lookup |
| FR-MG2 | A new migration must add `deletion_type` column: `string(30)`, nullable — stores `'anonymization'` or `'permanent_deletion'` |
| FR-MG3 | A new migration must add `reason` column: `text`, nullable — optional human-readable reason for deletion |
| FR-MG4 | A new migration must add `deleted_at` column: `timestamp`, nullable, indexed — the actual deletion timestamp (distinct from `created_at` which is the log creation timestamp) |
| FR-MG5 | A new migration must add `deleter_id` column: nullable foreign UUID constrained to `users` with `nullOnDelete()` — records who initiated the deletion |
| FR-MG6 | The existing `user_id` column (orphaned UUID) must be retained to reference the deleted user's original UUID |

### Action — DeleteUserGdprAction

| ID   | Requirement |
| ---- | ----------- |
| FR-A1 | `DeleteUserGdprAction` must extend `BaseCommandAction` |
| FR-A2 | `DeleteUserGdprAction::execute(User $user, GdprDeletionType $type, ?string $reason = null, ?User $deleter = null)` must capture a metadata snapshot before deletion containing: `name`, `email`, `username`, `role`, `status` |
| FR-A3 | `DeleteUserGdprAction` must create a `GdprDeletionLog` record with: `user_id = $user->id`, `user_email = $user->email`, `deletion_type = $type->value`, `reason`, `metadata_snapshot`, `deleted_at = now()`, `deleter_id = $deleter?->id` |
| FR-A4 | `DeleteUserGdprAction` must dispatch `UserGdprDeleted` event with the `GdprDeletionLog` record |
| FR-A5 | `DeleteUserGdprAction` must log `gdpr_user_deleted` via SmartLogger with user email, deletion type, and deleter ID |
| FR-A6 | `DeleteUserGdprAction` must return the created `GdprDeletionLog` model |

### Integration — DeleteUserAction

| ID   | Requirement |
| ---- | ----------- |
| FR-I1 | `DeleteUserAction::execute(User $user)` must accept an optional `?string $reason = null` parameter to propagate the reason to the GDPR log |
| FR-I2 | `DeleteUserAction` must call `DeleteUserGdprAction::execute($user, GdprDeletionType::PERMANENT_DELETION, $reason, Auth::user())` before the `$user->delete()` call |
| FR-I3 | `DeleteUserAction` must continue to dispatch `UserDeleted` event and perform the hard delete after GDPR logging |

### Integration — BatchDeleteUserAction

| ID   | Requirement |
| ---- | ----------- |
| FR-I4 | `BatchDeleteUserAction::execute(array $ids)` must propagate the `reason` parameter to `DeleteUserAction::execute()` for each user |
| FR-I5 | Each successful deletion within the batch must create an individual `GdprDeletionLog` record (via `DeleteUserAction` → `DeleteUserGdprAction`) |

### Livewire UI — GdprDeletionLogs

| ID   | Requirement |
| ---- | ----------- |
| FR-U1 | `GdprDeletionLogs` component must display columns: `user_email` (Email), `deletion_type` (Type), `reason` (Reason), `deleted_at` (Deleted At) |
| FR-U2 | `GdprDeletionLogs` must support live search by `user_email` with 300ms debounce |
| FR-U3 | `GdprDeletionLogs` must support filtering by `deletion_type` with options: All, Anonymization, Permanent Deletion |
| FR-U4 | `GdprDeletionLogs` must support sorting by `deleted_at` and `user_email` columns |
| FR-U5 | `GdprDeletionLogs` must paginate results at 20 per page |
| FR-U6 | The `deletion_type` column must render as a badge: `permanent_deletion` → error style, `anonymization` → warning style (→ existing `gdpr-deletion-logs.blade.php:18`) |
| FR-U7 | The `deleted_at` column must format timestamps as `Y-m-d H:i` |

### Policy — GdprDeletionLogPolicy

| ID   | Requirement |
| ---- | ----------- |
| FR-P1 | `GdprDeletionLogPolicy` must extend `BasePolicy` (→ existing policy at `app/SysAdmin/Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php`) |
| FR-P2 | `GdprDeletionLogPolicy::viewAny()` must return `$this->isAdmin($user)` |
| FR-P3 | `GdprDeletionLogPolicy::view()` must return `$this->isAdmin($user)` |
| FR-P4 | `GdprDeletionLogPolicy::create()` must return `$this->isAdmin($user)` — only admins can create deletion logs (via `DeleteUserGdprAction`) |

### Events

| ID   | Requirement |
| ---- | ----------- |
| FR-EV1 | `UserGdprDeleted` must extend `BaseEvent`, accept a `GdprDeletionLog` model, and expose `eventName()` → `'user.gdpr_deleted'` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | GDPR deletion log records must be append-only — the `GdprDeletionLog` model must set `UPDATED_AT = null` to prevent update timestamps, and no update/delete operations must be exposed via the admin UI (→ DD-2) |
| NFR-S2 | Only admin or super_admin users may view GDPR deletion logs — enforced by `GdprDeletionLogPolicy` (→ FR-P2, FR-P3) |
| NFR-S3 | The metadata snapshot must be captured BEFORE the user record is deleted — `DeleteUserGdprAction` must read snapshot fields before calling `$user->delete()` (→ DD-3) |
| NFR-S4 | The `deleter_id` foreign key must use `nullOnDelete()` to preserve log records if the deleting admin is later removed |
| NFR-P1 | `GdprDeletionLogs` paginated query must complete within 200ms for up to 10,000 log records (indexed `user_email` and `deleted_at` columns) |
| NFR-R1 | `DeleteUserGdprAction` must create the `GdprDeletionLog` record within the same transaction as the user deletion to ensure atomicity (→ DD-4) |
| NFR-R2 | If `GdprDeletionLog` creation fails, the entire deletion must roll back — no user is deleted without a GDPR compliance record |
| NFR-U1 | All GDPR log UI labels must use `__()` translation helper with keys from `sysadmin.gdpr_logs.*` namespace |
| NFR-U2 | The deletion type badge must use color coding: `permanent_deletion` → error (red), `anonymization` → warning (yellow) |
| NFR-M1 | All GDPR compliance classes must use `declare(strict_types=1)` |
| NFR-M2 | `GdprDeletionLog` must not have an `updated_at` column — logs are immutable after creation (→ DD-2) |

---

## 6. API / Data Contracts

### 6.1 GdprDeletionType Enum

```php
// app/SysAdmin/Observability/GdprDeletionLog/Enums/GdprDeletionType.php
enum GdprDeletionType: string implements LabelEnum
{
    case ANONYMIZATION       = 'anonymization';
    case PERMANENT_DELETION  = 'permanent_deletion';

    public function label(): string;
    // Returns __('sysadmin.gdpr_logs.type.'.$this->value)
}
```

### 6.2 GdprDeletionLogState Entity

```php
// app/SysAdmin/Observability/GdprDeletionLog/Entities/GdprDeletionLogState.php
final readonly class GdprDeletionLogState extends BaseEntity
{
    public function __construct(
        private ?string $userEmail,
        private ?string $deletionType,
        private ?string $reason,
        private ?array  $metadataSnapshot,
        private ?string $deletedAt,
    ) {}

    public static function fromModel(Model $model): static;
    public function typeLabel(): string;
    public function formattedDeletedAt(): string;      // 'Y-m-d H:i' or 'N/A'
    public function snapshotSummary(): string;          // "Jane Doe (jane@example.com)"
    public function isAnonymization(): bool;
    public function isPermanentDeletion(): bool;
    public function userEmail(): ?string;
    public function deletionType(): ?string;
    public function reason(): ?string;
    public function metadataSnapshot(): ?array;
    public function deletedAt(): ?string;
}
```

### 6.3 GdprDeletionLog Model

```php
// app/SysAdmin/Observability/GdprDeletionLog/Models/GdprDeletionLog.php
#[Fillable(['user_id', 'user_email', 'deletion_type', 'reason', 'metadata_snapshot', 'deleted_at', 'deleter_id'])]
class GdprDeletionLog extends BaseModel
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $casts = [
        'metadata_snapshot' => 'array',
    ];

    public function deleter(): BelongsTo;              // → User via deleter_id (nullable)
    public function asGdprDeletionLogState(): GdprDeletionLogState;
}
```

### 6.4 gdpr_deletion_logs Table Schema (After Migration)

```php
// database/migrations/2026_07_24_000001_add_gdpr_columns_to_gdpr_deletion_logs_table.php
Schema::table('gdpr_deletion_logs', function (Blueprint $table) {
    $table->string('user_email', 255)->nullable()->after('user_id')->index();
    $table->string('deletion_type', 30)->nullable()->after('user_email');
    $table->text('reason')->nullable()->after('deletion_type');
    $table->timestamp('deleted_at')->nullable()->after('reason')->index();
    $table->foreignUuid('deleter_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
});
```

**Existing columns (retained from original migration):**

| Column | Type | Notes |
|--------|------|-------|
| `id` | `uuid` (PK) | Primary key |
| `user_id` | `uuid`, nullable, indexed | Orphaned reference to deleted user |
| `metadata_snapshot` | `json`, nullable | Pre-deletion PII snapshot |
| `created_at` | `timestamp`, nullable | Log creation timestamp |

### 6.5 DeleteUserGdprAction

```php
// app/SysAdmin/Observability/GdprDeletionLog/Actions/DeleteUserGdprAction.php
final class DeleteUserGdprAction extends BaseCommandAction
{
    public function execute(
        User $user,
        GdprDeletionType $type,
        ?string $reason = null,
        ?User $deleter = null,
    ): GdprDeletionLog;

    // Captures metadata snapshot, creates GdprDeletionLog,
    // dispatches UserGdprDeleted, logs gdpr_user_deleted
}
```

### 6.6 Updated DeleteUserAction

```php
// app/User/UserManagement/Actions/DeleteUserAction.php
final class DeleteUserAction extends BaseCommandAction
{
    public function __construct(
        protected readonly DeleteUserGdprAction $gdprAction,
    ) {}

    public function execute(User $user, ?string $reason = null): void;
    // Calls gdprAction->execute() with PERMANENT_DELETION type before $user->delete()
}
```

### 6.7 Updated BatchDeleteUserAction

```php
// app/User/UserManagement/Actions/BatchDeleteUserAction.php
final class BatchDeleteUserAction extends BaseCommandAction
{
    public function __construct(
        protected readonly DeleteUserAction $deleteAction,
    ) {}

    public function execute(array $ids, ?string $reason = null): array;
    // Passes reason to DeleteUserAction::execute() for each user
}
```

### 6.8 UserGdprDeleted Event

```php
// app/SysAdmin/Observability/GdprDeletionLog/Events/UserGdprDeleted.php
final class UserGdprDeleted extends BaseEvent
{
    public function __construct(public readonly GdprDeletionLog $log) {}
    public function eventName(): string { return 'user.gdpr_deleted'; }
}
```

### 6.9 GdprDeletionLogPolicy

```php
// app/SysAdmin/Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php
class GdprDeletionLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool;   // isAdmin
    public function view(User $user, GdprDeletionLog $log): bool;  // isAdmin
    public function create(User $user): bool;    // isAdmin
}
```

### 6.10 GdprDeletionLogs Livewire Component

```php
// app/SysAdmin/Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php
class GdprDeletionLogs extends Component
{
    use WithPagination, WithSorting;

    public string $search = '';
    public string $filterType = '';

    public function headers(): array;
    // Returns: user_email, deletion_type, reason, deleted_at

    public function logs(): LengthAwarePaginator;
    // Filters by user_email (LIKE), deletion_type, sorts by deleted_at

    #[Layout('core::layouts.app')]
    public function render(): View;
}
```

### 6.11 Routes

```php
// routes/web/sysadmin.php:37
Route::get('/gdpr-logs', GdprDeletionLogs::class)->name('gdpr-logs');
// Middleware: auth, role:super_admin|admin
```

### 6.12 Localization Keys

```php
// lang/en/sysadmin.php — gdpr_logs section
'gdpr_logs' => [
    'title' => 'GDPR Deletion Logs',
    'search_placeholder' => 'Search by email...',
    'type_placeholder' => 'All types',
    'type' => [
        'anonymization' => 'Anonymization',
        'permanent_deletion' => 'Permanent Deletion',
    ],
],
```

---

## 7. Design Decisions

### DD-1 — Separate DeleteUserGdprAction vs Inline in DeleteUserAction

**Decision:** GDPR logging is encapsulated in a dedicated `DeleteUserGdprAction`, called by `DeleteUserAction` rather than inlined within it.
**Rationale:** The `DeleteUserAction` is already 36 lines and handles business rules (super admin protection, self-deletion prevention). Adding snapshot capture, log creation, event dispatch, and SmartLogger calls would bloat it beyond single-responsibility. The `DeleteUserGdprAction` is independently testable — you can verify GDPR logging without performing an actual deletion. It also enables reuse: `BatchDeleteUserAction` gets GDPR logging for free via `DeleteUserAction`.
**Trade-off:** Extra class and constructor injection dependency. Acceptable — the GDPR action is a cohesive unit with its own event, model, and logging concerns.

### DD-2 — Append-Only Deletion Logs (No Update, No Delete)

**Decision:** `GdprDeletionLog` has `UPDATED_AT = null` and the admin UI exposes no update or delete operations for log records.
**Rationale:** GDPR audit logs must be tamper-evident. If admins could edit or delete log entries, the audit trail loses its purpose. An append-only model means the log is a factual record of what happened — immutable after creation.
**Trade-off:** No way to correct erroneous log entries (e.g., wrong reason). Acceptable — if a log entry is incorrect, a new correcting entry can be added manually, and the original stays for integrity.

### DD-3 — Metadata Snapshot Captured Before Deletion

**Decision:** `DeleteUserGdprAction` reads PII fields from the User model and stores them in `metadata_snapshot` BEFORE calling `$user->delete()`.
**Rationale:** Once `$user->delete()` executes, the User row is gone. The snapshot is the only record of what PII existed. Capturing after deletion is impossible; capturing during deletion risks a race condition with the cascade.
**Trade-off:** The snapshot is a point-in-time copy. If user data changed between snapshot capture and actual deletion (unlikely in a synchronous transaction), the snapshot could be stale. Acceptable — the capture and deletion happen within the same transaction.

### DD-4 — GDPR Log Creation Inside the Deletion Transaction

**Decision:** The `GdprDeletionLog` record is created within the same database transaction as `$user->delete()`.
**Rationale:** If the log creation fails (disk full, constraint violation), the transaction rolls back and no user is deleted without a compliance record. This enforces the invariant: every deletion has a corresponding log entry. The alternative (create log after deletion) would allow a user to be deleted without a log if the log creation fails.
**Trade-off:** The transaction holds a lock slightly longer. Negligible — user deletion is infrequent and fast.

### DD-5 — user_email as a Separate Column (Not in metadata_snapshot)

**Decision:** `user_email` is stored as a first-class column on `gdpr_deletion_logs` in addition to being in `metadata_snapshot`.
**Rationale:** The admin UI needs to search by email (`WHERE user_email LIKE '%...%'`) and display it as a table column. Querying inside a JSON `metadata_snapshot` column is inefficient and database-dependent. A dedicated indexed column enables fast lookups and sorting.
**Trade-off:** Slight data duplication (email appears in both `user_email` and `metadata_snapshot`). Acceptable — the snapshot is a frozen copy for audit, while the column is for operational querying.

### DD-6 — Retaining Existing user_id Column

**Decision:** The existing `user_id` column (orphaned UUID reference) is retained alongside the new `user_email` column.
**Rationale:** `user_id` preserves the original UUID of the deleted user, which is valuable for correlation with activity logs, Spatie audit trails, and any historical references. It cannot be a foreign key (the referenced user no longer exists), so it is an orphaned UUID by design.
**Trade-off:** Two columns referencing the same entity (UUID and email). Acceptable — `user_id` is for ID-based correlation, `user_email` is for human-readable lookup.

---

## 8. Success Metrics

### Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Every user deletion creates a GDPR log | 100% | `GdprDeletionLog` count matches `user_deleted` activity log count |
| Metadata snapshot captured before deletion | 100% | Snapshot fields are non-null for all log entries |
| Log records are immutable | 0 updates | No UPDATE queries on `gdpr_deletion_logs` table |

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| GDPR log query (search + paginate) | < 200ms | `GdprDeletionLogs::logs()` with 10,000 records |
| Deletion with GDPR logging overhead | < 50ms | `DeleteUserGdprAction::execute()` snapshot + insert time |

### Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Policy coverage | 100% admin-only | `GdprDeletionLogPolicy` enforced on all routes |
| Deletion types | 2 | `ANONYMIZATION`, `PERMANENT_DELETION` |

### Negative Metrics (What Should NOT Happen)

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| User deleted without GDPR log | 0 | Transaction ensures atomicity (→ DD-4) |
| Non-admin viewing GDPR logs | 0 incidents | `GdprDeletionLogPolicy` enforced |
| Stale metadata snapshot (post-deletion read) | 0 | Snapshot captured before delete (→ DD-3) |
| Admin editing or deleting GDPR log entries | 0 | No update/delete operations exposed (→ DD-2) |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `BaseEntity`, `BaseEvent`, `BaseModel`, `BasePolicy`, `RejectedException` base classes |
| [logging-and-error-handling.md](logging-and-error-handling.md) (#6) | `SmartLogger` for GDPR event logging |
| [event-system.md](event-system.md) (#7) | `BaseEvent` contract, event dispatch and listener registration patterns |
| [rbac-and-authorization.md](rbac-and-authorization.md) (#8) | `isAdmin()` policy helper, role-based gates, `super_admin\|admin` middleware |
| [settings-infrastructure.md](settings-infrastructure.md) (#14) | Settings infrastructure for compliance configuration |
| [user-crud-and-status.md](user-crud-and-status.md) (#34) | `DeleteUserAction`, `BatchDeleteUserAction`, `UserManager` Livewire component |

### Build Guide

After implementing this spec, every user deletion (admin or batch) automatically creates an immutable GDPR compliance log with a pre-deletion PII snapshot, deletion type, optional reason, and deleter identity. The admin-facing GDPR Logs page becomes fully functional with email search, type filtering, and sorted history. The `DeleteUserGdprAction` is independently testable and reusable for future anonymization workflows.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [system-maintenance.md](system-maintenance.md) (#53) | GDPR log retention and archival run alongside system maintenance |
| 2 | [user-crud-and-status.md](user-crud-and-status.md) (#34) | `DeleteUserAction` updated with `reason` parameter and `DeleteUserGdprAction` dependency |

---

## Quick References

- `app/SysAdmin/Observability/GdprDeletionLog/Models/GdprDeletionLog.php` — Eloquent model with `#[Fillable]`, `UPDATED_AT = null`, `asGdprDeletionLogState()` bridge (27 lines)
- `app/SysAdmin/Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` — Admin-only authorization for view/viewAny/create (27 lines)
- `app/SysAdmin/Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php` — Admin UI: search, filter, paginated table (56 lines)
- `app/User/UserManagement/Actions/DeleteUserAction.php` — Existing user deletion action to be updated with GDPR logging (36 lines)
- `app/User/UserManagement/Actions/BatchDeleteUserAction.php` — Existing batch deletion to propagate reason (54 lines)
- `resources/views/sysadmin/observability/gdpr-deletion-log/gdpr-deletion-logs.blade.php` — Blade view with maryUI table, type badge, date formatting (25 lines)
- `database/migrations/2026_01_02_000005_create_gdpr_deletion_logs_table.php` — Original migration (25 lines)
- `database/factories/GdprDeletionLogFactory.php` — Test factory with metadata snapshot (26 lines)
- `routes/web/sysadmin.php:37` — GDPR logs route (`admin.gdpr-logs`) with auth + role middleware
- `lang/en/sysadmin.php:88-92` — English translation keys for GDPR log UI
- `config/menu.php:331-335` — Sidebar menu entry for GDPR Logs
- `tests/SysAdmin/Observability/GdprDeletionLog/Models/GdprDeletionLogTest.php` — Model tests (37 lines)
- `tests/SysAdmin/Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicyTest.php` — Policy tests (58 lines)
- `tests/SysAdmin/Observability/GdprDeletionLog/Livewire/GdprDeletionLogsTest.php` — Livewire render test (21 lines)
- **Related spec:** [base-classes.md](base-classes.md) (#2) — Base classes (`BaseCommandAction`, `BaseEntity`, `BaseEvent`)
- **Related spec:** [rbac-and-authorization.md](rbac-and-authorization.md) (#8) — `isAdmin()` policy helper, role middleware
- **Related spec:** [user-crud-and-status.md](user-crud-and-status.md) (#34) — `DeleteUserAction`, user lifecycle
- **Related doc:** [security.md](../infrastructure/security.md) — GDPR compliance section (§5)
- **Related doc:** [system-observability.md](../foundation/system-observability.md) — GDPR deletion logs overview
