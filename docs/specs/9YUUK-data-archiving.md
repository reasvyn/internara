# Data Archiving & Retention — Full Archival Lifecycle

> **Spec ID:** 9YUUK
> **Status:** Planned
> **Owner:** SysAdmin
> **Depends on:** E1MSJ, HBXCI, 7HNCF, 8FVZA, YB22J, R6BMW

## Description

Defines what happens to a finished cohort after the certificates are handed out: a sealing operation that freezes a versioned snapshot, locks the records behind an archived status at every layer, and keeps alumni reading their certificates while writing nothing. Retention periods are declared per data category with school-level overrides, recorded on every archive row, and never enforced by automatic deletion. Reopening a sealed cohort is an exceptional, fully audited act reserved for the highest operator role.

---

## 1. Problem Statements

### PS-1 — A Finished Cohort Has No Moment of Being Finished

Registrations, logbooks, attendance, assessments, reports, and certificates for a graduated group simply linger, editable, months after the closing ceremony. Nothing ever declares the cohort sealed, so a well-meaning correction in October quietly rewrites history that a regulator in March assumes was frozen in June. The absence of a sealing moment turns every old record into a draft forever.
**→ Requirement:** FR-ARCV-001 (sealing gate), FR-ARCV-002 (versioned snapshot).

### PS-2 — Retention Periods Are Declared and Then Ignored

Configuration files name retention lengths per category, but no record carries its own expiry and no view shows the countdown. Data accumulates without bound while the school believes it is compliant, because a number in a config file enforces nothing by itself. Each archived aggregate needs its retention written onto it at sealing time, visible to anyone who opens the registry.
**→ Requirement:** FR-ARCV-003 (retention recorded per row), FR-ARCV-004 (declared policy, manual enforcement).

### PS-3 — Alumni Lose Their Certificates With Their Logins

Graduation currently ends access entirely: the same transition that closes the cohort locks graduates out of the certificates they earned. An alumna applying for work five years later must phone the school office and wait days for a reprint, while the system that issued her certificate claims never to have known her. Continued read-only access is not generosity; it is the purpose of keeping the records at all.
**→ Requirement:** FR-ARCV-008 (alumni read-only continuity).

### PS-4 — Policies Alone Cannot Keep History Honest

A policy that denies edits is one forgotten gate away from silent writes: a new action, a console command, a seed script, each capable of touching rows the UI hides. Integrity that lives only in the presentation layer is a curtain, not a lock. The archived state must refuse writes in the model, deny them in the policy, and hide the controls in the interface, so all three have to fail together before history can move.
**→ Requirement:** FR-ARCV-005 (model immutability), FR-ARCV-006 (policy denial), FR-ARCV-007 (read-only interface).

### PS-5 — Reopening Must Be Possible and Must Hurt a Little

Sealed records sometimes need reopening: a mis-sealed cohort, a grade dispute upheld on appeal, a regulator asking for a correction with a paper trail. Banning reversal entirely guarantees the first emergency will be solved with database surgery and no audit trail. The honest design names the reversal, restricts it to the highest role, and records it indelibly, so the exceptional path is visible instead of clandestine.
**→ Requirement:** FR-ARCV-009 (exceptional audited reversal).

---

## 2. Goals & Non-Goals

### Goals

- **One sealing moment per cohort** — a coordinated operation that snapshots, locks, and registers the finished group. *Why:* without a single moment of closure, old records stay editable forever.
- **Immutable, layered archives** — writes refused in the model, denied in policy, hidden in the interface. *Why:* a single layer of protection fails the first time someone adds a new write path.
- **Alumni continuity** — graduates keep reading certificates and grades while writing nothing. *Why:* the archive exists so former students can prove what they earned.
- **Declared, visible retention** — every archive row carries its own retention horizon from overridable policy. *Why:* a retention number nobody can see on the record enforces nothing.
- **Audited exceptional reversal** — a highest-role-only reopening with a permanent trail. *Why:* emergencies will happen; the design must channel them, not pretend they won't.

### Non-Goals

- **Backup creation and restoration**. *Why:* owned by the backup system; archives live alongside backups, not inside them.
- **Erasure mechanics and deletion-log schema**. *Why:* owned by GDPR compliance; archival reuses that pipeline for manual post-expiry deletion.
- **Routine pruning, warming, and health checks**. *Why:* owned by system maintenance; archiving coordinates with the scheduler but never duplicates it.
- **Automatic deletion at expiry**. *Why:* expiry only marks eligibility; deletion stays a deliberate manual act with its own audit.
- **Cloud tiering or multi-tenant partitioning**. *Why:* single-tenant local storage at school scale needs neither.

---

## 3. User Stories / Use Cases

Sealing is an operator ceremony; retrieval is an alumni right; reversal is a rare, highest-role exception.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-ARCV-001 | Admin seals a completed cohort into an immutable snapshot with recorded retention | P0 | F | Planned |
| UC-ARCV-002 | Alumna retrieves her certificate and grades years later through read-only access | P0 | B | Planned |
| UC-ARCV-003 | Highest operator exceptionally reopens a sealed cohort with a permanent audit trail | P0 | F | Planned |
| UC-ARCV-004 | Admin browses the archive registry with status, retention countdown, and lifecycle actions | P1 | F | Planned |

### 3.1 Sealing and Retrieval

#### UC-ARCV-001 — Sealing the Class of 2026

June arrives, certificates are issued, and the coordinator opens the archive registry, selects the completed internship, and confirms the sealing with a short reason. The operation first verifies every readiness condition: assessments finalized, submissions accounted for, attendance reconciled, certificates issued. Then it freezes the versioned snapshot, the roster and composites and serials exactly as they stand, transitions the cohort to its terminal state, seals the student accounts through the existing account archival, and writes the registry row with the retention horizon resolved from policy. By afternoon the cohort reads as history. A teacher who opens an old grade afterward finds it exactly as the examination board signed it, which is the entire point.

#### UC-ARCV-002 — A Five-Year-Old Archive Retrieved for a Legal Check

Five years later, the same graduate needs her certificate for a civil-service application, and the hiring office wants to verify its serial. She signs in with her alumni credentials and reaches a quiet dashboard showing her certificate and her final grades, nothing else. No placement form accepts her input, no logbook offers a blank row, no attendance button responds. She downloads the certificate, the hiring office matches its serial against the school's copy, and the verification closes the same day. The archive justified its storage costs in that single afternoon.

### 3.2 Reversal and Oversight

#### UC-ARCV-003 — Reopening What Should Not Have Been Sealed

In September an appeal upholds a grade dispute for one student of the sealed cohort: the board orders a correction. The coordinator cannot reopen anything; the control simply is not shown to her role. The highest operator reviews the appeal letter, invokes the exceptional reversal with the appeal reference as the reason, and the cohort steps back to its pre-seal state while the audit trail records who ordered it, when, and why. The correction is made, the cohort is sealed again as a new snapshot version, and the registry now tells the whole story: sealed, reopened by name, corrected, resealed. Had this path not existed, someone would have edited the database directly and none of that would be written anywhere.

#### UC-ARCV-004 — Reading the Registry on a Quiet Morning

The operator opens the archive overview and sees every sealed aggregate in one paginated table: category, referenced cohort, status badge, who sealed it and when, the retention horizon with its countdown, and the available actions. Filtering by status isolates the sealed rows from the handful reopened over the years; sorting by sealing date puts the oldest first. Destructive actions ask for confirmation with the consequences spelled out, and the expired rows display their eligibility plainly without offering any automatic purge, because the registry advises and humans decide.

---

## 4. Functional Requirements

One table for the whole section. Group 4.1 seals the cohort, 4.2 locks the records while keeping alumni reading, 4.3 governs the exceptional way back.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARCV-001 | Cohort sealing validates completion and readiness before any write | P0 | F | Planned |
| FR-ARCV-002 | Sealing freezes a versioned JSON snapshot of roster, grades, attendance, logbook, scores, evaluations, and certificate serials | P0 | F | Planned |
| FR-ARCV-003 | Every archive row records category, reference, status, retention horizon, sealer identity, and sealing time | P0 | F | Planned |
| FR-ARCV-004 | Effective retention resolves from school override to config default and expiry never triggers automatic deletion | P0 | F | Planned |
| FR-ARCV-005 | Archived records refuse writes at the model layer behind the archived-state gate | P0 | U | Planned |
| FR-ARCV-006 | Policies deny non-read operations on archived records to every role including operators | P0 | U | Planned |
| FR-ARCV-007 | The interface renders sealed cohorts read-only with no edit controls offered | P1 | B | Planned |
| FR-ARCV-008 | Alumni retain sign-in to a read-only dashboard with certificates and grades and no write paths | P0 | F | Planned |
| FR-ARCV-009 | Exceptional reversal is restricted to the highest role, returns the cohort to its pre-seal state, and writes a permanent audit entry | P0 | F | Planned |
| FR-ARCV-010 | Cohort sealing delegates student-account archival to the existing account archival action | P1 | F | Planned |
| FR-ARCV-011 | Sealing and reversal emit domain events and structured log entries with masked personal data | P1 | F | Planned |
| FR-ARCV-012 | Sealing and reversal validate input and authorization and reject violations with translatable business errors | P0 | F | Planned |

### 4.1 Sealing

#### FR-ARCV-001 — No Seal Before the Work Is Done

A coordinator eager to tidy the dashboard might seal a cohort with two unissued certificates and a missing evaluation round. The sealing operation therefore interrogates readiness first: every assessment finalized, every submission accounted for, attendance reconciled, supervision logs present, certificates issued. Any gap aborts the whole operation before the first write, and the refusal names the missing piece in plain language. The examination board's sign-off meeting and this check are the same event in two forms: human judgment up front, mechanical verification at the gate.

#### FR-ARCV-002 — Freezing the Cohort Exactly As It Stood

At sealing time the operation assembles the roster, the grade composites, the attendance summary, logbook statistics, assignment and rubric scores, evaluation outcomes, and every certificate serial into one versioned JSON document. That document is the cohort's photograph: later corrections never retouch it but instead produce a new version beside it. Five years on, when a hiring office questions a serial, the school opens the version that was current at graduation rather than reconstructing truth from live tables that have since moved on.

#### FR-ARCV-003 — The Registry Remembers the Circumstances

Alongside the snapshot, the registry records which category was sealed and which cohort it points at, the current status, the retention horizon, the identity of the operator who sealed it, and the sealing timestamp. The retention horizon is never left blank: a missing horizon would make the row silently immortal or silently eligible, and both silences are unacceptable. Restoration and purge moments, when they occur, land in their own columns rather than overwriting the sealing facts.

#### FR-ARCV-004 — Declared Horizons, Human Hands

The horizon for each category resolves in one place: the school's stored override wins when present, otherwise the shipped configuration default applies, so a school with stricter local rules needs no deployment to honor them. When the horizon passes, the registry marks the row eligible and waits. No job sweeps eligible rows away overnight, because automatic deletion converts a misconfigured horizon into silent data loss. Expiry is advice displayed to an operator; deletion is a decision made by one.

### 4.2 Immutability and Access

#### FR-ARCV-005 — The Model Says No First

The deepest lock sits where new code is most likely to forget it. Model observers and state gates inspect the archived flag before any update or delete reaches the database and refuse with a business error. A future console command, an import script, or a well-meaning patch that bypasses the interface still meets this refusal, because it lives on the write path itself rather than on any particular screen. Tests prove the gate by attempting writes against sealed fixtures and asserting the refusal, not by clicking buttons.

#### FR-ARCV-006 — The Policy Says No Second

Above the model, authorization denies every non-read operation on sealed records regardless of the caller's role: coordinator, operator, supervisor, and student alike. Read operations continue to pass for those entitled to see them, so the denial is surgical rather than a blanket invisibility. The double barrier matters because each layer fails differently: policies are bypassed by direct action calls, models by raw queries, and only the pair covers both shortcuts at once.

#### FR-ARCV-007 — The Screen Does Not Offer What Is Forbidden

The cohort views check the archived state before rendering and simply omit every edit control: no inline editors, no bulk actions, no drag handles, no "quick fix" links. What remains is a calm, legible record with its sealed badge and its retention note. Hiding the controls is the least of the three locks technically, but it is the one users actually meet, and a forbidden button that invites a click before refusing breeds exactly the resentment the design wants to avoid.

#### FR-ARCV-008 — Graduation Ends Writing, Not Reading

Sealed students keep their credentials and meet a reduced dashboard: certificates downloadable, grades visible, and nothing else actionable. Placement applications, logbook entries, attendance buttons, and assignment uploads are absent rather than disabled, so there is no form to submit against a gate. Re-enrollment in a later cohort travels through a fresh status rather than by resurrecting the sealed identity, keeping the archive's meaning intact while the person's journey continues.

### 4.3 Exceptional Reversal

#### FR-ARCV-009 — The Way Back Is Narrow, Lit, and Watched

Only the highest operator role may invoke the reversal, and only against rows still sealed rather than already purged. The operation returns the cohort to its pre-seal state, stamps the reversal identity and moment, and writes an audit entry naming the authorizing reason, typically an appeal reference or a regulatory order. Coordinators and supervisors never see the control, so social pressure to "just reopen it for a moment" meets a genuine inability rather than a reluctant refusal. Each reversal is rare enough that its audit entry should be readable years later without supplementary explanation.

#### FR-ARCV-010 — Account Sealing Is Borrowed, Not Rebuilt

The cohort operation does not reimplement student-account transitions. It delegates that step to the existing account archival action, inheriting its chunking, its protection of the system identity, and its per-account logging. If that action's behavior ever improves, cohort sealing improves with it; if its guards tighten, sealing tightens too. The orchestration stays an orchestration instead of slowly accreting a second copy of account logic that drifts out of sync.

#### FR-ARCV-011 — Sealing Announces Itself Twice

Persisting the snapshot is followed by a domain event carrying the registry record and a structured log entry with the operator, the cohort reference, and the retention horizon. Personal data in the payload passes through the masking step before reaching any sink. Downstream reactions such as notification fan-out or cache invalidation attach to the event rather than lodging inside the sealing transaction, keeping the moment of closure itself small, synchronous, and easy to reason about.

#### FR-ARCV-012 — Refusals Speak the User's Language

Every validation failure and every authorization denial in the sealing and reversal paths surfaces as a business-rule rejection carrying a translatable sentence, never as a raw database error or an empty denial. The coordinator who selects a not-yet-completed cohort learns which readiness condition failed; the supervisor who guesses at a reversal URL meets a denial that explains nothing about the record's existence. Input arrives through validated data objects, so malformed payloads are rejected before any business logic runs.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-ARCV-001 | Archive, retrieval, and reversal strings render through the translation helper in both locales | N/A | P1 | A | Planned |
| NFR-ARCV-002 | Sealing is operator-only and reversal is highest-role-only through layered gates | N/A | P0 | U | Planned |
| NFR-ARCV-003 | Every sealing and reversal writes an actor-identified audit entry in both log channels | N/A | P0 | F | Planned |
| NFR-ARCV-004 | Snapshots and registry rows are never silently rewritten; corrections arrive as new versions | N/A | P0 | F | Planned |

### 5.1 Language, Access, and Proof

#### NFR-ARCV-001 — Two Languages Over the Same Archive

A coordinator sealing a cohort reads Indonesian; an external auditor reviewing the registry a year later may read English. Every status label, action name, confirmation sentence, and error string resolves through the translation helper with mirrored keys, and dynamic values like cohort names travel as placeholders. The snapshot content itself stays in its stored form, but everything around it, the badges, the countdowns, the confirmations, meets each reader in their own language.

#### NFR-ARCV-002 — The Most Dangerous Button Belongs to the Fewest Hands

Sealing reshapes the daily views for hundreds of students, so it sits behind the operator gate at both the route and the action. Reversal unmakes that decision, so it sits one level higher still, where only the system's highest role may reach it. Each gate is enforced twice, in the policy and in the action, because a single forgotten annotation on a new entry point must never promote a coordinator into an archivist by accident.

#### NFR-ARCV-003 — Every Transition Leaves Two Footprints

Sealing and reversal each write to the queryable activity store for the auditor and to the technical system log for the operator, with personal data masked before either sink. The entry names the actor, the cohort, the previous and new states, and the stated reason. Years later, when the question is not what the archive holds but who ordered each change, these paired footprints answer without requiring anyone's memory.

#### NFR-ARCV-004 — Corrections Accumulate Instead of Overwriting

A sealed snapshot is never edited in place, and a registry row's sealing facts are never revised to look tidier. When an upheld appeal changes a grade, the reversal and reseal produce a new snapshot version beside the old one, and the registry shows the full chain. Storage cost at school scale is negligible; the credibility earned by showing every version instead of only the latest is the reason the archive exists.

---

## 6. API / Data Contracts

### 6.1 Retention Policy

```php
// config/retention.php
return [
    'categories' => [
        'cohort'          => env('RETENTION_COHORT_YEARS', 5),
        'registration'    => env('RETENTION_REGISTRATION_YEARS', 10),
        'student_account' => env('RETENTION_STUDENT_ACCOUNT_YEARS', 5),
        'logbook'         => env('RETENTION_LOGBOOK_YEARS', 5),
        'attendance'      => env('RETENTION_ATTENDANCE_YEARS', 10),
        'assessment'      => env('RETENTION_ASSESSMENT_YEARS', 10),
        'report'          => env('RETENTION_REPORT_YEARS', 10),
        'certificate'     => env('RETENTION_CERTIFICATE_YEARS', 10),
    ],
];
// School overrides via settings('retention.{category}'); resolution lives in one policy class.
```

### 6.2 Registry Model and Status

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

// Enums/ArchiveStatus.php — ARCHIVED, RESTORED, PURGED with translated labels;
// ARCHIVED may reverse to RESTORED only through the exceptional reversal;
// PURGED is terminal and reached only by deliberate manual deletion.
```

### 6.3 Lifecycle Actions

```php
// app/Modules/SysAdmin/Archive/Actions/ArchiveCohortProcessAction.php
final class ArchiveCohortProcessAction extends BaseProcessAction
{
    public function execute(ArchiveCohortData $data): ActionResponse;
    // Validates COMPLETED + readiness, freezes the versioned snapshot,
    // delegates account sealing, writes the registry row, emits the event.
}

// app/Modules/SysAdmin/Archive/Actions/RestoreArchiveAction.php
final class RestoreArchiveAction extends BaseCommandAction
{
    public function execute(ArchiveRecord $record, ?string $reason = null): ActionResponse;
    // Highest role only; pre-expiry sealed rows; audited; purged rows never reverse.
}

// app/Modules/SysAdmin/Archive/Data/ArchiveCohortData.php
final class ArchiveCohortData extends BaseData
{
    public function __construct(
        public readonly string $internshipId,
        public readonly ?string $reason = null,
    ) {}
}
```

### 6.4 Events, Routes, Locales, Schema

Events `CohortArchived`, `ArchiveRestored`, and `ArchivePurged` extend the base event, each carrying the registry record with its translation key. Routes expose `GET /admin/archives` behind authentication plus the operator role gate; the alumni dashboard reuses the existing authenticated layout with archived-state scoping. Locale namespace `sysadmin.archive.*` is mirrored in both languages across titles, categories, statuses, actions, confirmations, and errors. The `archive_records` table carries a UUID primary key, category, polymorphic reference, status, non-nullable retention horizon, sealing and reversal audit columns with null-on-delete operator references, purge timestamp, reason, and a composite index over status and retention horizon.

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ARCV-001 | A central archive registry instead of per-table archive flags | P1 | — | — |
| DD-ARCV-002 | Sealed and purged as distinct states with manual-only deletion at expiry | P0 | — | — |
| DD-ARCV-003 | Post-expiry deletion reuses the GDPR erasure pipeline by hand, never by scheduler | P0 | — | — |
| DD-ARCV-004 | Cohort sealing delegates account transitions to the existing archival action | P1 | — | — |
| DD-ARCV-005 | Retention resolves from school override to config default at a single point | P1 | — | — |
| DD-ARCV-006 | Reversal is exceptional, highest-role-only, and permanently audited | P0 | — | — |

### 7.1 Registry, Retention, and Reversal

#### DD-ARCV-001 — One Register Instead of Flags Everywhere

Adding an archive flag to every sealable table would scatter retention logic across a dozen migrations and leave the question "what is sealed, until when, by whom" answerable only by joining the whole database. A single registry row per sealed aggregate gathers category, reference, status, horizon, and provenance in one queryable place while leaving module schemas untouched. The registry points at aggregates rather than duplicating them, which means a truly purged record is genuinely gone, the intended compliance behavior rather than a caching accident.

#### DD-ARCV-002 — Eligibility Is Not Execution

Sealed means frozen but recoverable; purged means gone with paperwork. Between them sits the retention horizon, which marks eligibility and does nothing else. Collapsing those states would either resurrect expired data through casual restores or delete live history through eager automation. Keeping them distinct lets the registry say "this may now be deleted" while requiring a human to answer "and so it shall be," with the deletion log as the receipt.

#### DD-ARCV-003 — The Scheduler Never Deletes

An overnight job that deletes on a predicate is one misconfigured horizon away from an empty archive and an apology letter. Post-expiry deletion therefore travels through the GDPR erasure workflow by an operator's explicit hand: snapshot already frozen, compliance record written, reason stated. The school keeps the proven deletion machinery without granting it autonomy, and every purge carries a human name beside it.

#### DD-ARCV-004 — Borrowed Account Logic Stays Borrowed

Account archival already handles chunking, protects the system identity, and logs its count. Reimplementing any of that inside the cohort operation would create two definitions of "archived student" that diverge within a year. Delegation keeps one definition with two callers, and the cohort operation remains what it should be: readiness, snapshot, registry, event. The inherited behaviors, including which identities are skipped, apply identically in both contexts by construction.

#### DD-ARCV-005 — Two Sources, One Answer

Schools need to adjust horizons without deploying, and fresh installs need sane defaults without a database. Stored overrides plus configuration defaults satisfy both, but two sources invite disagreement about which won. Funneling every read through a single policy class removes the ambiguity: callers ask for the horizon of a category and receive one number with a known provenance. Reviews check the policy class instead of hunting scattered fallbacks.

#### DD-ARCV-006 — Emergencies Deserve a Lit Path

The alternative to an audited reversal is not "nobody ever reopens" but "somebody reopens with raw SQL at midnight." Naming the reversal, gating it to the highest role, demanding a reason, and recording it permanently converts the midnight edit into a daylight procedure. The friction is calibrated: low enough that a genuine appeal succeeds, high enough that convenience never reaches for it.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Completed cohorts left unsealed past term end | 0 | Registry coverage against completed internships |
| Writes accepted against sealed records | 0 | Refusal assertions across model, policy, and interface paths |
| Alumni certificate retrievals served read-only | All served | Alumni dashboard walkthrough per term |
| Reversals lacking actor, reason, and timestamp | 0 | Audit completeness review of reversal entries |
| Rows auto-deleted at expiry | 0 | Absence of any scheduled deletion path |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [system-maintenance.md](E1MSJ-system-maintenance.md) | Account archival action, archived status, and scheduler patterns |
| [gdpr-compliance.md](7HNCF-gdpr-compliance.md) | Erasure workflow and deletion log for manual post-expiry deletion |
| [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) | Queue conventions for any deferred archival fan-out |
| [reports.md](R6BMW-reports.md) | Finalized report snapshots as archive content |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | Stored overrides for retention horizons |
| [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) | Operator gates and the highest-role distinction |
| [base-classes.md](SE5Q9-base-classes.md) | Data, action, process, event, model, and rejection contracts |
| [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) | Structured logger with masking |
| [internship-lifecycle.md](7C5WM-internship-lifecycle.md) | Completed state as the sealing trigger |

### Build Guide

Policy and registry first: horizons, the migration, the model, and the status vocabulary. Sealing follows with readiness, snapshot, delegation, and events, each exercised against its requirement ids. Immutability gates land alongside the first sealed fixture so no write path ever predates its lock. The reversal and the alumni dashboard close the build, proving the archive is both trustworthy and useful.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Maintenance is the final phase — the lifecycle runs continuously once built |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| A-1 | We assume school-scale cohorts produce snapshots and registries that stay small enough for indefinite local retention | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Phase 12 maintenance group and dependency order
- [System maintenance](E1MSJ-system-maintenance.md) — account archival and scheduler patterns reused here
- [GDPR compliance](7HNCF-gdpr-compliance.md) — erasure workflow for manual post-expiry deletion
- [Internship lifecycle](7C5WM-internship-lifecycle.md) — completed state as the sealing trigger
- [Reports](R6BMW-reports.md) — finalized snapshots as archive content
- [Program closure archival ADR](../adr/adr-program-closure-archival.md) — snapshot, terminal state, alumni, and exceptional reversal
- [MVP trim ADR](../adr/adr-mvp-spec-trim.md) — pipeline depth deferred to post-MVP phases
- [Cross-module communication ADR](../adr/adr-cross-module-communication.md) — delegation to account archival
- [SmartLogger dual-channel ADR](../adr/adr-smartlogger-dual-channel.md) — activity plus system channels
- [Exception hierarchy ADR](../adr/adr-exception-hierarchy.md) — business-rule rejection contract
