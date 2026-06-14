# Roadmap — Auto-Backup System

> **Last updated:** 2026-06-14
> **Changes:** initial — auto-backup system feature plan for SysAdmin/Backups module

> **Status:** Approved for implementation
> **Target module:** `SysAdmin/Backups`

---

## 1. Overview

Build an automated backup system for Internara that allows super administrators to:

- Schedule automatic backups (database, storage files, or both)
- Configure retention policy (how many backups to keep)
- Run manual backups on demand
- View backup history with status and file size
- Download or restore from backup files (via CLI)
- Receive notifications on backup failure

All configuration is managed through system settings, accessible only to super administrators.

---

## 2. Architecture

### Module Placement

```
app/SysAdmin/Backups/          (flat structure — cross-cutting system feature)
├── Actions/
│   ├── CreateBackupAction.php       — Command: run backup, store record
│   ├── DeleteBackupAction.php       — Command: remove backup file + record
│   ├── CleanupBackupsAction.php     — Command: purge expired backups per retention
│   └── ReadBackupHistoryAction.php  — Read: paginated backup history
├── Console/Commands/
│   └── SystemBackupCommand.php      — artisan system:backup
├── Entities/
│   └── BackupState.php              — status rules, file size formatting
├── Enums/
│   ├── BackupType.php               — database, storage, both
│   └── BackupStatus.php             — pending, running, completed, failed
├── Events/
│   ├── BackupCompleted.php
│   └── BackupFailed.php
├── Livewire/
│   └── BackupManager.php            — CRUD table: history, create, delete
├── Models/
│   └── Backup.php                   — Eloquent model
├── Notifications/
│   └── BackupFailedNotification.php — superadmin alert
├── Policies/
│   └── BackupPolicy.php             — superadmin only
└── Support/
    └── BackupRunner.php             — shell execution (mysqldump, zip, tar)
```

### Data Flow

```
Manual Trigger (CLI/Livewire)          Scheduled Trigger (Cron)
              │                                  │
              └──────────┬───────────────────────┘
                         │
              CreateBackupAction (Command)
                         │
              ┌──────────┼──────────────┐
              │          │              │
         BackupType   BackupRunner   Backup model
         (enum)     (shell exec)   (persist record)
              │          │              │
              │          ▼              │
              │    mysqldump|tar|zip    │
              │          │              │
              │          ▼              │
              │    storage/app/backup/  │
              │          │              │
              └──────────┼──────────────┘
                         │
                    Event dispatched
                    ├─ BackupCompleted → (future: download link)
                    └─ BackupFailed → BackupFailedNotification
                         │
                    CleanupBackupsAction
                    (retention enforcement)
```

### Database Schema

```sql
CREATE TABLE backups (
    id           CHAR(36) PRIMARY KEY,          -- UUID v7
    type         VARCHAR(20) NOT NULL,           -- 'database' | 'storage' | 'both'
    file_path    VARCHAR(512),                   -- relative path in storage
    file_size    BIGINT UNSIGNED DEFAULT 0,      -- bytes
    status       VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending|running|completed|failed
    metadata     JSON DEFAULT NULL,              -- {db_driver, db_size, file_count, ...}
    error_output TEXT DEFAULT NULL,              -- stderr from failed backup
    created_by   CHAR(36) NULL,                  -- user_id who triggered it
    started_at   TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL
);
```

---

## 3. Settings (System Key-Value Store)

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `backup.enabled` | boolean | `false` | Enable auto-backup scheduling |
| `backup.frequency` | string | `daily` | `daily`, `weekly`, `monthly` |
| `backup.schedule_time` | string | `02:00` | Time of day for backup (HH:MM, 24h) |
| `backup.retention_days` | integer | `30` | Delete backups older than N days |
| `backup.include_database` | boolean | `true` | Include database dump |
| `backup.include_storage` | boolean | `true` | Include uploaded files |

Settings are managed via the existing `app/Settings/` key-value store and cached with
event-driven invalidation.

---

## 4. File Storage

- Backup files stored at `storage/app/backup/`
- Naming convention: `backup_{type}_{Y-m-d_His}.{ext}`
  - `backup_database_2026-06-14_020000.sql.gz` — gzipped SQL dump
  - `backup_storage_2026-06-14_020000.tar.gz` — compressed storage files
  - `backup_both_2026-06-14_020000.tar.gz` — combined archive
- Database dump uses `mysqldump`, `pg_dump`, or SQLite file copy (auto-detected from .env)
- Storage backup uses `tar` + `gzip`

---

## 5. Console Commands

| Command | Description |
|---|---|
| `php artisan system:backup` | Run backup now (respects settings) |
| `php artisan system:backup --type=database` | Database only |
| `php artisan system:backup --type=storage` | Storage files only |
| `php artisan system:backup --type=both` | Both (default) |
| `php artisan system:backup --force` | Skip checks (disk space, etc.) |

---

## 6. Authorization

- **All backup operations** require `super_admin` role
- `BackupPolicy` enforces via `before()` (inherits from `BasePolicy`)
- Route group middleware: `role:super_admin`
- Livewire component calls `$this->authorize()` inline

---

## 7. Scheduled Tasks

Added to `routes/console.php`:

```php
Schedule::command('system:backup')
    ->daily()
    ->description('Run scheduled system backup');
```

Frequency is determined at runtime by reading `backup.frequency` setting.
The command exits early if `backup.enabled` is `false`.

---

## 8. Notifications

| Event | Trigger | Notification | Channel |
|---|---|---|---|
| `BackupFailed` | Backup process returns non-zero exit | `BackupFailedNotification` | In-app (database) |

---

## 9. Security Considerations

- Database dumps contain PII (student names, emails, etc.) — backup files inherit
  the same storage permissions as the application
- Backup files stored under `storage/app/backup/` — NOT in `public/` — never
  directly accessible via web
- Cleanup Action verifies file paths to prevent directory traversal
- Only superadmin can trigger, view, or delete backups

---

## 10. Implementation Order

1. Migration (`create_backups_table`)
2. Enums (`BackupType`, `BackupStatus`)
3. Entity (`BackupState`)
4. Model (`Backup`)
5. Policy (`BackupPolicy`)
6. Support (`BackupRunner`)
7. Action: `CreateBackupAction` (Command)
8. Action: `DeleteBackupAction` (Command)
9. Action: `CleanupBackupsAction` (Command)
10. Action: `ReadBackupHistoryAction` (Read)
11. Events (`BackupCompleted`, `BackupFailed`)
12. Notification (`BackupFailedNotification`)
13. Console command (`system:backup`)
14. Livewire component (`BackupManager`)
15. Routes (sysadmin.php)
16. Blade view
17. Scheduler entry (console.php)
18. Settings defaults (config)
19. Command auto-discovery (bootstrap/app.php)
20. Translations (lang/en, lang/id)
21. Tests
22. Update guide/05

---

## 11. Testing Strategy

| Test | Type | Scope |
|---|---|---|
| `CreateBackupActionTest` | Feature | Database backup, storage backup, failure modes |
| `DeleteBackupActionTest` | Feature | Delete record + file, not found, unauthorized |
| `CleanupBackupsActionTest` | Feature | Retention enforcement, dry run |
| `ReadBackupHistoryActionTest` | Feature | Pagination, filtering by type/status |
| `BackupPolicyTest` | Unit | Super admin grants, other roles deny |
| `BackupManagerTest` | Feature | Livewire: create, list, delete |

