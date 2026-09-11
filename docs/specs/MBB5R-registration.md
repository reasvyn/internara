# MBB5R — Registration

> **Spec ID:** MBB5R
> **Status:** Full
> **Owner:** Enrollment
> **Depends on:** [7C5WM](7C5WM-internship-lifecycle.md), [IT0OE](IT0OE-internship-groups.md)

## Description

Defines student intake in the Enrollment module: availability gating, a guided two-step
registration wizard with placement selection or company proposal, duplicate prevention,
admin verification with placement assignment and quota movement, per-registration document
submission and verification, and the registration status lifecycle. Placement capacity
lives in [J9GBH](J9GBH-placement.md); guest account applications live in
[920SO](920SO-account-application.md).

---

## 1. Problem Statements

### PS-1 — Registration Needs a Guided, Ordered Flow

Unstructured registration produces incomplete records: students skip steps, pick programs
they cannot join, propose placements that go nowhere, and register twice for the same
program. The flow must order the decisions — internship first, then placement — refuse
duplicates for the same student and program pair, and refuse ineligible programs before
any row is written.
**→ Requirement:** FR-REG-001–FR-REG-014 (availability, creation, duplicate guards).

### PS-2 — Required Documents Need Tracking, Not Folklore

Programs demand specific documents, but without a submission and verification workflow
students guess what is needed and admins track compliance in chat threads and
spreadsheets. Each required document needs a visible state — pending, verified, or
rejected — per registration, with upload and verification permissions cleanly separated.
**→ Requirement:** FR-REG-020–FR-REG-026 (document lifecycle).

### PS-3 — Registration Status Carries Known Debt

The registration status still travels as raw strings instead of a backed enum, which
means no compiler help, no transition map, and comparison logic scattered where the
`@todo` markers admit it. The debt is recorded openly with its migration path rather
than hidden, so no new code imitates the pattern and the enum conversion stays visible.
**→ Requirement:** FR-REG-012 (status representation), DD-REG-001 (debt record).

---

## 2. Goals & Non-Goals

### Goals

- **Guided two-step wizard** — select internship, then select or propose placement. *Why:* ordering prevents incomplete registrations and ineligible choices.
- **Per-registration document compliance** — upload plus admin verification per required document. *Why:* compliance visibility replaces spreadsheet folklore.
- **Duplicate prevention** — one active or pending registration per student and program pair. *Why:* duplicates corrupt placement assignment and quota counts.
- **Admin verification with placement assignment** — pending queue, slot-checked assignment, atomic activation. *Why:* activation moves quotas and unlocks downstream modules, so it must be guarded and atomic.
- **Availability gated on configured periods** — semantic open, upcoming, closed, and unconfigured states. *Why:* students deserve accurate messaging instead of silent refusal.

### Non-Goals

- **Placement capacity management**. *Why:* owned by [J9GBH](J9GBH-placement.md); registration only checks what placement reports.
- **Guest-to-student account application**. *Why:* owned by [920SO](920SO-account-application.md).
- **Multi-tenant placement handling**. *Why:* single-tenant by product definition.
- **Automated placement matching**. *Why:* selection is manual and mentor-informed; algorithms are post-MVP depth.
- **Bulk registration import from government systems**. *Why:* single-format CSV covers the MVP handoff; import pipelines are post-MVP depth.

---

## 3. User Stories / Use Cases

Three journeys spanning both sides of the desk: the student enrolling, the admin
activating, and the student proving eligibility through documents.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-REG-001 | Student completes the two-step wizard and lands a pending registration with proposed or selected placement | P0 | F | Full |
| UC-REG-002 | Admin verifies a pending registration, assigns a slot-checked placement and mentors, and activates it atomically | P0 | F | Full |
| UC-REG-003 | Student uploads required documents and tracks verification state per document | P0 | F | Full |

### 3.1 Enrollment

#### UC-REG-001 — Student Registers for a Program

Registration week: the student opens the registration center, sees which internships are
actually open, and steps through the wizard — program first, then either an existing
placement with free slots or a proposed company with a name and address. Submitting
creates a pending registration and fires the enrollment event that refreshes the
dashboard. A second attempt at the same program is refused outright; the system would
rather explain the duplicate than untangle two competing rows later.

#### UC-REG-003 — Student Proves Eligibility on Paper

After registering, the student faces the document list — each required item showing
whether it is uploaded, verified, or rejected. Uploads land as pending, and rejection
sends the student back with a reason rather than a shrug. The per-document states turn
"am I done with paperwork" from a phone call to the office into a glance at a screen.

### 3.2 Verification

#### UC-REG-002 — Admin Verifies and Places the Student

The admin works the pending queue with placement availability in view: pick a student,
pick a slot that actually has room, optionally attach mentors, and confirm. One atomic
step assigns the placement, stamps the dates, flips the status to active, and moves the
quota — indivisibly, so activation can never half-happen. From that moment the student
unlocks journals, assignments, and assessments downstream.

---

## 4. Functional Requirements

The table below is the complete normative list. Groups in §4.1–§4.6 collect the detail
narratives; the table itself is the single source of requirement rows.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-REG-001 | `ReadRegistrationAvailabilityAction` returns not_configured, open, upcoming, or closed from the configured registration period settings | P0 | F | Full |
| FR-REG-002 | Registration is open only while the current date falls inside the configured period | P0 | F | Full |
| FR-REG-003 | `RegistrationState` answers isActive, isPending, isCurrentlyOngoing, hasEnded, and canBeApproved | P0 | U | Full |
| FR-REG-004 | `canBeApproved()` requires pending status together with an assigned placement | P0 | U | Full |
| FR-REG-005 | `RegistrationState` computes daysRemaining and totalDuration date arithmetic | P1 | U | Full |
| FR-REG-006 | `RegistrationState` supports phased progress with currentPhaseIndex and currentPhase from elapsed share of duration | P2 | U | Full |
| FR-REG-007 | `RegisterInternshipAction` refuses duplicate active or pending registrations for the same student and internship | P0 | F | Full |
| FR-REG-008 | The registrations table constrains student and internship pairs to unique at the database level | P0 | F | Full |
| FR-REG-009 | Registration uses a UUID primary key with cascading deletes from student and internship references | P0 | A | Full |
| FR-REG-010 | The placement reference stays nullable with set-null on delete | P0 | A | Full |
| FR-REG-011 | Student-proposed company details persist as a JSON column | P1 | F | Full |
| FR-REG-012 | Registration status persists as pending and active strings with the enum migration recorded as debt | P0 | U | Full |
| FR-REG-013 | Successful creation dispatches the `StudentRegistered` event | P0 | F | Full |
| FR-REG-014 | The registration listener clears the dashboard cache | P1 | F | Full |
| FR-REG-015 | `VerifyRegistrationAction` requires the registration to be pending | P0 | F | Full |
| FR-REG-016 | `VerifyRegistrationAction` requires available slots on the target placement | P0 | F | Full |
| FR-REG-017 | Verification atomically assigns placement, stamps dates, activates status, and increments filled quota | P0 | F | Full |
| FR-REG-018 | `RegistrationPolicy` lets students create and update their own pending registrations | P0 | U | Full |
| FR-REG-019 | `RegistrationPolicy` reserves verification and approval to admins | P0 | U | Full |
| FR-REG-020 | `RegistrationDocument` tracks submissions linked to a registration | P0 | A | Full |
| FR-REG-021 | Document status uses the `RegistrationDocumentStatus` enum with PENDING, VERIFIED, REJECTED | P0 | U | Full |
| FR-REG-022 | `RegistrationDocumentStatus` implements the `LabelEnum` and `StatusEnum` contracts | P0 | A | Full |
| FR-REG-023 | `UploadRegistrationDocumentAction` stores one file per required document type | P0 | F | Full |
| FR-REG-024 | The document upload component shows required document identifiers with current upload state | P1 | F | Full |
| FR-REG-025 | `RegistrationDocumentPolicy` separates upload permission from verification permission | P0 | U | Full |
| FR-REG-026 | Document transitions run PENDING to VERIFIED or REJECTED with both ends terminal | P0 | U | Full |
| FR-REG-027 | `RegistrationCenter` lists open internships at `/registration` behind authentication | P0 | F | Full |
| FR-REG-028 | `RegistrationWizard` runs the two-step flow at `/register` behind authentication | P0 | F | Full |
| FR-REG-029 | `RegistrationVerification` shows the pending queue at the admin registrations path behind admin roles | P0 | F | Full |
| FR-REG-030 | `RegistrationDocumentUpload` serves document intake at `/registration/documents` behind authentication | P0 | F | Full |
| FR-REG-031 | `RegistrationData` extends `BaseData` with a required internshipId | P0 | U | Full |
| FR-REG-032 | `RegistrationData` accepts nullable placement, academic year, dates, and proposed company fields | P1 | U | Full |
| FR-REG-033 | `RegistrationWizardForm` validates through a Laravel Form Object | P0 | F | Full |

### 4.1 Availability and Lifecycle

#### FR-REG-001 — Availability speaks in four states

Unconfigured, open, upcoming, closed: the availability Action never answers with a bare
boolean because students act differently on each state. An unconfigured period is an
admin task, an upcoming one is patience, a closed one is finality. Collapsing those
into true or false would trade three useful messages for one useless shrug.

#### FR-REG-002 — Open means inside the period

The open verdict compares today against the configured start and end, nothing more.
No role, no program, no special cases widen it — eligibility questions belong to the
program window checks, not to the global period. One narrow definition keeps the
global gate predictable: inside the dates, open; outside, something else that names
itself.

#### FR-REG-003 — State predicates on demand

Active, pending, ongoing, ended, approvable: the entity answers each question directly
so callers stop comparing status strings inline. Every inline comparison ever written
was a future inconsistency, because the next author phrases it slightly differently.
Named predicates are where the scattered `@todo` era ends and the centralized era
begins.

#### FR-REG-004 — Approvable needs both halves

Pending without a placement is a wish; a placement without pending status is history.
Approval requires both, which stops the two classic errors — activating a student with
nowhere to go, and re-approving an already active row. The conjunction looks trivial
written down and prevents exactly the incidents that are embarrassing to explain.

#### FR-REG-005 — Countdown arithmetic in one place

Days remaining and total duration sound like view helpers until three screens compute
them three ways and disagree by one. Centralizing the date math fixes the off-by-one
class of bug at its source, timezone handling included. Views display; the entity
counts.

#### FR-REG-006 — Phased progress along the timeline

Registrations carry phase markers so long programs can show "week six of twelve"
rather than a bare date range. The current phase derives from elapsed share of total
duration — simple proportional mapping, no scheduling engine behind it. Modest, but it
turns an abstract span into a story the student can follow.

### 4.2 Creation and Constraints

#### FR-REG-007 — The duplicate guard

Same student, same internship, already pending or active: refused, with a message.
Double registration during enrollment-week rushes — double-clicks, retries on slow
connections — is not malice but it corrupts quota math identically. The application
guard catches it with an explanation; the database constraint behind it catches
whatever races past the guard.

#### FR-REG-008 — Uniqueness the database believes in

The unique pair constraint is the backstop that holds under concurrency, when two
requests pass the application guard in the same millisecond. Application checks
explain; constraints guarantee. Enrollment week is exactly when "same millisecond"
stops being hypothetical, so both layers stay.

#### FR-REG-009 — UUID keys and cascading identity

Registrations carry UUID primary keys like every other primary entity, with student
and internship links cascading on delete. Removing a student removes their
registrations rather than stranding rows that point at nobody. Key-type consistency
keeps every join in the system speaking the same identifier language.

#### FR-REG-010 — Placement link that lets go gently

The placement reference is nullable and set-null on delete because registrations
outlive placement reshuffles. Deleting a placement must not vaporize the student's
enrollment history; it clears the assignment and leaves the row for reassignment.
Null here means "awaiting placement," a legitimate state, not missing data.

#### FR-REG-011 — Proposed companies as JSON

When a student proposes a company, the name and address land in a JSON column —
structured enough to display in the verification queue, schemaless enough to avoid a
table for data that may never become a real company record. If the admin accepts the
proposal, a proper placement is created from it; until then the JSON is the whole
proposal.

#### FR-REG-012 — Strings today, enum tomorrow

Pending and active persist as raw strings, with the backed-enum migration recorded as
explicit debt in DD-REG-001. The strings work; they just forgo compiler help and a
transition map. New code must not imitate this shape — the state entity centralizes
comparisons so the eventual migration touches one neighborhood instead of the whole
codebase.

#### FR-REG-013 — Creation announces itself

Every successful registration dispatches `StudentRegistered`, decoupling creation from
everything that reacts to it. The Action's job ends at a committed row; listeners own
the aftermath. Future reactions — welcome messages, coordinator pings — attach to the
event without touching the creation path.

#### FR-REG-014 — Dashboards refresh on enrollment

The registration listener clears the dashboard cache so counts and lists reflect the
new row immediately. Stale dashboard numbers after a successful registration read as
system failure to an anxious student checking their status. Invalidation at creation
time is the cheapest reassurance available.

### 4.3 Verification and Activation

#### FR-REG-015 — Only pending rows activate

Verification demands pending status first, which bars re-verifying active rows and
resurrecting rejected ones through the same door. Status preconditions are the quiet
workhorses of workflow integrity — each one closes a "but what if it was already…"
story before it starts. The check is one predicate; the incidents it prevents are
countless.

#### FR-REG-016 — Slots must exist before assignment

The target placement reports available slots through its own capacity check, and
verification trusts that verdict absolutely. Assigning into a full placement would
overflow the quota the partners module guards, pitting two modules' arithmetic
against each other. Registration asks; placement answers; nobody overrides.

#### FR-REG-017 — Activation as one indivisible step

Placement assignment, date stamping, status flip, and quota increment commit
together or not at all. A half-activated student — status flipped but quota unmoved,
or quota moved but dates unset — breaks every downstream assumption simultaneously.
Atomicity here is not elegance; it is the difference between enrollment and a
reconciliation project.

#### FR-REG-018 — Students own their pending rows

Students may create registrations and edit their own while pending — fixing a typo in
a proposed company name should not require an admin ticket. Ownership plus pending
status bounds the power precisely: your rows, while they are still yours to change.
Activation ends the editing era, as it should.

#### FR-REG-019 — Verification belongs to admins

Approving a registration moves quotas and unlocks downstream modules, so the
authority sits with admins alone. Students cannot self-activate no matter how they
phrase the request, and mentors cannot approve outside their mandate. The policy
draws the line; the Action re-draws it for direct callers.

### 4.4 Documents

#### FR-REG-020 — Submissions as linked rows

Each document submission is its own row pointing at its registration, so a student
with five required documents has five trackable states instead of one fuzzy
"paperwork" flag. Row-per-document is what makes per-document verification,
rejection, and re-upload expressible at all. Aggregation for display happens above;
storage stays granular.

#### FR-REG-021 — Three document states

Pending, verified, rejected: the complete vocabulary of a document's life. Three
states fit the real workflow — submitted, accepted, sent back — with no limbo
between them. Reviewers learn the trio once and read every document queue fluently
thereafter.

#### FR-REG-022 — Documents follow the enum contracts

Unlike its parent registration, the document status implements the label and status
contracts from the start — display wording and transition rules included. The
contrast is instructive: this is what the registration status will look like after
its own migration. New enums imitate this one, never the legacy strings next door.

#### FR-REG-023 — One file per required type

The upload Action stores a single file against a required document identifier,
replacing any earlier attempt for the same slot. Single-file-per-type keeps the
verification queue unambiguous — reviewers always see the latest submission, never a
pile of versions to guess between. Re-upload after rejection is the same path as
first upload, which keeps the code honest.

#### FR-REG-024 — Requirements rendered with state

The upload screen pairs every required document identifier with its current state,
so "what do they want from me and what have I done" answers itself at a glance.
A bare file input without the requirements list is how documents go missing for
weeks. Visibility is the feature; the input is just plumbing.

#### FR-REG-025 — Uploaders upload, verifiers verify

Students may submit; only admins may verify or reject. Merging those permissions
would let submitters approve their own paperwork — the exact failure the workflow
exists to prevent. The policy split is small, obvious, and load-bearing.

#### FR-REG-026 — Terminal ends, no afterlife

Verified and rejected both end the document's journey; neither transitions onward
within this lifecycle. Rejected documents restart through a fresh submission rather
than an un-rejection, preserving the history of what was refused and why. Terminal
states keep the audit story clean: every document row tells a finished story.

### 4.5 Components and Routing

#### FR-REG-027 — Registration center behind login

The open-internship listing lives at its dedicated path for authenticated users.
Gating behind login is not secrecy — program names are hardly classified — but
identity: the center personalizes around the student's existing registrations and
eligibility. Anonymous browsing would show a generic list that helps nobody decide.

#### FR-REG-028 — Wizard at its own address

The two-step flow owns a dedicated route, also authenticated, separate from the
browsing center. Separating browsing from doing lets students explore without
accumulating half-finished wizard state, and lets the wizard assume intent from the
first step. URLs mirror the mental model: look here, enroll there.

#### FR-REG-029 — Pending queue in admin territory

The verification queue sits under the admin prefix with admin role middleware,
showing pending registrations beside placement availability. Colocating the queue
with the slot data is what makes verification a decision instead of a treasure
hunt. Students never reach this URL; the middleware guarantees it before any data
loads.

#### FR-REG-030 — Documents at their own counter

Document intake gets its own authenticated route, distinct from both browsing and
the wizard. Paperwork continues after enrollment — rejections, renewals, late
additions — so it needs a permanent address, not a wizard step that disappears
after submit. Students return here throughout the cycle.

### 4.6 Data Shape and Validation

#### FR-REG-031 — DTO anchored on the internship

The data object requires exactly one thing: which internship. Everything else is
context around that choice. A required internship identifier makes "registration to
nothing" unrepresentable, which is the cheapest validation rule ever written — the
type system enforces it before any logic runs.

#### FR-REG-032 — Optional context around the anchor

Placement, academic year, dates, and proposed company details all ride along as
nullable fields. Optionality reflects the wizard's reality: step one knows the
program, step two may add a placement or a proposal, dates may be assigned later by
verification. The DTO tolerates partial knowledge gracefully.

#### FR-REG-033 — Form Object validation

Wizard input validates through a Laravel Form Object before the Action sees it,
keeping HTTP concerns — field names, conditional rules, file handling — out of the
business layer. The Action receives clean data or nothing at all. Two validation
homes sounds redundant until the first redesign of the form leaves the business
rules untouched.

---

## 5. Non-Functional Requirements

Constraints on how registration behaves. `N/A` marks requirements enforced structurally
and verified via scans or tests rather than measured at runtime.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-REG-001 | Registration creation wrapped in a database transaction with SmartLogger activity entry and PII masking | N/A | P0 | F | Full |
| NFR-REG-002 | Wizard shows the student's current step out of two with labeled inputs and announced transitions | N/A | P1 | B | Full |
| NFR-REG-003 | Registration status migrates to a backed enum implementing label and status contracts | N/A | P1 | A | Full |
| NFR-REG-004 | Verification runs inside a transaction so placement, dates, status, and quota commit together | N/A | P0 | F | Full |
| NFR-REG-005 | Business-rule refusals throw `RejectedException` with translatable messages; unexpected failures log with context and show a generic message | N/A | P0 | A | Full |
| NFR-REG-006 | Enrollment UI stays keyboard-navigable with associated labels on every input | N/A | P1 | B | Full |
| NFR-REG-007 | All user-facing strings pass through the `__()` helper | N/A | P0 | A | Full |
| NFR-REG-008 | Translation keys exist in both English and Indonesian files with status wording from `LabelEnum::label()` | N/A | P0 | A | Full |

### 5.1 Reliability

#### NFR-REG-001 — Creation lands whole and logged

The creation transaction wraps the row, its side effects, and its audit entry, with
personal data masked before anything reaches the log. Enrollment writes carry names,
schools, and company details — exactly the fields that must never leak into log
storage. Atomicity plus masked logging means the operation is both indivisible and
discreet.

#### NFR-REG-004 — Verification commits as a unit

Placement assignment, date stamps, activation, and quota movement share one
transaction because they describe one fact: this student is placed. Splitting them
across commits would let each succeed or fail independently, producing the partial
states this spec refuses to name. One fact, one commit, no reconciliation.

#### NFR-REG-005 — Two failure voices, never mixed

Refusals — duplicates, closed periods, full placements — speak through
`RejectedException` with messages written for the affected human. Crashes speak
through the log with context and show the user only a generic failure. Mixing the
two teaches users to ignore real guidance or, worse, shows them internals; the
hierarchy exists so each failure finds its proper audience.

### 5.2 Operability and Presentation

#### NFR-REG-002 — Orientation inside the wizard

Students always see which step they stand on — one of two, two of two — with every
input labeled and step changes announced to assistive technology. A wizard that
hides its own progress breeds abandonment; a transition that moves silently strands
screen-reader users mid-flow. Orientation is a small courtesy with an outsized
effect on completion.

#### NFR-REG-006 — Keyboards first, labels always

Every enrollment input carries its label and a full keyboard path, from the wizard
through the verification queue. School-office hardware and student phones alike
punish mouse-only design. This row consolidates the former spread of overlapping
accessibility rows into one operability standard without the audit-grade numeric
targets that belonged to a formal certification effort, not MVP.

#### NFR-REG-007 — No hardcoded strings

Every human-readable string in enrollment UI passes through the translation helper,
including the admin queue's terse column headers that always feel "temporary."
Temporary strings have the longest half-life in any codebase; the helper plus the
scan keeps them bilingual from birth.

#### NFR-REG-008 — Mirrored locales, enum wording

Keys ship in both English and Indonesian, and status wording flows from the enum's
label method so badges, queues, and exports agree. A missing Indonesian key breaks
the primary staff UI first — the exact screen the school trusts — which is why the
mirror rule is a gate rather than a guideline.

### 5.3 Planned Hardening

#### NFR-REG-003 — The enum migration, kept visible

The backed-enum conversion for registration status stays an explicit planned row
until it lands, tracked rather than wished for. Its acceptance is concrete: label
and status contracts implemented, comparisons centralized, transition guards in
place. Keeping it in the NFR table instead of a comment means the traceability
scanner watches it too — debt with a spotlight behaves better than debt in the
dark.

---

## 6. API / Data Contracts

### 6.1 Registration Model

```php
// app/Modules/Enrollment/Registration/Models/Registration.php
// Table: registrations
// PK: id (uuid, cascade)
// FK: student_id → users (cascade delete)
// FK: internship_id → internships (cascade delete)
// FK: placement_id → placements (nullable, set null on delete)
// Fillable: student_id, internship_id, placement_id, start_date, end_date, status, proposed_company_details (json)
// Unique: (student_id, internship_id)
// Status: raw strings 'pending' | 'active' (enum migration: DD-REG-001)
```

### 6.2 RegistrationState Entity

```php
// app/Modules/Enrollment/Registration/Entities/RegistrationState.php
final readonly class RegistrationState extends BaseEntity
{
    public static function fromModel(Model $model): static;

    public function isActive(): bool;       // status === 'active'
    public function isPending(): bool;      // status === 'pending'
    public function isCurrentlyOngoing(?Carbon $today = null): bool;
    public function hasEnded(?Carbon $today = null): bool;
    public function canBeApproved(): bool;  // isPending() && hasPlacement
    public function daysRemaining(?Carbon $today = null): int;
    public function totalDuration(): int;
    public function withPhases(array $phases): static;
    public function phases(): array;
    public function currentPhaseIndex(?Carbon $now = null): ?int;
    public function currentPhase(?Carbon $now = null): ?string;
}
```

### 6.3 RegistrationData DTO

```php
// app/Modules/Enrollment/Registration/Data/RegistrationData.php
final readonly class RegistrationData extends BaseData
{
    public function __construct(
        public string $internshipId,              // required
        public ?string $placementId = null,
        public ?string $academicYear = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $proposedCompanyName = null,
        public ?string $proposedCompanyAddress = null,
    ) {}
}
```

### 6.4 Registration Actions

```php
// app/Modules/Enrollment/Registration/Actions/ReadRegistrationAvailabilityAction.php
final class ReadRegistrationAvailabilityAction extends BaseReadAction
{
    public function execute(): array;
    // Returns: ['status' => 'not_configured'|'open'|'upcoming'|'closed']
}

// app/Modules/Enrollment/Registration/Actions/RegisterInternshipAction.php
final class RegisterInternshipAction extends BaseCommandAction
{
    public function execute(RegistrationData $data, User $student): Registration;
}

// app/Modules/Enrollment/Registration/Actions/VerifyRegistrationAction.php
final class VerifyRegistrationAction extends BaseCommandAction
{
    public function execute(Registration $registration, Placement $placement, array $mentors = []): Registration;
}

// app/Modules/Enrollment/Registration/Actions/UploadRegistrationDocumentAction.php
final class UploadRegistrationDocumentAction extends BaseCommandAction
{
    public function execute(Registration $registration, string $requiredDocumentId, UploadedFile $file): RegistrationDocument;
}
```

### 6.5 RegistrationDocumentStatus Enum

```php
// app/Modules/Enrollment/Registration/Enums/RegistrationDocumentStatus.php
enum RegistrationDocumentStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    // Transitions: PENDING → [VERIFIED, REJECTED]
    // Terminal: VERIFIED, REJECTED
}
```

### 6.6 Events

```php
// app/Modules/Enrollment/Registration/Events/StudentRegistered.php
// Dispatched by: RegisterInternshipAction
// Listener: ClearDashboardOnRegistration (clears dashboard cache)
```

### 6.7 Routes

```php
// routes/web/enrollment.php (registration portion)

// Authenticated
Route::middleware('auth')->group(function () {
    Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
    Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
    Route::livewire('/registration/documents', RegistrationDocumentUpload::class)->name('registration.documents');
});

// Admin
Route::prefix('admin')->name('enrollment.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
});
```

### 6.8 Database Migrations

| Migration | Table |
| --------- | ----- |
| `2026_01_04_000003_create_registrations_table.php` | `registrations` |
| `2026_01_05_000002_create_registration_documents_table.php` | `registration_documents` |

---

## 7. Design Decisions

Three recorded decisions, narrated inline.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-REG-001 | Registration status persists as raw strings with the backed-enum migration tracked as explicit debt | P0 | — | — |
| DD-REG-002 | Registration window governed by period settings with semantic availability states | P1 | — | — |
| DD-REG-003 | Document submissions modeled as a dedicated model with its own status enum | P0 | — | — |

### 7.1 Representation

#### DD-REG-001 — Strings With a Spotlight

The registration model predates the status-enum contract pattern, and retrofitting the
enum means converting every comparison, guard, and component reference in one
coordinated pass — too wide to sneak into a feature commit. So the strings stay for
now, centralized behind the state entity, with the migration tracked as a first-class
planned row instead of a fading code comment. Daylight is the mitigation: nobody
extends a pattern they can see is marked for replacement.

#### DD-REG-002 — Windows From Settings, States With Names

Period bounds live in system settings because admins adjust enrollment calendars
without deploying code, and the availability Action translates raw dates into the
four named states the UI speaks. Settings-driven windows trade a small
misconfiguration risk — an admin forgetting to set the dates — for full operational
control, and the unconfigured state turns that exact mistake into a clear message
instead of a mystery.

### 7.2 Documents

#### DD-REG-003 — Documents as Their Own Model

A registration collects many documents, each with an independent verification fate,
so submissions live in a dedicated model in a one-to-many relationship. Embedding
them in the registration row would tangle independent lifecycles into one nested
blob that no query could address cleanly. The extra model pays for itself the first
time anyone asks "which students still lack a verified agreement letter."

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Duplicate prevention | Zero duplicate active or pending pairs | Constraint plus guard tests |
| Availability accuracy | Correct state for every period configuration | Availability tests against settings |
| Wizard completion | Students finish the two-step flow without support | Livewire journey tests |
| Quota integrity | Quota movement matches activations exactly | Verification tests with seeded slots |
| Document traceability | Every submission carries an independent verifiable state | Document lifecycle tests |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [internship-lifecycle.md](7C5WM-internship-lifecycle.md) | Program entities — registration enrolls students into programs |
| [internship-groups.md](IT0OE-internship-groups.md) | Group entities — enrolled students are assigned to groups |

### Build Guide

After this spec, students register into programs, admins verify them into placements,
and document compliance is tracked per registration. Enrollment records carry pending
and active states with quota-safe activation. The next step is placement, which matches
enrolled students to companies through partnerships.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [placement.md](J9GBH-placement.md) | Placement reads enrollment records from this spec and matches with companies from partnership management |
| 2 | [account-application.md](920SO-account-application.md) | New students self-register through that flow before reaching registration |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| A-1 | We assume manual placement selection stays workable until matching automation is requested | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Programs and Enrollment phases, all specs in build order
- [Internship lifecycle](7C5WM-internship-lifecycle.md) — programs and windows that gate registration
- [Internship groups](IT0OE-internship-groups.md) — cohorts that receive enrolled students
- [Placement](J9GBH-placement.md) — slot quotas checked at verification
- [Account application](920SO-account-application.md) — guest intake feeding registration
- [MVP spec trim ADR](../adr/adr-mvp-spec-trim.md) — what was deliberately left out of MVP scope
- [Exception hierarchy ADR](../adr/adr-exception-hierarchy.md) — RejectedException contract for refusals
- [SmartLogger dual-channel ADR](../adr/adr-smartlogger-dual-channel.md) — activity logging with PII masking
