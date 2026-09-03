# Internara Project — Initial Specification

> **Spec ID:** QLHDO

## Description

This is the **initial specification** of the Internara system — a self-hosted, single-tenant web
application for managing compulsory industrial fieldwork programs (PKL — _Praktik Kerja Lapangan_)
at Indonesian vocational schools (SMK). It is the **spec-zero blanket spec**: it establishes the
project boundary, the role model, the global cross-cutting requirements every feature spec
inherits, and the project-level design decisions. It deliberately does **not** restate
implementation-level detail — each phase's functional detail lives in its own feature spec
(indexed in [docs/specs/index.md](index.md)).

---

## 1. Problem Statements

### PS-1 — PKL Administration Is Fragmented

Indonesian vocational schools legally require PKL, yet most manage the full lifecycle — enrollment,
placement, attendance, logbook, supervision, assessment, certification, reporting — with paper
forms, Excel spreadsheets, WhatsApp messages, and ad-hoc email. A coordinator compiling final grade
cards must manually gather data from dozens of disconnected artifacts, making errors, delays, and
unfinished work inevitable.

### PS-2 — Hidden Scale

A typical medium-to-large SMK manages 500–1,000 active students across 150–300 partner companies
(DUDI) per placement period — on the order of 45,000 attendance records, 6,000 logbook entries,
1,500–2,500 submissions, and 500 evaluation forms per period. No manual process survives that volume
without data loss.

### PS-3 — No Accountability Trail

Without a unified system, verification and sign-off cannot be traced: attendance is unverifiable at
scale, logbook entries are ungraded and unsearchable, certificates are forgeable, and there is no
audit trail for administrative action. Schools need digital evidence of the educational process.

### PS-4 — No Single Source of Truth

Operational data lives in the private tooling of whoever happens to hold it (a teacher's sheet, a
supervisor's notebook, an admin's mail queue). There is no canonical, role-filtered record any
participant can query, and no consistent reporting base.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                                                                                                                                                                            |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| G1  | Digitize the complete PKL lifecycle end-to-end: foundation → configuration → identity → institutional → partnerships → programs → enrollment → daily ops → assessment → certification → reporting → maintenance |
| G2  | Provide one canonical, role-filtered source of truth for every participant (student, teacher, supervisor, admin)                                                                                                |
| G3  | Operate as a self-hosted, single-tenant, MIT-licensed application with zero recurring vendor costs and full data sovereignty                                                                                    |
| G4  | Enforce authorization and an audit trail at every layer (Secure — S1)                                                                                                                                           |
| G5  | Stay sustainable and maintainable through module colocation and clean boundaries (Sustain — S2)                                                                                                                 |
| G6  | Scale cleanly within a single tenant via spec-driven features and an Action-triad architecture (Scalable — S3)                                                                                                  |
| G7  | Support the full domain bilingually (Indonesian primary, English secondary) with `__()` on all user-facing strings                                                                                              |

### Non-Goals

| ID  | Non-Goal                                                           |
| --- | ------------------------------------------------------------------ |
| NG1 | Multi-tenant SaaS — single-tenant by design, no tenant-ID overhead |
| NG2 | HR / payroll features                                              |
| NG3 | Real-time chat                                                     |
| NG4 | Government database sync — CSV import/export only                  |
| NG5 | Mobile native apps — responsive web only                           |

---

## 3. User Stories / Use Cases

### UC-1 — School Initializes and Configures the System

**Actor:** Super Admin / Admin
**Preconditions:** Server deployed, environment audit passes
**Flow:** Super Admin runs the 6-step setup wizard → Admin configures branding, theme, locale, and
school profile; creates departments and academic years → Admin registers partner companies and
formal partnerships with slot quotas.
**Postconditions:** School can enroll students; `superadmin` account exists.
**Governing spec:** [8NZAU-installation](8NZAU-installation.md).

### UC-2 — Student Completes the PKL Lifecycle

**Actor:** Student
**Preconditions:** Registration open, placement slots available
**Flow:** Student registers → Admin verifies and places the student → Student clocks in/out, keeps
a reflective logbook, submits assignments, and acknowledges handbooks → Student downloads certificate
after assessment and report sign-off.
**Postconditions:** Full digital trail of the internship exists and is auditable.
**Governing specs:** [MBB5R-registration](MBB5R-registration.md), [J9GBH-placement](J9GBH-placement.md),
[1KSWL-daily-activity](1KSWL-daily-activity.md), [T657Z-assignment](T657Z-assignment.md),
[J0M04-certification](J0M04-certification.md).

### UC-3 — Teacher Supervises and Assesses

**Actor:** Teacher
**Preconditions:** Students placed, program active
**Flow:** Teacher supervises assigned students (logbook review, supervision logs, monitoring visits)
→ Teacher or supervisor scores against competency rubrics; submissions are graded → Teacher
compiles and finalizes the grade card; certificate becomes issuable.
**Postconditions:** Grades are aggregated; finalized artifacts are immutable.
**Governing specs:** [2EHSE-supervision](2EHSE-supervision.md), [ARDA6-assessment](ARDA6-assessment.md),
[R6BMW-reports](R6BMW-reports.md).

### UC-4 — Supervisor Evaluates Industry-Side Performance

**Actor:** Supervisor (industry)
**Preconditions:** Student active at the DUDI site
**Flow:** Supervisor verifies attendance and reviews logbook entries → Supervisor submits
competency evaluations for assigned students (direct or proxy-stamped) → Evaluations flow into final
score aggregation.
**Postconditions:** Industry-side scores are present in the final record.
**Governing specs:** [1KSWL-daily-activity](1KSWL-daily-activity.md), [ARDA6-assessment](ARDA6-assessment.md),
[T4B26-rbac-and-authorization](T4B26-rbac-and-authorization.md) §4.2 (Cross-Role Proxy).

### UC-5 — Admin Operates and Audits the System

**Actor:** Admin / Super Admin
**Preconditions:** System running
**Flow:** Admin manages users (CRUD, lock/unlock, role assignment, account slips) and announcements
→ Admin monitors health checks, audit logs, and job queues → Admin runs backups, GDPR
export/erasure, and archival per policy.
**Postconditions:** Operation is observable and recoverable within RPO/RTO targets.
**Governing specs:** [95EVB-user-crud-and-status](95EVB-user-crud-and-status.md),
[3S55V-announcement-system](3S55V-announcement-system.md), [E1MSJ-system-maintenance](E1MSJ-system-maintenance.md),
[HBXCI-backup-system](HBXCI-backup-system.md), [7HNCF-gdpr-compliance](7HNCF-gdpr-compliance.md).

### 3.1 Role Model (5 Roles + 2 Functional)

| Role        | Code          | Description                                                                               |
| ----------- | ------------- | ----------------------------------------------------------------------------------------- |
| Super Admin | `super_admin` | Unrestricted system access, infrastructure management, bypasses all permission checks     |
| Admin       | `admin`       | School-level operations: user management, programs, companies, departments                |
| Teacher     | `teacher`     | Academic supervision: journal review, assignment grading, site visits, grade compilation  |
| Student     | `student`     | Program participation: attendance, logbooks, assignments, certificate download            |
| Supervisor  | `supervisor`  | Industry-side supervision: attendance verification, journal review, competency evaluation |

Each user is assigned exactly one role. Three additional **functional roles** (`admin-group`, `mentor`,
`mentee`) are resolved at runtime via `Role::resolvesTo()` for business logic — never stored or used in
middleware. `admin-group` is the administrative grouping (`super_admin`/`admin`). Full RBAC contract
in [T4B26](T4B26-rbac-and-authorization.md) §4.2.

---

## 4. Functional Requirements

These are **global defaults every feature spec inherits**; a feature spec may tighten them but never
violate them. Where this section references an owning spec, that document is authoritative for the
detail.

### 4.1 Cross-Cutting Requirements

| ID    | Requirement                                                                                                                                                                                                                  | Owning spec                                                            | Status   | Last Verify |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- | -------- | ----------- |
| FR-G1 | Every user-facing string MUST use the `__()` helper; all modules ship `lang/en/` and `lang/id/` (D3)                                                                                                                         | [YB22J-settings-infrastructure](YB22J-settings-infrastructure.md)     | Shipped  | 2026-09-03  |
| FR-G2 | The system MUST expose exactly five stored roles — `super_admin`, `admin`, `teacher`, `student`, `supervisor` — plus three runtime-resolved functional roles (`admin-group`, `mentor`, `mentee`) via `Role::resolvesTo()`   | [T4B26-rbac-and-authorization](T4B26-rbac-and-authorization.md) §4.2 | Shipped  | 2026-09-03  |
| FR-G3 | The system MUST enforce `superadmin` integrity: name is always `Super Admin`, username always `superadmin`, immutable and non-deletable                                                                                       | [8NZAU-installation](8NZAU-installation.md)                           | Shipped  | 2026-09-03  |
| FR-G4 | All administrative mutations MUST be audit-logged (activity channel) with PII masking                                                                                                                                       | [89SRA-logging-and-error-handling](89SRA-logging-and-error-handling.md) | Shipped  | 2026-09-03  |
| FR-G5 | Sensitive endpoints MUST be rate-limited (global 30/min/IP; login 5/60s; forgot 3/3600s; reset 5/300s; recovery 3/300s)                                                                                                    | [2CF4Y-middleware-pipeline](2CF4Y-middleware-pipeline.md) FR-MW10     | Shipped  | 2026-09-03  |
| FR-G6 | The system MUST run a system health check covering PHP, extensions, memory, DB, migrations, storage, queue, cache, and app key                                                                                               | [J68GZ-system-requirements](J68GZ-system-requirements.md) FR-SY8      | Shipped  | 2026-09-03  |
| FR-G7 | All records MUST use UUID primary keys via `BaseModel`/`HasUuids`                                                                                                                                                            | [SE5Q9-base-classes](SE5Q9-base-classes.md)                            | Shipped  | 2026-09-03  |
| FR-G8 | Program data MUST flow in dependency order: Foundation → Configuration → Identity & Auth → Institutional → Partnerships → Programs → Enrollment → Daily Ops → Assessment → Certification → Reporting → Maintenance (full phase inventory in [index.md](index.md)) | [index.md](index.md) (build order)                                    | Shipped  | 2026-09-03  |

### 4.2 Lifecycle Phase Inventory (index only)

Each phase's functional detail lives in its governing spec(s) — see [docs/specs/index.md](index.md)
for the SSOT build order, and [implementation-matrix.md](implementation-matrix.md) for status. This
row is a navigation aid; do not duplicate per-feature detail here.

| Phase             | Governing spec(s) |
| ----------------- | ----------------- |
| Foundation        | [D2FT3](D2FT3-architecture.md), [FB792](FB792-tech-stack.md), [ZT6VS](ZT6VS-core-infra-services.md), [SE5Q9](SE5Q9-base-classes.md), [C8F0D](C8F0D-shared-utilities.md), [J68GZ](J68GZ-system-requirements.md), [I1BCV](I1BCV-module-discovery.md), [89SRA](89SRA-logging-and-error-handling.md), [NUCY3](NUCY3-event-system.md), [T4B26](T4B26-rbac-and-authorization.md), [2CF4Y](2CF4Y-middleware-pipeline.md), [1PGM4](1PGM4-security-headers.md), [B114U](B114U-module-manager.md) |
| Configuration     | [8NZAU](8NZAU-installation.md), [VEJCX](VEJCX-setup-wizard.md), [C9ZB6](C9ZB6-recovery-ecosystem.md), [YB22J](YB22J-settings-infrastructure.md), [52O1I](52O1I-branding-theme-locale.md), [81SMS](81SMS-school-profile.md) |
| Identity & Auth   | [8XMYS](8XMYS-layout-and-ui-system.md), [YB7RG](YB7RG-authentication.md), [TXR2H](TXR2H-notification-infrastructure.md), [3S55V](3S55V-announcement-system.md), [CKKZC](CKKZC-dashboard.md), [D9TKW](D9TKW-password-reset.md), [CQVSK](CQVSK-password-confirmation.md), [SHQ1J](SHQ1J-account-recovery-slips.md), [OCEMS](OCEMS-profile-management.md) |
| Institutional     | [4HWSB](4HWSB-department-management.md), [XW6F5](XW6F5-academic-year-management.md), [81SMS](81SMS-school-profile.md) |
| Partnerships      | [XI3LB](XI3LB-company-management.md), [NTHQA](NTHQA-partnership-management.md) |
| Programs          | [7C5WM](7C5WM-internship-lifecycle.md), [IT0OE](IT0OE-internship-groups.md) |
| Enrollment        | [MBB5R](MBB5R-registration.md), [J9GBH](J9GBH-placement.md), [920SO](920SO-account-application.md), [95EVB](95EVB-user-crud-and-status.md), [O2KCR](O2KCR-csv-import-export.md), [EWCZ0](EWCZ0-account-slips.md) |
| Daily Ops         | [1KSWL](1KSWL-daily-activity.md), [2EHSE](2EHSE-supervision.md), [3RU9S](3RU9S-incident.md) |
| Assessment        | [ARDA6](ARDA6-assessment.md), [AXKZW](AXKZW-evaluation.md), [T657Z](T657Z-assignment.md) |
| Certification     | [PKYX6](PKYX6-document-templates.md), [ZUFG8](ZUFG8-handbooks.md), [J0M04](J0M04-certification.md), [WQGTP](WQGTP-file-uploads-media.md), [7UB7S](7UB7S-pdf-generation.md) |
| Reporting         | [R6BMW](R6BMW-reports.md), [7H5D6](7H5D6-official-documents.md) |
| Maintenance       | [8FVZA](8FVZA-job-queue-infrastructure.md), [HBXCI](HBXCI-backup-system.md), [7HNCF](7HNCF-gdpr-compliance.md), [E1MSJ](E1MSJ-system-maintenance.md), [06IB6](06IB6-deployment.md), [3UOZP](3UOZP-dummy-data.md), [9YUUK](9YUUK-data-archiving.md) |

---

## 5. Non-Functional Requirements

These are **project-level NFRs**. Each row points to the owning spec for measurable targets and
acceptance criteria; the owning spec is authoritative for verification.

| ID     | Requirement                                                                                                                                                                                              | Owning spec(s)                                                                                | Status   | Last Verify |
| ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | -------- | ----------- |
| NFR-S1 | Security: authorization at every layer (Policy + Action/Entity business authorization via `RejectedException`); PII masked in logs per PDP law (UU No. 27/2022); CSP + security headers on all responses | [T4B26](T4B26-rbac-and-authorization.md), [89SRA](89SRA-logging-and-error-handling.md), [1PGM4](1PGM4-security-headers.md) | Shipped  | 2026-09-03  |
| NFR-S2 | Input: no raw SQL without bindings (C3); no raw `Request` into create/update (D5); all user input validated server-side — see `AGENTS.md` Critical Invariants                                              | [D2FT3](D2FT3-architecture.md), `AGENTS.md`                                                  | Shipped  | 2026-09-03  |
| NFR-P1 | Performance: pages respond within target budgets; Actions eager-load relations (no N+1); expensive queries cached with registered cache keys                                                             | [ZT6VS](ZT6VS-core-infra-services.md), [SE5Q9](SE5Q9-base-classes.md)                         | Shipped  | 2026-09-03  |
| NFR-R1 | Reliability: 4-hour RPO / under 1-hour RTO backup target; graceful degradation; job queues for heavy work (mail, PDF, reports)                                                                           | [HBXCI](HBXCI-backup-system.md), [8FVZA](8FVZA-job-queue-infrastructure.md), [E1MSJ](E1MSJ-system-maintenance.md) | Shipped  | 2026-09-03  |
| NFR-U1 | Usability: every page with a non-trivial workflow has a `*-guide.blade.php`; WCAG AA contrast; keyboard navigable; mobile-first responsive                                                              | [8XMYS](8XMYS-layout-and-ui-system.md)                                                        | Shipped  | 2026-09-03  |
| NFR-M1 | Maintainability: 4-layer module-first architecture enforced by `tools/` scans (C1–C8, D1–D6, contracts, naming, security); DRY — shared logic in Core                                                  | [D2FT3](D2FT3-architecture.md), `tools/`                                                      | Shipped  | 2026-09-03  |
| NFR-L1 | Localization: English + Indonesian, locale stored in session, togglable at runtime                                                                                                                       | [YB22J](YB22J-settings-infrastructure.md), [52O1I](52O1I-branding-theme-locale.md)             | Shipped  | 2026-09-03  |
| NFR-C1 | Compatibility: renders consistently across modern browsers; printed/exported artifacts (PDF/Excel/CSV) are precise and stable                                                                            | [7UB7S](7UB7S-pdf-generation.md), [O2KCR](O2KCR-csv-import-export.md)                         | Shipped  | 2026-09-03  |
| NFR-D1 | Database: SQLite WAL mode or MySQL; UUID primary keys; 55 tables (37 domain + 18 system)                                                                                                                 | [J68GZ](J68GZ-system-requirements.md), [ZT6VS](ZT6VS-core-infra-services.md)                  | Shipped  | 2026-09-03  |
| NFR-Q1 | Queue: separate `default` and `documents` pipelines                                                                                                                                                      | [8FVZA](8FVZA-job-queue-infrastructure.md)                                                    | Shipped  | 2026-09-03  |
| NFR-G1 | GDPR: deletion logging and data erasure workflows                                                                                                                                                       | [7HNCF](7HNCF-gdpr-compliance.md)                                                             | Shipped  | 2026-09-03  |

> **Curriculum/regulatory alignment** (legacy §11 of the QLHDO draft) is tracked outside the
> spec system as a research input in `docs/refs/curriculum-compliance.md` (non-testable
> description of how the system maps to Indonesian PKL regulations). It is intentionally **not** a
> spec requirement — regulations evolve and are outside engineering control.

---

## 6. API / Data Contracts

### 6.1 Identity Contract

- `users` table via `BaseModel` + `HasUuids` (UUID PK); one row per person; role column references
  the `Role` enum.
- `Role` enum cases: `super_admin`, `admin`, `teacher`, `student`, `supervisor`, with
  `Role::resolvesTo()` mapping runtime functional roles `admin-group`/`mentor`/`mentee`. Full contract
  in [T4B26](T4B26-rbac-and-authorization.md) §4.2.

### 6.2 Global Helpers

```php
setting(string|array|null $key = null, mixed $default = null, bool $skipCache = false): mixed
brand(string $key, mixed $default = null): mixed
app_info(?string $key = null, mixed $default = null): mixed
```

Full contracts in [C8F0D-shared-utilities](C8F0D-shared-utilities.md) (FR-SUP11) and
[YB22J-settings-infrastructure](YB22J-settings-infrastructure.md).

### 6.3 Module Landscape (18 business modules + Core + UI)

`app/` hosts zero top-level business directories; all code lives in modules. Each module owns its
vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`,
`Notifications/`, `Policies/`, `Livewire/`, `Services/`, `Support/`, routes, and `lang/`. The full
module dependency graph and registration order live in `config/module.php` and
`docs/refs/modules/index.md`.

### 6.4 Architecture Contracts (authoritative references)

- 4-layer model and Action Triad — [D2FT3-architecture](D2FT3-architecture.md)
- Base classes — [SE5Q9-base-classes](SE5Q9-base-classes.md)
- RBAC & authorization — [T4B26-rbac-and-authorization](T4B26-rbac-and-authorization.md)
- Module discovery & registration — [I1BCV-module-discovery](I1BCV-module-discovery.md)
- Logging & error handling — [89SRA-logging-and-error-handling](89SRA-logging-and-error-handling.md)
- Event system — [NUCY3-event-system](NUCY3-event-system.md)
- Middleware pipeline — [2CF4Y-middleware-pipeline](2CF4Y-middleware-pipeline.md)
- Security headers — [1PGM4-security-headers](1PGM4-security-headers.md)

---

## 7. Design Decisions

### DD-1 — Single-Tenant, Self-Hosted, MIT

**Decision:** Distribute as a self-packaged Laravel codebase running on school-owned infrastructure.
**Rationale:** Guarantees data sovereignty, offline robustness, zero recurring cost, and no vendor
lock-in for under-resourced schools.
**Trade-off:** No SaaS economics; every deployment is per-school (install cost accepted).

### DD-2 — Module-First Vertical Slicing

**Decision:** Organize code by business module rather than a flat `app/Models` +
`app/Http/Controllers` + `app/Services` structure.
**Rationale:** A business concept lives in one directory — findable, independently testable, safe to
change; prevents silent cross-module coupling (S2). See [D2FT3](D2FT3-architecture.md) DD-1.
**Trade-off:** Shared infrastructure must be deliberately extracted to Core (FR-G8 flow).

### DD-3 — Primary Indonesian, Secondary English

**Decision:** Ship full translations in both `lang/id/` (primary) and `lang/en/` (secondary) with a
runtime toggle.
**Rationale:** PKL is an Indonesian curriculum mandate; school staff and students are native
speakers. English supports bilingual schools and developers.
**Trade-off:** Every user-facing string has a translation cost — enforced by D3 and scan.

### DD-4 — Spec-Driven Build Order

**Decision:** The project is built phase-by-phase; each feature traces to a governed spec with
FR/NFR/UC IDs, and tests trace back to those IDs.
**Rationale:** No behavior without a requirement; verification is spec-gap/orphan scoring rather
than line coverage (maintains the S3 doctrine).
**Trade-off:** Writing the spec precedes coding — documentation-first discipline required.

### DD-5 — Bounded Non-Goals Enforcement

**Decision:** Out-of-scope areas (multi-tenant, HR, chat, gov sync, native apps) are explicit
non-goals rather than accidental omissions.
**Rationale:** Prevents scope creep and keeps the single-tenant PKL focus crisp at scale.
**Trade-off:** Schools needing government sync or payroll run those systems externally.

---

## 8. Success Metrics

| Metric                                 | Target                                     | Measurement                                                          |
| -------------------------------------- | ------------------------------------------ | -------------------------------------------------------------------- |
| Lifecycle coverage                     | All 12 phases fully spec'd and implemented | `docs/specs/implementation-matrix.md` (green/verified rows)          |
| Module colocation                      | 100% of `app/` under modules + Core        | `python3 tools/scan_naming.py`, directory audit                      |
| Architecture invariants (C1–C8, D1–D6) | 0 violations                               | `python3 tools/scan_violations.py`                                   |
| Spec↔code alignment                    | 0 spec gaps, 0 orphan tests                | per-module spec audits, `python3 tools/scan_spec_tests.py`           |
| Localization coverage                  | 0 hardcoded user strings in Blade/UI       | `python3 tools/scan_conventions.py` (D3)                             |
| Full suite                             | green                                      | `php artisan test --compact`                                         |
| Backup                                 | 4h RPO / <1h RTO                           | drill + monitoring                                                   |
| Security posture                       | no critical/high external-audit findings   | `qa-protocol` audits                                                 |

---

## 9. Roadmap

### Prerequisites

None — this is the foundational, **spec-zero** initial specification. Every other spec (and the
architecture-first build order) operates inside its scope.

### Build Guide

Implement the lifecycle in dependency order — the Foundation phase specs first ([D2FT3](D2FT3-architecture.md)
architecture, then [FB792](FB792-tech-stack.md) tech stack, [ZT6VS](ZT6VS-core-infra-services.md)
infra services, [SE5Q9](SE5Q9-base-classes.md) base classes), then each subsequent phase in build
order as listed in [docs/specs/index.md](index.md). Each phase's feature spec drives its own
implementation; this spec remains the spec-zero reference for global cross-cutting requirements
(roles, localization, security, audit).

### Next Steps

| Order | Spec                                                           | Connection                                                                     |
| ----- | -------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| 1     | [Architecture Design](D2FT3-architecture.md)                   | Defines the 4-layer architecture the whole codebase must satisfy (FR-G8, DD-2) |
| 2     | [Tech Stack](FB792-tech-stack.md)                              | Pins dependency versions the build executes on                                 |
| 3     | [Core & Infrastructure Services](ZT6VS-core-infra-services.md) | Runtime services (cache, session, DB, queue, mail, storage)                    |
| 4     | [Base Classes](SE5Q9-base-classes.md)                          | BaseModel/BaseAction/BaseEntity/BaseData contracts                             |
| 5     | [System Requirements](J68GZ-system-requirements.md)            | Domain table schema for all 12 phases                                          |

---

## Quick References

- `docs/guides/product-definition.md` — scope, personas, 3S doctrine, system boundary
- `docs/specs/index.md` — full spec index and build order
- `docs/specs/implementation-matrix.md` — implementation status matrix (priority-ordered)
- `docs/refs/modules/index.md` — module dependency graph and registration
- `config/module.php` — module bootstrap order
- `docs/architecture.md` — 4-layer model, Action Triad
- `docs/refs/curriculum-compliance.md` — research input on Indonesian PKL regulation alignment
  (non-spec, intentionally outside the spec system)
- **Related specs:** every spec in this directory — each derives scope from this spec-zero and/or is
  indexed under [index.md](index.md)
