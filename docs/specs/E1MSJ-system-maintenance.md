# System Maintenance — Cleanup, Archiving & Health Monitoring

> **Spec ID:** E1MSJ
> **Status:** Full
> **Owner:** SysAdmin
> **Depends on:** 89SRA, T4B26, 8FVZA, HBXCI

## Description

Keeps a running school system healthy between terms: a maintenance window that warns users before downtime, a health diagnostic that reports every subsystem in one view, scheduled and on-demand cleanup that bounds storage growth, cache warming that removes cold starts, and account lifecycle operations that retire dormant and graduated accounts. Backup creation itself belongs to the [backup system](HBXCI-backup-system.md); this spec schedules hygiene around it.

---

## 1. Problem Statements

### PS-1 — Storage Grows Until the Disk Decides Otherwise

Log files, activity entries, read notifications, failed queue rows, and expired uploads accumulate silently on a small self-hosted disk. Nobody notices until uploads start failing the week before grade submission, and the cleanup that follows is panicked, manual, and occasionally destructive. The system needs routine pruning with the same matter-of-fact regularity as sweeping a classroom.
**→ Requirement:** FR-MAINT-004 (orchestrated cleanup), FR-MAINT-006 (notification pruning).

### PS-2 — Dormant Accounts Outlive Their Owners

Graduates keep working credentials for years because nobody retires them. They clutter every user list, hold sessions open, and turn former students into lingering attack surface. An operator doing this by hand once a year will miss most of them; the transition needs to happen on its own, reversibly, while anyone is watching.
**→ Requirement:** FR-MAINT-008 (dormant auto-inactivation).

### PS-3 — Deployments Greet Users with Cold Caches

After a configuration change or a release, the first teacher to open the dashboard waits through recompiled views, re-read settings, and re-resolved brand assets, then reports that "the update broke the system." Nothing is broken; every cache is simply empty at once. Warming the caches before users arrive turns a perceived outage into a non-event.
**→ Requirement:** FR-MAINT-007 (cache warming).

### PS-4 — Health Is Discovered Through Complaints

A queue worker silently stops, the storage link breaks after a migration, disk usage creeps past ninety percent, and the first signal is an angry message in the staff chat. Each subsystem can be checked individually, but nobody runs eleven checks before coffee. One command that reports every subsystem with a pass, warning, or failure turns unknown unknowns into a morning routine.
**→ Requirement:** FR-MAINT-002 (health diagnostic), FR-MAINT-003 (threshold surfacing).

### PS-5 — Maintenance Happens Without Warning Its Users

Someone runs migrations at noon and the attendance page half-renders for a hundred students mid-submission. The tooling for a dignified maintenance window exists in the framework, but without a procedure the team either skips it or improvises one under pressure. A predictable toggle with a translated notice page protects both the data and the staff's credibility.
**→ Requirement:** FR-MAINT-001 (maintenance window).

---

## 2. Goals & Non-Goals

### Goals

- **Dignified downtime** — a maintenance toggle with a translated notice page and a clean exit. *Why:* midday migrations without warning corrupt submissions and trust alike.
- **One-view health** — every subsystem reported as pass, warning, or failure with machine-readable output. *Why:* eleven separate checks never get run; one command does.
- **Bounded storage** — orchestrated cleanup and notification pruning with safe defaults. *Why:* small self-hosted disks cannot absorb indefinite accumulation.
- **Warm caches** — settings, brand, configuration, views, and events pre-loaded on schedule. *Why:* the first user after a deploy should never pay the cold-start cost.
- **Retired accounts** — dormant logins inactivated automatically, graduated cohorts archived explicitly. *Why:* stale credentials are both clutter and risk.

### Non-Goals

- **Backup creation and restoration**. *Why:* owned by the backup system; maintenance only schedules hygiene around it.
- **Queue infrastructure itself**. *Why:* workers, drivers, and dispatch conventions belong to the queue spec; maintenance merely uses them.
- **Schema changes**. *Why:* migrations belong to system requirements; maintenance commands never alter schema.
- **Operating-system log rotation**. *Why:* host-level rotation is the server administrator's tooling, not application behavior.
- **Pulse dashboards**. *Why:* metric snapshots beyond what health and cleanup already report would add a scheduler surface the MVP never exercises.

---

## 3. User Stories / Use Cases

Cleanup and health are operator routines; archival of a graduated cohort is the one explicitly human decision in this spec.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-MAINT-001 | Admin opens a maintenance window before a midday migration and closes it after | P0 | F | Full |
| UC-MAINT-002 | Admin runs the health diagnostic and reads every subsystem at a glance | P0 | F | Full |
| UC-MAINT-003 | Scheduler and admin together keep storage bounded through routine cleanup | P1 | F | Full |
| UC-MAINT-004 | Dormant accounts inactivate themselves after months of silence | P1 | F | Full |
| UC-MAINT-005 | Admin archives a graduated cohort from the student manager | P0 | F | Full |

### 3.1 Availability

#### UC-MAINT-001 — A Maintenance Page During Enrollment Week

Tuesday of enrollment week, and a migration must run before noon or placement approvals stall. The admin enables the maintenance window with a reason the notice page will show, runs the migration while applicants see a calm translated page instead of a half-rendered form, then disables the window and confirms the site answers again. One applicant later says she saw the notice, waited ten minutes, and submitted cleanly. Without the window, her submission would have raced the migration and she would have blamed the school for losing it.

#### UC-MAINT-002 — The Morning Health Ritual

Before the school day starts, the operator runs the health diagnostic and watches the subsystem list resolve: environment, setup, language runtime, extensions, memory, database, migrations, storage, disk, queue, cache, application key, storage link, maintenance state. Thirteen greens and one amber for disk usage at eighty-seven percent. That amber becomes today's task instead of next month's outage. With the machine-readable flag the same run feeds the school's tiny monitoring script, which pages nobody today because nothing failed.

### 3.2 Hygiene & Lifecycle

#### UC-MAINT-003 — The Quiet Night Shift

At night the scheduler sweeps through stale password resets, expired cache tags, failed queue rows, aged activity entries, and orphaned uploads. Read notifications older than the retention window disappear while unread ones stay untouched. Each sub-task reports its outcome through the structured logger, and one failing sub-task never cancels the rest: a locked upload directory costs a warning line, not a skipped database sweep. Once a term the admin runs the same sweep by hand with a force flag before a backup, watching the disk gauge drop far enough that the backup fits.

#### UC-MAINT-004 — The Graduate Who Never Logged Out

A student from two cohorts ago still holds valid credentials and appears in every user picker. Ninety days after her last login, the nightly pass finds her silence, skips her because she holds no protected role or status, and moves her to the inactive state with a recorded reason. She can still return and reactivate through the normal recovery path; nothing about her record is destroyed. The protected system account the same query encounters is left exactly as found, because automation that touches the system's own identity is automation nobody wants.

#### UC-MAINT-005 — Sealing a Graduated Cohort

Placement is finalized and certificates are issued, so the coordinator filters the graduated cohort in the student manager and archives the filtered set. The operation walks the selection in small chunks, transitions each account to the archived state with a shared reason, skips the protected system identity, and reports the count through the structured log. Archived students can no longer sign in, which is the point: the cohort is closed. For very large selections the same transition fans out through queued jobs with retries, so a worker restart mid-cohort resumes rather than restarts.

---

## 4. Functional Requirements

One table for the whole section. Group 4.1 guards availability, 4.2 bounds storage and warms caches, 4.3 retires accounts.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-MAINT-001 | Maintenance window opens and closes through commands with a translated notice page and reason | P0 | F | Full |
| FR-MAINT-002 | Health diagnostic checks every subsystem, reports pass/warn/fail, and supports machine-readable output with failing exit codes | P0 | F | Full |
| FR-MAINT-003 | Disk and queue pressure surface as warning and failure thresholds in the health report | P1 | F | Full |
| FR-MAINT-004 | Cleanup orchestrates the built-in pruning sub-tasks and a failing sub-task never halts the rest | P0 | F | Full |
| FR-MAINT-005 | Cleanup honors a confirmation bypass and a configurable log-retention window | P1 | F | Full |
| FR-MAINT-006 | Read-notification pruning honors a retention floor and rejects invalid windows with a translatable error | P1 | F | Full |
| FR-MAINT-007 | Cache warming pre-loads settings, brand, configuration, views, and events, reporting each step | P1 | F | Full |
| FR-MAINT-008 | Dormant-account automation inactivates long-silent accounts while skipping protected identities | P1 | F | Full |
| FR-MAINT-009 | Cohort archival walks the selection in bounded chunks through the status action and logs the count | P0 | F | Full |
| FR-MAINT-010 | Large-cohort archival fans out through queued jobs with bounded retries and backoff | P1 | F | Full |

### 4.1 Availability

#### FR-MAINT-001 — Downtime With Manners

Enabling the window records the operator's reason and swaps every route for a translated notice that names no internals. Students mid-attendance see an apology in their own language instead of a stack trace, and the admin's own session retains a bypass so she can verify the migration result before reopening. Disabling the window restores traffic and logs both transitions with the operator's identity. A window left open overnight is still discoverable in the morning because the health diagnostic reports maintenance state as one of its checks.

#### FR-MAINT-002 — Eleven Questions, One Answer

The diagnostic asks its questions in dependency order: is the environment file present, did setup complete, does the runtime meet the floor, are required and recommended extensions loaded, is memory sufficient, does the database answer, are migrations current, is storage writable, how full is the disk, is the queue breathing, which cache driver serves, is the application key set, does the storage link resolve, and is a maintenance window open. Each answer carries a short detail string, and the machine-readable rendering lets a monitoring script consume the same run the operator reads as a table. Any failure flips the exit code so schedulers and deploy scripts can branch on it.

#### FR-MAINT-003 — Thresholds That Speak Before the Disk Does

Raw percentages help nobody at a glance, so the diagnostic translates them: disk usage past the mid-eighties warns, past the mid-nineties fails, and a failed-job backlog beyond a hundred rows warns that the workers are falling behind. These numbers live in configuration rather than in code, because the school that archives aggressively and the school that keeps everything will feel pressure at different points. A warning is a task for this week; a failure is a task for this hour.

### 4.2 Freshness

#### FR-MAINT-004 — One Sweep That Refuses to Stop Early

The cleanup command conducts rather than performs: it invokes the framework's own reset clearing, stale-tag pruning, failed-job pruning, activity-log cleaning, and media sweeping in sequence. Every sub-task outcome passes through the structured logger with the system module name, and an exception in one movement never cancels the remaining program. The completion entry records which sub-tasks succeeded and which stumbled, so the morning review distinguishes a healthy sweep from a partially blind one without re-running anything.

#### FR-MAINT-005 — A Force Flag and a Retention Dial

By default the command asks for confirmation, because deleting files deserves a moment of reflection; the force flag exists for the scheduler and for operators who already reflected. The retention dial sets how many days of log files survive, defaulting to a month. Setting it to zero or a negative number is rejected with a translated explanation rather than interpreted creatively. Both flags are documented in the command help so the night-shift operator who inherits this system needs no oral tradition.

#### FR-MAINT-006 — Forgetting What Was Already Read

Only notifications already marked read and older than the cutoff are removed; unread rows survive regardless of age, because deleting something nobody saw is data loss wearing a janitor's uniform. The window defaults to a month and refuses values below a single day. An operator who passes zero learns immediately through a translated error why the floor exists. The prune reports how many rows vanished so the log shows a number, not just an intention.

#### FR-MAINT-007 — Warming the Caches Before the Crowd

The warming pass touches settings, brand assets, configuration, compiled views, and the event map in order, announcing each step's success or failure as it goes. A failure in brand resolution does not skip view compilation; the completion entry lists the per-step outcomes. Run on schedule after deploys and configuration edits, the command means the first teacher through the door meets a system already awake. The completion event carries the module name so dashboards can chart warm runs against cold complaints.

### 4.3 Account Lifecycle

#### FR-MAINT-008 — Silence Becomes Inactivity, Not Deletion

The nightly pass finds accounts whose last activity predates the configurable window, defaulting to three months, and moves the eligible ones to the inactive state with a reason naming the automation. Protected identities and already-terminal states are skipped before any write, and every transition is logged with the account reference. Inactivation is deliberately reversible through the normal recovery flow: the automation locks the door but never demolishes the house, which is why this runs unattended while archival never does.

#### FR-MAINT-009 — Archiving a Cohort Without Loading It Into Memory

The archival action receives the coordinator's filtered query rather than an id list, and walks it in chunks of a hundred so a five-hundred-strong cohort never balloons memory. Each row transitions through the shared status action instead of a direct write, inheriting its guards and its logging. The protected system identity encountered mid-chunk is skipped like any other ineligible row. When the last chunk lands, the action logs the archived count under the cohort event name, which is the number the coordinator quotes in the handover meeting.

#### FR-MAINT-010 — The Queue Carries the Heavy Cohorts

Selections too large for a single request are split into id batches and handed to queued jobs, each retrying a bounded number of times with growing backoff across worker restarts. Inside, the job performs the same status transition per user that the synchronous path uses, so the two paths cannot drift into different meanings of "archived." A job that exhausts its retries reports the failure with context instead of vanishing, and the remaining batches continue unaffected.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-MAINT-001 | Every maintenance operation reports through the dual-channel structured logger | N/A | P0 | F | Full |
| NFR-MAINT-002 | Personal data is masked in all maintenance log output | N/A | P0 | F | Full |
| NFR-MAINT-003 | A failing sub-task never prevents the remaining maintenance work | N/A | P0 | F | Full |
| NFR-MAINT-004 | Large archival selections run through the queue rather than inline | N/A | P1 | F | Full |
| NFR-MAINT-005 | Maintenance and health strings render through the translation helper in both locales | N/A | P1 | A | Full |

### 5.1 Observability & Safety

#### NFR-MAINT-001 — If It Is Not Logged, It Did Not Happen

A cleanup that prints to the console and nowhere else is invisible by morning. Routing every maintenance outcome through the shared structured logger lands it in both the queryable activity store and the technical system log, where the next operator and the next incident review can each find it. Direct framework log calls inside maintenance code are treated as defects in review, because two logging dialects in one subsystem guarantee that half the evidence goes missing exactly when it matters.

#### NFR-MAINT-002 — The Sweeper Must Not Scatter Secrets

Cleanup output loves to include examples: the email on a pruned notification, the filename of an expired upload, the account name on a transitioned row. Each of those passes through the masking step before reaching any sink, so secrets vanish entirely and identifiers are partially obscured. The night the scheduler prunes ten thousand rows, the resulting log lines prove the work happened without becoming a phone book of former students.

#### NFR-MAINT-003 — Partial Success Beats Total Silence

A locked directory at three in the morning should cost one warning line, not the entire night's hygiene. Isolating sub-task failures means the database sweep still runs when the media sweep stumbles, and the completion entry names the casualty honestly. Operators learn to trust the sweep precisely because it reports its own wounds instead of dying quietly halfway through.

### 5.2 Scale & Language

#### NFR-MAINT-004 — Heavy Work Rides the Queue

Archiving a handful of accounts inline is fine; archiving a generation of graduates inline holds a request open until the web server loses patience. The queue boundary sits where the selection grows beyond a comfortable interactive size, and the retry budget absorbs worker restarts without operator intervention. Synchronous and queued paths share the same transition logic, so scale never changes semantics.

#### NFR-MAINT-005 — Warnings a Teacher Can Read

Every maintenance sentence a human might see, from the notice page to the threshold explanations, resolves through the translation helper with mirrored keys. An Indonesian teacher reading the maintenance page and an English-speaking auditor reading the same event in the log both meet their own language. Placeholder parameters carry the dynamic values so neither word order breaks the sentence.

---

## 6. API / Data Contracts

### 6.1 Maintenance Window

```php
// Enable:  php artisan system:maintenance --on --reason="Enrollment DB migration"
// Disable: php artisan system:maintenance --off
// Notice view renders the reason through __('sysadmin.maintenance.notice') in both locales.
```

### 6.2 Health Diagnostic

```php
// app/Modules/SysAdmin/Observability/Console/Commands/SystemHealthCommand.php
class SystemHealthCommand extends Command
{
    protected $signature = 'system:health {--json : Output results as JSON}';
}
// Each check answers OK / WARN / FAIL with a detail string; any FAIL fails the exit code.
```

### 6.3 Cleanup and Pruning

```php
// app/Modules/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php
class SystemCleanupCommand extends Command
{
    protected $signature = 'system:cleanup
        {--force : Do not ask for confirmation}
        {--log-retention=30 : Days to retain log files}';
}

// Prune read notifications older than the window (default 30 days, minimum 1):
// php artisan notifications:prune {--days=30}
```

### 6.4 Cache Warming and Dormant Accounts

```php
// php artisan system:cache-warm
// Pre-warms settings, brand, config, views, events; reports each step.

// php artisan accounts:auto-inactivate {--days=90}
// Skips protected identities; transitions the eligible to INACTIVE with a reason.
```

### 6.5 Cohort Archival

```php
// app/Modules/User/UserManagement/Actions/ArchiveStudentAccountsAction.php
final class ArchiveStudentAccountsAction extends BaseCommandAction
{
    public function execute(Builder $query): int;   // chunked at 100; returns archived count
}

// Queued fan-out for large selections: id batches, bounded retries with backoff,
// each user transitioned through SetUserStatusAction, never by direct write.
```

Scheduler shape (`routes/console.php`): nightly cleanup and notification pruning, hourly cache warming after deploys, nightly dormant-account pass. Exact cadences follow the queue and backup specs so maintenance never fights the backup window.

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-MAINT-001 | A single cleanup command conducts the built-in pruning sub-tasks | P1 | — | — |
| DD-MAINT-002 | Cohort archival accepts the coordinator query and splits queued fan-out by id batches | P0 | — | — |
| DD-MAINT-003 | Health stays a command-line diagnostic with machine-readable output | P1 | — | — |
| DD-MAINT-004 | Automation inactivates while only humans archive | P0 | — | — |

### 7.1 Operating Choices

#### DD-MAINT-001 — Conduct, Do Not Reimplement

The framework and its packages already know how to prune resets, tags, failed jobs, activity rows, and orphaned media. Reimplementing any of that would fork behavior the school depends on and rot within a release cycle. The cleanup command therefore conducts existing sub-tasks and owns only sequencing, reporting, and the log-file sweep nobody else covers. If a sub-task changes its flags upstream, exactly one call site needs attention, which is a maintenance cost the team can see and budget.

#### DD-MAINT-002 — Queries In, Id Batches Out

The coordinator's filter already exists as a query with scopes for role, status, and placement, so the archival action accepts that query and chunks through it without ever materializing the whole cohort. A queued job cannot carry a query object faithfully, which is why the fan-out translates selections into id batches first. The split looks redundant until the first five-hundred-student cohort arrives, at which point the synchronous path stays lean and the queued path stays resumable.

#### DD-MAINT-003 — Health Belongs on the Command Line

Subsystem truth like extension lists, symlink targets, and disk percentages is operator material, occasionally sensitive, and never something a student dashboard should render. A command with table output for humans and JSON for scripts serves both consumers without building a second privileged web surface to guard. The trade is accepted openly: no health page exists, and the operator reaches for a shell or the scheduler's report instead.

#### DD-MAINT-004 — The Line Between Reversible and Terminal

Inactivation returns through the recovery flow, so the scheduler may perform it alone at night without asking anyone. Archival closes the login door for good and belongs to a coordinator who has checked certificates and sign-offs, which no query predicate can verify. Letting automation cross that line would eventually seal a cohort whose grades were not final; keeping the line means dormant accounts never linger while graduated ones never vanish by accident.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Storage reclaimed per routine sweep | Visible drop in disk usage | Disk gauge before and after the scheduled run |
| Dormant accounts left active past the window | 0 | Count of over-window accounts still active after the nightly pass |
| Subsystems covered per health run | All listed subsystems | Health output against the contract list |
| Cold-start complaints after deploys | 0 | Warm completion entries versus user-reported slowness |
| Maintenance surprises during migrations | 0 unannounced windows | Window open/close entries around migration work |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) | Dual-channel structured logger with PII masking |
| [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) | Administrative-role gates for operator commands |
| [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) | Queue drivers and dispatch conventions for archival fan-out |
| [backup-system.md](HBXCI-backup-system.md) | Backup creation that maintenance schedules hygiene around |
| [user-crud-and-status.md](95EVB-user-crud-and-status.md) | Status transitions and the archived and inactive states |

### Build Guide

Availability first: the maintenance window and health diagnostic give operators control and visibility. Hygiene follows with cleanup, pruning, and warming on the scheduler. Lifecycle closes the loop with dormant inactivation and coordinator-driven archival, each exercised against its requirement ids.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [data-archiving.md](9YUUK-data-archiving.md) | Cohort sealing matures into the long-term archival lifecycle |
| 2 | [backup-system.md](HBXCI-backup-system.md) | Nightly hygiene stays clear of the backup window |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| A-1 | We assume school-scale cohorts fit the chunked and queued archival paths without dedicated batch infrastructure | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Phase 12 maintenance group and dependency order
- [Logging and error handling](89SRA-logging-and-error-handling.md) — structured logger and masking
- [RBAC and authorization](T4B26-rbac-and-authorization.md) — administrative-role gates
- [Job and queue infrastructure](8FVZA-job-queue-infrastructure.md) — queue drivers and retry conventions
- [Backup system](HBXCI-backup-system.md) — backup creation alongside hygiene
- [GDPR compliance](7HNCF-gdpr-compliance.md) — deletion logging beside maintenance
- [Action pattern ADR](../adr/adr-action-pattern-over-services.md) — command, read, and process contracts
- [Gradual migration ADR](../adr/adr-gradual-migration.md) — scheduler and queue adoption path
- [Cross-module communication ADR](../adr/adr-cross-module-communication.md) — delegation to user actions
