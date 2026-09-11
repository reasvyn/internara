# Backup Retention Cleanup — Scheduled Backup Purging

> **Spec ID:** HBXC2
> **Status:** Planned
> **Owner:** SysAdmin
> **Depends on:** HBXCI

## Description

Specification for backup retention cleanup: purging completed backups older than the configured
retention period (default 30 days) while preserving failed backups as diagnostic evidence. The
backup creation lifecycle — dumps, storage archives, lifecycle status, failure notification — is
defined in [backup-system](HBXCI-backup-system.md); this spec owns only what happens to completed
backups after their retention window closes.

> **Registry note:** this file is owned by registry ID `HBXC2` per the spec index; the filename
> keeps the shared `HBXCI-` prefix because both files govern the backup area.

---

## 1. Problem Statements

### PS-1 — Backup Files Accumulate Without Cleanup

Without retention policies, backup files accumulate indefinitely, consuming disk space. Schools
running on shared hosting with limited storage (50–100 GB) eventually run out of space if old
backups are never purged — and the failure that follows is a failed nightly backup on the
worst possible night.
**→ Requirement:** FR-RET-002 (expiry purge), FR-RET-007 (configurable window).

### PS-2 — Failed Backups Must Be Preserved for Diagnostics

Failed backup records contain `error_output` essential for diagnosing backup failures.
Auto-deleting them during cleanup would lose diagnostic evidence. Schools review and manually
delete failed backups via the UI instead.
**→ Requirement:** FR-RET-002 (completed-only scope), NFR-RET-001 (preservation guarantee).

---

## 2. Goals & Non-Goals

### Goals

- **Delete completed backups past the retention window** — default 30 days, configurable. *Why:* bounded disk usage on storage-poor shared hosting.
- **Process deletions in bounded chunks** — 100 records at a time. *Why:* memory stays flat regardless of how many backups expired.
- **Delete physical files before database records** — same crash-safe order as single deletion. *Why:* phantom records beat phantom files.
- **Log each cleanup pass** — retention days and deleted count. *Why:* the audit trail shows what left and when.
- **Trigger through the backup command's cleanup flag** — one scheduled path, not two. *Why:* a single nightly invocation cannot drift into two behaviors.

### Non-Goals

- **Auto-deletion of failed backups**. *Why:* failed records are diagnostic evidence, deleted by hand after review.
- **Configurable retention per backup type**. *Why:* a single global window is operable by non-technical admins; per-type windows are not.
- **Manual cleanup UI**. *Why:* CLI and scheduler cover it; a UI adds surface for a destructive batch operation.
- **Cleanup notifications**. *Why:* silent operation with a log entry; the nightly run should not page anyone when it works.
- **A standalone cleanup scheduler daemon**. *Why:* the `--cleanup` flag on the existing nightly backup command is the whole scheduling story; a second daemon doubles failure modes for zero gain.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). These two system workflows will
be verifiable once implemented, so `Layer`/`Status` carry the spec's Planned status.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-RET-001 | System purges completed backups older than the retention window in chunks, preserving failed records | P0 | F | Planned |
| UC-RET-002 | Nightly scheduled backup chains the cleanup pass, reclaiming disk in the same run | P1 | F | Planned |

### 3.1 Retention Operation

#### UC-RET-001 — Retention Cleanup Deletes Old Backups

Picture a school that has backed up nightly for four months without ever pruning: 120 completed
archives sit on a 60 GB shared-hosting disk that now reports 91% full. The cleanup Action
queries for completed backups older than the window, walks them a hundred at a time, removes
each physical file through the traversal-guarded deletion, then removes its row — and logs the
pass with the window length and the reclaimed count. Failed records from the two bad nights in
month two remain untouched, error text intact, because those are the evidence the morning
investigation still references.

#### UC-RET-002 — Daily Scheduled Cleanup

The 2 a.m. scheduled invocation runs the backup first and the cleanup second, in that order,
so the fresh snapshot always lands before anything old leaves — the disk never holds the new
backup's absence and the old backups' presence in the wrong sequence. By 2:05 the disk holds
exactly the retention window plus one fresh file, and the operator's morning starts with a
success line naming both the new backup's size and how many expired ones departed.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer that will verify it.
`Status` tracks implementation of this specific requirement.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-RET-001 | CleanupBackupsAction extends the command base, injects the runner, and returns the deleted count | P0 | F | Planned |
| FR-RET-002 | Cleanup deletes only completed backups older than the window; failed backups are never touched | P0 | F | Planned |
| FR-RET-003 | Cleanup processes deletions in chunks of 100 records | P1 | F | Planned |
| FR-RET-004 | Cleanup deletes each physical file before its database record | P0 | F | Planned |
| FR-RET-005 | Cleanup logs the pass with retention days and deleted count | P1 | F | Planned |
| FR-RET-006 | The backup command's cleanup flag triggers the retention pass after a successful backup | P0 | F | Planned |
| FR-RET-007 | The retention window is configurable with a 30-day default | P0 | F | Planned |

### 4.1 Expiry Purge

#### FR-RET-001 — One Action with a numeric answer

The cleanup Action takes the retention window in days and returns how many backups left —
an integer the command prints, the scheduler logs, and tests assert exactly. Constructor
injection of the runner keeps the file operations fakeable, so the test suite drives
hundreds of expired rows through the Action without touching a real disk.

#### FR-RET-002 — Completed and expired means deletable; everything else stays

The query names its victims precisely: status completed, created before the window edge.
Failed records never match no matter their age, running records never match no matter
the clock, and the test seeds one of each kind plus a fresh completed backup to prove
the survivor set is exactly three. This is the row the whole spec exists to enforce —
an over-broad delete here destroys the diagnostic evidence the companion spec promises
to keep.

#### FR-RET-003 — A hundred rows at a time, no matter how deep the backlog

Loading the entire expired set at once turns a neglected school's first cleanup into a
memory spike on the smallest hosting tier. Chunked deletion bounds memory to a hundred
rows regardless of whether the backlog is fifty or five thousand, and the chunk size
is fixed rather than configurable because nobody ever needed to tune it — fixed means
one less knob in the operator's mental model.

#### FR-RET-004 — The crash-safe order, inherited

File first, row second — the same order as single deletion in the companion spec, for
the same reason. A crash between the two leaves a phantom record pointing at nothing,
which the next pass or a manual delete clears; the reverse would leave phantom files
eating disk with no record admitting they exist. Batch deletion multiplies the crash
window by the backlog size, which is exactly why the safe order matters more here,
not less.

#### FR-RET-005 — Every pass writes its own receipt

Retention days in, deleted count out — the log entry names both, so an auditor asking
"what happened to March's backups" finds the April passes with their counts instead
of silence. Passes that delete nothing still log, because a zero-deletion night is
itself information: either the window is generous or the scheduler stopped running,
and the log distinguishes the two.

### 4.2 Trigger & Configuration

#### FR-RET-006 — One flag on the existing command, no second scheduler

After a successful backup the `--cleanup` flag invokes the retention pass with the
configured window, making the nightly run self-maintaining. There is deliberately no
separate cleanup daemon or second schedule entry — two schedulers drift, double the
failure modes, and answer a question nobody asked. The flag keeps one invocation, one
log line, one behavior.

#### FR-RET-007 — Thirty days by default, one setting to change it

Thirty days holds a month of nightly snapshots inside the RPO math with room to spare
on modest disks, and a single environment-backed setting moves the window for schools
with tighter storage or stricter policy. One global value, not per-type windows —
the operator who understands "keep a month" should never need to understand "keep a
month of database but two weeks of storage."

---

## 5. Non-Functional Requirements

A Non-Functional Requirement is a measurable constraint on the system. `Target` is the concrete
number/SLO. `Priority` and `Status` behave as in §4.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-RET-001 | Failed backups are never auto-deleted; they persist as diagnostic evidence | 100% of failed records preserved | P0 | F | Planned |
| NFR-RET-002 | Crash order favors phantom records over phantom files | < 1% orphan references | P0 | F | Planned |
| NFR-RET-003 | File deletion cannot escape the backup directory | 0 traversal incidents | P0 | F | Planned |
| NFR-RET-004 | Cleanup code follows strict typing | 100% `strict_types` | P1 | A | Planned |

### 5.1 Safety & Integrity

#### NFR-RET-001 — The evidence shelf is never cleared by automation

A failed backup's error text is often the only record of why a bad week happened —
disk full, credentials rotated, driver missing — and the cleanup pass must walk past
it every night without touching it. The verification seeds failed records of various
ages around expired completed ones and asserts the survivor set afterward contains
every failed row; a single missing failed record fails the requirement outright.

#### NFR-RET-002 — The batch crash lands on the safe side

With hundreds of file-then-row pairs in flight, a mid-pass crash is a matter of when,
not if — power cuts on school servers are routine. Because each pair deletes the file
first, the aftermath is phantom records the next pass reaps, never phantom files the
system cannot see. The orphan budget stays under one percent precisely because the
order makes the common crash cheap.

#### NFR-RET-003 — Batch deletion runs through the same guarded choke point

Every file removal in the pass flows through the runner's traversal-guarded deletion,
not around it — the batch path gets no shortcut past the realpath check. A corrupted
stored path in row 400 of a 500-row backlog is exactly the case the guard exists for,
and the pass aborts loudly on it rather than skipping quietly past a potential
breakout.

#### NFR-RET-004 — Strict types on destructive code paths

A retention window arriving as a string, a count compared loosely — on a deletion path
those coercions decide what lives and what dies. Strict typing across the cleanup
files removes the entire category, and the arch scan asserts it rather than trusting
review memory.

---

## 6. API / Data Contracts

### 6.1 CleanupBackupsAction

```php
// app/Modules/SysAdmin/Domain/Backups/Actions/CleanupBackupsAction.php
final class CleanupBackupsAction extends BaseCommandAction
{
    public function __construct(protected readonly BackupRunner $runner) {}
    public function execute(int $retentionDays = 30): int;
    // Deletes completed backups older than retentionDays, preserves failed backups
}
```

### 6.2 Cleanup Trigger

```php
// routes/console.php — the nightly run chains cleanup through the existing command
Schedule::command('system:backup --cleanup')
    ->daily()
    ->description('Run scheduled backup with retention cleanup');
```

### 6.3 Configuration

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

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-RET-001 | Failed backup records are preserved during cleanup | P0 | — | — |
| DD-RET-002 | Physical file deletion precedes database record deletion | P0 | — | — |
| DD-RET-003 | Deletions process in chunks of 100 records | P1 | — | — |

### 7.1 Preservation & Order

#### DD-RET-001 — Automation Never Clears the Evidence Shelf

Failed records carry the error text the morning investigation needs, and each one costs
kilobytes of metadata with no file attached — preserving them costs nothing measurable
while deleting them destroys irreplaceable diagnostics. Schools prune them by hand in
the manager UI after reading them, which keeps a human in the loop on every piece of
bad news the system ever recorded.

#### DD-RET-002 — Crash on the Side of the Visible

The companion spec's deletion order carries over unchanged: a crash leaves a phantom
record rather than a phantom file. Records are listed, counted, and re-deletable;
files without records are invisible disk consumption discovered only during the next
storage crisis. The batch pass widens the crash window, so the safe side matters more
here than anywhere else in the backup area.

#### DD-RET-003 — Fixed Chunks, No Knob

A hundred rows per chunk keeps memory flat on the smallest hosting tier whether the
backlog is fifty or five thousand. The size is fixed in code rather than configured
because no school ever had a reason to change it — every configuration option is a
support conversation waiting to happen, and this one would never be worth having.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Cleanup duration | < 60s for 1,000 expired backups | `CleanupBackupsAction::execute()` execution time |
| Disk space reclaimed | ≥ 0 (no negative deltas) | Diff before/after cleanup |
| Failed backup preservation | 100% (none auto-deleted) | Survivor-set assertion after cleanup runs |
| Orphaned records after cleanup | < 1% | File-first order per DD-RET-002 |

---

## 9. Roadmap

### Prerequisites

This spec builds on the backup lifecycle and settings contracts:

| Spec | What It Provides |
|------|-----------------|
| [backup-system.md](HBXCI-backup-system.md) (HBXCI) | `Backup` model, `BackupRunner` service, `CreateBackupAction`, `SystemBackupCommand` |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) (YB22J) | `backup.retention_days` setting |

### Build Guide

After implementing this spec, completed backups older than the retention window are purged
through the nightly `--cleanup` chain, keeping disk usage bounded without losing diagnostic
evidence from failed backups.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [backup-system.md](HBXCI-backup-system.md) (HBXCI) | `--cleanup` flag wires this pass into the daily backup cycle |
| 2 | [system-maintenance.md](E1MSJ-system-maintenance.md) (E1MSJ) | Cleanup health runs alongside other maintenance tasks |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume 30 days of nightly full snapshots fit the school's disk; schools near capacity lower the retention window rather than disabling backups | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Backup system](HBXCI-backup-system.md) — creation lifecycle this spec purges after
- [Job & queue infrastructure](8FVZA-job-queue-infrastructure.md) — queue conventions for async work
- [ADR: MVP spec trim](../adr/adr-mvp-spec-trim.md) — why the standalone cleanup daemon stayed out
