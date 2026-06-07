# Certification — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Aligned with the removal of the separate `certificate_templates` table (now inlined as
> HTML layouts) and added dependency on Final Grade Card finalization.

Manages certificate generation, digital QR signatures, and credential tracking.

For complete technical reference including API, models, actions, and components, see
[certification-reference.md](certification-reference.md).

---

## Key Principles

- **Certificates Awarded Upon Final Grade Finalization** — Certificates are issued only after the
  student's final grade card in the Reports module is finalized and locked.
- **Embedded Layouts** — Certificate layouts ( portrait/landscape, background seals, text
  placeholders) are rendered dynamically and saved as frozen, immutable HTML snapshots within the
  certificate record. This ensures permanent, tamper-proof compliance.
- **QR Cryptographic Verification** — Printed certificates display a secure QR code referencing a
  verification URL. The system verifies the cryptographically signed hash (`qr_hash`) to expose
  offline forgery.

---

## Context Boundary

The **Certification** module:

- Consumes **Reports (`reports`)** to verify that a student's final Final Grade Card is finalized before
  allowing certificate issuance.
- Consumes **User (`users`)** to identify the recipient student and the administrator who signed
  off.
- Generates validation metadata exposed for public certificate validation requests.

---

## Module Rules

- **Final Grade Card Prerequisite:** A certificate cannot be issued unless the registration has a
  corresponding `finalized` Report card record.
- **Revocation is Terminal:** Once revoked, a certificate's status is permanently updated to
  `revoked`, and its serial number is retired. Double revocation is idempotent.
- **Hash Integrity:** The verification hash is generated using a secure SHA-256 function of the
  student ID, institutional code, final score, and issuer's private key.

---

## Submodules

- **Certificate:** Handles certificate generation, serial numbering, PDF rendering, QR code signing,
  and revocation logs.

---

## Error Handling & Failure Modes

- **Issuance Without Final Grade:** Issuing a certificate for a student whose Final Grade Card is pending
  or uncompiled is blocked with a `RejectedException`.
- **Duplicate Issuance:** Re-issuing an active certificate for the same student registration returns
  a `ConflictException`.
- **Signature Integrity Failure:** If the rendering service or system signature fails, certificate
  creation is rolled back.

---

## Quick References

### Actions & Business Logic

- **3** actions across the module:
    - `IssueCertificateAction` — Generates and signs a single certificate.
    - `BatchIssueCertificateAction` — Non-blocking cohort batch generator.
    - `RevokeCertificateAction` — Revokes a credential with an audit trail.

### Data & Persistence

- **1** model: `Certificate`.
- UUID PKs, index on `registration_id`.

### User Interface

- **2** Livewire components:
    - `CertificateList` — Coordinator manager for tracking and revoking certificates.
    - `StudentCertificates` — Student list to view and download PDF credentials.

---

For complete technical reference, see [certification-reference.md](certification-reference.md).
