# Data Archiving & Retention — Full Archival Lifecycle

> **Spec ID:** 9YUUK

## Description

Defines the full data-archival lifecycle for completed PKL cohorts: a configurable retention policy
per data category, a central archive registry (`ArchiveRecord`) that tracks what was archived, when,
by whom, and until when, a Process Action that archives a completed cohort across modules, a queued
purge job that irreversibly deletes expired archives via the GDPR deletion pipeline, and a restore
action that reverses an archive before its retention expiry. Existing archival capabilities —
`ArchiveStudentAccountsAction`/`ArchiveStudentAccountsJob` (E1MSJ), report snapshots (R6BMW), and
per-document retention (config) — become coordinated capabilities inside this lifecycle rather than
isolated one-offs.

---

## 1. Problem Statements

### PS-1 — No Single Archival Lifecycle

Archival exists as disconnected one-offs: student accounts can be mass-archived (E1MSJ FR-AS), grade
reports freeze an `archived_data` snapshot (R6BMW FR-AR1-3), and documents carry per-type retention
(config). Nothing coordinates a **whole cohort**: when a PKL group finishes, its registrations,
logbooks, attendance, assessments, reports, and certificates have no single "this cohort is done and
sealed" operation, so data lingers editable or is left to manual, per-module effort.

### PS-2 — Retention Is Configured But Never Enforced

Retention periods exist as scattered config (`config/document-official.php` `retention`, `config/backup.php`
`retention_days`, E1MSJ log/notification pruning defaults) but nothing **acts** on them: no record
knows *when it becomes eligible for deletion*, and no scheduled job purges data whose retention has
expired. Data accumulates indefinitely against unbounded storage and legal-retention requirements.

### PS-3 — No Distinction Between Archive, Restore, and Purge

The current model conflates reversible archiving with irreversible deletion: an archived student
account is a terminal `AccountStatus` transition (E1MSJ DD-4), yet nothing records why or how long it
must be kept, and nothing offers a controlled restore path for records that were archived by mistake.
There is no audit-friendly distinction between "archived for retention" (reversible, time-boxed) and
"purged under compliance" (irreversible, logged to `GdprDeletionLog`).

---

## 2. Goals & Non-Goals

### Goals

| ID | Goal |
|----|------|
| G1 | Provide `config/retention.php` — central retention policy per data category, overridable via settings |
| G2 | Provide an `ArchiveRecord` model + `archive_records` table as the single registry of archived records |
| G3 | Provide `ArchiveStatus` enum (`ARCHIVED`, `RESTORED`, `PURGED`) with `LabelEnum`/`StatusEnum` transition rules |
| G4 | Provide `ArchiveCohortProcessAction` — archive a completed Internship cohort across modules in one operation |
| G5 | Provide `PurgeExpiredArchivesJob` — queued, scheduled deletion of retention-expired archives via GDPR pipeline |
| G6 | Provide `RestoreArchiveAction` — revert an archive before retention expiry, audited |
| G7 | Provide `ArchiveManager` Livewire component + admin routes for browsing and triggering the lifecycle |
| G8 | Integrate existing student-account archival (E1MSJ) and report snapshots (R6BMW) as capabilities of this lifecycle |
| G9 | Register `archives:purge-expired` on the scheduler for unattended compliance |

### Non-Goals

| ID | Non-Goal |
|----|----------|
| NG1 | Backup creation/restoration — see [backup-system.md](HBXCI-backup-system.md) |
| NG2 | GDPR deletion mechanics, anonymization, deletion-log schema — see [gdpr-compliance.md](7HNCF-gdpr-compliance.md) |
| NG3 | Log pruning, notification pruning, cache warming, health checks — see [system-maintenance.md](E1MSJ-system-maintenance.md) |
| NG4 | Auto-archiving without operator intent — cohort archival is always an explicit admin action (E1MSJ DD-4 principle) |
| NG5 | Cloud/S3 lifecycle policies or object-tiering — single-tenant local storage only |
| NG6 | Multi-tenant partitioning of archives — single-tenant product (S3) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Archives a Completed Cohort

**Actor:** Admin (super_admin / admin)
**Preconditions:** An Internship exists with status `completed` (7C5WM); registrations and their
child records (logbooks, attendance, assessments, reports, certificates) exist for it
**Flow:**
1. Admin opens **Admin → Archives** (`/admin/archives`)
2. Admin selects a completed Internship and confirms archival
3. `ArchiveCohortProcessAction::execute(ArchiveCohortData $data)` validates status, resolves the cohort
4. Action creates one `ArchiveRecord` per cohort aggregate (category `cohort`), stores `retention_until`
   from the retention policy, and records `archived_at` / `archived_by`
5. Action delegates student-account archival to the existing `ArchiveStudentAccountsAction` capability
6. Action logs `data_archive.cohort_archived` via SmartLogger with PII masking
**Postconditions:** Cohort sealed in the archive registry; student accounts transitioned to
`ARCHIVED`; audit trail records the operation

### UC-2 — Scheduler Purges Expired Archives

**Actor:** Scheduler (daily)
**Preconditions:** An `ArchiveRecord` exists with `status = ARCHIVED` and `retention_until < now`
**Flow:**
1. `archives:purge-expired` runs daily at 03:30 via `routes/console.php`
2. Command queries `ArchiveRecord` rows where `retention_until < now AND status = ARCHIVED`
3. Command dispatches `PurgeExpiredArchivesJob` per record (queued)
4. Job purges each record: user-scoped archives via `DeleteUserGdprAction` (7HNCF), record/aggregate
   archives via dependent-record deletion, capturing `GdprDeletionLog` entries
5. `ArchiveRecord.status` transitions to `PURGED`; `purged_at` recorded
**Postconditions:** Expired archives permanently deleted; GDPR deletion logged; registry marks `PURGED`

### UC-3 — Admin Restores an Archived Cohort

**Actor:** Admin
**Preconditions:** An `ArchiveRecord` exists with `status = ARCHIVED` and `retention_until > now`
**Flow:**
1. Admin opens the Archive Manager and selects an archived cohort
2. `RestoreArchiveAction::execute(ArchiveRecord $record)` validates status and retention window
3. Action re-opens the archive (reverses the cohort seal), sets `status = RESTORED`, records
   `restored_at` / `restored_by`
4. Action logs `data_archive.cohort_restored` via SmartLogger
**Postconditions:** Archive reversed and visible in the registry as `RESTORED`; `GdprDeletionLog`
required for `PURGED` records only — no restore after purge

### UC-4 — Admin Browses the Archive Registry

**Actor:** Admin
**Preconditions:** Admin authenticated; at least one `ArchiveRecord` exists
**Flow:**
1. Admin navigates to **Admin → Archives** (`/admin/archives`)
2. `ArchiveManager` renders a paginated table: category, entity reference, status badge, archived
   at/by, retention until / countdown, actions (restore / purge-now)
3. Admin filters by category or status, sorts by archived_at
4. Admin triggers archive / restore / purge with confirmation for destructive actions
**Postconditions:** Admin has full visibility and control over the archival lifecycle

### UC-5 — Existing Capability: Admin Mass-Archives Student Accounts

**Actor:** Admin (via Student Manager)
**Preconditions:** Cohort completed PKL, placement finalized (E1MSJ UC-3)
**Flow:**
1. Admin filters students in Student Manager
2. Clicks "Archive Filtered"
3. `ArchiveStudentAccountsAction` chunks through the filtered query (100/batch)
4. Each user transitioned to `ARCHIVED` status (super_admin skipped)
**Postconditions:** Student accounts archived, login blocked, count reported — this capability feeds
the cohort lifecycle in UC-1 (consolidated, not re-implemented)

---

## 4. Functional Requirements

### Retention Policy

| ID | Requirement |
|-----|-------------|
| FR-RE1 | `config/retention.php` must define a `categories` map with default retention per category: `cohort` (5y), `registration` (10y), `student_account` (5y), `logbook` (5y), `attendance` (10y), `assessment` (10y), `report` (10y), `certificate` (10y), `notification` (30d) |
| FR-RE2 | Each category retention must be overridable via settings key `retention.{category}` through the settings infrastructure (YB22J), falling back to `config/retention.php` |
| FR-RE3 | `ArchiveRetentionPolicy` (service) must expose `yearsFor(string $category): int` returning the effective retention in years (settings override → config default) |
| FR-RE4 | Document-category retention must default from `config/document-official.php` `retention.*_years` when the category maps to an official document type |

### ArchiveRecord Registry

| ID | Requirement |
|-----|-------------|
| FR-AR1 | The `archive_records` table migration must contain: `id` (uuid PK), `category` (string), `reference_type`/`reference_id` (morph — the archived aggregate), `status` (string enum), `retention_until` (timestamp), `archived_at`, `archived_by` (nullable uuid FK users, `nullOnDelete`), `restored_at`, `restored_by`, `purged_at`, `reason` (nullable text), `timestamps` |
| FR-AR2 | `ArchiveRecord` model must use `#[Fillable]` with: `category`, `reference_type`, `reference_id`, `status`, `retention_until`, `archived_at`, `archived_by`, `restored_at`, `restored_by`, `purged_at`, `reason` |
| FR-AR3 | `ArchiveRecord` model must cast `retention_until`, `archived_at`, `restored_at`, `purged_at` to `datetime` |
| FR-AR4 | `ArchiveRecord` must provide `archiver(): BelongsTo` → User via `archived_by` and `restorer(): BelongsTo` → User via `restored_by` |
| FR-AR5 | `ArchiveRecord` must provide `asArchiveRecordState(): ArchiveRecordState` bridge method |
| FR-AR6 | `ArchiveStatus` enum (string, `LabelEnum` + `StatusEnum`) must define cases: `ARCHIVED`, `RESTORED`, `PURGED` with translated labels via `__('sysadmin.archive.status.'.$this->value)` |
| FR-AR7 | `ArchiveStatus::canTransitionTo()` must permit: `ARCHIVED → RESTORED`, `ARCHIVED → PURGED`; `RESTORED` and `PURGED` are terminal |

### Cohort Archival

| ID | Requirement |
|-----|-------------|
| FR-AC1 | `ArchiveCohortProcessAction extends BaseProcessAction` must expose `execute(ArchiveCohortData $data): ActionResponse` |
| FR-AC2 | `ArchiveCohortData` (extends `BaseData`) must carry exactly: `internshipId: string`, `reason: ?string` |
| FR-AC3 | Action must reject a non-`completed` Internship with `RejectedException` |
| FR-AC4 | Action must reject a cohort that already has an active `ARCHIVED` `ArchiveRecord` (no double archive) |
| FR-AC5 | Action must create an `ArchiveRecord` per cohort with `category = cohort`, `retention_until = now + yearsFor('cohort')`, `archived_by = auth()->id()` |
| FR-AC6 | Action must delegate student-account archival to the existing `ArchiveStudentAccountsAction` capability (E1MSJ FR-AS1-4) |
| FR-AC7 | Action must dispatch `CohortArchived` event after successful archival |
| FR-AC8 | Action must log `data_archive.cohort_archived` via SmartLogger with PII masking and `withPayload(['internship_id', 'category', 'retention_until'])` |
| FR-AC9 | Action must return `ActionResponse` carrying the created `ArchiveRecord` |

### Purge

| ID | Requirement |
|-----|-------------|
| FR-PU1 | `PurgeExpiredArchivesJob implements ShouldQueue` must accept `recordIds: array` in its constructor |
| FR-PU2 | Job must set `tries = 3` and `backoff = [2, 10, 30]` |
| FR-PU3 | `archives:purge-expired` command (signature `{--dry-run}`) must query `ArchiveRecord` where `status = ARCHIVED AND retention_until < now` and dispatch `PurgeExpiredArchivesJob` per record |
| FR-PU4 | Job `handle()` must purge each record: user-context archives via `DeleteUserGdprAction` (7HNCF), aggregate archives via dependent-record deletion, then set `status = PURGED`, `purged_at = now()` |
| FR-PU5 | Job must skip records already `PURGED` or `RESTORED` (idempotent) |
| FR-PU6 | Job `failed()` must log via SmartLogger with `withPayload(['record_ids' => ...])` |
| FR-PU7 | Command must support `--dry-run` reporting the count of eligible records without mutating |
| FR-PU8 | Command must be scheduled daily at 03:30 in `routes/console.php` |

### Restore

| ID | Requirement |
|-----|-------------|
| FR-RS1 | `RestoreArchiveAction extends BaseCommandAction` must expose `execute(ArchiveRecord $record, ?string $reason = null): ActionResponse` |
| FR-RS2 | Action must reject a record whose `status` is not `ARCHIVED` with `RejectedException` |
| FR-RS3 | Action must reject a record whose `retention_until < now` (expired archives may only be purged, per UC-3) with `RejectedException` |
| FR-RS4 | Action must set `status = RESTORED`, `restored_at = now()`, `restored_by = auth()->id()`, `reason` |
| FR-RS5 | Action must dispatch `ArchiveRestored` event |
| FR-RS6 | Action must log `data_archive.cohort_restored` via SmartLogger with the archive ID |

### UI & Authorization

| ID | Requirement |
|-----|-------------|
| FR-UI1 | Route `GET /admin/archives` → `ArchiveManager` with `auth` + `role:super_admin\|admin` middleware |
| FR-UI2 | `ArchiveManager` must render a paginated table: category, reference, status badge, archived_at/by, retention countdown, status-filter, category-filter, actions |
| FR-UI3 | `ArchiveManager` must trigger archive (select completed Internship), restore (confirm), and purge-now (confirm) via Actions — no model mutations in the component |
| FR-UI4 | `ArchiveRecordPolicy` must restrict `viewAny/view/create/restore/purge` to admin roles via `isAdmin()` |
| FR-UI5 | All UI strings must use `lang/en|id/sysadmin.php` `archive.*` keys |

### Events

| ID | Requirement |
|-----|-------------|
| FR-EV1 | `CohortArchived extends BaseEvent` must carry the `ArchiveRecord` and expose `eventName()` → `'data_archive.cohort_archived'` |
| FR-EV2 | `ArchiveRestored extends BaseEvent` must carry the `ArchiveRecord` and expose `eventName()` → `'data_archive.cohort_restored'` |
| FR-EV3 | `ArchivePurged extends BaseEvent` must carry the `ArchiveRecord` and expose `eventName()` → `'data_archive.record_purged'` |

---

## 5. Non-Functional Requirements

| ID | Requirement |
|-----|-------------|
| NFR-S1 | Only `super_admin` and `admin` roles may view or trigger archive/restore/purge — enforced by `ArchiveRecordPolicy` |
| NFR-S2 | All archival/purge logging must use SmartLogger with `withPiiMasking()` — no raw PII in log payloads |
| NFR-S3 | `GdprDeletionLog` entries must be created for every `PURGED` record that references a user context (7HNCF integration) |
| NFR-P1 | `ArchiveCohortProcessAction` must complete for a 500-student cohort in under 60 seconds |
| NFR-P2 | `PurgeExpiredArchivesJob` must process records in bounded batches (≤ 100 per job dispatch) to cap memory |
| NFR-R1 | Archive/restore/purge operations must run inside `BaseAction` transactions — no partial registry writes |
| NFR-R2 | Scheduled purge must not halt if a single record fails — continuation pattern (E1MSJ NFR-R1 principle) |
| NFR-M1 | All classes must declare `strict_types=1` (D1) |
| NFR-M2 | All public methods must carry PHPDoc blocks |
| NFR-U1 | All UI labels/statuses must use `__()` with `lang/{en,id}/sysadmin.php` `archive.*` keys (D3) |

---

## 6. API / Data Contracts

### Config

```php
// config/retention.php
return [
    'categories' => [
        'cohort'            => env('RETENTION_COHORT_YEARS', 5),
        'registration'      => env('RETENTION_REGISTRATION_YEARS', 10),
        'student_account'   => env('RETENTION_STUDENT_ACCOUNT_YEARS', 5),
        'logbook'           => env('RETENTION_LOGBOOK_YEARS', 5),
        'attendance'        => env('RETENTION_ATTENDANCE_YEARS', 10),
        'assessment'        => env('RETENTION_ASSESSMENT_YEARS', 10),
        'report'            => env('RETENTION_REPORT_YEARS', 10),
        'certificate'       => env('RETENTION_CERTIFICATE_YEARS', 10),
        'notification'      => env('RETENTION_NOTIFICATION_DAYS', 30), // days, not years
    ],
];
```

Settings override keys (YB22J): `retention.cohort`, `retention.registration`, `retention.student_account`,
`retention.logbook`, `retention.attendance`, `retention.assessment`, `retention.report`,
`retention.certificate`, `retention.notification`.

### Service

```php
// app/Modules/SysAdmin/Archive/Services/ArchiveRetentionPolicy.php
final class ArchiveRetentionPolicy
{
    public function yearsFor(string $category): int;
    // settings('retention.'.$category) ?? config('retention.categories.'.$category)
}
```

### Model

```php
// app/Modules/SysAdmin/Archive/Models/ArchiveRecord.php
#[Fillable(['category', 'reference_type', 'reference_id', 'status', 'retention_until',
    'archived_at', 'archived_by', 'restored_at', 'restored_by', 'purged_at', 'reason'])]
class ArchiveRecord extends BaseModel
{
    protected $casts = [
        'retention_until' => 'datetime',
        'archived_at'     => 'datetime',
        'restored_at'     => 'datetime',
        'purged_at'       => 'datetime',
    ];

    public function archiver(): BelongsTo;   // User via archived_by
    public function restorer(): BelongsTo;   // User via restored_by
    public function asArchiveRecordState(): ArchiveRecordState;
}
```

### Enum

```php
// app/Modules/SysAdmin/Archive/Enums/ArchiveStatus.php
enum ArchiveStatus: string implements LabelEnum, StatusEnum
{
    case ARCHIVED = 'archived';
    case RESTORED = 'restored';
    case PURGED   = 'purged';

    // label(): __('sysadmin.archive.status.'.$this->value)
    // canTransitionTo(): ARCHIVED->[RESTORED, PURGED]; RESTORED/PURGED terminal
}
```

### Actions

```php
// app/Modules/SysAdmin/Archive/Actions/ArchiveCohortProcessAction.php
final class ArchiveCohortProcessAction extends BaseProcessAction
{
    public function execute(ArchiveCohortData $data): ActionResponse;
}

// app/Modules/SysAdmin/Archive/Actions/RestoreArchiveAction.php
final class RestoreArchiveAction extends BaseCommandAction
{
    public function execute(ArchiveRecord $record, ?string $reason = null): ActionResponse;
}
```

### DTO

```php
// app/Modules/SysAdmin/Archive/Data/ArchiveCohortData.php
final class ArchiveCohortData extends BaseData
{
    public function __construct(
        public readonly string $internshipId,
        public readonly ?string $reason = null,
    ) {}
}
```

### Job

```php
// app/Modules/SysAdmin/Archive/Jobs/PurgeExpiredArchivesJob.php
class PurgeExpiredArchivesJob implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [2, 10, 30];

    public function __construct(protected readonly array $recordIds) {}
    public function handle(): void;   // purge via DeleteUserGdprAction / dependent deletion
    public function failed(\Throwable $e): void;
}
```

### Command

```php
// app/Modules/SysAdmin/Archive/Console/Commands/ArchivePurgeCommand.php
class ArchivePurgeCommand extends Command
{
    protected $signature = 'archives:purge-expired {--dry-run : Report eligible records without purging}';
}
```

### Events

```php
// app/Modules/SysAdmin/Archive/Events/CohortArchived.php   — eventName() = 'data_archive.cohort_archived'
// app/Modules/SysAdmin/Archive/Events/ArchiveRestored.php  — eventName() = 'data_archive.cohort_restored'
// app/Modules/SysAdmin/Archive/Events/ArchivePurged.php    — eventName() = 'data_archive.record_purged'
// Each extends BaseEvent and carries public readonly ArchiveRecord $record
```

### Routes

| Method | URI | Handler | Middleware |
|--------|-----|---------|------------|
| GET | `/admin/archives` | `ArchiveManager` | `auth`, `role:super_admin\|admin` |

### Localization

`lang/en|id/sysadmin.php` `archive` section keys: `title`, `empty`, `category.*` (per category),
`status.*` (`archived`/`restored`/`purged`), `actions.*` (`archive`/`restore`/`purge_now`),
`success.*` (`archived`/`restored`/`purged`), `errors.*` (`not_completed`/`already_archived`/
`not_archived`/`expired`).

### Migration

```php
// database/migrations/2026_08_19_000001_create_archive_records_table.php
Schema::create('archive_records', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('category');
    $table->uuidMorphs('reference');
    $table->string('status');
    $table->timestamp('retention_until')->nullable();
    $table->timestamp('archived_at')->nullable();
    $table->foreignUuid('archived_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('restored_at')->nullable();
    $table->foreignUuid('restored_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('purged_at')->nullable();
    $table->text('reason')->nullable();
    $table->timestamps();

    $table->index(['status', 'retention_until']);
});
```

---

## 7. Design Decisions

### DD-1 — Central Registry vs. Distributed Archive Flags

**Decision:** A single `ArchiveRecord` registry tracks archive state, instead of adding an
`is_archived` flag to every archiveable table.
**Rationale:** A registry gives one queryable place for retention, status, and audit (who/when/until).
Cross-module archives (cohort → accounts, reports, certificates) are correlated by one `category` +
morph reference. It also avoids touching every module's schema.
**Trade-off:** The registry references aggregates by morph rather than storing copies — restoring a
purged record is impossible (data is gone), which is the intended compliance behavior.

### DD-2 — Archive vs. Purge: Two Explicit States

**Decision:** `ARCHIVED` (reversible, time-boxed) and `PURGED` (irreversible, GDPR-deleted) are
distinct terminal branches, with `RESTORED` as the exit from `ARCHIVED`.
**Rationale:** Mirrors the legal distinction — retention keeps data safely sealed; after retention
expiry the only legal action is deletion. Restore is allowed only *before* expiry, preventing a
loophole where expired data is resurrected instead of purged.
**Trade-off:** An archived record past retention can no longer be restored even for legitimate
re-opens; the admin must purge-then-export if needed.

### DD-3 — Purge Delegates to GDPR Pipeline

**Decision:** `PurgeExpiredArchivesJob` purges user-context archives through `DeleteUserGdprAction`
(7HNCF) rather than raw `Model::delete()`.
**Rationale:** Reuses the snapshot capture, PII handling, and `GdprDeletionLog` append-only audit that
7HNCF already guarantees — no second deletion path, no orphaned audit.
**Trade-off:** User-context purge depends on the GDPR subsystem; acceptable since compliance demands it.

### DD-4 — Cohort Archival Reuses Student-Account Capability

**Decision:** `ArchiveCohortProcessAction` calls the existing `ArchiveStudentAccountsAction` (E1MSJ)
for the account-sealing step instead of duplicating transition logic.
**Rationale:** DRY — account archival already chunks at 100/batch, skips super_admin, and logs
`student_accounts_archived`. The cohort action is the orchestrator, not a re-implementation.
**Trade-off:** Cohort archival transitively inherits E1MSJ's account behavior (e.g., super_admin
skip); acceptable and intended.

### DD-5 — Retention Driven by Settings + Config

**Decision:** Effective retention = settings override (`retention.{category}`) → `config/retention.php`
default.
**Rationale:** A school should adjust legal-retention periods without a deploy; the settings infra
(YB22J) already provides that. Config supplies deterministic defaults for fresh installs.
**Trade-off:** Two sources of truth; `ArchiveRetentionPolicy` is the single resolution point.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Cohort archival coverage | 100% of completed internships archivable | `ArchiveCohortProcessAction` on every completed cohort |
| Double-archive prevention | 0 duplicate `ARCHIVED` records per cohort | Unique constraint + `FR-AC4` check |
| Purge enforcement | 100% of retention-expired records purged within 24h | `archives:purge-expired --dry-run` vs `PURGED` count |
| GDPR log coverage per purge | 100% of user-context purges log a `GdprDeletionLog` (7HNCF) | Log row count per purged user-context record |
| Restore gate | 0 restores after retention expiry or purge | `FR-RS3`/`FR-PU5` assertions in tests |
| Cohort archive latency | < 60s for 500-student cohort | `ArchiveCohortProcessAction` execution time |
| Registry query | < 200ms paginated browse with 10k records | `ArchiveManager` query time |
| Retention completeness | 100% of records have a non-null `retention_until` at archive time | Migration not-null invariant + test |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [system-maintenance.md](E1MSJ-system-maintenance.md) | `ArchiveStudentAccountsAction`, `AccountStatus::ARCHIVED`, scheduler patterns for `routes/console.php` |
| [gdpr-compliance.md](7HNCF-gdpr-compliance.md) | `DeleteUserGdprAction`, `GdprDeletionLog`, `GdprDeletionType` for user-context purge |
| [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) | `ShouldQueue`, job dispatch conventions, queue configuration |
| [reports.md](R6BMW-reports.md) | FINALIZED report snapshots (`archived_data`) — the report archive target |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | `settings()` resolution for retention overrides |
| [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) | `isAdmin()` policy helper, `role:super_admin\|admin` middleware |
| [base-classes.md](SE5Q9-base-classes.md) | `BaseData`, `BaseCommandAction`, `BaseProcessAction`, `BaseEvent`, `BaseModel`, `RejectedException` |
| [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) | `SmartLogger` with `withPiiMasking()` |
| [internship-lifecycle.md](7C5WM-internship-lifecycle.md) | `InternshipStatus::COMPLETED` — the archival trigger state |

### Build Guide

After implementing this spec, `ArchiveCohortProcessAction` seals a completed cohort into the
`archive_records` registry while delegating account archival to `ArchiveStudentAccountsAction`;
`archives:purge-expired` runs daily to delete retention-expired archives through `DeleteUserGdprAction`
(7HNCF) and mark them `PURGED`; `RestoreArchiveAction` reopens pre-expiry archives. Implement the
retention policy first (`config/retention.php` + `ArchiveRetentionPolicy`), then the registry
(migration + model + status enum), then the lifecycle actions, the purge job + command, and finally
the `ArchiveManager` UI — each layer tested against its FR IDs.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Maintenance is the final phase — runs continuously after all features are built |

---

## 10. Risks & Assumptions

Open risks, assumptions, and unresolved decisions tracked against this spec. Each row links to the
GitHub Issue that tracks resolution; status updates as issues close. See [`spec-template.md`](spec-template.md)
for row conventions.

| ID   | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| ---- | ---------------------------------- | ------ | ----- | -------- |


## Quick References

- `app/Modules/SysAdmin/Archive/Models/ArchiveRecord.php` — Archive registry model (new)
- `app/Modules/SysAdmin/Archive/Enums/ArchiveStatus.php` — `ARCHIVED`/`RESTORED`/`PURGED` enum (new)
- `app/Modules/SysAdmin/Archive/Actions/ArchiveCohortProcessAction.php` — Cohort archival orchestrator (new)
- `app/Modules/SysAdmin/Archive/Actions/RestoreArchiveAction.php` — Pre-expiry restore (new)
- `app/Modules/SysAdmin/Archive/Jobs/PurgeExpiredArchivesJob.php` — Queued expired purge (new)
- `app/Modules/SysAdmin/Archive/Console/Commands/ArchivePurgeCommand.php` — `archives:purge-expired` (new)
- `app/Modules/SysAdmin/Archive/Livewire/ArchiveManager.php` — Archive registry UI (new)
- `config/retention.php` — Central retention policy (new)
- `app/Modules/User/UserManagement/Actions/ArchiveStudentAccountsAction.php` — Delegated account archival (E1MSJ)
- `app/Modules/User/Jobs/ArchiveStudentAccountsJob.php` — Queued account archival (E1MSJ)
- `app/Modules/SysAdmin/Observability/GdprDeletionLog/Actions/DeleteUserGdprAction.php` — Purge delegate (7HNCF)
- `docs/refs/modules/sysadmin.md` — SysAdmin module overview
- **Related specs:** [system-maintenance.md](E1MSJ-system-maintenance.md) — account archival, cleanup, scheduler
- **Related specs:** [gdpr-compliance.md](7HNCF-gdpr-compliance.md) — deletion pipeline, `GdprDeletionLog`
- **Related specs:** [backup-system.md](HBXCI-backup-system.md) — backup before purge
- **Related specs:** [reports.md](R6BMW-reports.md) — report snapshot archival
