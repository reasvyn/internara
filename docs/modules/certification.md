# Certification — Certificates, Templates & QR

> **Last updated:** 2026-07-11 **Changes:** sync — remove implementation details (Actions, Routes, File Structure) to reference doc

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
(`active` | `revoked`). Linked to the Registration record and the admin who authorized issuance.
Batch issuance for entire cohorts via non-blocking job dispatch.

## Key Concepts

### QR Cryptographic Verification

Each printed certificate displays a QR code encoding a verification URL with a cryptographic hash.
The hash is generated using SHA-256 over student ID, institutional code, final score, and issuer
private key. Public verification endpoints accept the hash and return the certificate's authenticity
status. This enables offline forgery detection without requiring database access at the verification
point.

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

Each certificate includes a QR code encoding a verification URL with SHA-256 hash. The public verification endpoint accepts the hash and returns authenticity status:

```
GET /verify/{hash}
```

**Response (JSON):**

```json
{
    "valid": true,
    "recipient": "John Doe",
    "program": "PKL 2025/2026",
    "issued_at": "2026-06-15T10:00:00Z",
    "status": "active"
}
```

### Integration Patterns

- **Reports Gate**: `IssueCertificateAction` checks for finalized Report record — throws `RejectedException` if missing
- **Queue**: Batch issuance dispatches individual jobs to the `documents` queue pipeline
- **Serial Number Management**: Auto-generated sequential numbers, unique, permanently retired on revocation
- **Event**: `CertificateIssued` event triggers notification to student and updates Pulse metrics
- **Cache**: Verification hash results cached with TTL 24h (key: `certificate.verify.{hash}`)

## Dependencies

- Core (base classes, SmartLogger)
- Reports (finalized grade card prerequisite)
- Enrollment (registration context)
- User (recipient and issuer identity)
- Settings (institution code for hash generation)

## Used By

- Public verification endpoints (no module dependency)


