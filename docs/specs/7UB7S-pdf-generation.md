# PDF Generation — Dompdf Rendering Pipeline

> **Spec ID:** 7UB7S
> **Status:** Full
> **Owner:** Core
> **Depends on:** WQGTP

## Description

Certificates, grade cards, and official documents all leave the system as PDF, rendered from Blade templates by a single Dompdf pipeline: renderer services turn domain data into bytes, small jobs return synchronously, and batch work goes to the queue so no HTTP worker dies rendering fifty certificates. Templates live in one directory with one shared layout; storage and lifecycle ride on [file-uploads-media](WQGTP-file-uploads-media.md).

---

## 1. Problem Statements

### PS-1 — Batch Rendering Kills the Worker That Runs It

Dompdf holds the whole document in memory while rendering. Issuing certificates for a fifty-student cohort inside a web request exhausts the worker's memory and crashes it mid-batch — some students get certificates, some do not, and nobody can tell which without checking one by one. Heavy rendering must leave the request cycle entirely.
**→ Requirement:** FR-PDF-006 (queued batch jobs), NFR-PDF-003 (no HTTP blocking).

### PS-2 — Every Module Renders PDFs Differently

Without a shared template system, one module hand-builds HTML strings in an Action, another hardcodes styles into a view, and the parents comparing a certificate against a grade card see two documents that clearly came from different schools. Inconsistent output erodes exactly the institutional credibility these documents exist to project.
**→ Requirement:** FR-PDF-001 (single engine), FR-PDF-002 (shared Blade layout).

### PS-3 — Layout Changes Require Surgery on Business Logic

When rendering code lives inside Actions, a request as innocent as "move the logo left" means editing, re-testing, and re-deploying issuance logic — with the attendant risk of breaking the thing the layout edit never touched. Content assembly and visual presentation must change independently.
**→ Requirement:** FR-PDF-003/004 (renderer services), DD-PDF-002 (rendering outside Actions).

---

## 2. Goals & Non-Goals

### Goals

- **One rendering engine with locked configuration** — every PDF comes from Dompdf with remote fetching disabled. *Why:* a single engine means a single set of CSS quirks to learn and one configuration to audit.
- **Blade templates with a shared layout** — all documents inherit typography, branding slots, and localization behavior. *Why:* visual consistency without per-template discipline.
- **Renderer services between Actions and the engine** — domain data enters, PDF bytes leave. *Why:* layout changes must not touch business logic.
- **Synchronous singles, queued batches** — a clicked download returns immediately; a cohort run goes to workers. *Why:* the two workloads have opposite failure modes and must not share a path.
- **Lifecycle-managed output** — rendered files persist as media, not as loose paths. *Why:* generated documents need the same cleanup and delivery guarantees as uploads.

### Non-Goals

- **Interactive or form-fillable PDFs**. *Why:* output documents are read-only artifacts.
- **Cryptographic digital signatures inside the PDF**. *Why:* authenticity is carried by the QR verification page, not by embedded signatures.
- **Real-time in-browser preview while editing templates**. *Why:* template development renders through the normal generation path.
- **Archival PDF/A compliance**. *Why:* post-MVP depth with no requesting regulation behind it.
- **Progressive or streaming multi-page rendering**. *Why:* MVP documents are bounded in size; chunked rendering is unneeded complexity.

---

## 3. User Stories / Use Cases

Single-document flows are verified against real rendering; batch flows are verified through queued workers with faked queues asserting dispatch.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-PDF-001 | System renders a student's certificate PDF after assessments finalize | P0 | F | Full |
| UC-PDF-002 | Admin downloads a grade-card PDF from a finalized report | P0 | F | Full |
| UC-PDF-003 | System issues a cohort's certificates as a queued batch with progress and failure logging | P0 | F | Full |

### 3.1 Rendering Journeys

#### UC-PDF-001 — The First Certificate of the Graduation Week

Assessments finalize on Monday morning and the coordinator issues the first certificate of the season. Issuance hands the student's data to the certificate renderer, the Blade template fills the familiar layout — school crest, holder name, program dates, certificate number — and the resulting PDF lands on the certificate record ready for download and printing. By Friday the same path has produced four hundred siblings, and the first still looks exactly like the last, because neither the template nor the data assembly changed in between.

#### UC-PDF-002 — An Admin Prints Grade Cards Before the Ceremony

The day before the closing ceremony, an admin opens a finalized report and clicks the download control. The document renderer assembles the grade data, fills the grade-card template, and returns the file as a download response within the request — no job, no polling, no "your document is being prepared" screen. The whole interaction fits inside a coffee break because a single bounded document is the workload the synchronous path was built for.

#### UC-PDF-003 — Fifty Certificates Without Fifty Refreshes

Nobody clicks "generate" fifty times. The coordinator selects the cohort, confirms issuance, and a single batch job fans out across queue workers — each certificate rendered independently, each stored against its record, progress visible while the coordinator does other work. Two renders fail on corrupt template data; they are logged with context and retried after the fix while the forty-eight successes stand untouched. The HTTP request that started all of this returned in under a second; the workers took the minutes.

---

## 4. Functional Requirements

Actions delegate rendering to renderer services and never touch the engine directly. `Priority` ranks criticality; `Layer` declares the verifying test layer; `Status` tracks this requirement's implementation.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-PDF-001 | All PDF rendering uses Dompdf with remote resource fetching disabled | P0 | A | Full |
| FR-PDF-002 | Templates are Blade views under `resources/views/pdf/` inheriting the shared PDF layout | P0 | A | Full |
| FR-PDF-003 | Certificate rendering is encapsulated in a dedicated certificate renderer service | P0 | F | Full |
| FR-PDF-004 | General document rendering is encapsulated in a dedicated document renderer service | P0 | F | Full |
| FR-PDF-005 | Generated PDFs persist through the media pipeline for lifecycle management | P0 | F | Full |
| FR-PDF-006 | Batch and large rendering jobs dispatch to the queue and never run inside the HTTP request | P0 | F | Full |
| FR-PDF-007 | Single-document downloads render synchronously and return as download responses | P0 | F | Full |
| FR-PDF-008 | Templates render all user-facing strings through the translation helper | P1 | F | Full |
| FR-PDF-009 | Template data is validated before rendering; failures reject with a translatable message and persist no partial file | P0 | F | Full |
| FR-PDF-010 | Generation endpoints are role-gated and audit-logged with PII masking | P0 | F | Full |
| FR-PDF-011 | Certificate output renders the school logo, the certificate number, and the issuance-supplied verification reference | P1 | F | Full |

### 4.1 Engine and Templates

#### FR-PDF-001 — One Engine, Fetches Off

Dompdf's remote-fetching switch is the kind of default that stays harmless until the day a template includes a user-influenced image URL and the renderer becomes a server-side request forgery vector phoning home on every certificate. The configuration therefore locks remote fetching off for every render, with local assets and embedded fonts covering everything MVP documents need. Any future template that genuinely requires a remote resource earns it through an explicit, reviewed exception — not through a relaxed global.

#### FR-PDF-002 — Templates Inherit, They Do Not Improvise

A new PDF feature starts from the shared layout — page geometry, font stack, header and footer slots, status styling — and contributes only its content block. The practical effect shows up the term the school rebrands: one layout edit updates every document at once instead of sending a developer hunting through a dozen views. A template found outside the PDF directory, or one that duplicates the layout's styles inline, is a review-blocking smell regardless of how good its output looks.

#### FR-PDF-008 — The Certificate That Speaks Two Languages

Graduation booklets print in Indonesian; the verification-facing certificate copy serves employers who read English. Both come from the same template because every string passes through the translation helper, with the locale resolved per render rather than per deployment. The incident this prevents is painfully concrete: a hardcoded heading that reads correctly in one language's ceremony and wrong in the other's, discovered only when the printed stack is already bound.

### 4.2 Renderer Services

#### FR-PDF-003 — Certificates Get Their Own Renderer

Certificate rendering accumulates quirks — crest placement, number formatting, the verification reference, signature blocks — that have no business inside the issuance Action and no reuse value for grade cards. The dedicated renderer owns all of it: it accepts the certificate's data, fills the certificate template, and returns bytes, while issuance stays focused on eligibility and state transitions. When the school redesigns the certificate next year, the diff touches the renderer and the template, and issuance tests do not even notice.

#### FR-PDF-004 — Everything Else Shares the Document Renderer

Grade cards, official letters, and future document kinds share one generic renderer that takes a template name plus data and returns bytes. The alternative — a renderer class per document — would multiply near-identical wrappers around the same three Dompdf calls, each drifting slightly with every engine upgrade. Generic here does not mean sloppy: the renderer still validates its inputs and still returns errors the caller can act on, it simply refuses to pretend each document is a special case.

#### FR-PDF-009 — Validate First, Render Never on Bad Data

Rendering is where missing data becomes most embarrassing — a null holder name typeset in the middle of a certificate, discovered after printing. Template data is therefore validated before the engine starts, against the same shared rules the domain uses, and a failure rejects with a translatable explanation while persisting nothing: no half-written PDF, no media row pointing at garbage, no certificate record claiming an artifact that does not exist. The render is all-or-nothing by contract, not by luck.

### 4.3 Delivery and Governance

#### FR-PDF-005 — Rendered Files Are Media, Not Loose Paths

A PDF written to an ad-hoc path with its location stored in a text column silently opts out of everything the media pipeline guarantees — no lifecycle cleanup, no safe naming, no delivery mediation. Renderers therefore persist through collections like any other upload, which makes the withdrawn-student story hold for generated documents too: deleting the certificate record removes its PDF. Storage of output is not a renderer decision; it is pipeline behavior the renderer inherits.

#### FR-PDF-006 — The Batch Always Leaves the Request

The rule admits no threshold debate: anything beyond a single bounded document dispatches to the queue. Cohort issuance, archive regeneration, bulk reprints — all of them run on workers with their own memory budget and retry policy, reporting progress where operators can see it. The HTTP request's job ends at validation and dispatch, which is why the coordinator's browser never spins, times out, or tempts a double-submit that would issue everything twice.

#### FR-PDF-007 — The Single Download Stays Put

Queueing a one-page grade card would trade a two-second download for a polling UI, a job-status endpoint, and a notification nobody asked for — machinery whose failure modes dwarf the problem it solves. Single bounded documents render inside the request and return as downloads, with the engine's time budget keeping the interaction honest. If a "single" document ever grows unbounded, the fix is to reclassify it as batch work, not to stretch this path.

#### FR-PDF-010 — Whose Document, Who Asked, Written Down

Generation endpoints answer two questions before rendering a byte: is this actor allowed to produce this document, and will the audit trail remember they did. Policy gates the request, the Action re-checks the business rule, and the accepted generation writes a masked entry — actor, document kind, outcome — with holder details redacted. A certificate that exists without a corresponding audit entry is treated as suspect, because in a dispute the log is the only witness that cannot be reprinted.

#### FR-PDF-011 — The Three Marks of an Official Certificate

An official certificate carries three non-negotiable marks: the school's logo, the unique certificate number, and the verification reference that ties the paper to its [QR verification page](J0M05-certificate-qr-verify.md). All three arrive as issuance-supplied data, never as renderer inventions — the renderer typesets what issuance attests. A certificate missing any of the three fails review even if it renders beautifully, because beauty is not what makes it official.

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO; structural rows read `N/A` and are verified via tests and review rather than measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-PDF-001 | Single-document rendering completes within the synchronous interaction budget | < 10 s per document | P1 | F | Full |
| NFR-PDF-002 | Generated documents stay emailable and printable without compression heroics | < 5 MB per file | P1 | F | Full |
| NFR-PDF-003 | Batch rendering never executes inside the HTTP request cycle | N/A (structural) | P0 | F | Full |
| NFR-PDF-004 | Printed output matches the approved template, with embedded fonts independent of viewer or printer | Visual parity on spot check | P1 | F | Full |

### 5.1 Rendering Guarantees

#### NFR-PDF-001 — Ten Seconds, Then It Is Batch Work

The synchronous path holds a worker and a user's attention simultaneously, and both are finite. Ten seconds bounds that commitment: anything rendering slower is, by definition, work that belongs on the queue, and the number is what forces that classification argument into the open. The budget is generous on purpose — it accommodates shared-hosting reality — which makes exceeding it a genuine signal rather than infrastructure noise.

#### NFR-PDF-002 — Files That Survive Email

A certificate that cannot pass through a standard mailbox attachment limit is a certificate the graduate cannot send to an employer, which rather defeats the purpose. Generated files stay comfortably below common attachment ceilings through font subsetting and sane image resolution, without any post-processing pipeline. Bloat here is treated as a defect in the template (uncompressed crest image, embedded full font) rather than as a fact of life.

#### NFR-PDF-003 — The Request Cycle Is Sacred Ground for Renders

This is the structural twin of FR-PDF-006, stated as a guarantee rather than a behavior: no code path reachable from an HTTP request may synchronously render more than one document, and the test suite asserts dispatch rather than output for batch flows. The memory-exhaustion crash from PS-1 becomes unrepresentable instead of merely unlikely — the shape of code that caused it cannot pass review, because the suite fails first.

#### NFR-PDF-004 — Print Is the Real Renderer

Nobody frames a PDF viewer; the document that matters is the one that comes out of the school's printer the night before the ceremony. Fonts travel embedded so the output survives machines that never heard of the template's typeface, and layout is verified against paper — margins, crest alignment, signature spacing — rather than against a screen. The incident behind this row is the batch that looked perfect in the browser and bled off the page on print, discovered with hours to spare and never to be repeated.

---

## 6. API / Data Contracts

### CertificateRenderer

```php
final class CertificateRenderer
{
    public function __construct(
        private MediaStorage $media,
    ) {}

    public function render(CertificateData $certificate): string
    {
        // Fill resources/views/pdf/certificate.blade.php; return PDF bytes.
    }

    public function renderToStorage(CertificateData $certificate): Media
    {
        // render() + persist through the media pipeline.
    }
}
```

### DocumentRenderer

```php
final class DocumentRenderer
{
    public function render(string $template, DocumentData $data): string
    {
        // Fill resources/views/pdf/{template}.blade.php; return PDF bytes.
    }

    public function renderToDownload(string $template, DocumentData $data, string $filename): Response
    {
        // render() + return as download response.
    }
}
```

Renderers are infrastructure services: they accept validated data objects, never raw requests or models, and they perform no authorization — callers (Actions) own validation and policy.

### Blade Template Structure

```html
<!-- resources/views/pdf/certificate.blade.php -->
@extends('pdf.layout')

@section('content')
    <h1>{{ __('certificate.title') }}</h1>
    <p>{{ $holderName }}</p>
@endsection
```

### Dompdf Configuration

```php
// config/dompdf.php
'isRemoteEnabled' => false,
'isHtml5ParserEnabled' => true,
'defaultFont' => 'DejaVu Sans',
'isFontSubsettingEnabled' => true,
```

### Batch Job Shape

```php
final class BatchIssueCertificatesJob implements ShouldQueue
{
    public function handle(): void; // one render per certificate, failures logged with context
}
```

Progress is reported through the queue's tracking; completion notifies the initiating admin.

---

## 7. Design Decisions

Decisions are recorded reasoning, not test rows; `Layer` / `Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-PDF-001 | Blade templates with a shared layout instead of hand-built HTML strings | P0 | — | — |
| DD-PDF-002 | Dedicated renderer services instead of rendering logic inside Actions | P0 | — | — |
| DD-PDF-003 | Synchronous singles with queued batches instead of one path for both | P0 | — | — |

### 7.1 Rendering Design

#### DD-PDF-001 — Templates, Not String Concatenation

Hand-built HTML in PHP forfeits everything Blade provides — localization, conditionals, loops, layout inheritance — in exchange for saving one view file, and the savings evaporate the first time a template needs an `if`. Blade compilation costs roughly a tenth of a second per render, invisible inside budgets measured in seconds. The shared layout is the deeper win: it turns "every document looks institutional" from a per-author discipline into a default nobody has to remember.

#### DD-PDF-002 — Rendering Lives Beside the Domain, Not Inside It

An Action that renders PDFs mixes two rates of change: business rules that shift with policy, and layouts that shift with taste. Separating them means each changes for its own reasons — the issuance Action learns a new eligibility rule without touching a view, the designer moves the crest without touching a test. The cost is two small service classes with crisp signatures, which the codebase absorbs without noticing.

#### DD-PDF-003 — Two Workloads, Two Paths, No Compromise Shape

A single rendering path would either make downloads wait behind queue machinery or make batches gamble on request timeouts — each workload pays for the other's needs. Splitting them lets each be honest: the synchronous path optimizes for latency with a hard budget, the queued path optimizes for throughput with retries and progress. The queue infrastructure already exists for mail and notifications, so the batch path adds a job class, not a platform.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Single-document render time | < 10 s | Feature-test timing on reference templates |
| Batch throughput on one worker | ≥ 5 certificates/min | Queued batch drill per release |
| Render success rate | 99.9% excluding invalid input | Worker failure logs per period |
| Memory-related worker failures | 0 | Queue failure log review |
| Print parity on approved templates | No visible deviation | Paper spot check per template change |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [file-uploads-media](WQGTP-file-uploads-media.md) | Media collections and lifecycle for rendered files |

### Build Guide

Build the engine configuration and shared layout first, then the two renderers, then wire issuance and reports onto them. Batch job behavior (retry, progress, failure logging) is specified here and implemented against the queue infrastructure — coordinate template data shapes with the owning features as they land.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [reports](R6BMW-reports.md) | Grade-card snapshots render through this pipeline |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If Dompdf's CSS subset cannot express a future approved design (flexbox layouts, complex tables), templates degrade or the engine choice is revisited | Open | Maintainer | — |
| A-1 | We assume MVP documents stay bounded (single certificate, single grade card) so the synchronous path never faces paginated bulk output | Accepted | Maintainer | — |

---

## Quick References

- [File uploads & media](WQGTP-file-uploads-media.md) — collections, lifecycle, and delivery for rendered files
- [Certificate QR verify](J0M05-certificate-qr-verify.md) — verification reference rendered onto certificates
- [Certification](J0M04-certification.md) — issuance flow that triggers certificate rendering
- [Reports](R6BMW-reports.md) — grade-card snapshots consuming this pipeline
- [Job & queue infrastructure](8FVZA-job-queue-infrastructure.md) — workers, retries, and progress for batch jobs
- [Base classes](SE5Q9-base-classes.md) — Action and service contracts renderers build on
- [Spec registry](index.md) — Certification phase ordering
