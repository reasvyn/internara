# Partners — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

External relationship management: company profiles and partnership agreements.

For complete technical reference including API, models, actions, and components, see [partners-reference.md](partners-reference.md).

---

## Key Principles

- **Companies host internships** — each company provides placement slots for students. Company profiles contain legal name, address, industry, website, and contact information.
- **Partnerships are contractual** — a partnership agreement (MOU) documents the terms between the school and the company. Each partnership has a lifecycle: ACTIVE → EXPIRED/TERMINATED.
- **One company can have multiple partnerships** over time (different programs, different terms). Partnerships are versioned by agreement number.
- **MOU documents are uploaded as media** — partnership agreements are stored as files via Spatie Media Library. Each partnership can have one or more attached documents.

---

## Context Boundary

Owns Company and Partnership models. Program links internships to companies (a program's placements are at specific companies). Guidance assigns company supervisors to students at a company.

---

## Domain Rules

- **Company must have valid contact information**: at minimum, name and address. Industry classification is recommended for reporting.
- **Partnership lifecycle transitions**: ACTIVE → EXPIRED (end date reached) or ACTIVE → TERMINATED (admin action). EXPIRED and TERMINATED are terminal states.
- **Partnership suspension pauses new placements**: when a partnership is EXPIRED or TERMINATED, no new placements can be created under that agreement. Existing placements are unaffected.
- **Expiry detection**: the system warns when a partnership is approaching expiry (default 30 days before end_date). Admins receive a notification to renew.
- **Historical partnerships are preserved**: terminated/expired partnerships remain visible for audit. They are filtered from active placement selection UIs.

---

## Aggregates

- **Company**: Organization profile — legal name, trading name, address, industry, website, phone, email, notes. Soft-deletes preserve historical placements.
- **Partnership**: Agreement record — number, title, start/end dates, scope, contact person, signing parties, MOU document upload, lifecycle status. One company can have multiple partnerships.

---

## Error Handling & Failure Modes

- **Deleting a company with active placements**: Blocked by a deletion guard. The system returns a list of active placements that prevent deletion.
- **Creating a placement under an expired partnership**: The system rejects with "Partnership is not active." Only ACTIVE partnerships allow new placements.
- **Missing MOU document**: Not a hard requirement, but the system warns when finalizing a partnership without an attached MOU document.

---

## Quick References

### Actions & Business Logic
- **10** actions across all aggregates
- Company CRUD, partnership CRUD, MOU document upload, lifecycle transitions, expiry detection

### Data & Persistence
- **2** models: `Company`, `Partnership`
- UUID PKs, `HasFactory`, `SoftDeletes` on Company. Partnership has StatusEnum lifecycle

### User Interface
- **2** Livewire components
- Company manager (CRUD table), partnership manager with MOU upload

### Authorization
- **2** policies: `CompanyPolicy`, `PartnershipPolicy`
- Admin/superadmin only for CRUD. Read access for teachers/supervisors viewing assigned companies

---

For complete technical reference, see [partners-reference.md](partners-reference.md).
