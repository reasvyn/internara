# Certification — Documentation Overview

> Last updated: 2026-06-05
> Changes: Added Error Handling & Failure Modes section

Manages certificate generation and credential tracking

For complete technical reference including API, models, actions, and components, see [certification-reference.md](certification-reference.md).

---

## Key Principles

- Certificates awarded upon completion
- Templates enable scalable certificate generation
- Digital signatures ensure authenticity

---

## Context Boundary

Owns certificate models. Program determines eligibility. SysAdmin manages templates. User owns certificate recipients.

---

## Module Rules

- Certificates only issued after program completion
- Template must be approved before use
- All certificates digitally signed and trackable
- Revocation records preserved for compliance

---

## Submodules

- **Certificate**: Core business entity for certificate management

---

## Error Handling & Failure Modes

- **Certificate issuance without completion**: Issuing a certificate for a student whose program is not in COMPLETED status is blocked with a `RejectedException`.
- **Template not approved**: Using an unapproved template for certificate generation throws a `ValidationFailedException`.
- **Duplicate certificate**: Attempting to issue a duplicate certificate for the same student and program returns a `ConflictException`.
- **Revocation of already-revoked certificate**: Double revocation is idempotent — returns success without side effects rather than an error.
- **Digital signature failure**: If the signing service is unavailable, certificate generation fails with a `ServiceUnavailableException` and the batch is rolled back.

---

## Quick References

### Actions & Business Logic
- **4** actions across all submodules
- Business logic operations for certification module

### Data & Persistence
- **2** models managing core data
- Eloquent relationships and queries

### User Interface
- **3** Livewire components for real-time interaction
- Views in `resources/views/certification/`

### Authorization
- **2** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [certification-reference.md](certification-reference.md).
