# Certification — Certificates, Templates & QR

## Description

Certificate generation, serial numbering, digital QR signature, public verification, and credential
revocation.

## Purpose & Boundary

Certification manages the issuance of internship completion certificates. Certificates are awarded
after a student's final grade card is finalized and locked in the Reports module. Each certificate
embeds a QR code carrying a verification hash that resolves to a public status page, so a holder
can be checked against the school's own record. Certificates can be revoked with an audit trail,
and revocation is terminal — serial numbers are permanently retired.

Out of scope: grade calculation (Reports), document templates (Document), evaluation feedback
(Evaluation).

## Submodules

### Certificate

Core entity: serial number (auto-generated), recipient name, program details, issue date, embedded
HTML layout snapshot (frozen at issuance for tamper-proof rendering), QR code hash, and status
(`issued` | `revoked`). Linked to the Registration record and the admin who authorized issuance.
Batch issuance for entire cohorts via `BatchIssueCertificateAction`.

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

### Embedded Layout Snapshots

Certificate layouts (portrait/landscape orientation, background seals, text placeholders) are rendered dynamically at issuance time and frozen as immutable HTML snapshots within the certificate record. This ensures certificates always render exactly as they were at issuance, even if templates change later.

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
- Reports (finalized grade card prerequisite)
- Enrollment (registration context)
- User (recipient and issuer identity)
- Settings (institution code for hash generation)

## Used By

- Student certificate list and admin issuance UI

## Design Principles

- **Eligibility gating is specified but not yet enforced** — FR-CERT-010/011 require assessment completion, placement verification, and handbook acknowledgment to be checked before issuance. Today `IssueCertificateAction` issues without that gate and only the UI filters candidate registrations. Do not describe certificates as eligibility-gated until the Action rejects ineligible registrations with `RejectedException`.
- **The verification hash is a fingerprint, not a signature** — the QR code encodes a SHA-256 digest over student ID, school name, report score, issuing admin ID, and certificate number. Because there is no private key and the digest is not reproducible by the verifier, verification requires the server-side `qr_hash` row; the database row is the truth.
- **Revocation is terminal, and a second attempt is rejected** — once revoked, a certificate stays revoked and its serial number is permanently retired. Double-revoke raises `RejectedException` (`certificate.already_revoked`) instead of silently succeeding; re-issuance requires a new serial number and a new record. Never mutate revocation state; it is one-way.
- **Frozen layout snapshots** — at issuance, the rendered HTML layout is snapshotted into the certificate record. Later template edits must never alter already-issued certificates; the snapshot is the legal artifact. Treat issuance as a write-once event.

## How It Works

A certificate is the terminal artifact of the whole internship, and everything about its design
follows from one requirement: a printed certificate has to be verifiable by a stranger, years
later. So the layout is frozen into the record at the moment of issuance — a later template change
cannot retroactively alter what a graduate was handed — and the QR code encodes a digest derived
from the student, the school, the final score, the issuing admin, and the certificate number rather
than a bare serial number. Scanning it resolves to an authoritative status answer from the school's
own record: issued, revoked, or not found.

Eligibility gating is the intended next step, not a current invariant: the specification requires a
finalized grade card and completed prerequisites, but the Action does not yet reject ineligible
registrations. Individual issuance is the exception path; batch issuance exists because a school
finishing a cohort should not have to click through hundreds of students one at a time, and a
Process Action collects per-student failures instead of aborting the run.

Revocation is a status change, never a deletion. The certificate stays in the system with its
record intact, and the public verification page reports the revocation — so a graduate holding a
withdrawn certificate is caught by a scan, while the school's own history stays auditable.

