# Certification — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Certification domain.

Manages certificate generation, documents, and credential tracking

For complete technical reference including API, models, actions, and components, see [certification-reference.md](certification-reference.md).

---

## Key Principles

- Certificates awarded upon completion
- Documents provide credential storage and archival
- Templates enable scalable certificate generation
- Digital signatures ensure authenticity

---

## Context Boundary

Owns certificate and document models. Program determines eligibility. Admin manages templates. User owns certificate recipients.

---

## Domain Rules

- Certificates only issued after program completion
- Template must be approved before use
- All certificates digitally signed and trackable
- Revocation records preserved for compliance

---

## Aggregates

- **Certificate**: Core business entity for certificate management
- **Document**: Core business entity for document management

---

## Quick References

### Actions & Business Logic
- **8** actions across all aggregates
- Business logic operations for certification domain

### Data & Persistence
- **3** models managing core data
- Eloquent relationships and queries

### User Interface
- **5** Livewire components for real-time interaction
- Views in `resources/views/certification/`

### Authorization
- **3** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [certification-reference.md](certification-reference.md).
