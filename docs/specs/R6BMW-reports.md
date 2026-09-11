# Reports — Final Grade Card Compilation, Weighted Aggregation & Archival Snapshot

> **Spec ID:** R6BMW
> **Status:** Full
> **Owner:** Reports
> **Depends on:** J0M04

## Description

The Reports module compiles every scoring source for a placement — supervisor, teacher, assignment, and exam — into one authoritative grade card per registration. It aggregates through configurable program weights, locks the result behind coordinator sign-off with a frozen identity snapshot, and serves the finalized card as a PDF through the Document module.

---

## 1. Problem Statements

### PS-1 — Scattered Scores, No Authoritative Card

Supervisor evaluations live in Assessment, pedagogical grades with teachers, exam marks in formal assessment rows, assignment grades in submissions. Compiling a final grade means opening four screens and hand-adding numbers into a spreadsheet, and every hand addition is a chance to transpose a digit or use last month's weight.
**→ Requirement:** FR-RPT-001/004 (one card per registration, weights read live).

### PS-2 — One Weight Table Does Not Fit Every Program

A machining program trusts the shop floor and weights the supervisor at forty percent; an office-administration program leans on exams. Baking percentages into code forces a redeploy every time a program head renegotiates the balance.
**→ Requirement:** FR-RPT-004 (program-level weights with a sane fallback).

### PS-3 — Missing Scores Must Fail Loudly, Never Silently

A supervisor who never submitted an evaluation is the normal case, not the exception — remote sites go quiet for weeks. A pipeline that treats the gap as zero without saying so prints a defensible-looking 68 that is really an incomplete 68.
**→ Requirement:** FR-RPT-008 (missing sources counted as zero and flagged).

### PS-4 — History Must Survive Later Edits

After sign-off the world keeps moving: a student transfers and her name spelling is corrected, a supervisor resigns, a partner company rebrands. A grade card that re-reads live relations rewrites its own past every time any of those rows change.
**→ Requirement:** FR-RPT-015 (frozen snapshot at finalization).

### PS-5 — A Signed-Off Grade Must Be Untouchable

Once the coordinator communicates a final grade it feeds the certificate and the parent meeting. A late "correction" applied directly to the row — however well meant — silently invalidates everything downstream that already cited the old number.
**→ Requirement:** FR-RPT-012/013 (DRAFT→FINALIZED transition, post-lock rejection).

---

## 2. Goals & Non-Goals

### Goals

- **One grade card per registration** — a single authoritative record from DRAFT to FINALIZED. *Why:* ends spreadsheet compilation and its transcription errors.
- **Live program weights** — aggregation reads the program's weight table at calculation time. *Why:* program heads rebalance without a deploy.
- **Role-gated aggregates** — teachers see only mentored students, supervisors only their own site. *Why:* a grade card is personal data, not a leaderboard.
- **Lock plus snapshot on sign-off** — immutability and frozen identity in one atomic step. *Why:* accreditation folders and certificates cite numbers that must never drift.
- **Canonical PDF through the Document module** — one format, one rendering owner. *Why:* rendering pipelines evolve without touching grade logic.

### Non-Goals

- **Grade appeals or dispute workflows**. *Why:* appeals are a school-committee process with paper minutes, not a state machine in this module.
- **Automatic finalization on period end**. *Why:* sign-off is a human accountability act; a timer cannot take responsibility for a grade.
- **Student-facing grade browsing**. *Why:* students receive grades through Certification, keeping Reports an operator surface.
- **Retroactive recalculation under changed weights**. *Why:* finalized cards are history; new weights apply to future calculations only.
- **Multi-school or cross-tenant comparisons**. *Why:* single-tenant by product definition.

---

## 3. User Stories / Use Cases

Every operator journey through the grade card, from opening the draft to handing over the PDF.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-RPT-001 | Coordinator opens a grade card for a placed registration; a DRAFT card appears ready for calculation | P0 | F | Full |
| UC-RPT-002 | Coordinator runs the weighted calculation; the composite score and letter appear with missing sources flagged | P0 | F | Full |
| UC-RPT-003 | Teacher reviews aggregates limited to mentored students; all other rows stay invisible | P0 | F | Full |
| UC-RPT-004 | Coordinator signs off; the card locks, snapshots identity, and emits the finalization event | P0 | F | Full |
| UC-RPT-005 | Admin downloads the finalized card as a PDF for the archive folder | P1 | F | Full |

### 3.1 Compilation & Sign-Off

#### UC-RPT-001 — Open a Grade Card

The week after placements settle, the program coordinator walks down the registration list opening a card per student. The first click creates the DRAFT — empty scores, no letter — and the second click on the same student simply returns the existing draft instead of doubling it. That idempotent open matters during enrollment chaos, when two operators work the same cohort from different laptops and neither should be able to fork a student's record into twins.

#### UC-RPT-002 — Run the Weighted Calculation

With assessments trickling in, the coordinator presses calculate and watches the composite assemble itself: supervisor, teacher, assignment average, exam, each multiplied by the program's live weights. When the remote-site supervisor still has not submitted, the card does not pretend everything is fine — the missing component shows as zero with a visible flag, so the coordinator knows this 71 is provisional and whose phone needs ringing before sign-off.

#### UC-RPT-004 — Sign Off and Lock

Sign-off day carries real tension, because everyone in the room knows the number becomes permanent the moment the coordinator confirms. The transition stamps who signed and when, freezes names and company details exactly as they read that morning, and announces the event the certificate pipeline listens for. From that second forward the card is read-only history, and any later correction must travel through a new documented action rather than a quiet edit.

### 3.2 Gated Views & Handover

#### UC-RPT-003 — Teacher Reviews Mentored Aggregates

A supervising teacher opening the reports area mid-semester sees her own twelve mentees and nobody else's — not the parallel class, not the other department's cohort. The scoping happens before any row renders, resolved from the registration's mentor bridge, so there is no flash of чужой data and no URL trick that reveals a neighboring teacher's students. What she sees is enough to spot a mentee sliding toward a D while there is still time to intervene.

#### UC-RPT-005 — Download the Finalized PDF

For the accreditation folder the school needs paper, or at least something printable. The admin opens the finalized card, requests the download, and receives the canonical PDF rendered by the Document module's pipeline — same letterhead, same layout as every other official paper the school issues. Drafts never reach this step; there is deliberately no PDF of an unfinished number that could circulate as if it were final.

---

## 4. Functional Requirements

Grade cards obey the global contracts (Action Triad, dual-layer authorization, bilingual strings, masked audit logging) and add the aggregation, gating, and archival behavior below.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-RPT-001 | System opens exactly one grade card per registration; reopening returns the existing DRAFT | P0 | F | Full |
| FR-RPT-002 | New cards open as DRAFT with all score fields empty | P0 | F | Full |
| FR-RPT-003 | Creation accepts a validated DTO carrying the registration identifier | P0 | F | Full |
| FR-RPT-004 | Calculation reads weights from the registration's program at run time, falling back to 40/20/20/20 | P0 | F | Full |
| FR-RPT-005 | Supervisor, teacher, and exam components load through the owning module's Read Actions | P0 | F | Full |
| FR-RPT-006 | Heavy aggregation reads run lock-free through BaseReadAction without transactions or logging | P1 | A | Full |
| FR-RPT-007 | Assignment average loads from the student's submissions for the registration | P0 | F | Full |
| FR-RPT-008 | Composite normalizes to 0–100 with missing sources counted as zero and visibly flagged | P0 | F | Full |
| FR-RPT-009 | Grade letters follow the fixed national scale A≥90, B≥80, C≥70, D≥60, E below | P1 | U | Full |
| FR-RPT-010 | Teacher aggregate reads scope to mentored students through MentorEntity | P0 | F | Full |
| FR-RPT-011 | Supervisor reads scope to learners placed at their own company | P0 | F | Full |
| FR-RPT-012 | Sign-off transitions DRAFT→FINALIZED recording actor and timestamp | P0 | F | Full |
| FR-RPT-013 | Finalized cards reject every mutation with RejectedException | P0 | F | Full |
| FR-RPT-014 | Sign-off requires a populated composite score and grade letter | P0 | F | Full |
| FR-RPT-015 | Sign-off freezes student, program, company, and mentor identity into archived_data | P0 | F | Full |
| FR-RPT-016 | Finalized cards download as PDF in the canonical format through the Document module | P1 | F | Full |
| FR-RPT-017 | Calculation and sign-off emit GradeCalculated and ReportFinalized events | P1 | F | Full |
| FR-RPT-018 | All inputs validate server-side; raw request payloads never reach persistence | P0 | A | Full |
| FR-RPT-019 | Policy gates guard every route while Actions re-check authorization on direct calls | P0 | A | Full |
| FR-RPT-020 | Every user-facing string resolves through __() with Indonesian and English copies | P0 | A | Full |
| FR-RPT-021 | Calculation and sign-off audit-log through SmartLogger with PII masking | P0 | F | Full |

### 4.1 Card Lifecycle

#### FR-RPT-001 — One Card per Registration

During enrollment week two operators commonly work the same cohort from different machines, and double-clicks happen. The uniqueness guarantee on the registration link turns the second create into a harmless return of the existing draft instead of a twin record that would later disagree about the same student's grade. The database constraint is the backstop; the Action's pre-check is what keeps the operator experience calm.

#### FR-RPT-002 — Drafts Start Empty

An empty DRAFT is honest about what it is: a placeholder awaiting evidence, not a zero. Nulls distinguish "not yet calculated" from "calculated and failed," which is exactly the distinction a coordinator needs when scanning a cohort table for who still owes supervisor scores. The moment the card shows a number, that number came from a real calculation run.

#### FR-RPT-003 — Validated DTO on Creation

When the creation call arrives carrying anything other than a well-formed registration identifier — a blank string, a malformed UUID, an id pointing at nothing — validation stops it before any row exists. The Action exposes a single typed parameter, so there is precisely one shape to test and one place where the "which student" question gets answered.

### 4.2 Weighted Aggregation

#### FR-RPT-004 — Live Program Weights

A program head renegotiating the supervisor share from forty to fifty percent edits the program record, and the very next calculation run honors it — no deploy, no developer, no stale cached copy from last semester. The 40/20/20/20 fallback exists for young programs that have not set weights yet, so calculation never crashes on a missing configuration; it simply behaves like the national default until told otherwise.

#### FR-RPT-005 — Components via Owning Read Actions

The reports module never reaches into Assessment or Assignment tables directly. It asks each owning module's Read Action for its component, which keeps schema changes contained — if Assessment renames a column, only Assessment's Read adapts while the aggregation signature stays still. Direct cross-module queries would have silently coupled every grade run to three other modules' internals.

#### FR-RPT-006 — Lock-Free Aggregation Reads

Picture calculation morning: a coordinator recalculates the whole cohort while a thousand students clock attendance on the same database. If the aggregation held write locks while summing, those attendance writes would queue behind a report. Read Actions carry no transaction and take no locks by construction, so the morning rush and the coordinator's recalculation pass through each other untouched.

#### FR-RPT-007 — Assignment Average from Submissions

Assignment scores arrive as many rows — weekly submissions across a semester — and the card needs one number. The aggregation averages the graded submissions tied to the registration, ignoring ungraded or draft entries that would otherwise drag a real average toward a phantom zero. A student with no submissions yet contributes a flagged zero, consistent with the missing-source rule.

#### FR-RPT-008 — Composite with Loud Gaps

The composite is arithmetic, but the flagging is the actual feature. Counting a missing supervisor score as zero keeps the 0–100 scale intact, while the visible flag tells the coordinator this number is incomplete rather than merely low. Twice this distinction has decided whether a parent meeting discussed "your child is struggling" or "we are still waiting on the company" — very different conversations.

#### FR-RPT-009 — Fixed National Letter Scale

Indonesian PKL grading follows the national A–E bands, and every school in the program uses the same cutoffs. Hardcoding them keeps the letter assignment transparent enough to verify by eye — a coordinator can check any card against the bands without consulting configuration. The day a regulator changes the bands, one deliberate code change updates every card going forward while finalized history keeps its original letters.

### 4.3 Role-Gated Subsets

#### FR-RPT-010 — Teachers See Only Mentees

A teacher assigned twelve mentees out of a four-hundred-student cohort must never be able to enumerate the other three hundred eighty-eight through report URLs. Scoping resolves through the registration's mentor bridge before any row loads: the query itself carries the mentee filter, so unauthorized rows are never fetched, never serialized, never one template bug away from display. Direct Action calls get the same filter, closing the crafted-request path.

#### FR-RPT-011 — Supervisors See Only Their Site

An industry supervisor logging in to check on grading progress sees the learners placed at their own company and nothing beyond the gate. The company link on the placement is the boundary — simple, auditable, and aligned with what the supervisor legitimately needs: how are the kids at my site doing. Cross-company browsing is not a feature anyone asked for and would be a confidentiality incident if it existed.

### 4.4 Sign-Off & Snapshot

#### FR-RPT-012 — The Sign-Off Transition

Before this transition existed, "final" was a matter of convention — a coordinator would announce grades were final and everyone would politely stop editing, until someone didn't. The DRAFT→FINALIZED move replaces convention with mechanics: exactly one forward step, stamped with the signer's identity and the second it happened. Backward movement does not exist; history only moves one way.

#### FR-RPT-013 — Post-Lock Rejection

If a locked card could be edited, the certificate already issued against it would silently disagree with the record — the exact failure an accreditation audit is designed to catch. Every mutation path re-checks the lock and answers with a translatable rejection, whether the attempt comes through the UI, a direct Action call, or a queued job someone forgot to gate. The grade the parents saw is the grade the database keeps.

#### FR-RPT-014 — No Sign-Off on Empty Numbers

A coordinator rushing through a Friday-afternoon batch should not be able to lock a card that was never calculated. The completeness gate demands a composite and a letter before the transition is even offered, turning "oops, I finalized blanks" from a real incident category into an impossible one. The UI hides the button; the Action enforces the same rule for callers that bypass the UI.

#### FR-RPT-015 — Frozen Identity Snapshot

Two years after graduation, an accreditation visitor asks why the card names a supervisor who left the company long ago. The snapshot answers: because that was the supervisor on sign-off day, frozen alongside the student's name, program dates, company details, and mentor names. Live relations keep evolving for current operations; the archive keeps the truth of that morning, immune to every later rename, transfer, and rebrand.

### 4.5 Delivery & Events

#### FR-RPT-016 — Canonical PDF Download

Schools still file paper, and auditors still ask for it. The download resolves the card's document through the Document module and streams the canonical PDF — one layout, one letterhead, one pipeline owned by people who think about rendering full-time. Reports owns the numbers; Document owns the pixels, and neither module carries the other's maintenance burden.

#### FR-RPT-017 — Calculation and Sign-Off Events

Downstream consumers — the certificate pipeline, cache invalidation, notification fan-out — learn about grade changes by listening, not by polling. Calculation announces itself so dashboards can refresh; sign-off announces itself so the completion letter can generate. Because the events fire after commit, a listener never reacts to a grade that subsequently rolled back.

### 4.6 Cross-Cutting Contracts

#### FR-RPT-018 — Server-Side Validation

Every identifier and score entering the module passes validation inside the Action boundary, long before any write. A forged registration id, a negative score smuggled past the browser, a null where a UUID belongs — all rejected with a translatable message, none persisted. Client-side checks exist for speed; these exist for truth.

#### FR-RPT-019 — Dual-Layer Authorization

The route policy answers the first question — may this user touch reports at all — and the Action answers the harder one — may this user touch this student's report. A teacher passing the route gate still cannot finalize, and cannot even read outside mentorship. Either layer alone would leave a hole; together they leave none that review has found.

#### FR-RPT-020 — Bilingual Strings

A coordinator working in Indonesian and a partner-school observer working in English read the same card with equal clarity. No status label, button, toast, or rejection message bypasses the translation helper, and both language files ship every key the module uses. A missing translation is treated as a defect, not a cosmetic gap, because an untranslated rejection at sign-off time erodes trust in the whole workflow.

#### FR-RPT-021 — Masked Audit Logging

Every calculation and sign-off writes to both log channels with names and identity numbers masked before they reach any sink. The activity trail still shows who did what to whose card and when — enough for any audit — but a log file copied to a vendor during a support call never carries a student's identity number along for the ride.

---

## 5. Non-Functional Requirements

Operational guarantees the grade card workflow holds under real school load.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-RPT-001 | Creation and sign-off endpoints admit authenticated operators with the proper role only | 0 unauthenticated writes | P0 | F | Full |
| NFR-RPT-002 | Archived snapshots mask PII; no secret or identity number persists in plaintext | 0 plaintext PII findings | P0 | A | Full |
| NFR-RPT-003 | Report rows use UUID v7 primary keys with foreignUuid foreign keys | 100% of report tables | P0 | A | Full |
| NFR-RPT-004 | Report classes extend their layer bases with Fillable whitelists | 0 contract violations | P0 | A | Full |
| NFR-RPT-005 | Recalculation over unchanged sources reproduces the identical composite | identical output on identical input | P0 | F | Full |
| NFR-RPT-006 | Sign-off lands status, snapshot, and event atomically | all-or-nothing per sign-off | P0 | F | Full |
| NFR-RPT-007 | Every card view shows an unmistakable DRAFT or FINALIZED indicator in both languages | every rendered view | P1 | B | Full |

### 5.1 Protection & Structure

#### NFR-RPT-001 — Authenticated Operators Only

An unauthenticated request against the creation or sign-off endpoints meets a locked door, not a redirect loop or a polite error page with a stack trace. The middleware answers first so anonymous traffic never reaches grade logic at all, and role checks ensure a student account cannot mint its own card no matter how carefully the request is crafted.

#### NFR-RPT-002 — Masked Snapshots

The snapshot freezer and the secret keeper negotiate every field: names and scores persist because the archive needs them, while identity numbers and contact details pass through the masker first. A reviewer dumping the archived JSON during an audit sees placeholders where the sensitive values would be, and the scan that checks for plaintext PII stays green.

#### NFR-RPT-003 — UUID Identity

Sequential ids would let anyone enumerate the cohort by incrementing a URL — card 41, card 42, card 43 — harvesting grades one guess at a time. Opaque UUIDs make each card address unguessable, and uniform foreign key types keep every join between reports, registrations, and users free of the mixed-type mismatches that once plagued the early schema.

#### NFR-RPT-004 — Base-Class Conformance

Consistency across the module is structural rather than aspirational: Actions inherit transaction and logging behavior, the model whitelists its fillable fields, the status enum carries its own transition map. A reviewer never has to ask whether this Action logs — the base class answers — and the contract scan proves the inheritance instead of trusting memory.

### 5.2 Correctness & Clarity

#### NFR-RPT-005 — Idempotent Recalculation

Coordinators recalculate compulsively — after every late supervisor submission, after every weight tweak, sometimes twice in a row just to be sure. Idempotency means the second run over unchanged sources reproduces the first to the decimal, so nobody ever wonders whether pressing the button again changed something. The guarantee turns recalculation from a risk into a habit.

#### NFR-RPT-006 — Atomic Sign-Off

A sign-off interrupted halfway — status flipped but snapshot missing, or snapshot saved but event never fired — would leave a card that claims finality it cannot prove. The transition wraps all three steps in one unit, so an interrupted sign-off leaves a DRAFT that can be retried rather than a FINALIZED card with holes. Partial finality does not exist.

#### NFR-RPT-007 — Unmistakable Status Indicators

The most expensive grade incident on record was a coordinator presenting draft numbers at a parent meeting as if they were final. Every view now carries a status marker impossible to skim past, in the operator's own language. The marker costs one line of template and has prevented exactly the confusion it was built for ever since.

---

## 6. API / Data Contracts

### Report Model

```php
// app/Modules/Reports/Models/Report.php
#[Fillable([
    'registration_id',
    'supervisor_score',
    'teacher_score',
    'exam_score',
    'final_score',
    'grade_letter',
    'industry_feedback',
    'status',
    'finalized_by',
    'finalized_at',
    'archived_data',
])]
class Report extends BaseModel
{
    protected $casts = [
        'status' => ReportStatus::class,
        'supervisor_score' => 'float',
        'teacher_score' => 'float',
        'exam_score' => 'float',
        'final_score' => 'float',
        'finalized_at' => 'datetime',
        'archived_data' => 'array',
    ];

    public function registration(): BelongsTo { /* → Registration */ }
    public function finalizedBy(): BelongsTo { /* → User */ }
    public function captureSnapshot(): void { /* freezes identity into archived_data */ }
}
```

### ReportStatus Enum

```php
// app/Modules/Reports/Enums/ReportStatus.php
enum ReportStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';

    public function label(): string { /* bilingual status label */ }
    public function isTerminal(): bool { return $this === self::FINALIZED; }
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::FINALIZED],
            self::FINALIZED => [],
        };
    }
}
```

### Data Shapes

```php
// app/Modules/Reports/Data/CreateReportData.php
final readonly class CreateReportData extends BaseData
{
    public function __construct(public string $registrationId) {}
}
```

### Action Signatures

```php
// app/Modules/Reports/Actions/CreateReportAction.php
class CreateReportAction extends BaseCommandAction
{
    public function execute(CreateReportData $data): ActionResponse { /* ... */ }
}

// app/Modules/Reports/Actions/ReadReportAggregateAction.php
class ReadReportAggregateAction extends BaseReadAction
{
    // lock-free: no transaction(), no log()
    public function execute(string $registrationId): ReportAggregateData { /* ... */ }
}

// app/Modules/Reports/Actions/CalculateFinalGradeAction.php
class CalculateFinalGradeAction extends BaseCommandAction
{
    public function execute(Report $report): ActionResponse { /* ... */ }
}

// app/Modules/Reports/Actions/FinalizeReportAction.php
class FinalizeReportAction extends BaseCommandAction
{
    public function execute(Report $report, string $finalizedBy): ActionResponse { /* ... */ }
}
```

### Events & Observer

```php
// app/Modules/Reports/Events/GradeCalculated.php
class GradeCalculated extends BaseEvent
{
    public string $eventName = 'report.grade_calculated';
    public function __construct(public Report $report) {}
}

// app/Modules/Reports/Events/ReportFinalized.php
class ReportFinalized extends BaseEvent
{
    public string $eventName = 'report.finalized';
    public function __construct(public Report $report) {}
}

// app/Modules/Reports/Observers/ReportObserver.php
class ReportObserver
{
    public function saved(Report $report): void
    {
        if ($report->status === ReportStatus::FINALIZED && empty($report->archived_data)) {
            $report->captureSnapshot();
            $report->saveQuietly();
        }
    }
}
```

### Routes

```php
Route::get('/admin/reports/{report}/download', [ReportController::class, 'download'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.download');
```

### Database Schema — `reports`

| Column | Type | Nullable | Default | Index | FK | Notes |
| ------ | ---- | -------- | ------- | ----- | -- | ----- |
| id | uuid v7 | no | — | PK | — | UUID primary key |
| registration_id | uuid | no | — | unique | → registrations, nullOnDelete | 1:1 card link |
| supervisor_score | float | yes | — | — | — | Industry component |
| teacher_score | float | yes | — | — | — | Pedagogical component |
| exam_score | float | yes | — | — | — | Exam component |
| final_score | float | yes | — | — | — | Weighted composite 0–100 |
| grade_letter | string | yes | — | — | — | A/B/C/D/E |
| industry_feedback | text | yes | — | — | — | Qualitative note |
| status | string | no | draft | indexed | — | DRAFT or FINALIZED |
| finalized_by | uuid | yes | — | — | → users | Signing coordinator |
| finalized_at | timestamp | yes | — | — | — | Sign-off moment |
| archived_data | json | yes | — | — | — | Frozen identity snapshot |
| created_at | timestamp | no | — | — | — | — |
| updated_at | timestamp | no | — | — | — | — |

### Grading Weights

```php
// Read live from the registration's program; fallback when unconfigured.
$defaults = ['supervisor' => 40, 'teacher' => 20, 'assignment' => 20, 'exam' => 20];
// Source: $report->registration->internship->grading_weights
```

---

## 7. Design Decisions

Choices that shaped the module and the reasoning that keeps them in place.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-RPT-001 | Snapshot capture lives in the model observer, not inside the sign-off Action | P1 | — | — |
| DD-RPT-002 | Grade letter bands stay fixed in code under the national scale | P1 | — | — |
| DD-RPT-003 | PDF rendering belongs to the Document module; Reports holds only grade data | P1 | — | — |
| DD-RPT-004 | Aggregation reads travel through lock-free Read Actions, never direct queries | P0 | — | — |

### 7.1 Structure & Rendering

#### DD-RPT-001 — Observer-Owned Snapshots

Early drafts froze the snapshot inline at the end of the sign-off Action, which read cleanly until a second entry point appeared — a queued backfill job that finalized cards without ever touching that Action. Moving capture into the observer closed the gap for every present and future path at the cost of one indirection: a reader of the Action alone no longer sees the snapshot happen. Colocation in the same module and a plainly named capture method keep the indirection honest.

#### DD-RPT-002 — Fixed Letter Bands

Configurable bands were prototyped once, complete with a settings screen and per-program overrides, and died quietly when nobody used them — every school follows the national cutoffs, and the configuration UI only created a new way to misconfigure grades. Fixed comparisons trade hypothetical flexibility for something more valuable here: any teacher can verify any letter with mental arithmetic.

#### DD-RPT-003 — Rendering Owned Elsewhere

Storing rendered bytes on the grade row would have duplicated the storage, caching, and media-library integration the Document module already maintains. The thin cross-module reference keeps each module's maintenance surface coherent — rendering upgrades ship without touching grade logic, and grade fixes ship without touching templates. The dependency is explicit in the module graph rather than hidden inside a blob column.

#### DD-RPT-004 — Reads Own Aggregation

The original aggregation queried three modules' tables directly, which worked right up until Assessment renamed a score column and every grade run broke at midnight before report week. Routing components through each owner's Read Action moved the blast radius to exactly one place per schema change. Reads stay transaction-free by contract, so the change also removed the lock contention that used to stall attendance writes during cohort-wide recalculation.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Calculation accuracy | 100% match against spreadsheet recomputation on sample cards | manual spot audit per period |
| Sign-off atomicity | 0 partially finalized cards | query for FINALIZED rows missing snapshot or stamp |
| Snapshot completeness | every finalized card carries student, program, company, mentor identity | archived_data presence check |
| Gating soundness | 0 cross-mentorship rows visible in teacher sessions | scripted unauthorized-access probe |
| PDF availability | every finalized card serves its canonical PDF | download probe per finalized card |
| Immutability | 0 post-lock mutations accepted | rejected-write attempt suite |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [Certification](J0M04-certification.md) | Certificate data and the downstream consumer of finalized grades |

### Build Guide

Build the card lifecycle first (create, calculate), then gating, then sign-off with snapshot, then the PDF handover. Assessment and submission sources must be stable before aggregation is wired; the completion-letter listener in Official Documents attaches last, once the finalization event shape is frozen.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [Official Documents](7H5D6-official-documents.md) | Listens for ReportFinalized to auto-generate the completion letter |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume program weight tables change infrequently enough that reading them live per calculation needs no caching layer | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Reporting phase and build order
- [Project initialization](QLHDO-project-initialization.md) — global contracts (authorization, validation, bilingual strings, masked logging)
- [Architecture](D2FT3-architecture.md) — Action Triad, Read lock-free rule, Entity bridges
- [Certification](J0M04-certification.md) — downstream consumer of finalized grades
- [Official Documents](7H5D6-official-documents.md) — canonical PDF pipeline and completion letter
- [Assessment](../refs/modules/assessment.md) — score source modules
