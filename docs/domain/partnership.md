# Partnership Domain

## Purpose

Partnership manages the external relationships that make internships possible — the companies
and organizations that host students. This domain tracks the companies the institution
collaborates with, the formal agreements that define each partnership's terms, and the full
lifecycle from active collaboration to eventual expiry or termination.

## Boundary

**In scope:** Company profiles (name, address, industry sector, website, description, contact
info), partnership agreements (agreement number, title, start/end dates, scope, status, contact
person details, signing parties, notes), agreement lifecycle (active → expired/terminated),
CSV import/export/template for companies and partnerships, MOU document upload via media
library, renewal of expired partnerships with new terms, company and partnership CRUD with
search, filter, sort, pagination via BaseRecordManager.

**Out of scope:** Student placement assignment (Placement domain), internship program definition
(Internship domain), mentor assignment from company supervisors (Mentor domain), legal document
generation (Document domain).

## Key Concepts

**Companies.** An external organization that participates in the internship program. Each company
has a profile: name, industry sector, address, phone, email, website, and description. Companies
are managed via `CompanyManager` Livewire component (extends `BaseRecordManager`). A company
cannot be deleted if it has associated placements or partnerships (enforced by `CompanyState`
entity).

**Partnership Agreements.** A formal agreement defining collaboration terms. Each agreement has:
an agreement number (unique), title, start/end dates, scope description, contact person details
(name, phone, email), signing parties (school and company representatives), signing date, notes,
and status (ACTIVE, EXPIRED, TERMINATED). Status transitions: ACTIVE → EXPIRED or TERMINATED.
Agreements can optionally have an MOU document uploaded via spatie/laravel-medialibrary.
Expiring agreements are detected via `PartnershipState::isExpiringSoon()` (configurable threshold,
default 30 days).

**Partnership Lifecycle.**
- ACTIVE: agreement is operational.
- EXPIRED: agreement reached end date without renewal.
- TERMINATED: agreement ended early via `TerminatePartnershipAction`.
- Renewal: `RenewPartnershipAction` creates a new agreement from expired data.

**Slot Statistics.** The `CompanyManager` displays stats: total companies, companies with
placements/partnerships, and available placement slots (from Placement domain via
`SUM(quota - filled_quota)`).

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to create company profiles so that I can track partner organizations
- **Admin:** As an admin, I want to create partnership agreements with start/end dates so that collaboration terms are formalized
- **Admin:** As an admin, I want to renew expired partnerships so that ongoing relationships continue smoothly
- **Admin:** As an admin, I want to terminate an active partnership when necessary so that the system reflects reality
- **Admin:** As an admin, I want to import/export companies and partnerships via CSV so that I can manage data in bulk
- **Admin:** As an admin, I want to view slot statistics per company so that I know how many placements are available
- A company cannot be deleted if it has any placements or partnerships associated.
- Only active partnerships can be terminated.
- Only non-active (expired or terminated) partnerships can be deleted.
- Only expired or terminated partnerships can be renewed — active partnerships must be
terminated or expired first.
- Partnership status transitions follow the state machine: ACTIVE → {EXPIRED, TERMINATED}.
Terminal states (EXPIRED, TERMINATED) have no valid transitions.
- Agreement numbers must be unique across all partnerships.
- Companies are stored in the `companies` table.

### Process Flow

```
Partnership Agreement Lifecycle:

ACTIVE ──→ EXPIRED (renewable)
    │
    ↓
TERMINATED (irreversible)
```

- **ACTIVE**: Agreement is operational
- **EXPIRED**: Reached end date without renewal — renewable via `RenewPartnershipAction`
- **TERMINATED**: Ended early via `TerminatePartnershipAction` — irreversible
- Only expired or terminated partnerships can be deleted
- A company cannot be deleted if it has associated placements or partnerships

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateCompanyAction` | Creates a new company profile |
| `UpdateCompanyAction` | Updates company details |
| `DeleteCompanyAction` | Deletes a company (blocked if associated placements/partnerships exist) |
| `CreatePartnershipAction` | Creates a new partnership agreement |
| `UpdatePartnershipAction` | Updates partnership details |
| `DeletePartnershipAction` | Deletes a non-active partnership |
| `RenewPartnershipAction` | Renews an expired partnership |
| `TerminatePartnershipAction` | Terminates an active partnership |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `InternshipCompany` (`companies`), `Partnership` |
| **Entities** | `CompanyState` (deletion guard); `PartnershipState` (active/expired/terminated checks, expiry warnings) |
| **Enums** | `PartnershipStatus` — `ACTIVE`, `EXPIRED`, `TERMINATED` |
| **Livewire** | `CompanyManager`, `PartnershipManager` |

## Dependencies

| Dependency | Reason |
|---|---|
| Core | BaseModel, BaseAction, BaseEntity, BaseRecordManager, RejectedException |
| Placement | Placement model for slot statistics and delete guard |
| Shared | CsvHandler for import/export |


