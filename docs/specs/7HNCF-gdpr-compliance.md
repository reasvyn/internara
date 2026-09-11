# GDPR Compliance — Data Deletion, Anonymization, and Audit Logging

> **Spec ID:** 7HNCF
> **Status:** Full
> **Owner:** SysAdmin
> **Depends on:** YB22J, 95EVB

## Description

Covers how Internara answers a deletion request: an erasure workflow that snapshots what is about to disappear, removes the person, and leaves an append-only deletion record behind. An admin-assisted export answers the "what do you hold about me" question, and a filterable log browser lets an auditor prove every erasure happened for a reason. This spec owns the erasure workflow and the deletion log; retention scheduling lives with [maintenance](E1MSJ-system-maintenance.md) and long-term cohort sealing with [archiving](9YUUK-data-archiving.md).

---

## 1. Problem Statements

### PS-1 — Deletion Leaves No Proof Behind

A graduate writes to the school asking for her account to be erased. The operator deletes the row, the foreign keys cascade, and nothing remains to show an auditor what was destroyed, who authorized it, or why. Under Indonesia's UU PDP that absence is itself the violation: the erasure happened, but the school cannot prove it was complete or authorized.
**→ Requirement:** FR-GDPR-001 (erasure workflow), FR-GDPR-008 (append-only log record).

### PS-2 — The Log Viewer Reads Columns That Do Not Exist

The admin log browser filters by email, badges by deletion type, and sorts by deletion timestamp, yet the underlying table only carries a user id, a JSON snapshot, and a creation timestamp. Search matches nothing, badges render empty, and sorting falls back to insertion order. The screen looks finished while the schema underneath is half-built.
**→ Requirement:** FR-GDPR-009 (completed schema), FR-GDPR-011 (browser backed by real columns).

### PS-3 — Backups Remember What the Database Forgot

Deleting a row removes it from the live tables, but last night's backup still holds the full name, email, and username. Without a snapshot-and-scrub step before deletion, nobody can say which personal fields existed at erasure time, and nobody can confirm the backup rotation will eventually age them out. The erasure is real but unverifiable.
**→ Requirement:** FR-GDPR-002 (snapshot before delete), FR-GDPR-005 (export of held data on request).

### PS-4 — Every Deletion Looks Identical

A student who asked to be forgotten and an abandoned spam account removed for inactivity produce the same residue: none. When the school later asks how many erasures were request-driven versus policy-driven, there is no column to group by and no vocabulary to describe the difference.
**→ Requirement:** FR-GDPR-007 (deletion-type vocabulary), FR-GDPR-003 (reason and deleter recorded).

### PS-5 — Two Logging Systems That Never Meet

Each deletion already writes an activity entry, yet the compliance log stays empty because nothing bridges the two. An auditor opening the GDPR log concludes no deletions ever occurred, while the activity feed quietly says otherwise. Two true stores disagree, which reads as a cover-up even when it is only missing wiring.
**→ Requirement:** FR-GDPR-003 (deletion writes the compliance record), FR-GDPR-013 (event for downstream listeners).

---

## 2. Goals & Non-Goals

### Goals

- **Erasure with proof** — every deletion captures a pre-deletion snapshot and writes an append-only log row. *Why:* the school must demonstrate what was erased, when, by whom, and why.
- **Request-driven workflow** — an incoming erasure request is validated, executed, and logged as one traceable operation. *Why:* ad-hoc row deletion cannot distinguish a lawful request from a mistake.
- **Admin-assisted export** — an operator can produce a JSON snapshot of what the system holds about one person. *Why:* answering "what do you hold about me" should not require database access.
- **Auditable log browser** — email search, type filter, chronological sort over real indexed columns. *Why:* a compliance log nobody can query is a write-only archive.
- **Admin-only visibility** — deletion records are visible solely to operators with an administrative role. *Why:* the log itself contains personal data and must not leak to students or supervisors.

### Non-Goals

- **Self-service deletion UI**. *Why:* students deleting their own accounts mid-semester would strand placements and grades; erasure stays an operator-executed workflow.
- **Consent and cookie management**. *Why:* a self-hosted school system has no tracking surface to consent to.
- **Cryptographic per-user erasure**. *Why:* personal data lives in relational rows, not per-user encrypted vaults; row deletion plus snapshot is the correct mechanism.
- **Automatic retention enforcement**. *Why:* scheduled purging belongs to maintenance and archiving, not to the erasure path itself.
- **Multi-stage approval chains**. *Why:* a single authorized operator action with a recorded reason is proportionate at school scale.

---

## 3. User Stories / Use Cases

The erasure workflow spans request, execution, and audit. Browser-level journeys stay thin: the Livewire browser reads through a Read Action and never mutates.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-GDPR-001 | Alumni requests erasure and the admin executes it, leaving a complete deletion record | P0 | F | Full |
| UC-GDPR-002 | Admin erases a batch of selected accounts and each erasure is logged individually | P0 | F | Full |
| UC-GDPR-003 | Admin browses, searches, and filters the deletion log history | P1 | F | Full |
| UC-GDPR-004 | Admin opens one deletion entry and inspects the pre-deletion snapshot | P1 | F | Full |

### 3.1 Erasure Workflow

#### UC-GDPR-001 — An Alumni Deletion Request, End to End

An alumna from the 2024 cohort emails the school office asking for her account to be erased now that she has her certificate. The operator opens her row in the user manager, confirms the dialog, and types a short reason referencing the request letter. Behind that click the system snapshots her name, email, username, role, and status, writes the compliance record with the operator's identity, fires the deletion event, and only then removes the row. When the alumna later asks for confirmation, the operator points at the log entry: erased on a specific date, by a named operator, for a stated reason. The request letter stays in the school's correspondence file; the system keeps the fact of erasure, not the data erased.

#### UC-GDPR-002 — A Batch Cleanup After Enrollment Fraud

During intake the staff discovers a dozen accounts created with fabricated emails to reserve placement slots. Removing them one by one would take the morning, so the admin selects all twelve and confirms a single batch deletion with one shared reason. The batch walks the selection user by user, and each successful removal earns its own log row with that same reason attached. Two of the twelve turn out to belong to a protected system account pattern and are skipped rather than forced. The result summary reads ten erased and two skipped, and the log shows exactly ten new entries. If any single erasure fails its log write, that user's row survives, because a deletion without its proof is treated as no deletion at all.

### 3.2 Audit & Review

#### UC-GDPR-003 — A Maintenance Page During Enrollment Week

It is the busiest Monday of enrollment, and a supervisor claims a student's account "vanished." The admin opens the deletion log between applicant interviews, types the student's email fragment into the search box, and the table narrows as she types. She switches the type filter to permanent deletions and sorts newest first. There it is: last Friday, erased by a named colleague, reason recorded. The mystery resolves in under a minute without touching the database. The browser paginates at twenty rows because a compliance screen that dumps ten thousand rows at once would freeze the very machine the admin is using to reassure a worried parent.

#### UC-GDPR-004 — Reading the Snapshot of Someone Already Gone

Months after an erasure, a dispute arises about which email address a deleted account actually used. The admin clicks the log row and sees the frozen snapshot: the name, email, and username exactly as they stood before deletion, plus the deletion type, reason, and timestamp. That snapshot is trusted precisely because it was captured before the row disappeared and has been untouchable ever since. Nobody can quietly correct it afterward; if the reason was mistyped, the correction arrives as a new note in school correspondence, never as an edit to history.

---

## 4. Functional Requirements

One table for the whole section. Group 4.1 walks the erasure itself, 4.2 fixes the vocabulary and the immutable record, 4.3 covers the review surface and its gates.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-GDPR-001 | Erasure runs through a dedicated GDPR command action that snapshots, logs, emits an event, and returns the log record | P0 | F | Full |
| FR-GDPR-002 | The personal-data snapshot is captured before the user row is deleted | P0 | F | Full |
| FR-GDPR-003 | Admin-initiated deletion delegates to the GDPR action with permanent-deletion type, reason, and deleter identity | P0 | F | Full |
| FR-GDPR-004 | Batch deletion creates one deletion log record per erased user and reports erased and skipped counts | P0 | F | Full |
| FR-GDPR-005 | An admin-assisted export produces a JSON snapshot of the personal data held about one user | P1 | F | Full |
| FR-GDPR-006 | Erasure validates its target and rejects protected accounts and self-deletion with a translatable business error | P0 | F | Full |
| FR-GDPR-007 | A deletion-type enum defines anonymization and permanent deletion with translated labels matching the UI filter | P0 | U | Full |
| FR-GDPR-008 | The deletion log model is append-only with a fillable contract, array-cast snapshot, no update timestamp, and a deleter relation | P0 | F | Full |
| FR-GDPR-009 | The deletion log schema carries email, type, reason, deletion timestamp, and deleter reference with indexes and null-on-delete | P0 | F | Full |
| FR-GDPR-010 | A log-state entity bridges the model to presentation with formatted timestamp, type label, and snapshot summary | P1 | U | Full |
| FR-GDPR-011 | The log browser shows email, type badge, reason, and deletion timestamp with search, type filter, sorting, and pagination | P1 | F | Full |
| FR-GDPR-012 | Viewing and creating deletion logs is restricted to administrative roles through policy gates | P0 | U | Full |
| FR-GDPR-013 | Every compliant erasure emits a domain event carrying the deletion log record | P1 | F | Full |

### 4.1 Erasure Workflow

#### FR-GDPR-001 — One Action Owns the Whole Erasure

Separating the compliance choreography from the plain user-deletion logic keeps both readable. The dedicated action receives the user, the deletion type, an optional reason, and the operator performing it. It snapshots first, persists the log row, announces the event, records the structured log entry, and hands the fresh record back to its caller. Because the caller invokes this action inside the same transaction as the row deletion, the two either land together or roll back together. The school never faces the awkward state of a missing person with no paperwork, or paperwork for a person still present.

#### FR-GDPR-002 — The Snapshot Comes First, Always

Once the delete statement runs, the row is gone and any reading afterward is fiction. So the action reads name, email, username, role, and status while the row still exists and freezes those values into the snapshot column. Picture the cascade firing a millisecond later and wiping every child reference: the snapshot has already escaped into the log. A developer tempted to "clean up the ordering" and delete first would find the feature tests failing, because the tests assert the snapshot content against a known fixture rather than trusting call order by convention.

#### FR-GDPR-003 — The Ordinary Delete Button Gains a Conscience

The existing single-user deletion keeps its familiar shape and gains an optional reason parameter plus a dependency on the GDPR action. Before touching the row it hands the user, the permanent-deletion type, the reason, and the currently authenticated operator to the GDPR action. The hard delete and the legacy deletion event still follow exactly as before, so every caller of the old action, including the batch path, inherits compliance without changing its own call sites. Operators notice nothing except that the log browser starts filling up.

#### FR-GDPR-004 — Batch Deletion Logs Person by Person

A batch over user ids threads the shared reason through to each individual deletion, and each one writes its own log row on the way out. The batch returns how many were erased and how many were skipped, which is the number the confirmation toast shows the operator. Skipped entries cover protected accounts and rows that vanished between selection and confirmation. Aggregating the batch into a single "twelve deleted" row would destroy per-person auditability, so the design deliberately pays one row per person even for large selections.

#### FR-GDPR-005 — Answering What the School Holds About One Person

When a student asks what data the school keeps, the operator should not need database credentials. A read-only query assembles the person's profile fields, placement links, and the categories of records attached to them into a single JSON document for the operator to share. The shape stays modest: identity fields plus references, not a full relational dump. If the person then confirms they want erasure, the operator moves to the erasure workflow with the request reference recorded as the reason.

#### FR-GDPR-006 — Some Accounts Must Refuse to Disappear

Not every deletion request is lawful to execute. The immutable system account cannot be erased by anyone, and an operator cannot erase the account they are currently signed in with, since that would orphan the audit trail mid-write. Both refusals surface as business-rule rejections with sentences the UI can translate, not as database errors or silent no-ops. The check lives in the action layer rather than only in the button's visibility, so a crafted request that bypasses the UI meets the same refusal.

### 4.2 Vocabulary and Immutable Record

#### FR-GDPR-007 — Two Words for Two Kinds of Goodbye

The vocabulary needs exactly two cases: a scrub that keeps a shell row and a removal that deletes it entirely. Each case carries the lowercase value the existing filter dropdown already offers, and its human label comes from the translation files in both supported languages. Adding a third case later would mean a new enum variant plus its translations, not a free-text column, because auditors group by this column and free text would splinter every report.

#### FR-GDPR-008 — A Log Row That Cannot Be Rewritten

The model declares precisely which columns may be mass-assigned, casts the snapshot to an array on read, and disables the update timestamp so the framework never pretends these rows evolve. A nullable relation points at the operator who performed the erasure, and a bridge method exposes the row as its presentation state. Immutability here is structural rather than aspirational: with no update timestamp and no edit screen, the ordinary code paths for "fixing" a row simply do not exist.

#### FR-GDPR-009 — Columns the Browser Was Already Promised

The follow-up migration adds the email column for lookup, the short type column, the free-text reason, the true deletion timestamp distinct from the log's creation time, and the deleter reference that nulls itself gracefully if that operator is later removed. Lookup columns are indexed because the browser searches and sorts on them; the original orphaned user id stays put so old entries keep correlating with activity history. Running the migration on a database that already holds log rows backfills nothing and breaks nothing, since every new column tolerates nulls.

#### FR-GDPR-010 — Presentation Logic Lives Outside the Model

Formatting concerns like turning a timestamp into a readable string or condensing a snapshot into a one-line summary would pollute the model if left there. The state object takes that role: constructed from the model, it answers the label, the formatted date with a graceful fallback when absent, the summary line, and the two type predicates. Unit tests build it from plain arrays in milliseconds, which is exactly why the formatting lives here instead of in the Blade view where it could never be tested.

### 4.3 Review Surface and Gates

#### FR-GDPR-011 — A Browser Built for Auditors, Not Developers

The component renders email, a colored type badge, the reason, and the deletion timestamp, paginated twenty at a time. Typing in the search box narrows by email with a short debounce so each keystroke does not hammer the database; the type dropdown offers the two known kinds plus an unfiltered view; clicking a column header flips the sort. The badge colors follow the application's existing severity language so a permanent deletion reads as strongly as the destructive act it records. Timestamps render in a fixed numeric format both locales share, avoiding locale-specific month names in an audit context.

#### FR-GDPR-012 — The Log Contains Personal Data, So It Is Guarded

Because each row freezes someone's email and name, the log browser is no place for students or supervisors. Policy gates answer the viewing and creation questions with the same administrative check used across the back office, and the routes sit behind the administrative role middleware as a second barrier. The creation gate matters too: only the erasure workflow itself should ever mint these rows, never a hand-crafted form submission.

#### FR-GDPR-013 — The Erasure Announces Itself

After persisting, the workflow emits a domain event carrying the fresh log record, named so listeners can subscribe without parsing free text. Downstream reactions such as cache invalidation or notification fan-out attach here rather than inside the action, keeping the erasure itself focused. The event extends the shared base event so its translation key and payload conventions match every other domain event in the system.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-GDPR-001 | Deletion log records are append-only and immutable after creation | N/A | P0 | F | Full |
| NFR-GDPR-002 | Only administrative roles may view deletion logs | N/A | P0 | U | Full |
| NFR-GDPR-003 | Log creation and row deletion are atomic so no erasure exists without its record | N/A | P0 | F | Full |
| NFR-GDPR-004 | Erasure and export activity is logged through the dual-channel logger with PII masking | N/A | P0 | F | Full |
| NFR-GDPR-005 | Every user-facing deletion-log string passes through the translation helper in both locales | N/A | P1 | A | Full |
| NFR-GDPR-006 | Compliance classes follow strict typing and the fillable-attribute convention | N/A | P1 | A | Full |

### 5.1 Integrity & Security

#### NFR-GDPR-001 — History That Cannot Be Edited Into Innocence

Imagine an operator who mistypes a deletion reason and asks a developer to "just fix the row." The correct answer must be that no code path allows it: no update timestamp, no edit screen, no bulk-update helper that touches this table. The refusal is what makes the log credible to an outside auditor. Corrections travel as new correspondence in the school's files while the original entry stands untouched, which is precisely how paper registers have always worked.

#### NFR-GDPR-002 — Curiosity Is Not Authorization

A supervisor with legitimate access to student records has no legitimate need to browse who was erased and why. The policy gates draw that line, and the route middleware redraws it, so both a direct URL guess and a crafted component call meet the same denial. Denials are quiet and generic rather than explanatory, because confirming the existence of a specific log entry to an unauthorized party would itself leak information.

#### NFR-GDPR-003 — The Paperwork and the Act Are One Transaction

If the log insert fails while the row delete succeeds, the school holds an unerasure it cannot prove, which is worse than failing openly. Wrapping both writes in a single transaction converts that nightmare into a clean rollback: the operator sees an error, the user row survives, and the attempt can be retried. Under enrollment-week load this costs a slightly longer lock on an infrequent operation, a price the design pays gladly.

### 5.2 Operability

#### NFR-GDPR-004 — Two Log Channels, Nothing Sensitive in Either

Every erasure writes its structured entry through the shared logger so it lands in both the operator-readable activity store and the technical system log. Before reaching either sink, the payload passes through the masking step that hides secrets entirely and partially obscures emails, phones, and names. A developer who accidentally logs the whole user array still cannot leak a password, because masking happens by key name at the sink boundary rather than by discipline at each call site.

#### NFR-GDPR-005 — An Auditor Reads Indonesian, a Regulator Reads English

All labels, placeholders, badges, and confirmation sentences resolve through the translation helper with mirrored keys in both language files. A missing key in either locale fails the translation scan rather than silently rendering the other language. Dynamic values like the erased address travel as placeholders inside the translated sentence, never concatenated, so word order differences between the languages cannot garble the meaning.

#### NFR-GDPR-006 — Conventions a Scanner Can Prove

Strict typing is declared on every compliance class, and mass assignment flows only through the fillable attribute the scanners recognize. These are not stylistic preferences: the class-contract scan walks every action, entity, and model asserting exactly these shapes, and a missing declaration fails the pre-commit gate. Consistency across eighteen modules cannot survive on reviewer memory, so the structure carries it.

---

## 6. API / Data Contracts

### 6.1 Deletion-Type Enum

```php
// app/Modules/SysAdmin/Observability/GdprDeletionLog/Enums/GdprDeletionType.php
enum GdprDeletionType: string implements LabelEnum
{
    case ANONYMIZATION      = 'anonymization';
    case PERMANENT_DELETION = 'permanent_deletion';

    public function label(): string;
    // Returns __('sysadmin.gdpr_logs.type.'.$this->value)
}
```

### 6.2 Log-State Entity

```php
// app/Modules/SysAdmin/Observability/GdprDeletionLog/Entities/GdprDeletionLogState.php
final readonly class GdprDeletionLogState extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function typeLabel(): string;
    public function formattedDeletedAt(): string;   // 'Y-m-d H:i' or 'N/A'
    public function snapshotSummary(): string;       // "Jane Doe (jane@example.com)"
    public function isAnonymization(): bool;
    public function isPermanentDeletion(): bool;
}
```

### 6.3 Deletion-Log Model

```php
// app/Modules/SysAdmin/Observability/GdprDeletionLog/Models/GdprDeletionLog.php
#[Fillable(['user_id', 'user_email', 'deletion_type', 'reason', 'metadata_snapshot', 'deleted_at', 'deleter_id'])]
class GdprDeletionLog extends BaseModel
{
    public const UPDATED_AT = null;

    protected $casts = ['metadata_snapshot' => 'array'];

    public function deleter(): BelongsTo;                           // → User via deleter_id (nullable)
    public function asGdprDeletionLogState(): GdprDeletionLogState;
}
```

### 6.4 Schema After the Follow-Up Migration

```php
Schema::table('gdpr_deletion_logs', function (Blueprint $table) {
    $table->string('user_email', 255)->nullable()->after('user_id')->index();
    $table->string('deletion_type', 30)->nullable()->after('user_email');
    $table->text('reason')->nullable()->after('deletion_type');
    $table->timestamp('deleted_at')->nullable()->after('reason')->index();
    $table->foreignUuid('deleter_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
});
```

Retained columns: `id` (UUID PK), `user_id` (orphaned UUID of the erased user), `metadata_snapshot` (JSON), `created_at` (log creation time).

### 6.5 Erasure Action and Its Callers

```php
// app/Modules/SysAdmin/Observability/GdprDeletionLog/Actions/DeleteUserGdprAction.php
final class DeleteUserGdprAction extends BaseCommandAction
{
    public function execute(
        User $user,
        GdprDeletionType $type,
        ?string $reason = null,
        ?User $deleter = null,
    ): GdprDeletionLog;
}

// app/Modules/User/UserManagement/Actions/DeleteUserAction.php
final class DeleteUserAction extends BaseCommandAction
{
    public function execute(User $user, ?string $reason = null): void;
    // Delegates to DeleteUserGdprAction with PERMANENT_DELETION before $user->delete()
}

// app/Modules/User/UserManagement/Actions/BatchDeleteUserAction.php
final class BatchDeleteUserAction extends BaseCommandAction
{
    public function execute(array $ids, ?string $reason = null): array;
    // Returns ['deleted' => N, 'skipped' => M]; one log row per erased user
}
```

### 6.6 Event, Policy, Component, Routes, Locales

```php
// Events/UserGdprDeleted.php — extends BaseEvent, eventName() returns 'user.gdpr_deleted'

// Policies/GdprDeletionLogPolicy.php — extends BasePolicy
// viewAny / view / create each answer the administrative-role check

// Livewire/GdprDeletionLogs.php — search, filterType, sortable deleted_at/user_email,
// paginated at 20 per page, badge per deletion type, timestamp as Y-m-d H:i
```

Routes: `GET /admin/gdpr-logs` behind `auth` plus the administrative role middleware. Locale namespace `sysadmin.gdpr_logs.*` mirrored in `lang/en` and `lang/id`, including the per-type labels.

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-GDPR-001 | GDPR logging lives in a dedicated action rather than inline in user deletion | P0 | — | — |
| DD-GDPR-002 | Deletion logs are append-only with no update or delete surface | P0 | — | — |
| DD-GDPR-003 | Snapshot-before-delete and same-transaction log creation | P0 | — | — |
| DD-GDPR-004 | Email as a first-class indexed column alongside the retained orphan user id | P1 | — | — |
| DD-GDPR-005 | Deletion logs are retained indefinitely with no automatic cleanup | P1 | — | — |

### 7.1 Lifecycle & Integrity

#### DD-GDPR-001 — A Small Action Instead of a Bigger Method

The plain user deletion already juggles its protection checks and the hard delete. Folding snapshot capture, log persistence, event dispatch, and structured logging into the same method would stretch it past a single responsibility and make the compliance half untestable without destroying a user. The dedicated action keeps each side focused, lets tests exercise erasure logging against a fixture that survives, and gives the batch path compliance for free since it already delegates downward.

#### DD-GDPR-002 — Tamper-Evident by Construction

An audit log that administrators can edit is a diary, not evidence. Removing the update timestamp and shipping no edit or delete screens turns "please don't touch history" from a policy sentence into a structural fact. The accepted cost is that a mistyped reason cannot be corrected in place; the school answers that with a fresh written note filed alongside, exactly as it would correct a paper register with a countersigned margin note rather than white-out.

#### DD-GDPR-003 — Read First, Write Together

Two orderings that look equivalent are not. Reading the personal fields after the delete returns nothing, and writing the log after the delete succeeds leaves a window where the person is gone but unproven. Capturing before and persisting inside the same transaction closes both windows at once: the snapshot sees the living row, and the log row and the deletion share one fate. The lock lasts a fraction longer on an operation that runs a handful of times a week.

#### DD-GDPR-004 — Queryable Column Plus Frozen Copy

Searching inside a JSON snapshot for an email address is slow, database-specific, and unreadable in review. A dedicated indexed email column makes the browser's search and sort trivially fast, while the snapshot keeps its own frozen copy for audit fidelity. The duplication is deliberate: the column serves the living query, the snapshot serves history. The orphaned original user id stays beside them so old entries still join against activity history by identifier even though the referenced row is gone.

#### DD-GDPR-005 — The Log Is Never Pruned by the System

No scheduled job deletes deletion records, however old they grow. The table is small — one row per erased account — so indefinite retention costs almost nothing and buys permanent provability. If a future regulation ever demands expiry of the log itself, that change will arrive as an explicit, reviewed amendment with its own migration, not as a quiet background prune an auditor discovers after the fact.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Erasures without a log record | 0 | Count of user deletions lacking a matching log row |
| Log rows ever updated or deleted | 0 | Absence of update/delete paths plus table audit |
| Non-admin log views | 0 incidents | Policy denial coverage on routes and actions |
| Export answers a subject request | One JSON document per request | Operator walkthrough per period |
| Stale snapshots from post-delete reads | 0 | Snapshot-content assertions in erasure tests |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [base-classes.md](SE5Q9-base-classes.md) | Command action, entity, event, model, and policy base classes with the rejection contract |
| [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) | Dual-channel structured logger with PII masking |
| [event-system.md](NUCY3-event-system.md) | Base event contract and listener registration |
| [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) | Administrative-role check and role middleware |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | Locale and compliance configuration surface |
| [user-crud-and-status.md](95EVB-user-crud-and-status.md) | User deletion actions and the user manager component |

### Build Guide

Erasure and its proof ship together: the enum and log model first, then the dedicated action, then the delegation inside the existing deletion path, and finally the browser over the completed columns. Each layer is exercised against its requirement ids before the next begins.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [system-maintenance.md](E1MSJ-system-maintenance.md) | Log retention posture and scheduled hygiene run alongside maintenance |
| 2 | [user-crud-and-status.md](95EVB-user-crud-and-status.md) | Reason parameter and GDPR dependency land in the user deletion path |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| A-1 | We assume one log row per erased account stays small enough for indefinite retention at school scale | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Phase 12 maintenance group and dependency order
- [User CRUD and status](95EVB-user-crud-and-status.md) — deletion actions this spec extends
- [Logging and error handling](89SRA-logging-and-error-handling.md) — dual-channel logger and masking
- [RBAC and authorization](T4B26-rbac-and-authorization.md) — administrative-role gates
- [System maintenance](E1MSJ-system-maintenance.md) — scheduled hygiene alongside compliance
- [Data archiving](9YUUK-data-archiving.md) — long-term cohort sealing
- [Program closure archival ADR](../adr/adr-program-closure-archival.md) — snapshot and immutability direction
- [SmartLogger dual-channel ADR](../adr/adr-smartlogger-dual-channel.md) — activity plus system channels
- [Exception hierarchy ADR](../adr/adr-exception-hierarchy.md) — business-rule rejection contract
