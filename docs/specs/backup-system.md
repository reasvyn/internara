# Backup System — Database & Storage Backup, Restore, and Retention

> **Last updated:** 2026-07-24 **Changes:** feat — initial spec documenting the backup system:
> backup creation (database, storage, combined), backup runner with multi-driver support, retention
> cleanup, CLI command, Livewire management UI, admin-only authorization, and failure notifications

## Description

Specification of Internara's backup system: a `BackupRunner` service that executes database dumps
(MySQL, PostgreSQL, SQLite) and storage archives (tar.gz), a `CreateBackupAction` that orchestrates
backup lifecycle (pending → running → completed/failed) with event dispatch, a `CleanupBackupsAction`
for retention-based purging, a `SystemBackupCommand` CLI with scheduled execution, a `BackupManager`
Livewire component for admin-facing backup management, and `BackupFailedNotification` dispatch to
super admins on failure.

---

## 1. Problem Statements

### PS-1 — No Automated Database Backup Mechanism

Internara stores all internship data — registrations, attendance, logbooks, evaluations, certificates
— in a single database. Without automated backups, a server failure, accidental deletion, or
corrupted migration results in total data loss. Schools have no IT staff to run manual `mysqldump`
commands on a schedule.

### PS-2 — Uploaded Files Are Not Backed Up

Student photos, company logos, certificate templates, and generated PDFs live on local storage
(`storage/app/`). A disk failure or accidental `rm -rf` destroys these files permanently. There is
no mechanism to archive `storage/app/public/` alongside database dumps.

### PS-3 — No Backup Retention Management

Without retention policies, backup files accumulate indefinitely, consuming disk space. Schools
running on shared hosting with limited storage (50–100 GB) will eventually run out of space if old
backups are never purged.

### PS-4 — No Visibility Into Backup Status

Administrators have no way to know whether backups are running, succeeding, or failing. A silent
backup failure (e.g., disk full, permission denied) goes unnoticed until data loss occurs.

### PS-5 — No Failure Alerting for Backup Operations

When a scheduled backup fails (database locked, storage full, driver not installed), no one is
notified. The failure is only discoverable by manually checking logs — which school administrators
never do.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a `BackupRunner` service that executes database dumps for MySQL, PostgreSQL, and SQLite, and storage archives via tar.gz |
| G2  | Provide a `CreateBackupAction` that orchestrates backup lifecycle (pending → running → completed/failed) within a database transaction |
| G3  | Provide a `CleanupBackupsAction` that deletes completed backups older than a configurable retention period, preserving failed backups |
| G4  | Provide a `SystemBackupCommand` CLI (`system:backup`) with `--type`, `--force`, and `--cleanup` options, scheduled daily via `Schedule::command()` |
| G5  | Provide a `BackupManager` Livewire component with stats dashboard, filterable backup history table, create/delete actions, and confirmation modal |
| G6  | Enforce admin-only authorization for all backup operations via `BackupPolicy` |
| G7  | Dispatch `BackupCompleted` and `BackupFailed` events on backup lifecycle transitions |
| G8  | Notify all super admins via `BackupFailedNotification` (database channel) when a backup fails |
| G9  | Store backup records in a `backups` table with type, file path, file size, status, metadata, error output, and creator tracking |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Backup restoration via UI or CLI — restoration is a manual procedure documented in `docs/foundation/backup-recovery.md` |
| NG2  | Remote backup destinations (S3, SFTP, cloud storage) — backups are local-only |
| NG3  | Encrypted backups or backup encryption keys |
| NG4  | Incremental or differential backups — every backup is a full snapshot |
| NG5  | Multi-tenant backup isolation — single-tenant architecture makes this unnecessary |
| NG6  | Real-time continuous backup or WAL-based point-in-time recovery |
| NG7  | Backup download via the web UI — backups are accessed directly from the filesystem |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Manual Backup via UI

**Actor:** Admin
**Preconditions:** User authenticated with admin or super_admin role, on the Backup Manager page
**Flow:**
1. Admin clicks the "Create Backup" dropdown in the `BackupManager` header
2. Dropdown shows three options: Database, Storage, Both
3. Admin selects "Database" → `createBackup('database')` is called
4. `BackupManager::createBackup()` authorizes via `BackupPolicy::create()`, resolves `BackupType::DATABASE`
5. `CreateBackupAction::execute(BackupType::DATABASE, $user)` is called
6. Action creates a `Backup` record with `status = 'running'` and `started_at = now()`
7. Action delegates to `BackupRunner::runDatabaseDump()` which detects the database driver and executes the appropriate dump command
8. Action updates record: `file_path`, `file_size`, `status = 'completed'`, `completed_at = now()`
9. Action logs `backup_created` via SmartLogger and dispatches `BackupCompleted` event
10. Flash success message displayed
**Postconditions:** Backup record exists with `status = 'completed'`, file stored at `storage/app/backup/`

### UC-2 — Admin Creates a Backup via CLI

**Actor:** System administrator (via terminal)
**Preconditions:** Application installed, database accessible
**Flow:**
1. Admin runs `php artisan system:backup --type=database --cleanup`
2. `SystemBackupCommand::handle()` checks `config('backup.enabled')` — exits if disabled and not `--force`
3. Resolves `BackupType::DATABASE` from the `--type` option
4. Calls `CreateBackupAction::execute(BackupType::DATABASE)`
5. Backup executes, status transitions to `completed`
6. Since `--cleanup` is set, calls `CleanupBackupsAction::execute(config('backup.retention_days', 30))`
7. Outputs success message with formatted file size
**Postconditions:** Backup created, old backups beyond retention deleted

### UC-3 — Backup Fails and Super Admins Are Notified

**Actor:** System (automated)
**Preconditions:** Scheduled backup configured, database driver unavailable or disk full
**Flow:**
1. `system:backup` scheduled command fires daily at configured time
2. `CreateBackupAction::execute()` is called, creates `Backup` record with `status = 'running'`
3. `BackupRunner::runDatabaseDump()` throws `RuntimeException` (e.g., `mysqldump: command not found`)
4. Action catches the exception, updates record: `status = 'failed'`, `error_output = $e->getMessage()`, `completed_at = now()`
5. Action logs `backup_failed` via SmartLogger and dispatches `BackupFailed` event
6. `SendBackupFailedNotification` listener handles the event
7. Listener queries `User::role('superadmin')->get()` and calls `$admin->notify(new BackupFailedNotification($backup))` for each
8. `BackupFailedNotification::via()` returns `['database']`, `toDatabase()` returns backup ID, type, error, and message
**Postconditions:** Backup record has `status = 'failed'`, super admins see notification in notification center

### UC-4 — Admin Deletes a Backup via UI

**Actor:** Admin
**Preconditions:** User authenticated with admin role, at least one completed or failed backup exists
**Flow:**
1. Admin clicks the trash icon on a backup row in the `BackupManager` table
2. `confirmDelete($id)` sets `deleteId` and opens the confirmation modal
3. Admin clicks "Delete" → `delete()` is called
4. `delete()` authorizes via `BackupPolicy::delete()`, finds the `Backup` by ID
5. `DeleteBackupAction::execute($backup)` checks `$backup->asBackupState()->isDeletable()` — throws `RejectedException` if not deletable
6. Action deletes the physical file via `BackupRunner::deleteFile()` (validates path is within backup directory)
7. Action deletes the database record within a transaction
8. Flash success message displayed
**Postconditions:** Backup record and physical file removed

### UC-5 — Retention Cleanup Deletes Old Backups

**Actor:** System (triggered by CLI `--cleanup` flag or scheduled command)
**Preconditions:** Backups older than retention period exist
**Flow:**
1. `CleanupBackupsAction::execute(30)` is called with 30-day retention
2. Action queries `Backup::where('status', 'completed')->where('created_at', '<', now()->subDays(30))`
3. Action chunks results (100 at a time), deletes physical files via `BackupRunner::deleteFile()`, then deletes database records
4. Action logs `backups_cleaned` with retention days and deleted count
5. Returns the count of deleted backups
**Postconditions:** Completed backups older than 30 days removed; failed backups preserved

---

## 4. Functional Requirements

### Model & Schema

| ID   | Requirement |
| ---- | ----------- |
| FR-M1 | `Backup` model must use `#[Fillable]` attribute with: `type`, `file_path`, `file_size`, `status`, `metadata`, `error_output`, `created_by`, `started_at`, `completed_at` |
| FR-M2 | `Backup` model must cast `file_size` → `integer`, `metadata` → `array`, `started_at` → `datetime`, `completed_at` → `datetime` |
| FR-M3 | `Backup` model must define `creator(): BelongsTo` relationship to `User` via `created_by` foreign key |
| FR-M4 | `Backup` model must provide `asBackupState(): BackupState` bridge method delegating to `BackupState::fromModel()` |
| FR-M5 | `backups` table `id` must be a UUID primary key |
| FR-M6 | `backups` table `type` column must be `string(20)` |
| FR-M7 | `backups` table `file_path` must be `string(512)`, nullable |
| FR-M8 | `backups` table `file_size` must be `unsignedBigInteger`, default `0` |
| FR-M9 | `backups` table `status` must be `string(20)`, default `'pending'` |
| FR-M10 | `backups` table `created_by` must be a nullable foreign UUID constrained to `users` with `nullOnDelete()` |
| FR-M11 | `backups` table must have a composite index on `[status, created_at]` |

### Enums

| ID   | Requirement |
| ---- | ----------- |
| FR-E1 | `BackupStatus` enum must implement `StatusEnum` with cases: `PENDING`, `RUNNING`, `COMPLETED`, `FAILED` |
| FR-E2 | `BackupStatus::label()` must return translated string via `__('backups.status.'.$this->value)` |
| FR-E3 | `BackupStatus::isTerminal()` must return `true` for `COMPLETED` and `FAILED` |
| FR-E4 | `BackupStatus::validTransitions()` must define: PENDING → [RUNNING, FAILED], RUNNING → [COMPLETED, FAILED], COMPLETED → [], FAILED → [] |
| FR-E5 | `BackupType` enum must implement `LabelEnum` with cases: `DATABASE`, `STORAGE`, `BOTH` |
| FR-E6 | `BackupType::label()` must return translated string via `__('backups.type.'.$this->value)` |

### Entity

| ID   | Requirement |
| ---- | ----------- |
| FR-EN1 | `BackupState` must be a `final readonly` class extending `BaseEntity` |
| FR-EN2 | `BackupState::fromModel()` must extract `status`, `type`, `file_size` (cast to int), and `error_output` from the model |
| FR-EN3 | `BackupState::isCompleted()` must return `true` when status equals `BackupStatus::COMPLETED` |
| FR-EN4 | `BackupState::isFailed()` must return `true` when status equals `BackupStatus::FAILED` |
| FR-EN5 | `BackupState::isDeletable()` must return `true` when completed OR failed |
| FR-EN6 | `BackupState::formattedSize()` must format bytes into human-readable strings: B, KB, MB, GB |
| FR-EN7 | `BackupState::type()` must return a `BackupType` enum instance |

### Actions — CreateBackupAction

| ID   | Requirement |
| ---- | ----------- |
| FR-C1 | `CreateBackupAction` must extend `BaseCommandAction` and accept `BackupRunner` via constructor injection |
| FR-C2 | `CreateBackupAction::execute(BackupType $type, ?User $user = null)` must create a `Backup` record within a transaction with `status = RUNNING` and `started_at = now()` |
| FR-C3 | `CreateBackupAction` must delegate to `BackupRunner` methods based on type: `DATABASE` → `runDatabaseDump()`, `STORAGE` → `runStorageDump()`, `BOTH` → `runCombinedDump()` |
| FR-C4 | On success, `CreateBackupAction` must update the record with `file_path`, `file_size`, `status = COMPLETED`, `completed_at = now()`, log `backup_created`, and dispatch `BackupCompleted` event |
| FR-C5 | On failure, `CreateBackupAction` must update the record with `status = FAILED`, `error_output`, `completed_at = now()`, log `backup_failed`, dispatch `BackupFailed` event, and throw `RejectedException` |
| FR-C6 | `CreateBackupAction::execute()` must return the `Backup` model on success |

### Actions — DeleteBackupAction

| ID   | Requirement |
| ---- | ----------- |
| FR-D1 | `DeleteBackupAction` must extend `BaseCommandAction` and accept `BackupRunner` via constructor injection |
| FR-D2 | `DeleteBackupAction::execute(Backup)` must check `$backup->asBackupState()->isDeletable()` and throw `RejectedException` if not deletable |
| FR-D3 | `DeleteBackupAction::execute(Backup)` must delete the physical file via `BackupRunner::deleteFile()` if `file_path` is set, then delete the database record within a transaction |
| FR-D4 | `DeleteBackupAction::execute(Backup)` must log `backup_deleted` with type and file_size |

### Actions — ReadBackupHistoryAction

| ID   | Requirement |
| ---- | ----------- |
| FR-R1 | `ReadBackupHistoryAction` must extend `BaseReadAction` and accept `Backup` model via constructor injection |
| FR-R2 | `ReadBackupHistoryAction::execute(int $perPage = 20, ?string $type = null, ?string $status = null)` must return a `LengthAwarePaginator` |
| FR-R3 | `ReadBackupHistoryAction` must eager-load the `creator` relationship |
| FR-R4 | `ReadBackupHistoryAction` must apply `type` and `status` filters conditionally when provided |

### Actions — ReadBackupStatsAction

| ID   | Requirement |
| ---- | ----------- |
| FR-S1 | `ReadBackupStatsAction` must extend `BaseReadAction` |
| FR-S2 | `ReadBackupStatsAction::execute()` must return an associative array with keys: `total`, `completed`, `failed`, `latest` |
| FR-S3 | `latest` must be the most recent backup with `status = COMPLETED`, or `null` if none exist |

### Actions — CleanupBackupsAction

| ID   | Requirement |
| ---- | ----------- |
| FR-CL1 | `CleanupBackupsAction` must extend `BaseCommandAction` and accept `BackupRunner` via constructor injection |
| FR-CL2 | `CleanupBackupsAction::execute(int $retentionDays = 30)` must delete only completed backups older than `$retentionDays` |
| FR-CL3 | `CleanupBackupsAction` must NOT delete failed backups during cleanup |
| FR-CL4 | `CleanupBackupsAction` must process deletions in chunks of 100 records |
| FR-CL5 | `CleanupBackupsAction` must delete physical files via `BackupRunner::deleteFile()` before deleting database records |
| FR-CL6 | `CleanupBackupsAction` must log `backups_cleaned` with `retention_days` and `deleted_count` when deletions occur |
| FR-CL7 | `CleanupBackupsAction::execute()` must return the count of deleted backups |

### BackupRunner Service

| ID   | Requirement |
| ---- | ----------- |
| FR-BR1 | `BackupRunner::runDatabaseDump()` must detect the configured database driver via `config('database.default')` and dispatch to MySQL, PostgreSQL, or SQLite dump commands |
| FR-BR2 | `BackupRunner::runDatabaseDump()` must store output at `storage/app/backup/backup_database_{timestamp}.sql.gz` |
| FR-BR3 | `BackupRunner::runDatabaseDump()` must create the backup directory if it does not exist |
| FR-BR4 | `BackupRunner::runDatabaseDump()` must throw `RuntimeException` for unsupported database drivers |
| FR-BR5 | `BackupRunner::runStorageDump()` must archive `storage/app/public/` via `tar -czf` to `storage/app/backup/backup_storage_{timestamp}.tar.gz` |
| FR-BR6 | `BackupRunner::runCombinedDump()` must create database and storage dumps individually, combine them into a single `backup_both_{timestamp}.tar.gz`, then delete the intermediate files |
| FR-BR7 | `BackupRunner::deleteFile(string $path)` must verify the path resolves within the backup directory before deleting |
| FR-BR8 | `BackupRunner::fileSize(string $path)` must return the file size in bytes, or `0` if the file does not exist |
| FR-BR9 | MySQL dumps must use `--single-transaction --routines --skip-lock-tables` flags and pass credentials via a temporary config file (chmod 0600) |
| FR-BR10 | PostgreSQL dumps must use `PGPASSFILE` environment variable with a temporary password file (chmod 0600) |
| FR-BR11 | SQLite copies must use `cp` followed by `gzip -f` |
| FR-BR12 | `BackupRunner` must clean up all temporary credential files in `__destruct()` and after each dump operation |

### CLI Command

| ID   | Requirement |
| ---- | ----------- |
| FR-CLI1 | `SystemBackupCommand` signature must be `system:backup {--type= : database, storage, or both} {--force : Skip pre-flight checks} {--cleanup : Run retention cleanup after backup}` |
| FR-CLI2 | `SystemBackupCommand` must check `config('backup.enabled')` and exit early with warning if disabled, unless `--force` is set |
| FR-CLI3 | `SystemBackupCommand` must resolve `BackupType` from the `--type` option, defaulting to `BOTH` when null |
| FR-CLI4 | `SystemBackupCommand` must output formatted size on success and deleted count when `--cleanup` is used |
| FR-CLI5 | The `system:backup` command must be scheduled daily via `Schedule::command()` in `routes/console.php` |

### Livewire UI

| ID   | Requirement |
| ---- | ----------- |
| FR-U1 | `BackupManager` must extend `BaseRecordManager` and use `AuthorizesRequests` trait |
| FR-U2 | `BackupManager::boot()` must call `$this->authorize('viewAny', Backup::class)` |
| FR-U3 | `BackupManager::headers()` must return columns: `type`, `status`, `file_size`, `creator.name`, `created_at`, `actions` |
| FR-U4 | `BackupManager::query()` must return `Backup::query()->with('creator')` |
| FR-U5 | `BackupManager::applyFilters()` must filter by `filterType` and `filterStatus` properties |
| FR-U6 | `BackupManager::stats()` must be a `#[Computed]` property delegating to `ReadBackupStatsAction::execute()` |
| FR-U7 | `BackupManager::createBackup(string $type)` must authorize via `create` gate, resolve `BackupType`, call `CreateBackupAction::execute()`, and flash success/error |
| FR-U8 | `BackupManager::confirmDelete(string $id)` must set `deleteId` and show the confirmation modal |
| FR-U9 | `BackupManager::delete()` must authorize via `delete` gate, call `DeleteBackupAction::execute()`, reset state, and flash success |
| FR-U10 | The backup manager view must display stats cards (total, completed, failed, latest size) above the filterable table |
| FR-U11 | The backup manager view must show delete buttons only for deletable backups (`isDeletable()` returns `true`) |
| FR-U12 | The backup manager view must include a help guide component (`backup-guide.blade.php`) accessible via a floating button |

### Policy

| ID   | Requirement |
| ---- | ----------- |
| FR-P1 | `BackupPolicy` must extend `BasePolicy` |
| FR-P2 | `BackupPolicy::viewAny()` must return `$this->isAdmin($user)` |
| FR-P3 | `BackupPolicy::view()` must return `$this->isAdmin($user)` |
| FR-P4 | `BackupPolicy::create()` must return `$this->isAdmin($user)` |
| FR-P5 | `BackupPolicy::delete()` must return `$this->isAdmin($user)` |

### Events & Listeners

| ID   | Requirement |
| ---- | ----------- |
| FR-EV1 | `BackupCompleted` must extend `BaseEvent`, accept a `Backup` model, and expose `eventName()` → `'backup.completed'` |
| FR-EV2 | `BackupFailed` must extend `BaseEvent`, accept a `Backup` model, and expose `eventName()` → `'backup.failed'` |
| FR-EV3 | `SendBackupFailedNotification` listener must handle `BackupFailed` event and notify all users with `superadmin` role |
| FR-EV4 | `SendBackupFailedNotification` must call `$admin->notify(new BackupFailedNotification($backup))` for each super admin |

### Notification

| ID   | Requirement |
| ---- | ----------- |
| FR-N1 | `BackupFailedNotification` must use the `database` channel via `via()` returning `['database']` |
| FR-N2 | `BackupFailedNotification::toDatabase()` must return an array with keys: `backup_id`, `type`, `error`, `message` |
| FR-N3 | `BackupFailedNotification` must use `Queueable` trait for deferred delivery |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | `BackupRunner::runDatabaseDump()` must complete within 60 seconds for databases up to 500 MB |
| NFR-P2 | `ReadBackupStatsAction::execute()` must complete within 100ms (3 indexed COUNT queries + 1 indexed latest query) |
| NFR-P3 | `ReadBackupHistoryAction::execute()` paginated query must complete within 200ms for up to 1,000 backup records |
| NFR-S1 | All backup operations must be restricted to admin users via `BackupPolicy` (→ FR-P1 through FR-P5) |
| NFR-S2 | `BackupRunner::deleteFile()` must validate that the file path resolves within the backup directory to prevent path traversal deletion |
| NFR-S3 | Database credentials must never appear in log output — `BackupRunner` writes them to temporary files with `chmod 0600` and deletes them after use |
| NFR-S4 | The `backups` table `created_by` foreign key must use `nullOnDelete()` to preserve backup records if the creating user is deleted |
| NFR-R1 | `CreateBackupAction` must wrap the entire backup lifecycle (record creation, dump execution, status update) in a single database transaction |
| NFR-R2 | `DeleteBackupAction` must delete the physical file before the database record to avoid orphaned database entries pointing to deleted files |
| NFR-R3 | `CleanupBackupsAction` must NOT delete failed backups — failed records serve as diagnostic evidence |
| NFR-R4 | `BackupRunner` must clean up temporary credential files even if the dump operation throws an exception (`finally` block) |
| NFR-U1 | All backup UI labels must use `__()` translation helper with keys from `backups.*` namespace |
| NFR-U2 | The backup manager must display a help guide modal with create, download, and restore instructions |
| NFR-U3 | The backup manager must show status badges with color coding: `completed` → success, `failed` → error, `running` → warning, `pending` → info |
| NFR-M1 | All backup classes must use `declare(strict_types=1)` |
| NFR-M2 | Backup file naming must follow the pattern `backup_{type}_{Y-m-d_His}.{ext}` for consistent identification |
| NFR-M3 | Backup files must be stored in `storage/app/backup/` which is not publicly accessible |

---

## 6. API / Data Contracts

### 6.1 Backup Model

```php
// app/SysAdmin/Backups/Models/Backup.php (56 lines)
#[Fillable(['type', 'file_path', 'file_size', 'status', 'metadata', 'error_output', 'created_by', 'started_at', 'completed_at'])]
class Backup extends BaseModel
{
    // Casts: file_size → integer, metadata → array, started_at → datetime, completed_at → datetime
    public function creator(): BelongsTo;       // → User via created_by
    public function asBackupState(): BackupState;
}
```

### 6.2 backups Table Schema

```php
// database/migrations/2026_01_01_000006_create_backups_table.php
Schema::create('backups', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type', 20);
    $table->string('file_path', 512)->nullable();
    $table->unsignedBigInteger('file_size')->default(0);
    $table->string('status', 20)->default('pending');
    $table->json('metadata')->nullable();
    $table->text('error_output')->nullable();
    $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->index(['status', 'created_at']);
});
```

### 6.3 BackupStatus Enum

```php
// app/SysAdmin/Backups/Enums/BackupStatus.php (49 lines)
enum BackupStatus: string implements StatusEnum
{
    case PENDING   = 'pending';
    case RUNNING   = 'running';
    case COMPLETED = 'completed';
    case FAILED    = 'failed';

    public function label(): string;
    public function isTerminal(): bool;
    public function canTransitionTo(StatusEnum $target): bool;
    public function validTransitions(): array;    // PENDING→[RUNNING,FAILED], RUNNING→[COMPLETED,FAILED], COMPLETED→[], FAILED→[]
    public function isFinished(): bool;
}
```

### 6.4 BackupType Enum

```php
// app/SysAdmin/Backups/Enums/BackupType.php (19 lines)
enum BackupType: string implements LabelEnum
{
    case DATABASE = 'database';
    case STORAGE  = 'storage';
    case BOTH     = 'both';

    public function label(): string;
}
```

### 6.5 BackupState Entity

```php
// app/SysAdmin/Backups/Entities/BackupState.php (62 lines)
final readonly class BackupState extends BaseEntity
{
    public function __construct(
        private string $status,
        private string $type,
        private int $fileSize,
        private ?string $errorOutput,
    ) {}

    public static function fromModel(Model $model): static;
    public function isCompleted(): bool;
    public function isFailed(): bool;
    public function isDeletable(): bool;        // completed OR failed
    public function formattedSize(): string;     // "0 B" → "3 GB"
    public function type(): BackupType;
}
```

### 6.6 CreateBackupAction

```php
// app/SysAdmin/Backups/Actions/CreateBackupAction.php (71 lines)
final class CreateBackupAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}
    public function execute(BackupType $type, ?User $user = null): Backup;
    // Creates Backup record (status=RUNNING), delegates to BackupRunner,
    // updates to COMPLETED or FAILED, dispatches BackupCompleted/BackupFailed
}
```

### 6.7 DeleteBackupAction

```php
// app/SysAdmin/Backups/Actions/DeleteBackupAction.php (35 lines)
final class DeleteBackupAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}
    public function execute(Backup $backup): void;
    // Validates isDeletable(), deletes file, deletes record, logs backup_deleted
}
```

### 6.8 ReadBackupHistoryAction

```php
// app/SysAdmin/Backups/Actions/ReadBackupHistoryAction.php (24 lines)
final class ReadBackupHistoryAction extends BaseReadAction
{
    public function __construct(protected readonly Backup $model) {}
    public function execute(int $perPage = 20, ?string $type = null, ?string $status = null): LengthAwarePaginator;
}
```

### 6.9 ReadBackupStatsAction

```php
// app/SysAdmin/Backups/Actions/ReadBackupStatsAction.php (22 lines)
final class ReadBackupStatsAction extends BaseReadAction
{
    public function execute(): array;
    // Returns ['total' => int, 'completed' => int, 'failed' => int, 'latest' => ?Backup]
}
```

### 6.10 CleanupBackupsAction

```php
// app/SysAdmin/Backups/Actions/CleanupBackupsAction.php (44 lines)
final class CleanupBackupsAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}
    public function execute(int $retentionDays = 30): int;
    // Deletes completed backups older than retentionDays, preserves failed backups
}
```

### 6.11 BackupRunner Service

```php
// app/SysAdmin/Backups/Services/BackupRunner.php (200 lines)
class BackupRunner
{
    public function runDatabaseDump(): string;   // Returns file path
    public function runStorageDump(): string;    // Returns file path
    public function runCombinedDump(): string;   // Returns combined file path
    public function deleteFile(string $path): bool;
    public function fileSize(string $path): int;
    // Private: mysqlDumpCommand(), pgDumpCommand(), sqliteCopyCommand(), createTempFile(), cleanupTempFiles()
}
```

### 6.12 SystemBackupCommand

```php
// app/SysAdmin/Backups/Console/Commands/SystemBackupCommand.php (63 lines)
final class SystemBackupCommand extends Command
{
    protected $signature = 'system:backup
        {--type= : Backup type: database, storage, or both}
        {--force : Skip pre-flight checks}
        {--cleanup : Run retention cleanup after backup}';
    // Checks config('backup.enabled'), resolves BackupType, delegates to CreateBackupAction
}
```

### 6.13 BackupManager Livewire Component

```php
// app/SysAdmin/Backups/Livewire/BackupManager.php (111 lines)
final class BackupManager extends BaseRecordManager
{
    public bool $showConfirmDelete = false;
    public ?string $deleteId = null;
    public string $filterType = '';
    public string $filterStatus = '';

    public function boot(): void;                    // authorizes viewAny
    public function headers(): array;
    protected function query(): Builder;             // Backup::query()->with('creator')
    protected function applyFilters(Builder $query): Builder;
    #[Computed] public function stats(): array;
    public function createBackup(string $type): void;
    public function confirmDelete(string $id): void;
    public function delete(DeleteBackupAction $action): void;
    public function cancelDelete(): void;
    #[Layout('core::layouts.app')] public function render(): View;
}
```

### 6.14 BackupPolicy

```php
// app/SysAdmin/Backups/Policies/BackupPolicy.php (32 lines)
class BackupPolicy extends BasePolicy
{
    public function viewAny(User $user): bool;   // isAdmin
    public function view(User $user, Backup $backup): bool;  // isAdmin
    public function create(User $user): bool;    // isAdmin
    public function delete(User $user, Backup $backup): bool; // isAdmin
}
```

### 6.15 Events

```php
// app/SysAdmin/Backups/Events/BackupCompleted.php (18 lines)
final class BackupCompleted extends BaseEvent
{
    public function __construct(public readonly Backup $backup) {}
    public function eventName(): string { return 'backup.completed'; }
}

// app/SysAdmin/Backups/Events/BackupFailed.php (18 lines)
final class BackupFailed extends BaseEvent
{
    public function __construct(public readonly Backup $backup) {}
    public function eventName(): string { return 'backup.failed'; }
}
```

### 6.16 SendBackupFailedNotification Listener

```php
// app/SysAdmin/Backups/Listeners/SendBackupFailedNotification.php (21 lines)
final class SendBackupFailedNotification
{
    public function handle(BackupFailed $event): void;
    // Queries User::role('superadmin')->get(), notifies each with BackupFailedNotification
}
```

### 6.17 BackupFailedNotification

```php
// app/SysAdmin/Backups/Notifications/BackupFailedNotification.php (34 lines)
final class BackupFailedNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly Backup $backup) {}
    public function via(User $notifiable): array { return ['database']; }
    public function toDatabase(User $notifiable): array;
    // Returns: backup_id, type, error, message (translated)
}
```

### 6.18 Routes

```php
// routes/web/sysadmin.php:41
Route::get('/backups', BackupManager::class)->name('backups');
// Middleware: auth, role:super_admin|admin
```

### 6.19 Schedule

```php
// routes/console.php:35-37
Schedule::command('system:backup')
    ->daily()
    ->description('Run scheduled system backup if enabled');
```

### 6.20 File Naming Convention

| Backup Type | File Pattern | Example |
|-------------|-------------|---------|
| Database | `backup_database_{Y-m-d_His}.sql.gz` | `backup_database_2026-07-24_020000.sql.gz` |
| Storage | `backup_storage_{Y-m-d_His}.tar.gz` | `backup_storage_2026-07-24_020000.tar.gz` |
| Both | `backup_both_{Y-m-d_His}.tar.gz` | `backup_both_2026-07-24_020000.tar.gz` |

### 6.21 Database Driver Commands

| Driver | Tool | Key Flags |
|--------|------|-----------|
| MySQL | `mysqldump` | `--single-transaction --routines --skip-lock-tables`, credentials via `--defaults-extra-file` |
| PostgreSQL | `pg_dump` | `--format=c`, credentials via `PGPASSFILE` |
| SQLite | `cp` + `gzip -f` | Direct file copy then compress |

---

## 7. Design Decisions

### DD-1 — BackupRunner as a Service Class, Not a Static Helper

**Decision:** `BackupRunner` is a regular class with instance state (backup directory, timestamp, temp files), instantiated via constructor injection in Actions, not a static utility.
**Rationale:** The runner maintains a timestamp for consistent file naming within a single backup operation, tracks temp credential files for cleanup in `__destruct()`, and isolates the shell execution logic. Constructor injection allows mocking in tests (`Mockery::mock(BackupRunner::class)`).
**Trade-off:** Extra class instantiation cost per backup. Negligible — backups run at most once per day.

### DD-2 — Entity Layer (BackupState) for UI State Logic

**Decision:** Business logic about backup state (deletability, formatted size, type resolution) lives in a `BackupState` entity, not on the Eloquent model.
**Rationale:** Follows the project's Entity pattern (→ base-classes.md) — the model stays thin with only relationships and casts, while `BackupState` encapsulates derived behavior. The Blade template calls `$backup->asBackupState()->isDeletable()` and `$backup->asBackupState()->formattedSize()` rather than putting these methods on the model.
**Trade-off:** Extra class (62 lines) and a bridge method on the model. But the entity is pure, testable without a database, and reusable across Livewire and Blade.

### DD-3 — Transactional Backup Lifecycle in CreateBackupAction

**Decision:** The entire backup lifecycle — record creation, dump execution, status update — runs inside `$this->transaction()`.
**Rationale:** If the dump succeeds but the record update fails (extremely unlikely but possible), the transaction rolls back the orphaned record. The trade-off is that a long-running dump holds a database transaction open. For SQLite this could cause write contention; for MySQL/PostgreSQL with `--single-transaction` it's fine.
**Trade-off:** Long transaction for potentially slow operations. Mitigated by the fact that the backup table is rarely written to (once per day) and the transaction is short relative to the dump time.

### DD-4 — Physical File Deletion Before Database Record Deletion

**Decision:** `DeleteBackupAction` and `CleanupBackupsAction` delete the physical file first, then the database record.
**Rationale:** If the file deletion fails (permission denied, file already gone), the action can throw without leaving an orphaned database record. The reverse order (delete DB first, then file) would leave an unrecoverable reference to a file the system can't clean up.
**Trade-off:** If the process crashes between file deletion and DB deletion, the database record persists without a corresponding file. This is the safer failure mode — a phantom record is less harmful than a phantom file reference.

### DD-5 — Failed Backup Records Preserved During Cleanup

**Decision:** `CleanupBackupsAction` only deletes `completed` backups during retention cleanup; failed backups are never auto-deleted.
**Rationale:** Failed backup records contain `error_output` which is essential for diagnosing backup failures. Auto-deleting them would lose diagnostic evidence. Schools should review and manually delete failed backups via the UI.
**Trade-off:** Disk space consumed by accumulated failed backup records (minimal — each record is a few KB of metadata with no associated file). Failed backup records without files do not consume significant storage.

### DD-6 — Credential Isolation via Temporary Files

**Decision:** Database credentials for `mysqldump` and `pg_dump` are written to temporary files with `chmod 0600`, passed via `--defaults-extra-file` or `PGPASSFILE`, and deleted after use.
**Rationale:** Passing credentials via command-line arguments (`--password=...`) exposes them in `/proc/*/cmdline` and `ps` output. Temporary files with restrictive permissions are the standard secure approach for non-interactive database tools.
**Trade-off:** Extra file I/O and cleanup logic (12 lines). Necessary for security — command-line password exposure is a well-known vulnerability.

### DD-7 — Native `database` Channel for BackupFailedNotification

**Decision:** `BackupFailedNotification` uses Laravel's native `database` channel with `toDatabase()`, not the custom `CustomDatabaseChannel` with `toCustomDatabase()`.
**Rationale:** Backup failure notifications are simple alerts for super admins only. They do not need structured `type`/`title`/`message` fields in the notification center — the native `database` channel with a JSON `data` blob is sufficient. This avoids coupling the backup module to the notification infrastructure contract.
**Trade-off:** Inconsistent with other notifications that use `CustomDatabaseChannel`. Acceptable because backup failures are admin-only operational events, not user-facing notifications.

---

## 8. Success Metrics

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Database dump (up to 500 MB DB) | < 60s | `BackupRunner::runDatabaseDump()` execution time |
| Storage archive (up to 1 GB files) | < 120s | `BackupRunner::runStorageDump()` execution time |
| Stats query | < 100ms | `ReadBackupStatsAction::execute()` with 1,000 records |
| History pagination | < 200ms | `ReadBackupHistoryAction::execute()` with 1,000 records |

### Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Database drivers supported | MySQL, PostgreSQL, SQLite | `BackupRunner` match arms |
| Backup types | database, storage, both | `BackupType` enum cases |
| Policy coverage | 100% admin-only | `BackupPolicy` enforced on all operations |

### Reliability

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Credential exposure in logs | 0 occurrences | No `password` or `pass` in log output |
| Temp file cleanup on failure | 100% | `finally` block in `runDatabaseDump()`, `__destruct()` |
| Failed backup notification delivery | 100% to super admins | `SendBackupFailedNotification` iterates all superadmin-role users |
| Path traversal in file deletion | 0 incidents | `BackupRunner::deleteFile()` validates `realpath()` within backup dir |

### Negative Metrics (What Should NOT Happen)

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Non-admin backup access | 0 incidents | `BackupPolicy` enforced on all routes |
| Orphaned database records (file deleted, record persists) | < 1% | File deletion before DB deletion (→ DD-4) |
| Auto-deleted failed backups | 0 | `CleanupBackupsAction` preserves failed records (→ DD-5) |
| Credential leakage in CLI output | 0 | Credentials via temp files, not command-line args (→ DD-6) |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `BaseReadAction`, `BaseEntity`, `BaseEvent`, `BaseModel`, `BasePolicy`, `RejectedException` base classes |
| [logging-and-error-handling.md](logging-and-error-handling.md) (#6) | `SmartLogger` for backup event logging |
| [event-system.md](event-system.md) (#7) | `BaseEvent` contract, event dispatch and listener registration patterns |
| [rbac-and-authorization.md](rbac-and-authorization.md) (#8) | `isAdmin()` policy helper, role-based `viewAny`/`create`/`delete` gates, `super_admin\|admin` middleware |
| [settings-infrastructure.md](settings-infrastructure.md) (#14) | `config('backup.enabled')` and `config('backup.retention_days')` settings integration |
| [notification-infrastructure.md](notification-infrastructure.md) (#18) | Database notification channel for `BackupFailedNotification` delivery |
| [job-queue-infrastructure.md](job-queue-infrastructure.md) (#50) | Queue configuration for async backup operations |

### Build Guide

After implementing this spec, the backup system is fully operational: admins can create backups via the UI or CLI, view backup history with stats, delete old backups, and receive failure notifications. The `SystemBackupCommand` is scheduled daily and respects `config('backup.enabled')` and `config('backup.retention_days')`. The `BackupRunner` handles MySQL, PostgreSQL, and SQLite with secure credential handling. The next phase is integrating backup triggers with other module lifecycle events (e.g., notifying before data-intensive operations).

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [system-maintenance.md](system-maintenance.md) (#53) | Backup cleanup runs alongside system maintenance tasks |
| 2 | [settings-infrastructure.md](settings-infrastructure.md) (#14) | `backup.enabled`, `backup.retention_days` settings exposed in Admin → Settings UI |
| 3 | [notification-infrastructure.md](notification-infrastructure.md) (#18) | `BackupFailedNotification` registers in the notification center via database channel |

---

## Quick References

- `app/SysAdmin/Backups/Models/Backup.php` — Eloquent model with `#[Fillable]` and `asBackupState()` bridge (56 lines)
- `app/SysAdmin/Backups/Entities/BackupState.php` — Immutable entity for state logic: deletable, formatted size, type (62 lines)
- `app/SysAdmin/Backups/Enums/BackupStatus.php` — Status enum: PENDING, RUNNING, COMPLETED, FAILED with transitions (49 lines)
- `app/SysAdmin/Backups/Enums/BackupType.php` — Type enum: DATABASE, STORAGE, BOTH (19 lines)
- `app/SysAdmin/Backups/Actions/CreateBackupAction.php` — Backup lifecycle orchestration with transaction and events (71 lines)
- `app/SysAdmin/Backups/Actions/DeleteBackupAction.php` — Deletability check, file removal, record deletion (35 lines)
- `app/SysAdmin/Backups/Actions/ReadBackupHistoryAction.php` — Paginated backup history with type/status filters (24 lines)
- `app/SysAdmin/Backups/Actions/ReadBackupStatsAction.php` — Aggregate stats: total, completed, failed, latest (22 lines)
- `app/SysAdmin/Backups/Actions/CleanupBackupsAction.php` — Retention-based cleanup preserving failed backups (44 lines)
- `app/SysAdmin/Backups/Services/BackupRunner.php` — Multi-driver dump execution with secure credential handling (200 lines)
- `app/SysAdmin/Backups/Console/Commands/SystemBackupCommand.php` — CLI `system:backup` with --type, --force, --cleanup (63 lines)
- `app/SysAdmin/Backups/Livewire/BackupManager.php` — Admin UI: stats, history table, create/delete actions (111 lines)
- `app/SysAdmin/Backups/Policies/BackupPolicy.php` — Admin-only authorization for all backup operations (32 lines)
- `app/SysAdmin/Backups/Events/BackupCompleted.php` — Event dispatched on successful backup (18 lines)
- `app/SysAdmin/Backups/Events/BackupFailed.php` — Event dispatched on failed backup (18 lines)
- `app/SysAdmin/Backups/Listeners/SendBackupFailedNotification.php` — Notifies super admins on failure (21 lines)
- `app/SysAdmin/Backups/Notifications/BackupFailedNotification.php` — Database channel notification with error details (34 lines)
- `resources/views/sysadmin/backups/backup-manager.blade.php` — Backup manager Livewire view with maryUI (115 lines)
- `resources/views/sysadmin/backups/components/backup-guide.blade.php` — Help guide modal with create/download/restore info (57 lines)
- `database/migrations/2026_01_01_000006_create_backups_table.php` — Backups table schema (35 lines)
- `routes/web/sysadmin.php:41` — Backup manager route (`admin.backups`) with auth + role middleware
- `routes/console.php:35-37` — Daily scheduled `system:backup` command
- **Related spec:** [base-classes.md](base-classes.md) (#2) — Base classes (`BaseCommandAction`, `BaseEntity`, `BaseEvent`)
- **Related spec:** [rbac-and-authorization.md](rbac-and-authorization.md) — `isAdmin()` policy helper, role middleware
- **Related spec:** [notification-infrastructure.md](notification-infrastructure.md) — Database notification channel for failure alerts
- **Related doc:** [backup-recovery.md](../foundation/backup-recovery.md) — Restoration procedures, manual backup commands, monitoring
