# System Maintenance — Cleanup, Archiving & Health Monitoring

> **Last updated:** 2026-07-24 **Changes:** feat — new spec for Phase 12 Maintenance;
> covers log pruning, notification cleanup, account archival, cache warming, health checks

## Description

Automated and on-demand system maintenance operations that keep the application healthy,
storage bounded, and data lifecycle-compliant. Covers log file pruning, notification cleanup,
stale job removal, account lifecycle automation, cache warming, health diagnostics, and
Pulse metric snapshots. These operations run on a scheduler or are triggered manually by
admins.

---

## 1. Problem Statements

### PS-1 — Unbounded Storage Growth

Without automated cleanup, log files, activity logs, stale cache entries, failed job records,
read notifications, and expired media accumulate indefinitely. On a self-hosted single-tenant
system with limited disk, this eventually causes storage exhaustion and performance degradation.

### PS-2 — Dormant Accounts

Student accounts from previous PKL cohorts remain active indefinitely, cluttering user lists,
consuming sessions, and posing a security risk if credentials are not rotated. Administrators
need automated lifecycle transitions for accounts that have not been accessed in 90+ days.

### PS-3 — Cache Staleness After Deployments

After code deployments or configuration changes, cached settings, brand assets, compiled views,
and event maps may be stale. Without proactive warming, users hit cold caches on first request,
causing slow page loads and inconsistent behavior.

### PS-4 — No Health Visibility

Administrators have no systematic way to verify that PHP version, extensions, database,
migrations, storage, queue, cache, and disk space are all healthy. Problems are discovered
only when users report errors.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide `SystemCleanupCommand` — orchestrates all cleanup sub-tasks in one command |
| G2  | Provide `PruneNotificationsCommand` — delete read notifications older than N days |
| G3  | Provide `AutoInactivateAccounts` — auto-inactivate accounts idle for configurable days |
| G4  | Provide `ArchiveStudentAccountsAction` — mass-archive student accounts via chunked query |
| G5  | Provide `ArchiveStudentAccountsJob` — queued batch archival for large cohorts |
| G6  | Provide `SystemCacheWarmCommand` — pre-warm settings, brand, config, views, events |
| G7  | Provide `SystemHealthCommand` — comprehensive 14-check health diagnostic |
| G8  | Provide `PulseRecordSnapshotsCommand` — record custom Pulse metrics for dashboard cards |
| G9  | Register all maintenance commands in the scheduler for unattended operation |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Backup creation — see [backup-system.md](backup-system.md) |
| NG2  | GDPR deletion logging — see [gdpr-compliance.md](gdpr-compliance.md) |
| NG3  | Job dispatching infrastructure — see [job-queue-infrastructure.md](job-queue-infrastructure.md) |
| NG4  | Database migrations or schema changes — see [system-requirements.md](system-requirements.md) |
| NG5  | Log rotation via OS-level tools (logrotate) — out of scope for application-level spec |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Triggers Manual Cleanup

**Actor:** Admin
**Preconditions:** Admin authenticated, system installed
**Flow:**
1. Admin runs `php artisan system:cleanup --force` or clicks UI trigger
2. Command runs sub-tasks: `auth:clear-resets`, `cache:prune-stale-tags`, `queue:prune-failed`,
   `activitylog:clean`, `media-library:clean`
3. Log files older than retention period (default 30 days) are pruned
4. Results reported via SmartLogger
**Postconditions:** Stale data removed, disk usage reduced

### UC-2 — System Auto-Inactivates Dormant Accounts

**Actor:** Scheduler (daily)
**Preconditions:** Users exist with last_login_at older than 90 days
**Flow:**
1. `accounts:auto-inactivate` runs daily at 03:00
2. Finds users inactive for 90+ days
3. Transitions eligible accounts to INACTIVE status (skips super_admin, protected)
4. Logs transitions for audit
**Postconditions:** Dormant accounts locked, audit trail updated

### UC-3 — Admin Mass-Archives Cohort

**Actor:** Admin (via Student Manager)
**Preconditions:** Cohort completed PKL, placement finalized
**Flow:**
1. Admin filters students in Student Manager
2. Clicks "Archive Filtered"
3. `ArchiveStudentAccountsAction` chunks through filtered query (100/batch)
4. Each user transitioned to ARCHIVED status (super_admin skipped)
5. Count returned, logged via SmartLogger
**Postconditions:** Student accounts archived, login blocked, count reported

### UC-4 — Admin Runs Health Check

**Actor:** Admin
**Preconditions:** System installed
**Flow:**
1. Admin runs `php artisan system:health` or `php artisan system:health --json`
2. Command checks 14 subsystems: environment, setup status, PHP version, extensions,
   recommended extensions, memory, database, migrations, storage, disk space, queue,
   cache, app key, storage link, maintenance mode
3. Results displayed as table or JSON
4. FAIL if any check fails, SUCCESS if all pass
**Postconditions:** Admin informed of system health status

### UC-5 — Scheduler Pre-Warms Cache

**Actor:** Scheduler (hourly)
**Preconditions:** Application running
**Flow:**
1. `system:cache-warm` runs hourly
2. Pre-warms: settings cache, brand cache, config cache, view cache, event cache
3. First user request after warm gets cached response
**Postconditions:** Application caches populated, cold-start latency eliminated

---

## 4. Functional Requirements

### SystemCleanupCommand

| ID      | Requirement |
| ------- | ----------- |
| FR-CL1  | `system:cleanup` must orchestrate sub-tasks: `auth:clear-resets`, `cache:prune-stale-tags`, `queue:prune-failed`, `activitylog:clean`, `media-library:clean` |
| FR-CL2  | `--force` flag must skip confirmation prompt |
| FR-CL3  | `--log-retention=N` flag must prune `storage/logs/laravel-*.log` files older than N days (default: 30) |
| FR-CL4  | Each sub-task failure must be logged via SmartLogger but not halt subsequent tasks |
| FR-CL5  | Cleanup completion must be logged via SmartLogger with module `system`, event `cleanup.completed` |

### PruneNotificationsCommand

| ID      | Requirement |
| ------- | ----------- |
| FR-PN1  | `notifications:prune` must delete notifications where `is_read = true` AND `created_at < cutoff` |
| FR-PN2  | `--days=N` flag must set retention period (default: 30, minimum: 1) |
| FR-PN3  | Must reject days < 1 with error message |

### AutoInactivateAccounts

| ID      | Requirement |
| ------- | ----------- |
| FR-AI1  | `accounts:auto-inactivate` must find users where `last_login_at < now - N days` (default: 90) |
| FR-AI2  | Must skip super_admin role and protected status |
| FR-AI3  | Must transition eligible accounts to INACTIVE status with reason |
| FR-AI4  | Scheduled daily at 03:00 via `routes/console.php` |

### ArchiveStudentAccounts

| ID      | Requirement |
| ------- | ----------- |
| FR-AS1  | `ArchiveStudentAccountsAction` must chunk through query results (chunk size: 100) |
| FR-AS2  | Must skip super_admin role users |
| FR-AS3  | Must transition each user to ARCHIVED status with reason "Mass archived via Student Manager" |
| FR-AS4  | Must log `student_accounts_archived` event with count via SmartLogger |
| FR-AS5  | `ArchiveStudentAccountsJob` must accept `studentIds` array, dispatch on queue |
| FR-AS6  | Job must retry up to 3 times with backoff [2, 10, 30] seconds |
| FR-AS7  | Job must use `SetUserStatusAction` (not direct model mutation) for each user |

### SystemCacheWarmCommand

| ID      | Requirement |
| ------- | ----------- |
| FR-CW1  | `system:cache-warm` must pre-warm: settings cache, brand cache, config cache, view cache, event cache |
| FR-CW2  | Each warm step must be reported as a task with success/failure |
| FR-CW3  | Completion must be logged via SmartLogger with module `system`, event `cache.warm.completed` |
| FR-CW4  | Scheduled hourly via `routes/console.php` |

### SystemHealthCommand

| ID      | Requirement |
| ------- | ----------- |
| FR-HC1  | `system:health` must check 14 subsystems: environment (.env exists), setup status, PHP version, required extensions, recommended extensions, memory limit, database connectivity, migration status, storage writability, disk space, queue status, cache driver, app key, storage symlink, maintenance mode |
| FR-HC2  | `--json` flag must output results as JSON array |
| FR-HC3  | Each check returns status: OK, WARN, or FAIL with detail string |
| FR-HC4  | Command exit code must be FAILURE if any check returns FAIL |
| FR-HC5  | Disk space thresholds: >= 95% = FAIL, >= 85% = WARN |
| FR-HC6  | Queue threshold: > 100 failed jobs = WARN |

### PulseRecordSnapshotsCommand

| ID      | Requirement |
| ------- | ----------- |
| FR-PS1  | `pulse:record-snapshots` must invoke `RegistrationRecorder::recordSnapshot()` and `SystemRecorder::recordSnapshot()` |
| FR-PS2  | Snapshots feed custom Pulse dashboard cards |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-M1  | All commands must declare `strict_types=1` |
| NFR-M2  | All public methods must have PHPDoc blocks |
| NFR-L1  | All cleanup operations must log via SmartLogger (not `logger()` directly) |
| NFR-L2  | PII must be masked in all log output via `withPiiMasking()` |
| NFR-P1  | `ArchiveStudentAccountsAction` peak memory must not exceed 50MB for 500 users |
| NFR-R1  | Sub-task failures in `SystemCleanupCommand` must not halt subsequent tasks |
| NFR-R2  | `ArchiveStudentAccountsJob` must use queue (not synchronous) for large batches |

---

## 6. API / Data Contracts

### SystemCleanupCommand

```php
// app/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php
class SystemCleanupCommand extends Command
{
    protected $signature = 'system:cleanup
        {--force : Do not ask for confirmation}
        {--log-retention=30 : Days to retain log files}';
}
```

### PruneNotificationsCommand

```php
// app/SysAdmin/Console/Commands/PruneNotificationsCommand.php
class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune
        {--days=30 : Delete read notifications older than this many days}';
}
```

### AutoInactivateAccounts

```php
// app/User/UserManagement/Console/Commands/AutoInactivateAccounts.php
class AutoInactivateAccounts extends Command
{
    protected $signature = 'accounts:auto-inactivate
        {--days=90 : Days of inactivity before auto-inactivation}';
}
```

### ArchiveStudentAccountsAction

```php
// app/User/UserManagement/Actions/ArchiveStudentAccountsAction.php
final class ArchiveStudentAccountsAction extends BaseCommandAction
{
    public function execute(Builder $query): int;
    // Returns count of archived users
}
```

### ArchiveStudentAccountsJob

```php
// app/Jobs/User/ArchiveStudentAccountsJob.php
class ArchiveStudentAccountsJob implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [2, 10, 30];

    public function __construct(protected readonly array $studentIds) {}
    public function handle(SetUserStatusAction $setUserStatus): void;
}
```

### SystemCacheWarmCommand

```php
// app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php
class SystemCacheWarmCommand extends Command
{
    protected $signature = 'system:cache-warm';
}
```

### SystemHealthCommand

```php
// app/SysAdmin/Observability/Console/Commands/SystemHealthCommand.php
class SystemHealthCommand extends Command
{
    protected $signature = 'system:health
        {--json : Output results as JSON}';
}
```

### PulseRecordSnapshotsCommand

```php
// app/SysAdmin/Observability/Console/Commands/PulseRecordSnapshotsCommand.php
class PulseRecordSnapshotsCommand extends Command
{
    protected $signature = 'pulse:record-snapshots';
}
```

---

## 7. Design Decisions

### DD-1 — Single Cleanup Command as Orchestrator

**Decision:** `SystemCleanupCommand` orchestrates existing Artisan sub-tasks rather than
implementing cleanup logic directly.
**Rationale:** Reuses Laravel's built-in pruning commands (`activitylog:clean`, `queue:prune-failed`,
etc.) and community package commands. The orchestrator provides a single entry point and
consistent reporting.
**Trade-off:** If a sub-task changes its interface, the orchestrator must be updated. Acceptable —
sub-task interfaces are stable Laravel/package APIs.

### DD-2 — Chunked Archival via Builder Query

**Decision:** `ArchiveStudentAccountsAction` accepts a `Builder` query, not a user ID array.
**Rationale:** The Student Manager builds a filtered query with scopes (role, status, placement).
Passing the query directly avoids loading all matching users into memory. The action chunks
through results (100/batch) for memory efficiency.
**Trade-off:** Cannot dispatch the action from a queued job with a query object. Solved by
`ArchiveStudentAccountsJob` which accepts an ID array and reconstructs the query.

### DD-3 — Health Check as CLI-Only

**Decision:** `SystemHealthCommand` is CLI-only (no Livewire component).
**Rationale:** Health checks verify low-level system state (PHP extensions, disk space, database
connectivity) that is irrelevant to end users and potentially sensitive. CLI output is
sufficient for administrators. JSON output enables integration with monitoring tools.
**Trade-off:** No web UI for health status. Acceptable — admins can run via SSH or scheduling.

### DD-4 — Auto-Inactivate vs Auto-Archive

**Decision:** Scheduler auto-inactivates (not auto-archives) dormant accounts.
**Rationale:** Inactivation is reversible — users can reactivate by logging in. Archival is
terminal and requires admin intervention. Auto-inactivation is safe for scheduled automation;
archival should always be an explicit admin decision.
**Trade-off:** Dormant accounts remain in INACTIVE state indefinitely until manually archived.
Acceptable — inactive accounts cannot log in and pose minimal risk.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Cleanup completion | < 5 minutes for full cleanup cycle | `system:cleanup` execution time |
| Log file pruning | 100% of files older than retention deleted | `ls storage/logs/laravel-*.log \| wc -l` after cleanup |
| Auto-inactivate coverage | 100% of 90+ day dormant accounts transitioned | Count of accounts transitioned per run |
| Archive memory | < 50MB peak for 500 users | `ArchiveStudentAccountsAction` chunk test |
| Health check coverage | 14 subsystem checks per run | `system:health` output line count |
| Cache warm latency | < 30 seconds for full warm cycle | `system:cache-warm` execution time |
| Scheduler reliability | All 6 scheduled commands execute daily | Scheduler log verification |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [logging-and-error-handling.md](logging-and-error-handling.md) | `SmartLogger` for structured cleanup logging |
| [rbac-and-authorization.md](rbac-and-authorization.md) | `isAdmin()` policy, role-based gate for admin commands |
| [job-queue-infrastructure.md](job-queue-infrastructure.md) | `ShouldQueue` interface, queue configuration for `ArchiveStudentAccountsJob` |
| [backup-system.md](backup-system.md) | Backup creation runs alongside cleanup; `SystemBackupCommand` is scheduled alongside maintenance tasks |
| [user-crud-and-status.md](user-crud-and-status.md) | `SetUserStatusAction`, `AccountStatus::ARCHIVED` enum, user lifecycle transitions |

### Build Guide

After implementing this spec, the system runs 6 scheduled maintenance commands daily/hourly:
`accounts:auto-inactivate` (daily 03:00), `notifications:prune` (daily), `system:cleanup` (daily),
`system:cache-warm` (hourly), `pulse:check` (every minute), `system:backup` (daily).
Admins can trigger `system:health` on-demand for diagnostics and `system:cleanup --force` for
manual cleanup. Mass student archival is available from the Student Manager UI.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Maintenance is the final phase — runs continuously after all features are built |

---

## Quick References

- `app/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php` — Orchestrated cleanup (108 lines)
- `app/SysAdmin/Console/Commands/PruneNotificationsCommand.php` — Notification pruning (39 lines)
- `app/User/UserManagement/Console/Commands/AutoInactivateAccounts.php` — Dormant account lifecycle
- `app/User/UserManagement/Actions/ArchiveStudentAccountsAction.php` — Mass student archival (33 lines)
- `app/Jobs/User/ArchiveStudentAccountsJob.php` — Queued batch archival (51 lines)
- `app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php` — Cache warming (98 lines)
- `app/SysAdmin/Observability/Console/Commands/SystemHealthCommand.php` — Health diagnostics (388 lines)
- `app/SysAdmin/Observability/Console/Commands/PulseRecordSnapshotsCommand.php` — Pulse metrics (28 lines)
- `routes/console.php` — Scheduler registration for all maintenance commands
- `app/SysAdmin/Observability/Recorders/RegistrationRecorder.php` — Pulse registration snapshots
- `app/SysAdmin/Observability/Recorders/SystemRecorder.php` — Pulse system snapshots
- `app/User/Enums/AccountStatus.php` — ARCHIVED status enum value
- **Related specs:** [backup-system.md](backup-system.md) — Backup creation and retention
- **Related specs:** [gdpr-compliance.md](gdpr-compliance.md) — GDPR deletion logging
- **Related specs:** [job-queue-infrastructure.md](job-queue-infrastructure.md) — Queue for archival jobs
- **Related specs:** [logging-and-error-handling.md](logging-and-error-handling.md) — SmartLogger
