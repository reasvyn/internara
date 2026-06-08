# Certification

> **Last updated:** 2026-06-08

Certificate generation, serial numbering, digital QR signature, public verification, and credential revocation.

## Purpose & Boundary

Certification manages the issuance of internship completion certificates. Certificates are awarded after a student's final grade card is finalized and locked in the Reports module. Each certificate includes a cryptographically signed QR code for offline forgery detection. Certificates can be revoked with an audit trail, and revocation is terminal — serial numbers are permanently retired.

Out of scope: grade calculation (Reports), document templates (Document), evaluation feedback (Evaluation).

## Submodules

### Certificate
Core entity: serial number (auto-generated), recipient name, program details, issue date, embedded HTML layout snapshot (frozen at issuance for tamper-proof rendering), QR code hash, and status (`active` | `revoked`). Linked to the Registration record and the admin who authorized issuance. Batch issuance for entire cohorts via non-blocking job dispatch.

## Key Concepts

### QR Cryptographic Verification

Each printed certificate displays a QR code encoding a verification URL with a cryptographic hash. The hash is generated using SHA-256 over student ID, institutional code, final score, and issuer private key. Public verification endpoints accept the hash and return the certificate's authenticity status. This enables offline forgery detection without requiring database access at the verification point.

### Final Grade Prerequisite

Certificates cannot be issued unless the student's registration has a corresponding finalized Report card record. This enforcement is at the Action layer — `IssueCertificateAction` checks for a finalized report before proceeding. If missing, a `RejectedException` informs the operator which students are ineligible.

### Revocation is Terminal

Once a certificate is revoked, its status is permanently set to `revoked` and its serial number is retired. Double revocation is idempotent — attempting to revoke an already-revoked certificate is a no-op. Re-issuance requires a new serial number and a new certificate record.

### Embedded Layout Snapshots

Certificate layouts (portrait/landscape orientation, background seals, text placeholders) are rendered dynamically at issuance time and frozen as immutable HTML snapshots within the certificate record. This ensures certificates always render exactly as they were at issuance, even if templates change later.

## Dependencies

- Core (base classes, SmartLogger)
- Reports (finalized grade card prerequisite)
- Enrollment (registration context)
- User (recipient and issuer identity)

## Used By

- Public verification endpoints (no module dependency)
