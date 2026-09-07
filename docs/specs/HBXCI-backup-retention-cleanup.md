# Backup Retention Cleanup — Scheduled Backup Purging

> **Spec ID:** HBXC2

## Description

Specification for the backup retention cleanup pipeline: scheduled purging of completed backups
older than the configured retention period (default 30 days), with failed backups preserved for
diagnostic purposes. The backup creation lifecycle (database dumps, storage archives, lifecycle
status, failure notification) is defined in [backup-system.md](HBXCI-backup-system.md).

---

## 1. Problem Statements

### PS-1 — Backup Files Accumulate Without Cleanup

Without retention policies, backup files accumulate indefinitely, consuming disk space. Schools
running on shared hosting with limited storage (50–100 GB) will eventually run out of space if old
backups are never purged.

### PS-2 — Failed Backups Must Be Preserved for Diagnostics

Failed backup records contain `error_output` essential for diagnosing backup failures. Auto-deleting
them during cleanup would lose diagnostic evidence. Schools should review and manually delete
failed backups via the UI.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Delete completed backups older than the configured retention period (default 30 days) |
| G2  | Process deletions in chunks (100 records at a time) to avoid memory pressure |
| G3  | Delete physical files before database records to prevent orphans |
| G4  | Log retention cleanup with retention days and deleted count |
| G5  | Trigger cleanup via CLI `--cleanup` flag or scheduled daily run |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Auto-deletion of failed backups (preserved for diagnostics) |
| NG2  | Configurable retention period per backup type (single global value) |
| NG3  | Manual cleanup UI (CLI/scheduler only) |
| NG4  | Cleanup notifications (silent operation) |

---

## 3. User Stories / Use Cases

### UC-HBXC2-1 — Retention Cleanup Deletes Old Backups

**Actor:** System (triggered by CLI `--cleanup` flag or scheduled command)
**Preconditions:** Backups older than retention period exist
**Flow:**
1. `CleanupBackupsAction::execute(30)` is called with 30-day retention
2. Action queries `Backup::where('status', 'completed')->where('created_at', '<', now()->subDays(30))`
3. Action chunks results (100 at a time), deletes physical files via `BackupRunner::deleteFile()`, then deletes database records
4. Action logs `backups_cleaned` with retention days and deleted count
5. Returns the count of deleted backups
**Postconditions:** Completed backups older than 30 days removed; failed backups preserved

### UC-HBXC2-2 — Daily Scheduled Cleanup

**Actor:** System (automated via `system:backup --cleanup` scheduled daily)
**Preconditions:** `config('backup.enabled')` is true
**Flow:**
1. Scheduled command runs `php artisan system:backup --type=both --cleanup`
2. After successful backup, `--cleanup` triggers `CleanupBackupsAction`
3. Old backups are removed, freeing disk space for the next cycle
**Postconditions:** Disk space reclaimed; backups within retention window retained

---

## 4. Functional Requirements

| ID   | Requirement |
| ---- | ----------- |
| FR-HBXC2-CL1 | `CleanupBackupsAction` must extend `BaseCommandAction` and accept `BackupRunner` via constructor injection |
| FR-HBXC2-CL2 | `CleanupBackupsAction::execute(int $retentionDays = 30)` must delete only completed backups older than `$retentionDays` |
| FR-HBXC2-CL3 | `CleanupBackupsAction` must NOT delete failed backups during cleanup |
| FR-HBXC2-CL4 | `CleanupBackupsAction` must process deletions in chunks of 100 records |
| FR-HBXC2-CL5 | `CleanupBackupsAction` must delete physical files via `BackupRunner::deleteFile()` before deleting database records |
| FR-HBXC2-CL6 | `CleanupBackupsAction` must log `backups_cleaned` with `retention_days` and `deleted_count` when deletions occur |
| FR-HBXC2-CL7 | `CleanupBackupsAction::execute()` must return the count of deleted backups |
| FR-HBXC2-CL8 | `SystemBackupCommand` must support `--cleanup` flag to trigger cleanup after backup |
| FR-HBXC2-CL9 | `config('backup.retention_days')` must be the configurable retention period (default 30) |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-HBXC2-R1 | `CleanupBackupsAction` must NOT delete failed backups — failed records serve as diagnostic evidence |
| NFR-HBXC2-R2 | If the process crashes between file deletion and DB deletion, the database record persists without a corresponding file (safer failure mode) |
| NFR-HBXC2-R3 | `BackupRunner::deleteFile()` must validate the file path resolves within the backup directory to prevent path traversal deletion |
| NFR-HBXC2-M1 | All PHP files must declare `strict_types=1)` |

---

## 6. API / Data Contracts

### CleanupBackupsAction

```php
// app/Modules/SysAdmin/Backups/Actions/CleanupBackupsAction.php (44 lines)
final class CleanupBackupsAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}
    public function execute(int $retentionDays = 30): int;
    // Deletes completed backups older than retentionDays, preserves failed backups
}
```

### Cleanup Trigger

```php
// routes/console.php
Schedule::command('system:backup --cleanup')
    ->daily()
    ->description('Run scheduled backup with retention cleanup');

// or run cleanup separately:
Schedule::call(function () {
    app(CleanupBackupsAction::class)->execute(config('backup.retention_days', 30));
})->daily();
```

### Configuration

```php
// config/backup.php
return [
    'enabled' => env('BACKUP_ENABLED', true),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'schedule' => env('BACKUP_SCHEDULE', 'daily'),
];
```

---

## 7. Design Decisions

### DD-1 — Failed Backup Records Preserved During Cleanup

**Decision:** `CleanupBackupsAction` only deletes `completed` backups during retention cleanup;
failed backups are never auto-deleted.

**Rationale:** Failed backup records contain `error_output` which is essential for diagnosing
backup failures. Auto-deleting them would lose diagnostic evidence. Schools should review and
manually delete failed backups via the UI.

**Trade-off:** Disk space consumed by accumulated failed backup records (minimal — each record is
a few KB of metadata with no associated file). Failed backup records without files do not consume
significant storage.

### DD-2 — Physical File Deletion Before Database Record Deletion

**Decision:** `CleanupBackupsAction` deletes the physical file first, then the database record.

**Rationale:** If the file deletion fails (permission denied, file already gone), the action can
throw without leaving an orphaned database record. The reverse order (delete DB first, then file)
would leave an unrecoverable reference to a file the system can't clean up.

**Trade-off:** If the process crashes between file deletion and DB deletion, the database record
persists without a corresponding file. This is the safer failure mode — a phantom record is less
harmful than a phantom file reference.

### DD-3 — Chunked Deletion to Bound Memory

**Decision:** `CleanupBackupsAction` processes deletions in chunks of 100 records.

**Rationale:** Loading the entire expired set into memory at once would risk memory exhaustion for
schools with thousands of old backups. Chunking keeps memory bounded regardless of retention window.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Cleanup duration | < 60s for 1000 expired backups | `CleanupBackupsAction::execute()` execution time |
| Disk space reclaimed | ≥ 0 (no negative deltas) | Diff before/after cleanup |
| Failed backup preservation | 100% (none auto-deleted) | Status filter on `completed` only |
| Orphaned records after cleanup | < 1% | File deletion before DB deletion |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [backup-system.md](HBXCI-backup-system.md) | `Backup` model, `BackupRunner` service, `CreateBackupAction`, `SystemBackupCommand` |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | `config('backup.retention_days')` setting |

### Build Guide
After implementing this spec, completed backups older than the retention period are automatically
purged (via scheduled command or manual `--cleanup` flag), keeping disk usage bounded without losing
diagnostic evidence from failed backups.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [backup-system.md](HBXCI-backup-system.md) | `SystemBackupCommand --cleanup` flag wires cleanup into the daily backup cycle |
| 2 | [system-maintenance.md](E1MSJ-system-maintenance.md) | Cleanup runs alongside other system maintenance tasks |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
