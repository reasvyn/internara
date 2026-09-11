# Backup System — Database & Storage Backup, Restore, and Failure Notification

> **Spec ID:** HBXCI
> **Status:** Full
> **Owner:** SysAdmin
> **Depends on:** NUCY3, T4B26, YB22J, TXR2H, 8FVZA

## Description

Specification of Internara's backup system: a `BackupRunner` service that executes database dumps
and storage archives, a `CreateBackupAction` that orchestrates the backup lifecycle
(pending → running → completed/failed) with event dispatch, a `SystemBackupCommand` CLI with
scheduled execution, a `BackupManager` Livewire component for admin-facing backup management, and
failure notification to super admins. Backup retention and cleanup lives in the companion
[backup-retention-cleanup](HBXCI-backup-retention-cleanup.md) spec.

Recovery objectives for the whole product are fixed here: a 4-hour recovery point objective with
under-1-hour recovery time, verified by periodic restore drills. Cross-cutting contracts come from
[architecture](D2FT3-architecture.md) (Action Triad, entities), [logging](89SRA-logging-and-error-handling.md)
(SmartLogger dual-channel with PII masking), and the [queue](8FVZA-job-queue-infrastructure.md)
spec (queued notification delivery).

---

## 1. Problem Statements

### PS-1 — No Automated Database Backup Mechanism

Internara stores all internship data — registrations, attendance, logbooks, evaluations,
certificates — in a single database. Without automated backups, a server failure, accidental
deletion, or corrupted migration results in total data loss. Schools have no IT staff to run
manual dump commands on a schedule.
**→ Requirement:** FR-BACK-006 (creation lifecycle), FR-BACK-019 (daily schedule).

### PS-2 — Uploaded Files Are Not Backed Up

Student photos, company logos, certificate templates, and generated PDFs live on local storage
(`storage/app/`). A disk failure or accidental deletion destroys these files permanently. There
is no mechanism to archive `storage/app/public/` alongside database dumps.
**→ Requirement:** FR-BACK-013 (storage archive), FR-BACK-014 (combined dump).

### PS-3 — No Visibility Into Backup Status

Administrators have no way to know whether backups are running, succeeding, or failing. A silent
backup failure (disk full, permission denied) goes unnoticed until data loss occurs.
**→ Requirement:** FR-BACK-010 (history), FR-BACK-011 (stats), UC-BACK-001 (management UI).

### PS-4 — No Failure Alerting for Backup Operations

When a scheduled backup fails (database locked, storage full, driver not installed), no one is
notified. The failure is only discoverable by manually checking logs — which school
administrators never do.
**→ Requirement:** FR-BACK-008 (failure path), FR-BACK-024/025 (super-admin fan-out).

---

## 2. Goals & Non-Goals

### Goals

- **Automated database dumps for every supported driver** — MySQL, PostgreSQL, SQLite through one runner. *Why:* schools have no staff to run manual dumps.
- **Storage archived alongside the database** — `storage/app/public/` in the same lifecycle. *Why:* certificates and photos are as irreplaceable as rows.
- **Lifecycle with visible status** — pending → running → completed/failed, queryable from the UI. *Why:* silent backup failure is how data loss surprises schools.
- **Failure notification to super admins** — database-channel alert on every failed run. *Why:* nobody reads logs; the alert must come to the operator.
- **Recovery objectives with drills** — 4-hour RPO, under-1-hour RTO, proven by restore practice. *Why:* a backup never restored is a hope, not a plan.
- **Admin-only operations end to end** — policy gates on every path. *Why:* backup files contain the whole school.

### Non-Goals

- **Backup restoration via UI or CLI**. *Why:* restoration is a manual procedure documented in the recovery guide; automating it risks one-click data destruction.
- **Remote backup destinations (S3, SFTP, cloud storage)**. *Why:* local-only keeps the single-tenant sovereignty promise; off-site copies are operator practice, not product.
- **Encrypted backups or backup encryption keys**. *Why:* key management without IT staff creates more lockout risk than it removes.
- **Incremental or differential backups**. *Why:* every backup is a full snapshot; school-scale data makes incrementals unnecessary complexity.
- **Multi-tenant backup isolation**. *Why:* single-tenant architecture makes it unnecessary.
- **Real-time continuous backup or point-in-time recovery**. *Why:* daily full snapshots meet the 4-hour RPO at this scale.
- **Backup download via the web UI**. *Why:* backups are accessed directly from the filesystem by the operator.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). These four operator workflows
are verifiable at this spec's level, so `Layer`/`Status` are filled.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-BACK-001 | Admin creates a manual backup from the manager UI and sees it complete with file metadata | P0 | F | Full |
| UC-BACK-002 | Operator creates a backup from the CLI with type selection and optional retention cleanup | P0 | F | Full |
| UC-BACK-003 | A failing scheduled backup records the failure and notifies all super admins | P0 | F | Full |
| UC-BACK-004 | Admin deletes a deletable backup from the UI with confirmation; record and file are removed | P1 | F | Full |

### 3.1 Backup Operation

#### UC-BACK-001 — Admin Creates a Manual Backup via UI

Monday morning, the day before a server migration the school's part-time operator has been
dreading, the admin opens the backup manager and picks "Database" from the create dropdown.
The row appears immediately as running, and a minute later flips to completed with a human file
size beside it. That visible flip is the whole point: the admin walks into the migration with a
fresh, verified snapshot instead of a prayer, and the stats cards above the table confirm the
latest completed backup is minutes old rather than weeks.

#### UC-BACK-002 — Admin Creates a Backup via CLI

Over SSH the operator runs the backup command with an explicit type and the cleanup flag in one
line. The command first checks whether backups are enabled at all — refusing politely unless
forced — then resolves the type, runs the same Action the UI calls, and prints the formatted
size on success alongside how many expired backups the cleanup pass reclaimed. One entry point
serves both the human at the terminal and the scheduler at 2 a.m., so the nightly run and the
manual run can never drift apart in behavior.

#### UC-BACK-003 — Backup Fails and Super Admins Are Notified

At 2 a.m. the scheduled run fires into a full disk and the dump command dies. The Action
catches the failure, marks the record failed with the error text attached, writes the failure
to the logs, and fires the failure event — and minutes later every super admin finds a
notification waiting in the notification center naming the backup, its type, and what went
wrong. Nobody checked a log file. The school learns about the full disk from the product
itself, with the diagnostic text preserved on the failed record for the morning.

#### UC-BACK-004 — Admin Deletes a Backup via UI

The table has grown long and the admin prunes an old completed snapshot: trash icon,
confirmation modal, delete. Behind the modal the Action first asks the entity whether this
record is deletable — running backups refuse, because deleting beneath a live dump corrupts
both — then removes the physical file before touching the database row. The order matters more
than it looks: a crash between the two steps leaves a phantom record pointing at nothing,
which is annoying, whereas the reverse order would leave a phantom file nobody can account
for, which is dangerous.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer that will verify it.
`Status` tracks implementation of this specific requirement.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-BACK-001 | Backup model exposes the fillable set, casts, creator relation, and entity bridge | P0 | A | Full |
| FR-BACK-002 | Backups table uses a UUID primary key with typed columns, null-on-delete creator, and a status/created index | P0 | A | Full |
| FR-BACK-003 | BackupStatus enum carries the four lifecycle states with translated labels, terminal detection, and a transition map | P0 | U | Full |
| FR-BACK-004 | BackupType enum carries database, storage, and combined cases with translated labels | P0 | U | Full |
| FR-BACK-005 | BackupState entity derives deletability, human size, and type from the model without persisting | P0 | U | Full |
| FR-BACK-006 | CreateBackupAction runs the full lifecycle inside a transaction and delegates dump work by type | P0 | F | Full |
| FR-BACK-007 | Successful runs record file metadata, write the creation audit entry, and fire the completion event | P0 | F | Full |
| FR-BACK-008 | Failed runs record the error, write the failure audit entry, fire the failure event, and raise RejectedException | P0 | F | Full |
| FR-BACK-009 | DeleteBackupAction refuses non-deletable records, removes the file before the row, and logs the deletion | P0 | F | Full |
| FR-BACK-010 | Backup history is served by the manager component with pagination, eager creator, and type/status filters | P0 | F | Full |
| FR-BACK-011 | Stats read returns total, completed, failed, and latest completed backup | P1 | F | Full |
| FR-BACK-012 | Database dumps dispatch per configured driver to fixed paths, creating the directory and rejecting unknown drivers | P0 | F | Full |
| FR-BACK-013 | Storage dumps archive the public disk via tar to a timestamped file | P0 | F | Full |
| FR-BACK-014 | Combined dumps build both parts, merge them, and remove intermediates | P0 | F | Full |
| FR-BACK-015 | File deletion is confined to the backup directory; size lookup returns zero for missing files | P0 | F | Full |
| FR-BACK-016 | Credentials travel in mode-0600 temporary files that are always cleaned up | P0 | F | Full |
| FR-BACK-017 | The backup command exposes type, force, and cleanup options | P0 | F | Full |
| FR-BACK-018 | The command respects the enabled flag, resolves the type, and reports size and cleanup counts | P0 | F | Full |
| FR-BACK-019 | The backup command runs on the daily schedule | P0 | F | Full |
| FR-BACK-020 | The manager component authorizes, lists, filters, creates, and deletes through the Actions | P0 | F | Full |
| FR-BACK-021 | The manager view shows stats cards, deletable-only delete buttons, and the help guide | P1 | B | Full |
| FR-BACK-022 | Backup policy restricts every operation to admins | P0 | U | Full |
| FR-BACK-023 | Completion and failure domain events carry the backup with stable event names | P0 | F | Full |
| FR-BACK-024 | The failure listener notifies every super admin | P0 | F | Full |
| FR-BACK-025 | The failure notification travels the database channel as a queued payload of backup id, type, error, and message | P0 | F | Full |

### 4.1 Persistence

#### FR-BACK-001 — The model is a thin, honest record

Earlier drafts spread model guarantees across eleven overlapping rows; they collapse here into
one contract. The `Backup` model declares exactly which fields mass assignment may touch,
casts sizes, metadata, and timestamps into their working types, belongs to its creator, and
bridges to the entity for every derived question. Nothing about deletability or formatting
lives on the model — those are entity judgments — so the model stays a persistence adapter
that a migration can reshape without breaking UI logic.

#### FR-BACK-002 — The table is built for lookup and survival

A backup table that cannot answer "what is the latest completed backup" quickly makes every
dashboard render a full scan, hence the composite index on status and creation time. The
creator key nulls out instead of cascading because the operator who ran last month's backup
may leave the school while the backup must remain; an audit trail that deletes itself when
staff turns over is no trail at all. UUID keys keep the table consistent with every other
primary entity in the system.

### 4.2 Status & Type Model

#### FR-BACK-003 — Four states with nowhere illegal to go

Pending, running, completed, failed — and the transition map is the lifecycle's seatbelt. A
completed backup can never re-enter running, which is what stops a retry race from
re-dumping over a verified snapshot. Terminal detection lets cleanup, deletion guards, and
the UI share one definition of "finished" instead of each re-deciding it with string
comparisons scattered across the codebase.

#### FR-BACK-004 — Three backup kinds, named once

Database, storage, or both — the enum fixes the vocabulary so the CLI flag, the UI dropdown,
and the runner's dispatch arms can never disagree on what "both" means. Labels resolve
through translations, keeping the Indonesian-first promise even on infrastructure screens
that developers are tempted to leave in English.

#### FR-BACK-005 — The entity answers, never writes

`BackupState` is a frozen snapshot of one row: is it deletable, how big is it in human
terms, which kind is it. Constructing it needs no database, so the deletability rule and
the size formatter are unit-tested in milliseconds against hand-built snapshots. The
`fromModel` bridge is the single point where a column rename ripples, and everything
downstream — the manager, the delete guard, the Blade badges — talks to named predicates
instead of raw attributes.

### 4.3 Backup Actions

#### FR-BACK-006 — One Action owns the whole lifecycle

At 2 a.m. nobody is watching, so the creation path must be a single traceable sequence:
open a transaction, write the running record, hand the dump to the runner by type, then
close out as completed or failed. Splitting those steps across callers is how earlier
designs produced running-forever rows when a dump crashed between record creation and
status update. Constructor injection of the runner keeps the sequence testable — tests
swap in a fake runner and assert the lifecycle without shelling out to real dump tools.

#### FR-BACK-007 — Success leaves a complete trail

When the dump lands, the record gains its path, size, completion timestamp, and completed
status in the same transaction, the audit log gains a creation entry with actor and size,
and the completion event fans out to any listener the future adds. Each of the three
serves a different reader — the operator checking the UI, the auditor reconstructing
history, the automation reacting to fresh snapshots — and all three fire together or not
at all.

#### FR-BACK-008 — Failure is loud, masked, and typed

A failed dump writes its error text onto the record, logs through SmartLogger so PII
masking applies before anything reaches a sink, fires the failure event that wakes the
super-admin notifications, and raises `RejectedException` so callers handle it as a
business-rule outcome rather than an infrastructure crash. The exception choice is
deliberate: a backup that cannot run is a rejected operation with a user-facing message,
not a 500 page, and Livewire renders it accordingly.

#### FR-BACK-009 — Deletion asks permission, then removes evidence before record

The deletability check runs inside the Action rather than only in the policy, so even a
hand-crafted component call cannot delete a running backup mid-dump. File first, row
second: if file removal fails the Action throws with the record intact, while the
reverse order would orphan a file nobody references. The deletion audit entry carries
type and size so the trail shows what left the building.

#### FR-BACK-010 — History flows through the standard table pipeline

An earlier revision carried a dedicated history Read Action that duplicated the generic
table query and fought the base record manager's search, sort, and pagination pipeline —
it was deleted and history moved into the manager component's query and filter hooks.
The guarantees survived the move: paginated results, eager-loaded creators so the table
never N+1s across a hundred rows, and conditional type/status filters that compose with
the standard pipeline instead of bypassing it.

#### FR-BACK-011 — Stats answer the morning question in one call

Total, completed, failed, latest completed — four numbers that tell the operator whether
last night worked, served by a lock-free read that never touches the backup lifecycle's
transactions. The latest-completed pointer is what the RPO math consumes: compare its
timestamp against now and the school knows its worst-case data loss window at a glance.

### 4.4 Runner Service

#### FR-BACK-012 — One dump entry point, per-driver execution

The runner reads the configured driver and takes the matching path — transaction-safe
dump flags for MySQL, password-file authentication for PostgreSQL, file copy plus
compression for SQLite — writing to a fixed timestamped path and creating the backup
directory on first use. An unknown driver throws instead of guessing, because a silently
skipped dump is worse than a loud failure: the loud one pages the super admins, the
silent one is discovered after the disk dies.

#### FR-BACK-013 — The public disk rides along

Student photos and generated certificates live outside the database, so a rows-only
backup restores a school with no faces and no documents. The storage dump tars the
public disk into its own timestamped archive on the same schedule, which means a full
restore replays two files — dump plus archive — instead of one file plus an apology.

#### FR-BACK-014 — Combined mode keeps no litter behind

Combined backups build the database and storage parts individually, merge them into one
archive, and delete the intermediates — the operator downloads a single file and the
disk never accumulates half-assembled pieces. If the merge step fails, the parts remain
as ordinary timestamped files rather than mystery temp names, so nothing is
unidentifiable afterward.

#### FR-BACK-015 — Deletion cannot escape its directory

Every file-removal path resolves the real path and confirms it sits inside the backup
directory before unlinking, which closes the traversal hole where a crafted stored path
could point at arbitrary files. Size lookup treats a missing file as zero bytes rather
than an error, because cleanup and stats routinely ask about files that a previous
crashed run already removed.

#### FR-BACK-016 — Credentials never touch a command line

Database passwords travel in temporary files with owner-only permissions, passed by
reference to the dump tools, and removed afterward — they never appear in process lists,
shell history, or log output. This is the row the reliability metrics audit: a single
credential sighting in any log is a defect, not a footnote.

### 4.5 CLI & Schedule

#### FR-BACK-017 — Three flags cover every invocation

Type selects the backup kind, force overrides the enabled check for emergency runs, and
cleanup chains the retention pass so the nightly run reclaims space in the same breath.
Keeping the surface at three flags is intentional: every flag added here is a flag the
scheduler, the docs, and the operator's memory must all carry.

#### FR-BACK-018 — The command behaves the same for humans and the scheduler

Disabled means disabled — the command exits with a warning instead of running — unless
force says otherwise, which is the escape hatch for the migration-weekend emergency. On
success the output names the size in human terms and, when cleanup ran, how many expired
backups left the disk; that output is the scheduler's log line and the operator's
receipt, produced by the same code path either way.

#### FR-BACK-019 — Nightly by default, through the scheduler

The daily schedule entry is what turns backups from an intention into a habit: without
it, every school means to back up and none does. Daily cadence against the 4-hour RPO
leaves headroom for the scheduled time to drift without breaching the objective, and
the schedule description names the behavior so the scheduler list reads as documentation.

### 4.6 Management UI & Policy

#### FR-BACK-020 — The manager is thin; the Actions are not

Boot authorizes, headers declare columns, query and filters shape the table, stats
delegate to the read, and create/delete delegate to the command Actions with flash
messages on the way out. No business rule lives in the component — the deletability
refusal, the type resolution, the lifecycle — so the CLI and the UI cannot implement
the same operation two different ways.

#### FR-BACK-021 — The view shows state, not just rows

Stats cards answer the morning question before any scrolling, delete buttons appear
only where the entity permits deletion so impossible actions are never offered, and the
floating guide puts create and restore instructions one tap away. A school operator with
no training should be able to run a backup and read its outcome without opening any
other page.

#### FR-BACK-022 — Admin-only means every method, not most

View, create, and delete each check admin status independently — there is no "view is
harmless so it can stay open" exception, because the backup list itself reveals the
school's data rhythms. The unit tests walk every gate with every role, since a single
unguarded method would expose the whole school's snapshots.

### 4.7 Failure Fan-Out

#### FR-BACK-023 — Two events, stable names, full payload

Completion and failure each get a domain event carrying the backup record under a
fixed name, which is what lets future listeners — retention hooks, health dashboards,
off-site copy triggers — subscribe without touching backup code. The names are part of
the contract: renaming an event is a breaking change for every subscriber, so they are
fixed here.

#### FR-BACK-024 — Every super admin hears about failure

The listener resolves all super-admin accounts and notifies each one, rather than a
configured address list, because address lists rot — people leave, inboxes die — while
the role roster is always current. A school where the only super admin changed phones
still gets the alert on next login through the database channel.

#### FR-BACK-025 — The alert queues and carries the essentials

Database channel keeps the notification inside the product where the operator already
works; the queueable trait keeps a slow mail path from blocking the failure handling
itself. The payload is deliberately small — backup id, type, error, message — because
the notification links to the full record instead of duplicating it, and small
payloads survive queue serialization without surprises.

---

## 5. Non-Functional Requirements

A Non-Functional Requirement is a measurable constraint on the system. `Target` is the concrete
number/SLO. `Priority` and `Status` behave as in §4.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-BACK-001 | Database dumps complete within budget for school-scale databases | 60 s for DB up to 500 MB | P0 | F | Full |
| NFR-BACK-002 | Recovery point and recovery time objectives hold with drill evidence | 4 h RPO / 1 h RTO; drill each period | P1 | F | Full |
| NFR-BACK-003 | All backup operations are restricted to admin users | 0 non-admin accesses | P0 | U | Full |
| NFR-BACK-004 | File deletion cannot escape the backup directory | 0 traversal incidents | P0 | F | Full |
| NFR-BACK-005 | Database credentials never appear in logs or process output | 0 exposures | P0 | F | Full |
| NFR-BACK-006 | Backup records survive deletion of their creating user | 100% records preserved | P1 | F | Full |
| NFR-BACK-007 | Lifecycle runs transactionally with file-before-record deletion order | < 1% orphan references | P0 | F | Full |
| NFR-BACK-008 | Temporary credential files are removed even when dumps throw | 0 leftover temp files | P0 | F | Full |
| NFR-BACK-009 | Backup UI is localized with guided help and color-coded status | all labels via `__()`; guide present | P1 | B | Full |
| NFR-BACK-010 | Backup code follows strict typing with private, patterned storage | 100% `strict_types`; `storage/app/backup/` | P1 | A | Full |

### 5.1 Recovery Objectives

#### NFR-BACK-001 — The dump fits inside the night

Five hundred megabytes covers the largest schools on record — tens of thousands of
attendance rows plus a year of documents — and sixty seconds keeps the dump inside the
scheduler window with room for the storage archive after it. If a school ever outgrows
the budget, the number forces an explicit conversation about splitting dumps rather
than a slow silent overrun that starts colliding with morning traffic.

#### NFR-BACK-002 — Objectives are proven, not declared

Four hours of worst-case loss and one hour to restore are the numbers the whole backup
design serves: daily scheduling with headroom, fixed file layouts the restore guide can
narrate blindfolded, and a drill each period where the school actually restores to a
staging copy and times it. The drill is the requirement's teeth — an unr drilled backup
is the hope this spec exists to eliminate, and the drill log is the evidence the RTO
claim stands on.

### 5.2 Security & Integrity

#### NFR-BACK-003 — The whole school in one file demands the strictest gate

A backup archive contains every student record, every grade, every document — the
highest-value file the system produces. Admin-only on every path is the floor, and the
verification walks each gate with each role because the failure mode is silent: an
open endpoint nobody notices until the archive walks out the door.

#### NFR-BACK-004 — Path handling is a security boundary, not a convenience

The night a stored path contains `../..` — by malice or by a corrupted record — the
realpath check is the only thing between a cleanup run and deleted application code.
Zero incidents is the target because one is already catastrophic, and the check sits
in the single choke point every deletion flows through rather than at each call site.

#### NFR-BACK-005 — Credential hygiene is audited, not assumed

Temporary credential files, owner-only permissions, guaranteed removal, and SmartLogger
masking on every backup log line form four layers between a database password and the
outside world. The audit greps logs and process captures for credential patterns after
backup runs — any hit is a defect against this row, since passwords in logs outlive
every rotation policy.

#### NFR-BACK-006 — Staff turnover must not eat history

Operators graduate, change roles, and leave; the backups they ran stay. The null-on-delete
foreign key is a small schema choice with a large consequence — without it, deleting a
departed operator's account would cascade away the evidence of every backup they ever
ran, right when an auditor asks who ran the pre-migration snapshot.

### 5.3 Operability & Hygiene

#### NFR-BACK-007 — Crash order favors the annoying failure over the dangerous one

If the process dies between file deletion and row deletion, a phantom record points at
nothing — visible, re-deletable, harmless. The reverse order would leave a phantom file
consuming disk with no record admitting it exists. The transaction around the lifecycle
narrows the crash window; the deletion order picks the safe side of whatever window
remains.

#### NFR-BACK-008 — Temp files die with the operation, success or throw

Credential temp files cleaned only on the happy path accumulate on every failure — and
failures cluster, so the happy-path-only cleanup fills a directory with secrets exactly
when the system is least watched. The finally-block guarantee makes cleanup
unconditional, and the verification counts temp files after forced-failure runs.

#### NFR-BACK-009 — Infrastructure screens still speak the operator's language

Backup screens are used by the least technical admins in the school, so every label
passes through translation and the status badges read at a glance — green completed,
red failed, amber running, blue pending. The embedded guide means the restore
instructions are already open on the screen of the person having the worst day of
their semester.

#### NFR-BACK-010 — Strict types and a private, predictable home

Every backup class declares strict types so path strings and byte counts never
silently coerce, files land in a non-public directory under timestamped names the
operator can sort by eye, and the naming pattern lets a human distinguish a database
dump from a storage archive without opening either.

---

## 6. API / Data Contracts

### 6.1 Backup Model

```php
// app/Modules/SysAdmin/Domain/Backups/Models/Backup.php
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
// app/Modules/SysAdmin/Domain/Backups/Enums/BackupStatus.php
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
// app/Modules/SysAdmin/Domain/Backups/Enums/BackupType.php
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
// app/Modules/SysAdmin/Domain/Backups/Entities/BackupState.php
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
// app/Modules/SysAdmin/Domain/Backups/Actions/CreateBackupAction.php
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
// app/Modules/SysAdmin/Domain/Backups/Actions/DeleteBackupAction.php
final class DeleteBackupAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}
    public function execute(Backup $backup): void;
    // Validates isDeletable(), deletes file, deletes record, logs backup_deleted
}
```

### 6.8 BackupManager History Query

```php
// app/Modules/SysAdmin/Domain/Backups/Livewire/BackupManager.php
final class BackupManager extends BaseRecordManager
{
    protected function query(): Builder
    {
        return Backup::query()->with('creator');
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filterType, fn ($q, $t) => $q->where('type', $t))
            ->when($this->filterStatus, fn ($q, $s) => $q->where('status', $s));
    }
}
```

### 6.9 ReadBackupStatsAction

```php
// app/Modules/SysAdmin/Domain/Backups/Actions/ReadBackupStatsAction.php
final class ReadBackupStatsAction extends BaseReadAction
{
    public function execute(): array;
    // Returns ['total' => int, 'completed' => int, 'failed' => int, 'latest' => ?Backup]
}
```

### 6.10 BackupRunner Service

```php
// app/Modules/SysAdmin/Domain/Backups/Services/BackupRunner.php
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

### 6.11 SystemBackupCommand

```php
// app/Modules/SysAdmin/Domain/Backups/Console/Commands/SystemBackupCommand.php
final class SystemBackupCommand extends Command
{
    protected $signature = 'system:backup
        {--type= : Backup type: database, storage, or both}
        {--force : Skip pre-flight checks}
        {--cleanup : Run retention cleanup after backup (see backup-retention-cleanup.md)';
    // Checks config('backup.enabled'), resolves BackupType, delegates to CreateBackupAction
}
```

### 6.12 BackupManager Livewire Component

```php
// app/Modules/SysAdmin/Domain/Backups/Livewire/BackupManager.php
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

### 6.13 BackupPolicy

```php
// app/Modules/SysAdmin/Domain/Backups/Policies/BackupPolicy.php
class BackupPolicy extends BasePolicy
{
    public function viewAny(User $user): bool;   // isAdmin
    public function view(User $user, Backup $backup): bool;  // isAdmin
    public function create(User $user): bool;    // isAdmin
    public function delete(User $user, Backup $backup): bool; // isAdmin
}
```

### 6.14 Events

```php
// app/Modules/SysAdmin/Domain/Backups/Events/BackupCompleted.php
final class BackupCompleted extends BaseEvent
{
    public function __construct(public readonly Backup $backup) {}
    public function eventName(): string { return 'backup.completed'; }
}

// app/Modules/SysAdmin/Domain/Backups/Events/BackupFailed.php
final class BackupFailed extends BaseEvent
{
    public function __construct(public readonly Backup $backup) {}
    public function eventName(): string { return 'backup.failed'; }
}
```

### 6.15 SendBackupFailedNotification Listener

```php
// app/Modules/SysAdmin/Domain/Backups/Listeners/SendBackupFailedNotification.php
final class SendBackupFailedNotification
{
    public function handle(BackupFailed $event): void;
    // Queries User::role('superadmin')->get(), notifies each with BackupFailedNotification
}
```

### 6.16 BackupFailedNotification

```php
// app/Modules/SysAdmin/Domain/Backups/Notifications/BackupFailedNotification.php
final class BackupFailedNotification extends Notification
{
    use Queueable;
    public function __construct(public readonly Backup $backup) {}
    public function via(User $notifiable): array { return ['database']; }
    public function toDatabase(User $notifiable): array;
    // Returns: backup_id, type, error, message (translated)
}
```

### 6.17 Routes

```php
// routes/web/sysadmin.php
Route::get('/backups', BackupManager::class)->name('backups');
// Middleware: auth, role:super_admin|admin
```

### 6.18 Schedule

```php
// routes/console.php
Schedule::command('system:backup')
    ->daily()
    ->description('Run scheduled system backup if enabled');
```

### 6.19 File Naming Convention

| Backup Type | File Pattern | Example |
|-------------|-------------|---------|
| Database | `backup_database_{Y-m-d_His}.sql.gz` | `backup_database_2026-07-24_020000.sql.gz` |
| Storage | `backup_storage_{Y-m-d_His}.tar.gz` | `backup_storage_2026-07-24_020000.tar.gz` |
| Both | `backup_both_{Y-m-d_His}.tar.gz` | `backup_both_2026-07-24_020000.tar.gz` |

### 6.20 Database Driver Commands

| Driver | Tool | Key Flags |
|--------|------|-----------|
| MySQL | `mysqldump` | `--single-transaction --routines --skip-lock-tables`, credentials via `--defaults-extra-file` |
| PostgreSQL | `pg_dump` | `--format=c`, credentials via `PGPASSFILE` |
| SQLite | `cp` + `gzip -f` | Direct file copy then compress |

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-BACK-001 | BackupRunner as an injectable service class, not a static helper | P0 | — | — |
| DD-BACK-002 | Entity layer for backup state logic instead of model methods | P0 | — | — |
| DD-BACK-003 | Full backup lifecycle inside one transaction in the creation Action | P0 | — | — |
| DD-BACK-004 | Physical file deletion before database record deletion | P0 | — | — |

### 7.1 Structure

#### DD-BACK-001 — A Runner With Memory, Not a Static Helper

The runner carries a timestamp so every file in one backup operation shares a name stem,
tracks its credential temp files for guaranteed cleanup, and arrives by constructor
injection so tests substitute a fake without touching the shell. A static helper could
do none of that without global state, and backups run once a day — instantiation cost
is unmeasurable against a sixty-second dump.

#### DD-BACK-002 — State Judgments Belong to the Entity

Deletability, human-readable size, and type resolution read like model conveniences, but
putting them on the model couples UI logic to the query builder and the test database.
The entity answers all three from a frozen snapshot, unit-tested in milliseconds, while
the model keeps relationships and casts. The Blade template asks the entity; the
sixty-line class is the price of that separation.

### 7.2 Lifecycle & Deletion

#### DD-BACK-003 — The Transaction Holds the Whole Lifecycle

Record creation, dump execution, and status update share one transaction so a crash
between steps rolls the record back instead of stranding a running-forever row. The
cost is a transaction held open across a slow dump — acceptable against a table
written once a day, and the driver flags keep the dump itself from locking live
tables while it runs.

#### DD-BACK-004 — Delete the File First, Mourn the Record After

File-then-row means a failure mid-delete throws with the record intact and retryable,
while a crash between the steps leaves a phantom record — visible and re-deletable.
Row-then-file would risk the mirror image: disk consumed by files no record admits
exist. Given the choice between an annoying phantom and an invisible one, the spec
picks annoying every time.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Database dump (up to 500 MB DB) | < 60s | `BackupRunner::runDatabaseDump()` execution time |
| Storage archive (up to 1 GB files) | < 120s | `BackupRunner::runStorageDump()` execution time |
| Restore drill | RTO met each period | Timed restore to staging per NFR-BACK-002 |
| Database drivers supported | MySQL, PostgreSQL, SQLite | `BackupRunner` match arms |
| Backup types | database, storage, both | `BackupType` enum cases |
| Policy coverage | 100% admin-only | `BackupPolicy` enforced on all operations |
| Credential exposure in logs | 0 occurrences | Grep for credential patterns after runs |
| Failed backup notification delivery | 100% to super admins | Listener iterates all superadmin-role users |
| Path traversal in file deletion | 0 incidents | Traversal fixtures against `deleteFile()` |
| Non-admin backup access | 0 incidents | Per-role gate tests on all operations |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `BaseReadAction`, `BaseEntity`, `BaseEvent`, `BaseModel`, `BasePolicy`, `RejectedException` base classes |
| [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) (89SRA) | `SmartLogger` for backup event logging |
| [event-system.md](NUCY3-event-system.md) (NUCY3) | `BaseEvent` contract, event dispatch and listener registration patterns |
| [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) (T4B26) | `isAdmin()` policy helper, role-based gates, `super_admin\|admin` middleware |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) (YB22J) | `backup.enabled` and `backup.retention_days` settings integration |
| [notification-infrastructure.md](TXR2H-notification-infrastructure.md) (TXR2H) | Database notification channel for failure delivery |
| [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) (8FVZA) | Queue configuration for async notification delivery |

### Build Guide

After implementing this spec, the backup system is fully operational: admins create backups via
UI or CLI, view history with stats, delete old backups, and receive failure notifications. The
backup command runs daily and respects the enabled flag. The runner handles MySQL, PostgreSQL,
and SQLite with secure credential handling. Retention purging is the companion
[backup-retention-cleanup](HBXCI-backup-retention-cleanup.md) spec.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [backup-retention-cleanup.md](HBXCI-backup-retention-cleanup.md) (HBXC2) | Retention cleanup purges expired completed backups |
| 2 | [system-maintenance.md](E1MSJ-system-maintenance.md) (E1MSJ) | Backup health runs alongside system maintenance tasks |
| 3 | [settings-infrastructure.md](YB22J-settings-infrastructure.md) (YB22J) | `backup.enabled`, `backup.retention_days` exposed in Admin → Settings UI |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If no operator ever runs the periodic restore drill, the RTO claim degrades to an untested assertion; mitigation is the drill cadence in NFR-BACK-002 | Open | Maintainer | — |
| A-1 | We assume daily full snapshots fit the school's disk alongside 30 days of retention; schools near capacity tune retention down per the companion spec | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Backup retention cleanup](HBXCI-backup-retention-cleanup.md) — retention purging companion spec
- [Job & queue infrastructure](8FVZA-job-queue-infrastructure.md) — queued notification delivery
- [Architecture](D2FT3-architecture.md) — Action Triad, entities, module layout
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — local-only backup rationale
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — audit logging with PII masking
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — RejectedException for rejected operations
