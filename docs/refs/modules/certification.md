# Certification — Certificates, Templates & QR

## Description

Certificate generation, serial numbering, digital QR signature, public verification, and credential
revocation.

## Purpose & Boundary

Certification manages the issuance of internship completion certificates. Certificates are awarded
after a student's final grade card is finalized and locked in the Reports module. Each certificate
includes a cryptographically signed QR code for offline forgery detection. Certificates can be
revoked with an audit trail, and revocation is terminal — serial numbers are permanently retired.

Out of scope: grade calculation (Reports), document templates (Document), evaluation feedback
(Evaluation).

## Submodules

### Certificate

Core entity: serial number (auto-generated), recipient name, program details, issue date, embedded
HTML layout snapshot (frozen at issuance for tamper-proof rendering), QR code hash, and status
(`issued` | `revoked`). Linked to the Registration record and the admin who authorized issuance.
Batch issuance for entire cohorts via `BatchIssueCertificateAction`.

## Key Concepts

### QR Cryptographic Verification

Each printed certificate displays a QR code encoding a verification URL with a cryptographic hash.
The hash is generated using SHA-256 over student ID, institutional code, final score, and issuer
private key (`qr_hash` column). This enables offline forgery detection without requiring database
access at the verification point.

### Final Grade Prerequisite

Certificates cannot be issued unless the student's registration has a corresponding finalized Report
card record. This enforcement is at the Action layer — `IssueCertificateAction` checks for a
finalized report before proceeding. If missing, a `RejectedException` informs the operator which
students are ineligible.

### Revocation is Terminal

Once a certificate is revoked, its status is permanently set to `revoked` and its serial number is
retired. Double revocation is idempotent — attempting to revoke an already-revoked certificate is a
no-op. Re-issuance requires a new serial number and a new certificate record.

### Embedded Layout Snapshots

Certificate layouts (portrait/landscape orientation, background seals, text placeholders) are rendered dynamically at issuance time and frozen as immutable HTML snapshots within the certificate record. This ensures certificates always render exactly as they were at issuance, even if templates change later.

### Verification API

Planned but not yet routed. Each certificate carries a QR code with a SHA-256 hash; a public
verification endpoint has not been implemented:

```
GET /verify/{hash}   (not yet implemented)
```

**Planned response (JSON):**

```json
{
    "valid": true,
    "recipient": "John Doe",
    "program": "PKL 2025/2026",
    "issued_at": "2026-06-15T10:00:00Z",
    "status": "issued"
}
```

### Integration Patterns

- **Reports Gate**: `IssueCertificateAction` checks for finalized Report record — throws `RejectedException` if missing
- **Batch**: `BatchIssueCertificateAction` issues certificates for an entire cohort in a single process action
- **Serial Number Management**: Auto-generated sequential numbers, unique, permanently retired on revocation
- **Event**: `CertificateIssued` event triggers notification to student and updates Pulse metrics
- **Cache**: Verification hash caching (`certificate.verify.{hash}`) is not yet implemented — the key is not registered in `config/cache-keys.php`

## Dependencies

- Core (base classes, SmartLogger)
- Reports (finalized grade card prerequisite)
- Enrollment (registration context)
- User (recipient and issuer identity)
- Settings (institution code for hash generation)

## Used By

- Student certificate list and admin issuance UI

## Design Principles

- **Certificate issuance is gated by a finalized grade card** — no certificate may be issued without a corresponding finalized Report card. `IssueCertificateAction` is the single gate; reject with `RejectedException` and enumerate ineligible students rather than silently skipping.
- **Cryptographic hash is the verification primitive** — the QR code encodes a SHA-256 hash over student ID, institutional code, final score, and issuer private key. Verification must remain possible offline (no DB lookup required at the verification point); the hash is the truth, not the database row.
- **Revocation is terminal and idempotent** — once revoked, a certificate stays revoked and its serial number is permanently retired. Double-revoke is a no-op; re-issuance requires a new serial number and a new record. Never mutate revocation state; it is one-way.
- **Frozen layout snapshots** — at issuance, the rendered HTML layout is snapshotted into the certificate record. Later template edits must never alter already-issued certificates; the snapshot is the legal artifact. Treat issuance as a write-once event.

## How It Works

*Content to be added — verify against actual implementation.*

