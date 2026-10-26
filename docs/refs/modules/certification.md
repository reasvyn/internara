# Certification — Certificates, Templates & QR

## Description

Certificate generation, serial numbering, QR verification hash, public verification page, and
credential revocation.

## Purpose & Boundary

Certification manages the issuance of internship completion certificates. A certificate is the
terminal artifact of a completed internship: the Report module holds the grade, and this module
converts it into a serialized, publicly verifiable credential. Each certificate embeds a QR code
carrying a verification hash that resolves to a public status page, so a holder can be checked
against the school's own record. Certificates can be revoked with an audit trail, and revocation is
terminal — serial numbers are permanently retired.

Out of scope: grade calculation (Reports), document templates (Document), evaluation feedback
(Evaluation).

## Submodules

### Certificate

Certificate record: serial number (auto-generated), registration link, frozen template-content
snapshot (`template_content`), QR verification hash, status (`issued` | `revoked`), and the
issuing/revoking admin with their timestamps. Recipient and program details are not copied onto the
row — they are read through the Registration relation when the certificate is rendered. Batch
issuance for entire cohorts via `BatchIssueCertificateAction`.

## Key Concepts

### QR Verification Hash

Each printed certificate displays a QR code encoding a verification URL carrying a 64-character
hash. The hash is a SHA-256 digest — `hash('sha256', student id | school name | report score |
issuing admin id | certificate number)` — persisted in the `qr_hash` column. It is a deterministic
fingerprint of the issuance facts, not a signature: there is no private key, and verifying a
certificate requires the server-side row, because the hash alone cannot be recomputed by the
verifier. A QR code therefore proves the holder has a certificate the school issued, and the
lookup against `qr_hash` is what answers "genuine and current".

### Eligibility Gates (specified, not yet enforced)

FR-CERT-010/011 require issuance to evaluate assessment completion, placement verification, and
handbook acknowledgment before a certificate is produced. That gate is **not implemented** in
`IssueCertificateAction`: the Action reads `$registration->report?->score` (nullable) into the hash
input and issues regardless of report finalization. The only current filter is UI-level — the
single-issue picker lists registrations with `status = 'active'` and the batch picker further
excludes registrations that already have a certificate. Treat eligibility as a spec gap, not as an
existing invariant.

### Revocation is Terminal

Once a certificate is revoked, its status is permanently set to `revoked` and its serial number is
retired. Revocation is terminal, not idempotent: a second attempt on an already-revoked certificate
is rejected — `RevokeCertificateAction` throws a `RejectedException` carrying
`certificate.already_revoked`, which the Livewire confirms as an error toast. Re-issuance requires a
new serial number and a new certificate record.

### Frozen Template Snapshots

At issuance the active template's `template_content` is copied onto the certificate row, so later
edits to the shared template never alter an already-issued certificate. Rendering happens on
demand: `CertificateRenderer::resolvePlaceholders()` fills the canonical `{placeholder}` set from
the live registration (student, school, department, company, internship dates, score/letter,
certificate number, supervisor) and the result is rendered through Blade at download time. The
snapshot is therefore a frozen *template*, not frozen HTML.

### PDF Generation Defers to First Download

Batch issuance never renders PDFs. The first authorized download renders and stores the PDF; every
later download serves that stored file untouched, so the artifact a graduate received never changes
underneath them. Generation is idempotent — a missing file is produced once, an existing file is
reused — which keeps cohort issuance fast and the served document immutable.

### Public Verification Page

Implemented as an anonymous `GET /verify/{qr_hash}` route named `certificates.verify`:
`VerifyCertificateController` → `ReadCertificateStatusAction` → the `certification.verify` Blade
view. The route carries no auth middleware, is constrained to `[A-Za-z0-9]{32,64}`, and is limited
by `throttle:30,1` (30 requests per minute per IP).

The answer is an **HTML page, not a JSON API**. The action validates the hash shape with the same
pattern and returns `CertificateStatusView::notFound()` for malformed or unknown input, so a bad
link renders a clean not-found panel rather than an error:

| Status       | Rendered panel                                                            |
| ------------ | ------------------------------------------------------------------------- |
| `issued`     | success panel + holder, school, certificate number, issuance date         |
| `revoked`    | warning panel + revocation date, no success styling                       |
| `not_found`  | error panel only — no existence hints                                     |

Exposed fields are limited to holder name, school name, certificate number, issuance date, and
revocation date. Responses set `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` so a
revocation is visible on the next scan.

### Integration Patterns

- **Eligibility Gate**: specified by FR-CERT-010/011 but not enforced — see "Eligibility Gates" above
- **Single issuance**: `IssueCertificateAction` (Command Action) — transaction, audit log, then `CertificateIssued`
- **Batch**: `BatchIssueCertificateAction` (Process Action) composes single issuance per registration, collecting per-student failures instead of aborting the cohort; `DispatchBatchIssueCertificatesAction` queues it
- **Serial Number Management**: Auto-generated from internship prefix + year + zero-padded count, unique by domain generation and database constraint, permanently retired on revocation
- **Event**: `CertificateIssued` is dispatched after the transaction; no listener is registered yet (recorded as intentional in `config/event.php`)
- **Cache**: Verification hash caching (`certificate.verify.{hash}`) is not implemented — the key is not registered in `config/cache-keys.php`; every verification hits the database

## Dependencies

- Core (base classes, SmartLogger)
- Enrollment (registration context)
- Reports (report score feeds the verification hash; finalization is not enforced)
- Settings (`school.name` feeds the verification hash)
- User (recipient and issuer identity)

## Used By

- Student certificate list and admin issuance UI

## Design Principles

- **Eligibility gating is specified but not yet enforced** — FR-CERT-010/011 require assessment completion, placement verification, and handbook acknowledgment to be checked before issuance. Today `IssueCertificateAction` issues without that gate and only the UI filters candidate registrations. Do not describe certificates as eligibility-gated until the Action rejects ineligible registrations with `RejectedException`.
- **The verification hash is a fingerprint, not a signature** — the QR code encodes a SHA-256 digest over student ID, school name, report score, issuing admin ID, and certificate number. Because there is no private key and the digest is not reproducible by the verifier, verification requires the server-side `qr_hash` row; the database row is the truth.
- **Revocation is terminal, and a second attempt is rejected** — once revoked, a certificate stays revoked and its serial number is permanently retired. Double-revoke raises `RejectedException` (`certificate.already_revoked`) instead of silently succeeding; re-issuance requires a new serial number and a new record. Never mutate revocation state; it is one-way.
- **Frozen template snapshots** — at issuance, the active template's content is copied into the certificate row (`template_content`). Later template edits must never alter an already-issued certificate; rendering from that snapshot at download time is the legal artifact. Treat issuance as a write-once event.

## How It Works

A certificate is the terminal artifact of the whole internship, and everything about its design
follows from one requirement: a printed certificate has to be verifiable by a stranger, years
later. So the template content is frozen into the record at the moment of issuance — a later
template change cannot retroactively alter what a graduate was handed — and the QR code encodes a
digest derived from the student, the school name, the final score, the issuing admin, and the
certificate number rather than a bare serial number. Scanning it resolves to an authoritative
status answer from the school's own record: issued, revoked, or not found. Because the digest is
not reproducible without the server-side row, the record is the truth, not the paper.

Eligibility gating is the intended next step, not a current invariant: the specification requires a
finalized grade card and completed prerequisites, but the Action does not yet reject ineligible
registrations. Individual issuance is the exception path; batch issuance exists because a school
finishing a cohort should not have to click through hundreds of students one at a time, and a
Process Action collects per-student failures instead of aborting the run.

Revocation is a status change, never a deletion. The certificate stays in the system with its
record intact, and the public verification page reports the revocation — so a graduate holding a
withdrawn certificate is caught by a scan, while the school's own history stays auditable.

