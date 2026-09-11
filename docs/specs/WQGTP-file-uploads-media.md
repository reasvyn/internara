# File Uploads & Media — Spatie MediaLibrary Integration

> **Spec ID:** WQGTP
> **Status:** Full
> **Owner:** Core
> **Depends on:** SE5Q9

## Description

Every module that accepts a file — profile photos, handbook PDFs, certificate templates, company logos, partnership documents — funnels through one media infrastructure built on Spatie MediaLibrary: named collections with per-collection rules, validated and safely stored files, automatic thumbnails, and lifecycle cleanup. This spec fixes the upload contract so that no module invents its own storage logic. Rendered PDFs ride on top of this infrastructure per [pdf-generation](7UB7S-pdf-generation.md).

---

## 1. Problem Statements

### PS-1 — Every Module Reinvents Uploads, Badly

Left alone, each module writes its own upload handler: one stores under `public/uploads` with the user's original filename, another forgets MIME validation entirely, a third keeps files nobody can find again. The result is a patchwork where the weakest handler defines the application's security posture. One validated pipeline, owned here, removes the choice.
**→ Requirement:** FR-UPL-001 (single abstraction), FR-UPL-003/004/005 (validation trio).

### PS-2 — Full-Resolution Files Served Where Thumbnails Belong

A 4 MB profile photo rendered at 40 pixels in a student roster forces every visitor to download the whole file. Across a 500-student placement list that is hundreds of megabytes per page view — on school connections that bill by the gigabyte, this is a budget line, not a nicety.
**→ Requirement:** FR-UPL-008 (thumbnail conversions).

### PS-3 — Deleted Records Leave Files Behind Forever

A student withdraws, their record is removed, and their uploaded documents sit on disk indefinitely — invisible, unowned, accumulating. On a $5 shared host with a fixed quota, orphaned files are a slow-motion outage, and under data-erasure expectations they are a compliance problem too.
**→ Requirement:** FR-UPL-009 (deletion cleanup), NFR-UPL-004 (zero orphans).

### PS-4 — Guessable File URLs Turn Storage into a Browsable Archive

Sequential filenames under a public path (`/uploads/1.pdf`, `/uploads/2.pdf`) invite exactly the attack a school cannot afford: incrementing a URL to read another student's documents. Storage location and naming are therefore security decisions, not cosmetic ones.
**→ Requirement:** FR-UPL-006 (outside web root), FR-UPL-007 (non-guessable names).

---

## 2. Goals & Non-Goals

### Goals

- **One upload pipeline for all modules** — every file flows through MediaLibrary behind Command Actions. *Why:* a single validated path replaces a dozen hand-rolled handlers.
- **Per-purpose collections with their own rules** — a profile photo and a certificate template obey different constraints by construction. *Why:* MIME and size rules differ per document kind; scoping them prevents cross-purpose mistakes.
- **Validated, sanitized, safely stored bytes** — MIME allowlist, size cap, filename safety, outside-web-root storage, non-guessable names. *Why:* uploads are the application's largest untrusted-input surface.
- **Automatic thumbnails for images** — small renditions generated at upload time. *Why:* list and card views must not download full-resolution originals.
- **Lifecycle cleanup** — deleting a record deletes its files. *Why:* unbounded orphan growth kills fixed-quota hosts.
- **Atomic failure semantics** — a rejected upload changes nothing. *Why:* half-stored files and phantom records corrupt both disk and database.

### Non-Goals

- **Virus scanning of uploaded files**. *Why:* no scanning infrastructure at MVP; MIME and size validation plus safe storage carry the risk.
- **In-app image editing beyond thumbnails**. *Why:* cropping and filters are editor features, not infrastructure.
- **Cloud synchronization or CDN distribution**. *Why:* local disk is the MVP target; remote disks arrive via configuration when needed.
- **Real-time preview without download**. *Why:* browser rendering of the stored file suffices.
- **File versioning or revision history**. *Why:* collections hold the current file; history is post-MVP depth.

---

## 3. User Stories / Use Cases

Upload journeys run through Livewire into Command Actions; cleanup is verified against the real disk.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-UPL-001 | Student uploads a profile photo and sees it rendered with thumbnails | P0 | F | Full |
| UC-UPL-002 | Admin uploads a document template that becomes available for generation | P0 | F | Full |
| UC-UPL-003 | Deleting a record removes its files and conversions so nothing orphans | P0 | F | Full |

### 3.1 Upload and Lifecycle Journeys

#### UC-UPL-001 — A Student Uploads a Profile Photo on a Phone

A student on a mid-range Android phone picks a gallery photo for their profile. The file is a 3 MB JPEG with a camera-default name full of spaces and parentheses; the pipeline validates it against the profile-images collection, stores it under a generated name the student never sees, and produces the small renditions the roster and the profile header actually render. When the student revisits the roster on mobile data, the page loads thumbnails measured in kilobytes, and the original remains available only where full resolution is genuinely needed.

#### UC-UPL-002 — An Admin Registers a New Handbook Template

The academic office finalizes this year's student handbook as a PDF and registers it as the active template. Upload runs the document-templates rules — PDF only, within the size cap — and the stored file becomes the source the generation pipeline reads from, linked to its document type rather than floating as an attachment. Replacing the template next year follows the same path: the old file is superseded through the collection, never left as a stale copy someone might generate from by accident.

#### UC-UPL-003 — The Withdrawn Student Leaves No Files Behind

Mid-semester, a student's record is removed after withdrawal. In the same operation, every file the student ever uploaded through the system — profile photo with its thumbnails, assignment attachments, evidence photos — disappears from disk along with their database rows. An administrator checking the storage directory afterward finds no trace: no orphaned originals, no forgotten conversions, no directory of the unowned slowly filling the quota. Erasure here is a property of the pipeline, not a cleanup chore someone must remember.

---

## 4. Functional Requirements

Uploads execute inside Command Actions (transaction plus audit log); reads of media URLs never mutate. `Priority` ranks criticality; `Layer` declares the verifying test layer; `Status` tracks this requirement's implementation.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-UPL-001 | All file uploads flow through Spatie MediaLibrary via the `InteractsWithMedia` contract | P0 | A | Full |
| FR-UPL-002 | Files are organized into named collections per module purpose following the `{module}.{purpose}` convention | P0 | A | Full |
| FR-UPL-003 | Each collection enforces a MIME allowlist; anything outside it is rejected before storage | P0 | F | Full |
| FR-UPL-004 | Each collection enforces a configurable maximum file size; oversized uploads are rejected before storage | P0 | F | Full |
| FR-UPL-005 | Original client filenames are sanitized and never used as storage paths | P0 | F | Full |
| FR-UPL-006 | Files are stored outside the web root on the configured disk | P0 | F | Full |
| FR-UPL-007 | Stored filenames are non-guessable tokens that cannot be derived from record IDs | P0 | F | Full |
| FR-UPL-008 | Image collections generate thumbnail conversions at upload time | P1 | F | Full |
| FR-UPL-009 | Deleting a record deletes its media files and conversions with it | P0 | F | Full |
| FR-UPL-010 | A rejected upload creates no media record and changes no model state, surfacing a translatable rejection | P0 | F | Full |
| FR-UPL-011 | Upload and deletion operations are role-gated and audit-logged with PII masking | P0 | F | Full |
| FR-UPL-012 | All upload-facing strings render through the translation helper in both locales | P1 | A | Full |

### 4.1 Single Abstraction and Collections

#### FR-UPL-001 — One Pipeline, No Hand-Rolled Handlers

Before this contract, the fastest way to accept a file was `store()` in a Livewire component — five lines, no validation, no collection, no cleanup, and no trace in the audit log. That shortcut is now a structural violation: every upload travels through MediaLibrary inside a Command Action, which buys transactions, logging, and events for free. A reviewer checking a new upload feature asks exactly one question — does it go through the pipeline — and the arch scans answer it mechanically.

#### FR-UPL-002 — Collections Scope the Rules to the Purpose

A profile photo and a partnership document share nothing except being files, so they share no collection either. Each module declares named collections per purpose, carrying that purpose's MIME set and size cap, which means a misconfiguration is contained: loosening the logo collection cannot accidentally admit executables into certificate templates. The naming convention keeps the registry greppable — an unfamiliar collection name tells any developer which module owns it and what it holds.

### 4.2 Validation and Safe Storage

#### FR-UPL-003 — The MIME Allowlist Is the Bouncer

Extension checks are theater — renaming a script with a `.jpg` suffix fools them every time — so each collection validates the actual content type against its allowlist before a byte reaches permanent storage. The allowlists are deliberately narrow per collection: images admit image types, document collections admit document types, and nothing admits executables, archives, or HTML. When a new document kind needs support, its collection gains an entry through a considered change, not through a loosened global.

#### FR-UPL-004 — Size Caps Before the Disk Feels It

On a shared host, one unbounded upload endpoint is a denial-of-service invitation extended to every authenticated user. Each collection therefore declares its own ceiling — small for photos and logos, larger for handbook PDFs — and anything above it is refused before storage, before conversion, before the database hears about it. The caps live in configuration rather than code so a school that genuinely needs larger templates adjusts a value instead of forking behavior.

#### FR-UPL-005 — Client Filenames Are Untrusted Input

The filename arriving with an upload is attacker-controlled text wearing a friendly face: path separators, null bytes, Unicode homoglyphs, and names crafted to collide with system files have all appeared in real upload attacks. The pipeline treats it accordingly — the original name may be remembered as display metadata, but it never touches the filesystem, and the stored path is built entirely from server-generated components. A file named to escape its directory ends its journey as a harmless string in a column.

#### FR-UPL-006 — Below the Web Root, Behind the Application

Files stored under the public path are served by the web server directly, bypassing every policy, throttle, and audit hook the application owns — that is precisely how a misconfigured directory becomes a free file host. Media therefore lives outside the web root on the configured disk, and every download passes through application code that can authorize, log, and throttle it. Switching disks (local today, remote tomorrow) changes configuration, never call sites.

#### FR-UPL-007 — Names Nobody Can Walk

Sequential or ID-derived filenames turn any one shared link into a map of the whole archive: change the number, read someone else's document. Stored names are unguessable tokens drawn from a space no enumeration can dent, with no relationship to record IDs, timestamps, or upload order. Public delivery URLs are derived per entity rather than exposing the storage layout, so even a leaked URL teaches nothing about its neighbors.

### 4.3 Conversions, Lifecycle, and Governance

#### FR-UPL-008 — Thumbnails Exist Before the Next Request Needs Them

The scenario this requirement forecloses is depressingly common: the upload succeeds, the page refreshes, and the roster shows a broken image because the thumbnail job has not run yet. Conversions for image collections generate synchronously at upload time, so by the time the Action returns success the renditions are on disk and addressable. The small upload-time cost is the price of never showing a placeholder where a photo belongs.

#### FR-UPL-009 — Deletion Means Deletion, Files Included

MediaLibrary's lifecycle hooks bind each file's fate to its owning record: when the record goes, the originals and every conversion go with them in the same operation. This is what makes the withdrawn-student story in UC-UPL-003 structural rather than aspirational — no scheduled sweeper to configure, no orphan report to review, no grace period during which erased data lingers. A deletion that leaves files behind is not partial success; it is this requirement failing.

#### FR-UPL-010 — Rejection Leaves No Footprint

Consider the oversized upload that fails validation after the file already landed in a temp path and a media row already recorded the attempt: the disk holds garbage, the database holds a lie, and the user sees an error for an upload the system half-remembers. The pipeline orders validation before any persistent side effect, and a rejection returns a translatable explanation with the system exactly as it was — no media row, no state change, no temp file left to rot. Atomicity here is verified by asserting the before-and-after, not just the error message.

#### FR-UPL-011 — Who May Upload, Written Down and Logged

Upload and deletion endpoints sit behind the same dual authorization as everything else: policies gate the request, the Action re-validates the business rule, and violations surface as rejections rather than silent 403s where the domain owns the decision. Every accepted operation writes a masked audit entry — actor, collection, outcome — with filenames and personal details redacted, so a later question about who replaced the active handbook template has an answer that is itself safe to read.

#### FR-UPL-012 — Error Messages in the User's Language

An upload rejection is a conversation with a frustrated user — a student on a phone, an admin against a deadline — and it must happen in their language. Every user-facing string in the upload path passes through the translation helper with mirrored keys, including validation messages, size-limit explanations, and success confirmations. Dynamic values (the limit, the offending type) arrive as placeholders, never as concatenated fragments that translators cannot reorder.

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO; all rows below carry measurable targets verified by feature tests.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-UPL-001 | Image thumbnail conversions complete within the upload request | ≤ 5 s per image | P1 | F | Full |
| NFR-UPL-002 | The pipeline accepts files up to the largest collection cap without worker changes | 10 MB max file | P1 | F | Full |
| NFR-UPL-003 | Large-file downloads stream to the client without holding the worker in memory | Constant memory, no full buffering | P1 | F | Full |
| NFR-UPL-004 | No orphaned files remain after record deletion | 0 orphaned files | P0 | F | Full |

### 5.1 Performance and Reliability

#### NFR-UPL-001 — Thumbnails Keep Pace with the Request

A student uploading a photo expects to see it, not a spinner followed by a retry. Because conversions run synchronously inside the upload (FR-UPL-008), they inherit a deadline: each image's renditions must be ready within seconds, or the synchronous choice was wrong for that file class. If a future collection admits multi-megapixel originals that cannot convert in time, that collection moves to queued conversion explicitly — the deadline is what forces the conversation.

#### NFR-UPL-002 — Ten Megabytes Without Ceremony

The largest collection cap defines the pipeline's promise: a handbook PDF at the ceiling must upload, validate, store, and link without raising memory limits, restarting workers, or touching server configuration. This is the shared-hosting reality check — the schools this system serves cannot tune PHP for one upload — so the ceiling is validated on Tier-1-shaped infrastructure, not on a developer laptop with gigabytes to spare.

#### NFR-UPL-003 — Downloads That Do Not Swallow the Worker

Serving a 10 MB handbook by loading it into memory holds a PHP worker hostage for the duration of a slow mobile download, and a handful of concurrent downloads exhausts a small server's worker pool entirely. Large files therefore stream — read in chunks, constant memory — so the worker's footprint stays flat regardless of file size or client speed. The failure mode this retires is the mysterious midday slowdown that turned out to be three simultaneous handbook downloads.

#### NFR-UPL-004 — Zero Orphans as a Standing Invariant

After any record deletion, the count of files on disk without an owning media row must be exactly zero — not "cleaned up nightly", not "eventually consistent", but zero when the operation returns. The number is checked by comparing the media table against the storage directory after deletion flows, and any positive count is a defect in FR-UPL-009 rather than acceptable drift. On fixed-quota hosting there is no slack for "a few orphans"; the invariant is the quota protection.

---

## 6. API / Data Contracts

### MediaLibrary Integration on Model

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document.templates')
            ->acceptsMimeTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }
}
```

Size caps and disk selection live in module configuration, not in the collection definition, so schools adjust values without code changes.

### Collection Registry

| Module | Collection | Accepted Types | Max Size |
|--------|-----------|----------------|----------|
| User | `user.avatars` | JPEG, PNG, WebP | 2 MB |
| Document | `document.templates` | PDF, DOCX | 10 MB |
| Document | `document.handbooks` | PDF | 10 MB |
| Certification | `certification.templates` | PDF | 10 MB |
| Partners | `partners.logos` | JPEG, PNG, SVG | 2 MB |
| Partners | `partners.mou` | PDF | 10 MB |

### Storage Disk Configuration

```php
// config/filesystems.php
'disks' => [
    'media' => [
        'driver' => 'local',
        'root' => storage_path('app/media'),
    ],
],
```

The media root sits outside the web root; delivery URLs are entity-derived and never expose the storage layout.

### Upload Action Shape

```php
final class StoreDocumentMediaAction extends BaseCommandAction
{
    public function execute(StoreMediaData $data): ActionResponse;
}
```

Validation (MIME, size, filename safety) runs inside `execute()` on the DTO; violations throw the domain rejection with a translatable message before any file is persisted.

---

## 7. Design Decisions

Decisions are recorded reasoning, not test rows; `Layer` / `Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-UPL-001 | MediaLibrary as the single storage abstraction instead of the raw Storage facade | P0 | — | — |
| DD-UPL-002 | One named collection per module purpose instead of a global bucket | P0 | — | — |
| DD-UPL-003 | Synchronous thumbnail conversion for MVP-size images; queued conversion when a collection outgrows it | P1 | — | — |
| DD-UPL-004 | Outside-web-root storage with non-guessable names instead of public-path files | P0 | — | — |

### 7.1 Storage Design

#### DD-UPL-001 — A Library Instead of a Facade

Raw storage calls scatter four concerns — validation, naming, conversions, cleanup — across every call site, and each new uploader re-solves all four slightly differently. MediaLibrary centralizes them behind collections and lifecycle hooks, so the per-feature upload code shrinks to declaring a collection and calling the pipeline. The dependency and its small per-operation overhead are the price of never auditing twelve bespoke handlers again.

#### DD-UPL-002 — Small Buckets with Their Own Rules

A single global uploads bucket would need the union of every rule — the most permissive MIME set, the largest size cap — which is another way of saying no rules at all. Per-purpose collections invert this: each bucket carries exactly the constraints its document kind needs, and a change to one purpose cannot leak into another. The maintenance cost is a handful of five-line declarations, each readable in isolation.

#### DD-UPL-003 — Sync by Default, Queue by Evidence

Thumbnails needed at page-refresh time cannot wait for a worker that a shared host may not even run, so MVP-size image conversions happen inside the upload request. This is explicitly conditional rather than dogmatic: the deadline in NFR-UPL-001 is the tripwire, and the first collection whose originals cannot convert in time moves to queued conversion with placeholder rendering. The default serves today's files; the tripwire serves tomorrow's.

#### DD-UPL-004 — Files the Web Server Cannot See

Serving uploads straight from the public path feels efficient right up until the first enumeration incident, after which every file needs application-mediated delivery anyway. Storing outside the web root with generated names pays that cost once, up front: authorization, logging, and throttling apply to downloads exactly as they apply to pages, and no deployment-time directory permission can silently re-expose the archive. The QR verification page in [certificate-qr-verify](J0M05-certificate-qr-verify.md) relies on the same principle — readability through the application, never around it.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Orphaned files after deletion flows | 0 | Storage-vs-table reconciliation test |
| Thumbnail availability at page refresh | 100% synchronous readiness | Upload-then-render feature test |
| Rejected-upload footprint | No media row, no state change | Before/after assertion per rejection |
| Invalid MIME penetration | 0 stored files outside allowlists | Adversarial upload test set |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes](SE5Q9-base-classes.md) | `BaseCommandAction` (transaction, audit log) for upload operations |

### Build Guide

Build the pipeline before any module that accepts files; logbook evidence, assignment submissions, and certificate assets all depend on it. Collections are added per module as those features land — this spec owns the mechanism, the features own their declarations.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [pdf-generation](7UB7S-pdf-generation.md) | Rendered PDFs persist as media through this pipeline |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If SVG logos are admitted, embedded scripts in SVG content could execute where files are previewed inline; mitigation is MIME validation plus `Content-Disposition: attachment` or sanitization before any inline rendering | Open | Maintainer | — |
| A-1 | We assume local-disk media storage suffices for MVP scale and that shared-host quotas tolerate the thumbnail multiplier per image | Accepted | Maintainer | — |

---

## Quick References

- [PDF generation](7UB7S-pdf-generation.md) — rendered documents persist via this pipeline
- [Certificate QR verify](J0M05-certificate-qr-verify.md) — entity-derived public delivery precedent
- [Base classes](SE5Q9-base-classes.md) — `BaseCommandAction` contract for upload operations
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — dual-layer authorization on upload endpoints
- [Logging & error handling](89SRA-logging-and-error-handling.md) — masked audit logging, rejection contract
- [Spec registry](index.md) — Certification phase ordering
