# Official Documents — School-Parent-Student-Industry Correspondence

> **Spec ID:** 7H5D6
> **Status:** Full
> **Owner:** Document
> **Depends on:** MBB5R, PKYX6, R6BMW

## Description

This spec catalogs every official paper the PKL chain requires — school-to-company letters, parent consent, supervisor assignments, visit and incident minutes, completion and handover records — with each type's audience, approval path, variable contract, and generation trigger. Template storage and PDF rendering belong to the Document Templates module; this spec owns what exists, who signs it, and when it is issued.

---

## 1. Problem Statements

### PS-1 — No Shared Catalog of Required Papers

A new teacher joining the program asks which letters a placement needs and receives three different answers from three seniors, each waving a different inherited folder of Word files. Parent consent — the one paper regulators actually demand — is the one most often forgotten, because nothing lists it as required and nobody owns the checklist.
**→ Requirement:** FR-OFFD-001/004 (registry shape, pre-PKL catalog).

### PS-2 — The Inter-Organization Chain Is Invisible

The school sends an introduction, the company replies with acceptance, the school assigns supervisors, parents consent, the company evaluates, the school certifies completion. Tracked across email threads, chat groups, and a physical binder, the chain's state for any given student is unknowable — nobody can say which links exist and which are still missing.
**→ Requirement:** FR-OFFD-015/017 (per-registration checklist, auto-advance with warnings).

### PS-3 — Variables Resolved by Guesswork

Every letter needs names, numbers, and dates pulled from registrations, placements, and school settings. Without a declared contract per type, generation fills what it can and leaves the rest as blank space or, worse, raw placeholder text printed on official letterhead and already delivered to a company.
**→ Requirement:** FR-OFFD-002 (variable contract schema), FR-OFFD-020 (fail closed on missing variables).

### PS-4 — Consent Is Legally Required but Unprovable

Regulations require written parental consent for off-campus placement, and accreditation visitors ask to see it. A drawer of unsorted scans cannot answer "show me consent for every active student," and an incident investigation that needs one student's form fast will not wait while staff flip through folders.
**→ Requirement:** FR-OFFD-004 (consent in the pre-PKL catalog), FR-OFFD-015 (upload tracked per registration), FR-OFFD-024 (verified upload handling).

---

## 2. Goals & Non-Goals

### Goals

- **Catalog every official type** — purpose, audience, approval, and trigger for each paper in the chain. *Why:* new staff and new programs start from a list, not tribal memory.
- **Bind each type to its lifecycle moment** — enrollment, daily operations, or certification. *Why:* letters arrive when the process needs them, not when someone remembers.
- **Declare every variable contract** — name, type, and source for each placeholder. *Why:* generation never guesses and never prints blanks.
- **Fix approval per type** — principal, company, mentor, or automatic. *Why:* a letter without its required signature is just paper.
- **Track issuance per registration** — issued, pending, missing, uploaded at a glance. *Why:* compliance becomes a checklist instead of an investigation.
- **Batch-generate cohort papers** — one action for a whole intake's consent forms or assignment letters. *Why:* four hundred students cannot be clicked one by one.

### Non-Goals

- **Template storage and PDF rendering internals**. *Why:* owned by Document Templates; this spec consumes its renderer.
- **Certificate issuance**. *Why:* owned by Certification, which has its own verification semantics.
- **Grade card rendering**. *Why:* owned by Reports; only the completion letter trigger crosses over.
- **Partnership MoU storage**. *Why:* owned by Partnership Management at the company level, not per registration.
- **Digital or cryptographic signatures**. *Why:* wet signatures remain the legally recognized form for minor consent; the system tracks paper, it does not replace it.
- **Government system integration**. *Why:* CSV handoff only at this scope.
- **Multilingual templates**. *Why:* official correspondence ships in Indonesian; translation doubles template maintenance for no regulatory demand.

---

## 3. User Stories / Use Cases

How each paper travels from request to filed record across the three lifecycle phases.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-OFFD-001 | Admin generates the school-to-company introduction letter for a program intake | P0 | F | Full |
| UC-OFFD-002 | Student uploads the signed parent consent form; admin verifies it | P0 | F | Full |
| UC-OFFD-003 | Admin issues the company acceptance confirmation for a placed student | P0 | F | Full |
| UC-OFFD-004 | Admin batch-generates supervisor assignment letters for an intake | P0 | F | Full |
| UC-OFFD-005 | Admin reads a registration's document checklist and chases the gaps | P0 | F | Full |
| UC-OFFD-006 | System auto-generates the completion letter when the grade card finalizes | P0 | F | Full |

### 3.1 Pre-PKL Papers

#### UC-OFFD-001 — Send the Introduction Letter

Placement season opens with the school introducing itself to partner companies — who we are, which program, which dates, how many students. The admin picks the intake, reviews the resolved school and company details, and issues the letter carrying the principal's signature block and a proper sequential number. The issuance is recorded against the program so that when a company calls to ask "did you send anything," the answer is a timestamped record rather than a memory.

#### UC-OFFD-002 — Collect Parent Consent

Consent starts as a blank form the student downloads, prints, gets signed at home, and photographs back into the system. The upload lands in a pending state that an admin must explicitly verify — a blurry photo of half a page does not count, and the verifier is the one who says so. Until verification, the registration's checklist keeps consent visibly outstanding, which is precisely what makes a coordinator chase it before placement instead of discovering the gap during an incident.

#### UC-OFFD-003 — Confirm Company Acceptance

When a company accepts a student, the school issues the mirror paper: this learner, this identity number, this site, these dates. The confirmation closes the loop the introduction opened and gives the student something concrete to carry on day one. Like every issuance, it freezes the variables it printed, so a later company address change never rewrites what the acceptance actually said.

### 3.2 Operations & Completion

#### UC-OFFD-004 — Assign Supervisors in Batch

An intake with thirty supervisors cannot be lettered one by one without losing a day. The admin triggers batch generation over the intake's assignments, and each supervisor receives a letter naming them, their supervised students, the site, and the dates — queued in the background while the admin gets on with real work. A single failed rendering never aborts the batch; it is reported and retried while the rest proceed.

#### UC-OFFD-005 — Chase the Checklist

Before activating placements, the coordinator opens each registration and reads its document checklist like a departure board: introduction issued, consent verified, acceptance pending. The board is computed from live issuance records, not cached flags, so it never disagrees with reality. Registrations with missing required papers surface a warning at activation time — the last checkpoint before an undocumented placement slips through.

#### UC-OFFD-006 — Auto-Issue the Completion Letter

Nobody should have to remember to write the completion letter for each of four hundred graduates. The grade card's finalization event carries everything the letter needs — name, identity number, site, dates, score, letter — and the listener issues it immediately, idempotently, without an admin lifting a finger. A retried event replays safely instead of minting a duplicate with a second letter number.

---

## 4. Functional Requirements

Document behavior obeys the global contracts (Action Triad, dual-layer authorization, bilingual strings, masked audit logging) and adds the catalog, contracts, workflow, tracking, and numbering below.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-OFFD-001 | System keeps a registry describing every official type with lifecycle phase, audience, approval, and auto-generate flag | P0 | F | Full |
| FR-OFFD-002 | Every type declares its variables as a schema of name, type, source, and optionality | P0 | F | Full |
| FR-OFFD-003 | Types seed as initial data, never through schema migrations | P1 | F | Full |
| FR-OFFD-004 | Pre-PKL catalog covers introduction, application, parent consent, acceptance, supervisor assignment, and registration form with per-type approval | P0 | F | Full |
| FR-OFFD-005 | Pre-PKL variable contracts resolve school, program, company, student, and letter-number fields | P0 | F | Full |
| FR-OFFD-006 | During-PKL catalog covers absence approval, monitoring visit minutes, and incident minutes with per-type approval | P0 | F | Full |
| FR-OFFD-007 | During-PKL variable contracts resolve visit, absence, and incident fields including arrays | P0 | F | Full |
| FR-OFFD-008 | Post-PKL catalog covers completion letter, company evaluation, and report cover with auto-generate flags | P0 | F | Full |
| FR-OFFD-009 | Post-PKL variable contracts resolve score, grade, competency, and certificate fields | P0 | F | Full |
| FR-OFFD-010 | Administrative catalog covers submission receipt, handover record, and program circular | P1 | F | Full |
| FR-OFFD-011 | Administrative variable contracts resolve receipt, handover-item, and circular fields | P1 | F | Full |
| FR-OFFD-012 | Single documents render synchronously while batches of ten or more queue in the background | P1 | F | Full |
| FR-OFFD-013 | Completion and registration papers auto-generate from their domain events, idempotently | P0 | F | Full |
| FR-OFFD-014 | Every issuance records type, registration, actor, template version, frozen variables, and letter number | P0 | F | Full |
| FR-OFFD-015 | Each registration carries a document_status checklist of issued, pending, missing, and uploaded states | P0 | F | Full |
| FR-OFFD-016 | Checklist reads run lock-free through a Read Action without transactions | P1 | A | Full |
| FR-OFFD-017 | Statuses auto-advance on generation and upload, and activation warns on missing required papers | P0 | F | Full |
| FR-OFFD-018 | Letter numbers follow prefix, per-type sequence, and year with an annual reset | P1 | F | Full |
| FR-OFFD-019 | Document reads scope to the viewer's mentorship through MentorEntity; principal-signed types require the principal's sign-off record | P0 | F | Full |
| FR-OFFD-020 | Generation validates all inputs and fails closed with RejectedException on missing variables | P0 | A | Full |
| FR-OFFD-021 | Policies gate generation and verification while Actions re-check on direct calls | P0 | A | Full |
| FR-OFFD-022 | Every user-facing string resolves through __() with Indonesian primary and English secondary | P0 | A | Full |
| FR-OFFD-023 | Every issuance audit-logs through SmartLogger with PII masking | P0 | F | Full |
| FR-OFFD-024 | Consent uploads validate MIME, size, and filename safety and store outside the web root | P0 | F | Full |

### 4.1 Registry Foundations

#### FR-OFFD-001 — The Type Registry

Before this registry, "which documents exist" was answered by whoever had been at the school longest. The registry turns that memory into data: fifteen types, each with its phase, its audience, whether it needs a signature and whose, and whether the system may generate it unprompted. Adding a sixteenth type is a seeded data change reviewed like code, not a whispered addition to someone's folder.

#### FR-OFFD-002 — Variable Schemas

A letter template without a schema is a form with invisible required fields. Each type declares its variables up front — the name the template uses, whether it is a string, date, or number, which record it comes from, and whether it may be absent. The schema is what lets generation distinguish "optional note left blank" from "principal name missing, stop everything" before a single page renders.

#### FR-OFFD-003 — Seeded, Never Migrated

Document types are data about the school's bureaucracy, not structure of the database, so they arrive through seeders that can be re-run, diffed, and versioned alongside the code that consumes them. A migration would freeze them into schema history where no reviewer looks; a seeder keeps them where the team actually reads. Environment-specific additions layer on top without touching the canonical fifteen.

### 4.2 Pre-PKL Catalog

#### FR-OFFD-004 — Six Papers Before Placement

The enrollment paper trail runs introduction, application, consent, acceptance, supervisor assignment, registration form — each with its own direction and signature. The introduction and application carry the principal's authority outward to companies; consent travels home and back with a parent's wet signature; acceptance returns from the company; assignment organizes the school's own mentors; the registration form captures the student's declaration. Missing any one of the six leaves a hole a future audit will find.

#### FR-OFFD-005 — Pre-PKL Variable Contracts

These six letters draw from everywhere: school profile for letterhead and principal, program for names and dates, company for addresses, student for identity, settings for the letter sequence. The contracts pin each placeholder to its source so generation never improvises — the company address comes from the partnership record, not from a cached copy someone typed into a form last year. When a source record is absent, generation stops instead of printing a gap.

### 4.3 During-PKL Catalog

#### FR-OFFD-006 — Three Papers for Active Placement

Active placements produce absence approvals when a student must miss a day, visit minutes when a teacher inspects a site, and incident minutes when something goes wrong. Each needs its proper signature — mentor, visiting teacher, administering coordinator — because an unsigned incident minute is, legally speaking, a rumor. The three types stay deliberately narrow; daily operations generate enough paper without inventive new forms.

#### FR-OFFD-007 — Operations Variable Contracts

Visit minutes carry arrays — several students seen, several issues found, several follow-ups promised — which makes them the most structurally complex papers in the catalog. Absence approvals hinge on dates, reasons, and the deciding mentor's identity. Incident minutes must capture witnesses and actions taken while memories are fresh. The contracts treat array fields as first-class citizens so a visit covering six students renders all six instead of silently dropping five.

### 4.4 Post-PKL Catalog

#### FR-OFFD-008 — Three Papers to Close Out

Completion letters certify the finished placement, company evaluations return the industry's verdict, and report covers dress the student's final report for the shelf. The completion letter generates itself off the finalization event — the one paper in the catalog no human triggers — while evaluations and covers follow their own request flows. Together they form the graduation packet every stakeholder expects.

#### FR-OFFD-009 — Closing Variable Contracts

The completion letter is where grade data meets official paper: final score, letter, certificate number alongside identity and dates, all arriving in the finalization event's payload. Company evaluations carry competency arrays rather than a single mark, preserving the detail behind the industry's overall rating. Report covers bind academic year and submission date so a report found years later still declares its own context.

### 4.5 Administrative Papers

#### FR-OFFD-010 — Receipts, Handovers, Circulars

Beyond the lifecycle chain sit three administrative workhorses: the receipt proving a student submitted a document, the handover record both parties sign when custody changes, and the circular announcing program terms to parents. None belongs to a single student's journey, yet a missing handover record has caused more inter-organization arguments than any grade dispute. The catalog keeps them precisely because they are easy to forget.

#### FR-OFFD-011 — Administrative Variable Contracts

Handover items arrive as arrays — equipment, documents, keys — each line a potential future disagreement, so the contract enumerates them explicitly. Receipts bind document type, receiver, and a sequential receipt number into one provable fact. Circulars carry deadlines and requirement summaries, the fields parents actually read. Small papers, exact contracts; informality here is where schools lose arguments.

### 4.6 Generation Workflow

#### FR-OFFD-012 — Sync Singles, Queued Batches

A coordinator generating one letter waits for it — seconds, in the request, with the PDF in hand. A batch of four hundred consent forms must never hold a request open; at ten documents the system switches to the background queue and returns immediately, letting the admin track progress instead of staring at a spinner. The threshold is deliberately low, because the failure mode it prevents — a timed-out request with half a batch generated — is miserable to untangle.

#### FR-OFFD-013 — Event-Driven Auto-Generation

The completion letter's trigger is the grade card's finalization, and the registration form's is the student's enrollment — events the system already emits for its own purposes. Listening keeps generation decoupled from the triggering modules, which never learn documents exist. Idempotency guards the seam: a redelivered event finds the existing issuance and stands down instead of numbering a duplicate.

#### FR-OFFD-014 — Issuance Records

Every generated paper leaves a record stating what was issued, for whom, by whom (or by which event, when automatic), from which template version, with which frozen variable values and letter number. Years later, when a company disputes what an acceptance letter said, the record reproduces it exactly — the template may have been redesigned twice since, but the snapshot remembers. Template version tracking is what makes regeneration faithful rather than approximate.

### 4.7 Status Tracking

#### FR-OFFD-015 — The Per-Registration Checklist

Each registration carries a living map from document type to state — issued, pending, missing, uploaded — recomputed from issuance records rather than maintained as fragile flags. The coordinator's view renders it as a completion percentage backed by the per-type detail, so "eighty percent" always decomposes into exactly which papers remain. A checklist that disagrees with reality is worse than none, which is why derivation beats storage here.

#### FR-OFFD-016 — Lock-Free Checklist Reads

Enrollment week puts hundreds of coordinators and teachers on these checklists simultaneously while issuances write underneath. Serving the reads through a transaction-free Read Action keeps the dashboard fluid under that contention — no locks held while assembling a student's paper state, no write queue forming behind a popular report. Freshness comes from reading live records, not from caching.

#### FR-OFFD-017 — Auto-Advance with Activation Warnings

Statuses move on their own: generation marks issued, student upload marks uploaded, verification clears the requirement, and anything required but untouched reads as missing without anyone updating it. The teeth sit at placement activation — flipping a student to active with required papers missing raises a warning naming exactly what is absent. Warnings stop most undocumented placements; the override, when genuinely needed, is itself logged.

### 4.8 Numbering & Scoping

#### FR-OFFD-018 — Sequential Letter Numbers

Indonesian administrative convention numbers letters per type per year, and the system follows it: prefix, sequence, year, with each type's counter resetting every January. The prefix defaults to the school code from settings so numbers read naturally on letterhead. Uniqueness is enforced where it matters — two introduction letters never share a number in the same year — because a duplicate number on official paper is an embarrassment no coordinator wants to explain.

#### FR-OFFD-019 — Mentorship-Scoped Reads, Principal Sign-Off

A teacher browsing documents sees papers for mentored students only, resolved through the same mentor bridge that gates grades — the consent form of another teacher's student is none of her business. Supervisor visibility stops at their own company's placements. Principal-signed types additionally require the principal's recorded sign-off before issuance completes, so the signature block on the paper always corresponds to an actual recorded approval.

### 4.9 Cross-Cutting Contracts

#### FR-OFFD-020 — Validation That Fails Closed

Generation validates everything it touches: the registration exists, the type is known, overrides are well-formed, every required variable resolved. A missing principal name or an unresolvable company address aborts with a translatable rejection instead of rendering a letter with a hole in it. Placeholder text reaching printed paper is treated as a defect class of its own, because one such letter delivered to a partner company undoes months of credibility work.

#### FR-OFFD-021 — Dual-Layer Authorization

Route policies decide who may enter the document area at all; the generating Action decides whether this actor may issue this paper for this student. A teacher passing the first gate still cannot issue principal-signed letters, and no direct Action call bypasses the ownership check. Student uploads travel the same two layers — authenticated, owning student, verified afterward by staff.

#### FR-OFFD-022 — Bilingual Interface Strings

Official paper prints in Indonesian, but the interface around it — buttons, checklists, warnings, rejection messages — serves both Indonesian staff and English-speaking observers. Every string passes through the translation helper with both language files carrying each key. An untranslated activation warning at the busiest moment of enrollment week would be worse than useless; the key-parity check keeps that from happening.

#### FR-OFFD-023 — Masked Issuance Logging

Each issuance writes to both log channels with identity numbers, phone numbers, and contact details masked before they land. The trail preserves the operational facts — which paper, for which registration, by whom, when — while ensuring a log archive handed to an auditor or vendor carries no student's personal data with it. Masking happens by key convention, so payload authors name sensitive fields consistently.

#### FR-OFFD-024 — Verified Consent Uploads

A photographed consent form is an upload like any other until verified: MIME checked against an allowlist, size capped per module configuration, filename sanitized and replaced with a non-guessable stored name outside the web root. The verification step then decides whether the image is legible and complete enough to count. Malware riding in on a consent scan and a consent form nobody can read fail at different gates, and both gates hold.

---

## 5. Non-Functional Requirements

Handling, safety, and durability guarantees for the paper pipeline.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-OFFD-001 | Document types live in the enum or configuration, never hardcoded in templates | 0 hardcoded type references | P0 | A | Full |
| NFR-OFFD-002 | Missing required variables abort generation; no placeholder text reaches output | 0 placeholder leaks | P0 | F | Full |
| NFR-OFFD-003 | Every generation and verification writes a SmartLogger entry | 100% of issuances logged | P0 | F | Full |
| NFR-OFFD-004 | Each issuance stores its frozen variable snapshot for later audit | 100% of issuances carry snapshots | P0 | F | Full |
| NFR-OFFD-005 | Papers containing student PII never persist under publicly reachable paths | 0 public-path findings | P0 | A | Full |
| NFR-OFFD-006 | Parent consent records honor the configured multi-year retention window | retention honored per policy | P1 | F | Full |
| NFR-OFFD-007 | Single papers render inside the request while batches process without blocking operators | singles in-request, batches queued | P1 | F | Full |

### 5.1 Definition Integrity

#### NFR-OFFD-001 — Types as Code, Not Folklore

When a document type name appears as a string literal inside a template, renaming it means hunting through views with text search and hoping. Declaring every type once — in the enum with its contracts as methods, or in configuration — gives the IDE and the test suite something to hold onto. The scan that flags stray literals is the guardrail; the single declaration is the habit.

#### NFR-OFFD-002 — No Placeholder Leaks

The nightmare is specific and has happened elsewhere: a letter delivered to a partner company reading "Dear {{company_name}}." Generation treats any unresolved required variable as a hard stop, and the leak counter the team watches is measured in delivered PDFs containing brace patterns — a number that has held at zero since the gate was introduced. Optional variables left blank are a separate, explicitly marked case, never an accident.

### 5.2 Auditability & Safety

#### NFR-OFFD-003 — Logged Issuance

An issued official paper without a log entry is indistinguishable from a forgery in the system's own eyes. Every generation and every verification writes its SmartLogger entry naming type, registration, and actor, so the question "who issued this acceptance and when" always has an answer. The entries feed the same dual channels as the rest of the platform — queryable activity rows plus durable system lines.

#### NFR-OFFD-004 — Frozen Snapshots

Templates evolve — letterheads refresh, wording improves, principals change — but an issued paper must remain reproducible exactly as delivered. The frozen variable snapshot plus the template version on each issuance make regeneration faithful years later, which is what an accreditation visitor implicitly demands when asking to see "the completion letter as issued." Storage cost per snapshot is trivial beside the evidentiary value.

#### NFR-OFFD-005 — PII Storage Boundaries

Consent forms and acceptance letters carry identity numbers, and identity numbers must never sit under a path the web server will happily serve to whoever guesses the URL. Storage outside the web root with non-guessable names, served only through authorized controller responses, keeps that boundary. The security scan treats any PII-bearing file under a public path as a finding, not a suggestion.

#### NFR-OFFD-006 — Consent Retention

Regulations and school policy require consent forms to outlive the placement by years, not weeks. The retention window is a configuration value rather than a constant, defaulting to five years, so a school facing stricter local rules adjusts policy without a code change. Cleanup routines consult the same value, which means retention and deletion can never disagree about when a form may go.

#### NFR-OFFD-007 — Responsive Generation Mix

Operators experience two speeds and both must feel right: a single letter appears promptly in the same request, while a cohort batch disappears into the queue with progress to watch. The contract is about the mix, not milliseconds — no batch may ever block an interactive session, and no single paper may ever require polling a job status. Batch failures report per document so one bad record never sinks four hundred good ones.

---

## 6. API / Data Contracts

### OfficialDocumentType Enum

```php
// app/Modules/Document/Enums/OfficialDocumentType.php
enum OfficialDocumentType: string implements LabelEnum
{
    case INTRODUCTION_LETTER = 'introduction_letter';
    case APPLICATION_LETTER = 'application_letter';
    case PARENT_CONSENT = 'parent_consent';
    case ACCEPTANCE_LETTER = 'acceptance_letter';
    case SUPERVISOR_ASSIGNMENT = 'supervisor_assignment';
    case REGISTRATION_FORM = 'registration_form';
    case ABSENCE_APPROVAL = 'absence_approval';
    case MONITORING_VISIT = 'monitoring_visit';
    case INCIDENT_REPORT_DOC = 'incident_report_doc';
    case COMPLETION_LETTER = 'completion_letter';
    case COMPANY_EVALUATION = 'company_evaluation';
    case FINAL_REPORT_COVER = 'final_report_cover';
    case SUBMISSION_RECEIPT = 'submission_receipt';
    case HANDOVER_RECORD = 'handover_record';
    case PROGRAM_CIRCULAR = 'program_circular';

    public function label(): string { /* Indonesian name */ }
    public function englishLabel(): string { /* English name */ }
    public function lifecyclePhase(): string { /* enrollment, daily_ops, certification, administrative */ }
    public function approvalRequired(): string { /* principal, company, mentor, teacher, admin, auto, both_parties */ }
    public function autoGenerate(): bool { /* true for completion letter and registration form */ }
    public function requiredVariables(): array { /* variable contract schema */ }
}
```

### DocumentIssuance Model

```php
// app/Modules/Document/Models/DocumentIssuance.php
#[Fillable([
    'official_document_type',
    'registration_id',
    'generated_by',
    'template_version',
    'variable_snapshot',
    'letter_number',
    'issued_at',
])]
class DocumentIssuance extends BaseModel
{
    protected $casts = [
        'official_document_type' => OfficialDocumentType::class,
        'variable_snapshot' => 'array',
        'issued_at' => 'datetime',
    ];

    public function registration(): BelongsTo { /* → Registration */ }
    public function generatedBy(): BelongsTo { /* → User, nullable for event-driven */ }
}
```

### Registration Checklist

```php
// registrations.document_status — derived JSON, structure:
{
    "introduction_letter": "issued",
    "parent_consent": "uploaded",
    "acceptance_letter": "pending"
}
// States: issued | pending | missing | uploaded
```

### Action Signatures

```php
// app/Modules/Document/Actions/GenerateOfficialDocumentAction.php
class GenerateOfficialDocumentAction extends BaseCommandAction
{
    public function execute(GenerateOfficialDocumentData $data): ActionResponse { /* ... */ }
}

// app/Modules/Document/Actions/BatchGenerateOfficialDocumentsAction.php
class BatchGenerateOfficialDocumentsAction extends BaseCommandAction
{
    public function execute(BatchGenerateOfficialDocumentsData $data): ActionResponse { /* ... */ }
}

// app/Modules/Document/Actions/UpdateDocumentStatusAction.php
class UpdateDocumentStatusAction extends BaseCommandAction
{
    public function execute(UpdateDocumentStatusData $data): ActionResponse { /* ... */ }
}

// app/Modules/Document/Actions/ReadDocumentChecklistAction.php
class ReadDocumentChecklistAction extends BaseReadAction
{
    // lock-free: no transaction(), no log()
    public function execute(string $registrationId): DocumentChecklistData { /* ... */ }
}

// app/Modules/Document/Actions/GenerateLetterNumberAction.php
class GenerateLetterNumberAction extends BaseCommandAction
{
    public function execute(GenerateLetterNumberData $data): ActionResponse { /* ... */ }
}
```

### Events

| Event | Trigger | Payload |
| ----- | ------- | ------- |
| `DocumentIssued` | After generation succeeds | `DocumentIssuance`, `Registration` |
| `DocumentChecklistIncomplete` | Placement activation with missing required papers | `Registration`, missing type list |

### Config

```php
// config/document-official.php
return [
    'letter_prefix' => env('DOCUMENT_LETTER_PREFIX', 'SMK'),
    'retention' => [
        'parent_consent_years' => 5,
        'completion_letter_years' => 10,
    ],
    'batch_queue' => 'documents',
    'batch_threshold' => 10,
    'required_per_phase' => [
        'enrollment' => ['introduction_letter', 'parent_consent', 'acceptance_letter'],
        'daily_ops' => ['supervisor_assignment'],
        'certification' => ['completion_letter'],
    ],
];
```

---

## 7. Design Decisions

Choices that shaped the paper pipeline and why they stuck.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-OFFD-001 | Document types live in a PHP enum with contract methods, not a database table | P0 | — | — |
| DD-OFFD-002 | Each issuance freezes its variable values into a stored snapshot | P0 | — | — |
| DD-OFFD-003 | Letter numbers sequence per type with an annual reset under school convention | P1 | — | — |
| DD-OFFD-004 | Parent consent travels as a verified upload, not a digital signature | P0 | — | — |
| DD-OFFD-005 | Automatic papers generate from domain events, never from schedules | P0 | — | — |

### 7.1 Representation & History

#### DD-OFFD-001 — Enum Over Table

A database table for fifteen rows that change once a year would buy runtime flexibility nobody exercises while surrendering compile-time safety the team uses daily — autocomplete, exhaustiveness checks, and tests that fail the moment a case is renamed. The enum carries each type's contracts as methods beside the case itself, so the variable schema for a letter lives one jump from its name. A genuinely new paper type arrives as a reviewed code change with its contracts attached, which is exactly the ceremony an official document deserves.

#### DD-OFFD-002 — Frozen Variable Snapshots

School data churns — principals rotate, addresses update, companies rebrand — while issued paper must stand still. Storing the exact values each issuance printed, typically a few kilobytes of JSON, buys permanent reproducibility: any paper can be regenerated pixel-faithful regardless of how the live records have since moved. The alternative, re-resolving live data at reprint time, once produced a completion letter naming a principal who had never held the post during that student's placement.

### 7.2 Numbering, Consent & Triggers

#### DD-OFFD-003 — Per-Type Annual Sequences

Indonesian school administration numbers letters the way it always has — sequential per kind of letter, restarting each January — and fighting that convention would only produce papers that look wrong to every reader who matters. Each type owns its counter, the school code prefixes by default, and uniqueness is enforced per type per year where duplicates would embarrass. Manual overrides stay out of scope; a misnumbered letter is regenerated rather than patched, keeping the sequence honest.

#### DD-OFFD-004 — Uploads, Not E-Signatures

Accreditation practice in this context recognizes wet signatures on consent forms; a drawn-on-screen squiggle carries no legal weight for a minor's off-campus placement and would give schools false confidence. The system therefore tracks what it can genuinely vouch for — that a signed sheet was uploaded and a staff member verified it as legible and complete — while the physical original stays in the school's files. Signature authenticity remains a human judgment, assisted by the verification workflow rather than replaced by it.

#### DD-OFFD-005 — Events, Not Schedules

Completion letters must appear the moment grades finalize, not at the next hourly sweep — a graduate waiting at the counter should not wait for a cron tick. Listening to the finalization event delivers immediacy with zero polling infrastructure and zero delay windows to explain. The price is idempotency discipline in the listener, since queues redeliver; the handler checks for an existing issuance first, making replays safe by construction.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Catalog coverage | every real PKL paper mapped to a type | enum cases against the regulatory checklist |
| Contract completeness | no generation aborts from undeclared variables | abort log review per intake |
| Numbering uniqueness | no duplicate numbers per type per year | uniqueness query per year |
| Checklist visibility | coordinators see per-registration completion without delay | registration detail observation |
| Consent tracking | every active registration carries a consent state | checklist completeness query |
| Auto-generation reliability | every finalization yields exactly one completion letter | issuance count versus finalization count |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [Registration](MBB5R-registration.md) | Registration model, lifecycle, and upload infrastructure |
| [Document Templates](PKYX6-document-templates.md) | Renderer, template CRUD, and the PDF pipeline |
| [Reports](R6BMW-reports.md) | Finalization event that triggers the completion letter |

### Build Guide

Define the enum with all fifteen types and their variable contracts first, then the generation Action on top of the existing renderer. Wire the event listeners for automatic papers, add checklist derivation to registrations, and seed the registry. Batch queueing and letter sequencing come last, once single-document generation is solid.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | — | Consumed by enrollment, daily operations, and certification flows as needed; no direct downstream spec |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume fifteen types cover the full paper chain and new regulatory papers arrive slowly enough for code-reviewed additions | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Reporting phase and build order
- [Project initialization](QLHDO-project-initialization.md) — global contracts (authorization, validation, bilingual strings, masked logging)
- [Architecture](D2FT3-architecture.md) — Action Triad, lock-free Reads, Entity bridges
- [Reports](R6BMW-reports.md) — finalization event triggering the completion letter
- [Document Templates](PKYX6-document-templates.md) — renderer and template infrastructure
- [Registration](MBB5R-registration.md) — registration lifecycle and uploads
- [Certification](J0M04-certification.md) — graduation packet consumer
