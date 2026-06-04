# Certification — Documentation Overview

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format

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

## Domain Rules

- Certificates only issued after program completion
- Template must be approved before use
- All certificates digitally signed and trackable
- Revocation records preserved for compliance

---

## Aggregates

- **Certificate**: Core business entity for certificate management

---

## Quick References

### Actions & Business Logic
- **4** actions across all aggregates
- Business logic operations for certification domain

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
