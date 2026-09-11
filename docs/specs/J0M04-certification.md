# J0M04 — Certification

> **Spec ID:** J0M04
> **Status:** Full
> **Owner:** Certification
> **Depends on:** [ARDA6](ARDA6-assessment.md), [AXKZW](AXKZW-evaluation.md)

## Description

The certificate is the school's signed promise that a student completed the industrial
fieldwork program. This spec defines its full lifecycle: template authoring with layout
discipline, eligibility-gated single and batch issuance with unique serials, canonical PDF
rendering with content snapshots, revocation for error correction, student self-service
download with lazy generation, and continuity of retrieval for alumni after program
closure. Public QR verification lives in
[certificate-qr-verify](J0M05-certificate-qr-verify.md).

---

## 1. Problem Statements

### PS-1 — Handmade Certificates Do Not Survive Scrutiny

Completion certificates assembled in word processors carry inconsistent layouts, retyped
names, and no verifiable identity. An employer calling to confirm a graduate receives a
scanned image with nothing to check against, and a misspelled name discovered at the
graduation rehearsal means an overnight reprint run.
**→ Requirement:** FR-CERT-001/008/009 (managed templates, serial discipline).

### PS-2 — Cohort Issuance Without a Batch Path

Two hundred graduates need certificates in the same week. Issuing one by one through a
single-record form takes days, and the first failure — an incomplete assessment — aborts
whatever naive loop the admin scripted.
**→ Requirement:** FR-CERT-012/013/014 (batch issuance with per-student error collection).

### PS-3 — Ineligible Students Must Not Receive Certificates

A certificate issued to a student with incomplete attendance or a missing handbook
acknowledgment is worse than no certificate: it attests to something untrue and
undermines every genuine one.
**→ Requirement:** FR-CERT-010/011 (eligibility gates evaluated on the entity).

### PS-4 — Errors Need a Formal Undo

Wrong student, wrong program dates, corrected scores after issuance — without a revocation
path, schools recall paper or quietly issue duplicates, leaving two live certificates for
one completion.
**→ Requirement:** FR-CERT-017/018 (revocation as a terminal transition).

### PS-5 — Graduates Keep Needing Their Certificates

Years after closure, alumni return for employment checks needing their certificates, and
employers need to verify them. If closure or account archival severs retrieval, the
school's promise expires with the program.
**→ Requirement:** FR-CERT-020/021 (closure batch completion, alumni continuity).

---

## 2. Goals & Non-Goals

### Goals

- **Managed certificate templates with layout discipline** — portrait or landscape HTML with a declared placeholder set. *Why:* ends handmade layout drift across cohorts.
- **Eligibility-gated issuance** — no certificate without complete assessment, verified placement, and current handbook acknowledgment. *Why:* a certificate must attest to something true.
- **Unique serial discipline enforced in domain and database** — certificate numbers and verification hashes unique by construction and by constraint. *Why:* verifiability collapses without uniqueness.
- **Batch issuance that survives partial failure** — per-student error collection, closure-time completion of remaining certificates. *Why:* cohorts graduate together; one incomplete record must not block two hundred.
- **Immutable content snapshots** — each certificate freezes the template content it was born from. *Why:* later template edits must not rewrite history.
- **Alumni retrieval continuity** — graduates retain read-only certificate access after closure and account archival. *Why:* the school's promise outlives the program.

### Non-Goals

- **Public QR verification endpoint**. *Why:* owned by [certificate-qr-verify](J0M05-certificate-qr-verify.md).
- **Cryptographic digital signatures on PDFs**. *Why:* serial plus hash verification covers school-scale trust; PKI ceremony is post-MVP.
- **Email delivery of certificates**. *Why:* self-service download plus verification covers distribution without mail infrastructure.
- **Re-issue by un-revoking**. *Why:* revocation is terminal; corrections arrive as new certificates, never resurrected ones.

---

## 3. User Stories / Use Cases

Admins author templates and issue certificates; students download their own; alumni
retrieve theirs after closure. The table lists each journey once; the groups below
narrate them.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-CERT-001 | Admin authors and retires certificate templates with layout choice | P0 | F | Full |
| UC-CERT-002 | Admin issues a single certificate to an eligible graduate | P0 | F | Full |
| UC-CERT-003 | Admin batch-issues certificates for a cohort with per-student results | P0 | F | Full |
| UC-CERT-004 | Student downloads their own issued certificate with lazy PDF generation | P0 | F | Full |
| UC-CERT-005 | Alumnus retrieves their certificate read-only after program closure | P1 | F | Full |

### 3.1 Authoring and Issuance

#### UC-CERT-001 — Admin prepares the template

Before graduation season the admin creates the year's certificate template, choosing
landscape for the new border artwork and filling the content with the declared
placeholders for names, scores, and dates. A typo in the school address is corrected
through a new active version rather than an edit that would rewrite already-issued
certificates. Retired layouts stay viewable so last year's certificates remain
explainable, but only the active one is selectable for new issuance.

#### UC-CERT-002 — Admin issues to one graduate

The coordinator opens the issuance view, selects the graduate's registration and the
active template, and confirms. Eligibility evaluates first: complete assessment,
verified placement, current handbook acknowledgment. A misspelled-name case from last
year is now impossible at this step because the name resolves from the registration
record rather than a typed field. Success creates the numbered record, snapshots the
template content, stores or defers the PDF, and announces the issuance event the
student's notification listens for.

#### UC-CERT-003 — Admin issues to the cohort

Two hundred names are selected for the graduation batch. The process walks the list,
issuing to every eligible registration while collecting per-student failures — an
incomplete evaluation here, a missing acknowledgment there — into a result the admin
reviews afterward. The batch never aborts on the first problem; the morning ends with
190 certificates issued and 10 actionable errors instead of zero certificates and one
stack trace. At program closure the same process sweeps the remaining eligible
students so nobody graduates without their paper because they were alphabetically
last.

### 3.2 Retrieval

#### UC-CERT-004 — Student downloads their certificate

A graduate opening the certificate page sees the issued record with its number and
date, and downloads the PDF with one action. If the PDF was deferred at issuance to
keep the batch fast, the first download generates and stores it transparently; the
second download serves the stored file. A revoked certificate remains visible with
its terminal state clearly marked, because hiding it would erase the explanation for
why a replacement exists.

#### UC-CERT-005 — Alumnus returns after closure

Two years later the same graduate, now archived as alumni with a read-only account,
logs in and retrieves the identical PDF for an employment check. Closure and account
archival never severed the retrieval path; they only removed every write path. The
employer verifies the serial through the verification flow, completing a chain of
trust that outlived the program itself.

---

## 4. Functional Requirements

Issuance travels through Command and Process Actions with transactions, eligibility
evaluation on the entity, and audit logging; serial uniqueness is enforced in domain
logic and at the database; revocation is a terminal transition. The table is the
complete contract; the groups below narrate each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-CERT-001 | Admin creates and retires certificate templates through Command Actions accepting validated data | P0 | F | Full |
| FR-CERT-002 | Template layout is drawn from a fixed portrait and landscape vocabulary with portrait default | P1 | U | Full |
| FR-CERT-003 | Template content uses declared HTML placeholders drawn from the canonical variable set | P0 | F | Full |
| FR-CERT-004 | Only active templates are selectable for new issuance; retired templates stay readable for audit | P0 | F | Full |
| FR-CERT-005 | Certificate serial and verification hash are unique by domain generation and by database constraint | P0 | F | Full |
| FR-CERT-006 | Serial uniqueness invariant is evaluated on the certificate entity before any write | P0 | U | Full |
| FR-CERT-007 | Issued certificates snapshot the template content active at issuance time | P0 | F | Full |
| FR-CERT-008 | Issuance records the acting admin, the issuance timestamp, and the registration bridge | P0 | F | Full |
| FR-CERT-009 | Single issuance runs inside a Command Action transaction with audit logging and an issuance event | P0 | F | Full |
| FR-CERT-010 | Graduate eligibility evaluates assessment completion, placement verification, and current handbook acknowledgment | P0 | F | Full |
| FR-CERT-011 | Ineligible registrations are rejected with a translatable business-rule message naming the unmet condition | P0 | F | Full |
| FR-CERT-012 | Batch issuance composes single issuance per registration inside a Process Action with per-student error collection | P0 | F | Full |
| FR-CERT-013 | Batch issuance never aborts on a single failure and returns issued records alongside error details | P0 | F | Full |
| FR-CERT-014 | Program closure batch-issues remaining eligible certificates before archival proceeds | P0 | F | Full |
| FR-CERT-015 | Certificate PDFs render through the canonical DomPDF pipeline with deterministic local-disk storage | P0 | F | Full |
| FR-CERT-016 | First download lazily generates a deferred PDF idempotently; later downloads serve the stored file | P1 | F | Full |
| FR-CERT-017 | Revocation transitions issued certificates to a terminal revoked state that retains the record | P0 | F | Full |
| FR-CERT-018 | Revoked certificates are never re-issued or resurrected; corrections arrive as new certificates | P0 | U | Full |
| FR-CERT-019 | Certificate reads are role-gated so students see only their own and admins see the cohort | P0 | A | Full |
| FR-CERT-020 | Alumni retain read-only certificate retrieval after closure and account archival | P1 | F | Full |
| FR-CERT-021 | Certificate issuance validates server-side through form and data-object rules sharing entity constraints | P0 | A | Full |
| FR-CERT-022 | Every certificate string passes through the translation helper with mirrored Indonesian and English keys | P0 | A | Full |
| FR-CERT-023 | Certificate mutations write dual-channel audit entries with PII masking | P0 | F | Full |
| FR-CERT-024 | Certificate storage uses ordered UUID keys with explicit delete behaviour and composite indexes | P0 | A | Full |
| FR-CERT-025 | Certificate classes extend their layer bases with strict typing and a single-entry Action surface | P0 | A | Full |

### 4.1 Templates and Serials

#### FR-CERT-001 — Command-driven template authoring

Graduation-season template edits arrive under time pressure, which is exactly when
half-written records happen. The Command Action wraps validation, persistence, and
event dispatch in a transaction, so a template save either lands whole or not at
all. The listing manager maps the response into feedback and never writes directly,
keeping the one-mutation-path invariant intact even during the pre-ceremony rush.

#### FR-CERT-002 — Two layouts, no surprises

Certificates print on fixed stationery, and stationery comes in two orientations.
Fixing the vocabulary to portrait and landscape with a portrait default keeps the
render dimensions predictable and the preview honest. A free-text orientation field
once produced a `landsacpe` template that rendered blank on ceremony morning; the
enum makes that typo unrepresentable.

#### FR-CERT-003 — Placeholders from a closed set

The certificate carries seventeen resolved values — student and school identity,
company and program context, dates, scores with letter form, serial and issue date
— each drawn from the declared set. Authors see exactly which names are legal, and
anything outside the set fails at preview rather than printing blank on two hundred
certificates. The closed set is also what keeps the snapshot reproducible years
later.

#### FR-CERT-004 — Active gate with readable history

Last year's ornate border retires when this year's design activates, but last year's
certificates must remain explainable. The active flag separates the two needs:
selection scopes to active templates while history views bypass the scope. The
retirement itself is audited, so a future dispute about which design was official
in June has a timestamped answer.

#### FR-CERT-005 — Uniqueness in two places

A duplicate certificate number would let two graduates present the same credential,
collapsing verification. Generation produces random, non-sequential components that
resist guessing, and unique constraints at the database stand behind generation as
the final arbiter under concurrency. Either layer alone is insufficient — the pair
is what makes duplicates structurally impossible rather than merely unlikely.

#### FR-CERT-006 — The entity refuses duplicates first

Before any write reaches the database, the certificate entity evaluates the serial
invariant against the proposed number and hash, rejecting collisions with a
translatable message. Catching the violation in domain logic keeps the failure
readable — a named business rejection rather than a raw constraint exception — and
keeps the rule unit-testable in milliseconds without a database round-trip. The
database constraint remains as the concurrency backstop the entity cannot provide
alone.

#### FR-CERT-007 — Snapshots freeze history

Editing a template after issuance once retroactively altered the wording on
already-graduated certificates, discovered when an employer compared a presented
certificate against a reprint. Snapshotting the active content into each issued
record ends that class of incident: the certificate carries its own frozen wording
forever, and template evolution affects only future issuance. Storage cost is
negligible against evidentiary value.

#### FR-CERT-008 — Every certificate names its maker

The issued record carries the registration it belongs to, the admin who issued it,
and the moment of issuance. That triple answers the three questions every dispute
starts with: whose, by whom, when. The registration bridge also means the printed
name always resolves from the canonical student record, closing the misspelled-name
reprint cycle that plagued handmade certificates.

#### FR-CERT-009 — Transactional single issuance

Issuing writes the record, resolves placeholders, stores or defers the PDF, and
announces the event. If the disk write fails, the transaction rolls the record back
so the listing never shows a certificate with no file; on success the audit entry
lands and the issuance event dispatches after commit, where notifications attach.
The atomicity is what lets an admin retry a failed issuance without first hunting
phantoms.

### 4.2 Eligibility and Batch

#### FR-CERT-010 — Three gates before issuance

Assessment completion, placement verification, and current-version handbook
acknowledgment evaluate together on the entity before any certificate exists. The
conjunction is deliberate: scores without verified attendance attest to unobserved
work, and completion without acknowledged safety rules attests to uninformed
participation. Each gate reads live state through its owning module's surface
rather than a cached flag that could stale.

#### FR-CERT-011 — Rejections that teach

An ineligible issuance attempt returns a message naming the unmet condition in the
admin's language — which evaluation is missing, whose acknowledgment lapsed after
the handbook revision — instead of a bare refusal. Actionable rejection turns the
issuance screen into a graduation-readiness checklist: the admin sees exactly what
each blocked student still needs, which is the entire operational value of gating.

#### FR-CERT-012 — Batches compose singles

The batch process injects the single-issuance Action and invokes it once per
registration rather than reimplementing issuance logic, so eligibility, serial
discipline, snapshots, and audit behave identically whether one certificate or two
hundred are issued. Composition through injection keeps the batch testable with a
stubbed single and keeps future issuance-rule changes landing in exactly one
place.

#### FR-CERT-013 — Partial success as the normal outcome

Real cohorts always contain a handful of incomplete records at batch time. The
process collects each failure next to its registration and continues, returning
the issued set alongside the error set for the admin's review. Aborting on first
failure would hold 190 graduates hostage to one missing evaluation; collecting
errors graduates everyone eligible today and gives the rest a precise punch list.

#### FR-CERT-014 — Closure leaves nobody eligible behind

Program closure runs the batch sweep over remaining eligible registrations before
archival locks the records. Without this step, students who completed late — a
deferred evaluation, a re-submitted assignment — would graduate into an archived
program with no certificate and no write path left to create one. The sweep is the
closure sequence's conscience: archival must attest that every eligible student
received their paper, not merely that most did.

### 4.3 Rendering and Revocation

#### FR-CERT-015 — One engine, deterministic files

Resolved HTML compiles through the canonical DomPDF pipeline and lands on local
disk under a deterministic path derived from the certificate identity, served only
through authorized routes that never expose server internals. Funneling every
certificate through one renderer keeps font and margin fixes landing once, and
deterministic naming keeps re-renders idempotent instead of littering duplicates.

#### FR-CERT-016 — Deferral with transparent catch-up

Rendering two hundred PDFs inside the issuance transaction would stretch the batch
past timeouts, so issuance may persist the record first and defer the file. The
first download then generates and stores transparently; the student notices only
normal latency. Idempotent paths make the catch-up safe to retry: generating twice
overwrites the same file instead of forking versions.

#### FR-CERT-017 — Revocation as a recorded transition

A certificate issued to the wrong student, or with pre-correction scores, moves
from issued to revoked through a guarded transition that records who revoked and
when. The record stays — revocation explains rather than erases — so the audit
trail shows the error, its correction, and the replacement that followed. Only the
issued-to-revoked direction exists; there is no path back.

#### FR-CERT-018 — Terminal means terminal

Allowing a revoked certificate to return to issued would resurrect a credential
the school already declared invalid, possibly after the holder presented the
revocation as resolved. The state machine forbids it structurally: revoked has no
outgoing transitions, and corrections arrive as fresh certificates with fresh
serials. Employers checking either serial get a coherent story — one revoked, one
live — instead of a resurrected ambiguity.

### 4.4 Access and Continuity

#### FR-CERT-019 — Reads that respect roles

Students listing certificates see only their own; admins see the cohort board with
numbers, states, and dates. Both gates enforce: the route Policy refuses foreign
records, and the read path re-scopes by ownership so a guessed identifier yields a
forbidden page rather than another student's credential. The pairing matters
because identifiers travel in URLs that get forwarded.

#### FR-CERT-020 — Retrieval that outlives the program

Closure transitions the program to its terminal archived state and marks student
accounts alumni, removing every write path — no new registrations, no logbook
submissions, no attendance. Retrieval is deliberately excluded from the lockdown:
alumni keep a read-only view of certificates and grades, resolved through the same
ownership scoping as before. The design treats the certificate as a promise whose
audience includes future employers, not just current students, so the read path
survives as long as the archive does.

### 4.5 Cross-Cutting Contracts

#### FR-CERT-021 — Validation in one home

Issuance payloads validate through form rules at the boundary and data-object
rules inside the Action, both drawn from the entity's shared constraints. A layout
value outside the vocabulary or a missing registration reference is rejected
before any write in both paths. Sharing the definitions keeps the two rejections
identical, so API and UI callers receive the same contract.

#### FR-CERT-022 — Two languages for the ceremony

Certificate labels, issuance feedback, and rejection messages render through the
translation helper with mirrored catalogues, and personal names travel as
placeholders rather than concatenation. A raw key on a graduation certificate
reads as an institutional failure in front of families; catalogue completeness
keeps the ceremony dignified in both languages.

#### FR-CERT-023 — Audited without leaking

Each issuance, batch completion, and revocation writes a dual-channel entry with
actor identity and change summary, masked before either sink. The activity channel
gives the school its queryable issuance ledger; the system channel gives operators
their debuggable stream. Graduate names never reach plaintext logs by accident
because masking runs before persistence, not after.

#### FR-CERT-024 — Keys that join cleanly

Certificate rows reference registrations and users through ordered UUIDs with
explicit delete behaviour — registration removal cascades, actor removal nulls —
and indexed status and serial columns keep cohort listings and verification
lookups fast. Uniform keys let the closure sweep join hundreds of registrations
without type-mismatch failures at the worst possible moment.

#### FR-CERT-025 — Bases and boundaries

Certificate classes extend their layer bases under strict typing, Actions expose
a single entry point, and the entity carries rules without persisting. The
contract scan asserts these properties structurally, so reviewers spend their
attention on eligibility semantics rather than on whether a new Action remembered
its transaction.

---

## 5. Non-Functional Requirements

Constraints that keep credentials trustworthy and issuance operable at cohort scale.
Each row names its target; the groups below narrate the reasoning.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-CERT-001 | Serial and hash uniqueness holds under concurrent issuance with zero duplicates | N/A | P0 | F | Full |
| NFR-CERT-002 | Ineligible registrations are unissuable through every entry path including batch and closure | N/A | P0 | A | Full |
| NFR-CERT-003 | Revoked certificates stay terminally revoked and retrievable for audit | N/A | P0 | A | Full |
| NFR-CERT-004 | Stored PDFs are reachable only through authorized routes and expose no server paths | N/A | P0 | A | Full |
| NFR-CERT-005 | Batch and closure issuance completes at full-cohort volume with per-student outcomes reported | N/A | P1 | F | Full |
| NFR-CERT-006 | Alumni certificate retrieval remains available read-only after closure and archival | N/A | P1 | F | Full |
| NFR-CERT-007 | Indonesian and English certificate strings remain complete and mirrored across releases | N/A | P1 | A | Full |

### 5.1 Trust

#### NFR-CERT-001 — Duplicates impossible, not just unlikely

Concurrent batch workers generating serials simultaneously must still produce zero
collisions. Domain-level randomness makes collisions improbable; the database
constraint makes them impossible, converting any residual race into a retried
generation rather than a duplicated credential. Verification asserts the pair by
driving concurrent issuance and counting distinct serials.

#### NFR-CERT-002 — Gates without side doors

Eligibility is asserted identically for single issuance, batch items, and the
closure sweep, because three code paths evaluating three rule sets would
eventually disagree. A registration that fails the gate through the form must
fail identically when named inside a two-hundred-item batch. The shared entity
evaluation is what keeps the three doors locked with the same key.

#### NFR-CERT-003 — Revocation that sticks

Once revoked, a certificate renders its terminal state forever and never becomes
issuable again through any path, while remaining retrievable so the audit story
stays complete. The property is verified by attempting every resurrection — direct
transition, re-issue against the same record, batch inclusion — and watching each
refuse. Employers checking a revoked serial receive a definitive answer, never a
resurrected maybe.

### 5.2 Operations and Continuity

#### NFR-CERT-004 — Files behind authorization

Stored PDFs never answer to direct paths; only the ownership-checked route serves
them, and responses carry streams rather than filesystem hints. The guarantee
covers failure modes too, when verbose errors are most tempted to confess paths.
A graduate's credential stays between the graduate, the school, and the verifier
it is shown to.

#### NFR-CERT-005 — Cohorts graduate together

The batch and closure sweeps process full-cohort volumes within operational
tolerance, reporting per-student outcomes rather than a single pass or fail. Slow
is acceptable; silent is not: every registration in the input set ends the run
either issued or explained. Deferred PDF generation is the lever that keeps the
run bounded while preserving completeness.

#### NFR-CERT-006 — The archive keeps its promise

After closure and archival, alumni authentication still resolves to the read-only
certificate view, verified by exercising retrieval against an archived program
with archived accounts. The failure being prevented is quiet and devastating: a
graduate returning for an employment check finding their credential unreachable
because the program they completed no longer exists as a readable record.

#### NFR-CERT-007 — Two languages, no drift

Release checks assert both catalogues cover every certificate string. Graduation
is the school's most public moment, and an untranslated key on the ceremony's
central artifact would be witnessed by every family present. Mirrored catalogues
keep the institution's voice intact in both languages.

---

## 6. API / Data Contracts

### 6.1 Certificate Model

```
Certificate — certificates table, UUID v7 PK
  Fillable: registration_id, certificate_number, qr_hash, status,
    template_content, issued_by, issued_at, revoked_by, revoked_at
  Casts: status → CertificateStatus, issued_at → datetime, revoked_at → datetime
  Default: status = ISSUED
  Relations: registration() BelongsTo Registration, issuer() BelongsTo User
```

### 6.2 CertificateTemplate Model

```
CertificateTemplate — certificate_templates table, UUID v7 PK
  Fillable: name, layout, content_template, is_active, created_by
  Casts: is_active → boolean
  Relations: createdBy() BelongsTo User
```

### 6.3 CertificateStatus Enum and Entity

```
CertificateStatus: string, implements StatusEnum
  ISSUED = issued, REVOKED = revoked
  isTerminal(): true for REVOKED
  Transitions: ISSUED → [REVOKED], REVOKED → []

CertificateEntity extends BaseEntity (final readonly)
  Snapshot: serial, hash, status, registration identity, eligibility view
  Invariants: serialUniqueness(), hashUniqueness(), eligibilityGates()
  Bridge: Certificate.asCertificate(): CertificateEntity via fromModel()
```

### 6.4 Renderer Service

```
CertificateRenderer (final readonly, constructor-injected)
  resolvePlaceholders(Registration, Certificate): array — canonical 17 pairs
  renderHtml(Registration, Certificate): string
  renderPdf(Registration, Certificate): string — via DomPDF
  storePdf(Registration, Certificate): string — deterministic local path
  pdfPath(Certificate): string, getDiskPath(string): string
  Disk: certificates/ (local, outside web root)
```

### 6.5 Actions, Events, Policies, Routes

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| IssueCertificateAction | BaseCommandAction | Registration, CertificateTemplate | Certificate |
| BatchIssueCertificateAction | BaseProcessAction | array registrationIds, CertificateTemplate | array issued plus errors |
| CreateCertificateTemplateAction | BaseCommandAction | validated DTO | CertificateTemplate |
| RevokeCertificateAction | BaseCommandAction | Certificate | Certificate |

| Event | Dispatched by |
| ----- | ------------- |
| CertificateIssued | IssueCertificateAction |

| Policy | Abilities |
| ------ | --------- |
| CertificatePolicy | viewAny: super_admin, admin, student · view: admin or owning student · create: admin · update, delete: never · revoke: admin |
| CertificateTemplatePolicy | viewAny, create, update, delete: admin |

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| GET /certificates/{certificate}/download | CertificateDownloadController | auth |
| GET /student/certificates | StudentCertificates | auth, role:student |
| GET /admin/certificates/templates | CertificateTemplateManager | auth, role:super_admin, admin |
| GET /admin/certificates | CertificateList | auth, role:super_admin, admin |

### 6.6 Database Schema

```
certificates:
  id: uuid PK
  registration_id: foreignUuid → registrations.id cascadeOnDelete indexed
  certificate_number: string unique
  qr_hash: string unique
  status: string default 'issued' indexed
  template_content: text nullable
  issued_by: foreignUuid → users.id nullOnDelete nullable
  issued_at: datetime
  revoked_by: foreignUuid → users.id nullOnDelete nullable
  revoked_at: datetime nullable
  timestamps

certificate_templates:
  id: uuid PK
  name: string
  layout: string(20) default 'portrait'
  content_template: text
  is_active: boolean default true indexed
  created_by: foreignUuid → users.id nullOnDelete nullable
  timestamps
  Indexes: created_at
```

---

## 7. Design Decisions

Recorded choices with their context. The table lists each decision once; the groups below
carry the narrative.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-CERT-001 | Template content uses safe placeholder substitution rather than executable Blade compilation | P0 | — | — |
| DD-CERT-002 | Verification identity is a random hash rather than a URL-bound token | P1 | — | — |
| DD-CERT-003 | PDF generation defers to first download rather than blocking batch issuance | P1 | — | — |
| DD-CERT-004 | Issued content is snapshotted at issuance rather than referenced live | P0 | — | — |
| DD-CERT-005 | Closure completes remaining issuance and preserves alumni retrieval rather than locking everything | P0 | — | — |

### 7.1 Rendering and Identity

#### DD-CERT-001 — Placeholders instead of executable templates

Certificate templates are edited by school staff, not developers, so compiling them as
executable Blade would hand arbitrary code execution to every template author. String
substitution over a closed placeholder set gives authors everything they need — names,
dates, scores — with nothing they could misuse. Expressiveness in conditionals and loops
was traded for a guarantee no ceremony program can accidentally, or maliciously, break.

#### DD-CERT-002 — Hashes that survive endpoint changes

Binding the verification identity to a URL would couple every issued certificate to the
deployment's current routing; a domain change or route rename would invalidate the
printed QR codes on already-graduated certificates. A random hash stays meaningful
independent of where verification lives today, and display layers embed it into QR
imagery without the record caring. Deployment freedom was worth the indirection.

### 7.2 Timing and History

#### DD-CERT-003 — Fast issuance, lazy files

Rendering a PDF per student inside the batch transaction multiplies batch latency by
the slowest render, pushing cohort issuance past timeouts. Issuing the numbered
record first and generating the file on first download keeps the batch bounded while
preserving the student's experience — the wait moves to a single download nobody
notices. Idempotent paths make the deferral safe to retry and re-render.

#### DD-CERT-004 — Each certificate carries its own past

Referencing the live template from every certificate would make template evolution
retroactive: correcting this year's wording would rewrite last year's credentials.
Snapshotting freezes each certificate against its birth version, so history stays
put while design moves on. Duplicated content across rows is trivial storage; the
alternative is untrustworthy history.

### 7.3 Closure

#### DD-CERT-005 — Archival that completes before it locks

Locking the program before sweeping remaining issuance would strand late completers
without certificates and without any path to create them. Running the closure batch
first, then locking records and transitioning accounts to alumni with read-only
retrieval, keeps the archive both complete and humane: every eligible graduate
holds their paper, and every alumnus can still prove it years later. The ordering
is the decision — completeness before immutability.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Duplicate serial or hash | 0 | Uniqueness-constraint and entity-invariant review |
| Certificate issued to an ineligible registration | 0 | Eligibility-gate review across single, batch, and closure paths |
| Eligible graduate missing a certificate after closure | 0 | Closure-sweep completeness review |
| Template edit altering an issued certificate | 0 | Snapshot-presence review |
| Revoked certificate resurrected or re-issued | 0 | Terminal-state review |
| Alumnus unable to retrieve their certificate | 0 | Post-closure retrieval review |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [assessment](ARDA6-assessment.md) | Scores feeding eligibility and printed results |
| [evaluation](AXKZW-evaluation.md) | Industry evaluation feeding eligibility and content |

### Build Guide

Certification closes the student journey: eligibility reads assessment, evaluation,
placement, and handbook acknowledgment; issuance snapshots templates; closure sweeps
remaining graduates before archival; alumni retrieval survives into the archived
program.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [certificate-qr-verify](J0M05-certificate-qr-verify.md) | Public verification of issued serials and hashes |
| 2 | [file-uploads-media](WQGTP-file-uploads-media.md) | Evidence and artifact storage conventions |
| 3 | [pdf-generation](7UB7S-pdf-generation.md) | Canonical DomPDF engine shared with other documents |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the canonical placeholder set covers employer-required certificate fields | Accepted | Maintainer | — |
| A-2 | We assume alumni authentication remains available for archived accounts | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Certification phase and build order
- [Spec template](../templates/spec-template.md) — section skeleton and ID rules
- [Document templates](PKYX6-document-templates.md) — template repository and renderer
- [Handbooks](ZUFG8-handbooks.md) — acknowledgment feeding eligibility
- [Certificate QR verify](J0M05-certificate-qr-verify.md) — public verification endpoint
- [Assessment](ARDA6-assessment.md) — scores feeding eligibility
- [Evaluation](AXKZW-evaluation.md) — industry evaluation feeding eligibility
- [RBAC and authorization](T4B26-rbac-and-authorization.md) — role model and policies
- [Logging and error handling](89SRA-logging-and-error-handling.md) — dual-channel logging and exception trees
