# QLHDO — Internara Project Initial Specification

> **Spec ID:** QLHDO
> **Status:** Partial
> **Owner:** Core
> **Depends on:** None (spec-zero)

## Description

Internara is a self-hosted, single-tenant web application for managing compulsory industrial fieldwork
programs (PKL — *Praktik Kerja Lapangan*) at Indonesian vocational schools (SMK). It replaces the
fragmented, paper-and-chat workflow — WhatsApp + Excel + physical forms — with a canonical digital
record: placement, attendance, logbook, supervision, assessment, certification, and reporting.

This is the **spec-zero** spec. It establishes the project boundary, role model, global requirements
every feature spec inherits, and the MVP scope. All 62 feature specs are indexed in
[docs/specs/index.md](index.md).

---

## 1. Problem Statements

### PS-1 — Fragmented Administration at Scale

A medium-to-large SMK manages 500–1,000 active students across 150–300 partner companies (DUDI)
per placement period: ≈45,000 attendance records, 6,000 logbook entries, 1,500–2,500 submissions,
and 500 evaluation forms per period. No manual or semi-digital process (chat + Excel) survives that
volume without data loss and delay. *(Source: pkl-operational-research.md POV 1–5, Sep 2026)*

### PS-2 — Anti-Fraud Accountability Gap

Paper logbooks and WhatsApp-reported attendance are trivially falsifiable: "Sering terjadi
ketidakjujuran siswa dalam pengisian absensi dan logbook" (SMKN 1 Sintuk Toboh Gadang 2022). Schools
need digital evidence of the educational process for BAN-PDM accreditation evidence folders and
industry certificate sign-off. *(Source: pkl-operational-research.md PS-3, POV 3)*

### PS-3 — Supervision Degrades with Distance

City sites (<15 km): 3–4 visits/period. Out-of-town (50–150 km): 1 visit. Remote (>150 km): 0–1
visits (Palangka Raya study, Kanderang Tingang Jan 2026). The standard monitoring visit (Panduan
Monitoring PKL) requires attendance verification, work journal review, supervisor interview, and
documentation check. Without digital tools, school supervision collapses beyond city distance.

### PS-4 — Multi-System Re-Entry Burden

Coordinators manually re-type the same data into WhatsApp → Excel → e-Rapor → Dapodik (PKS records,
rombel, teacher data). This is a documented, verified friction across every POV. *(Source:
pkl-operational-research.md POV 2)*

### PS-5 — Grade Aggregation is Manual and Late

Final grades are assembled from separate 0–100 sheets (site leader, field advisor, seminar) across
paper and Google Docs/Scribd formats, then re-entered into e-Rapor. "Kurang efisien dalam mengolah
nilai… kesalahan… lambatnya penyerahan data" (JRAMI Unindra). *(Source: pkl-operational-research.md POV 1)*

---

## 2. Goals & Non-Goals

### Goals

| ID | Goal | Why it matters |
|----|------|----------------|
| G1 | Digitize the complete PKL lifecycle: placement → attendance → logbook → supervision → assessment → certificate | Ends the paper-and-chat workflow at scale |
| G2 | Role-filtered canonical record: every participant sees only what their role allows | Single source of truth, no informal data silos |
| G3 | Anti-fraud attendance: verifiable, timestamped, with evidence trail | BAN-PDM evidence, certificate integrity |
| G4 | Remote supervision support: digital monitoring where physical visits are impractical | Addresses PS-3 for schools with geographically dispersed placements |
| G5 | Self-hosted, MIT-licensed, zero vendor cost per school | Data sovereignty, accessible to under-resourced SMK |
| G6 | Bilingual (Indonesian primary, English secondary) with `__()` on all user-facing strings | Natively supports SMK staff and students |
| G7 | Single-tenant by design; no `tenant_id` overhead | MVP simplicity, no multi-tenancy complexity |

### Non-Goals

| ID | Non-Goal | Why excluded |
|----|----------|--------------|
| NG1 | Multi-tenant SaaS | NG-1: single-tenant is a design decision, not a limitation |
| NG2 | HR / payroll features | Out of PKL scope |
| NG3 | Real-time chat | WhatsApp is the existing platform; integration is not MVP |
| NG4 | Government database sync (Dapodik/e-Rapor) | CSV import/export only (NG-4) |
| NG5 | Mobile native apps | Responsive web covers the use case; BPS 2024: 72.78% internet access nationally |
| NG6 | Full WCAG AAA / formal accessibility audit | WCAG AA contrast + keyboard nav MVP only; full audit is post-MVP (NG-6) |
| NG7 | Offline-first / PWA | Important for rural connectivity (5–20% gap vs urban) but scoped post-MVP; progressive enhancement acceptable for MVP |

---

## 3. User Stories / Use Cases

### Role Model

| Role | Code | Description |
|------|------|-------------|
| Super Admin | `super_admin` | Infrastructure and superuser access; name immutable `Super Admin`, username `superadmin` |
| Admin | `admin` | School operations: users, programs, companies, departments |
| Teacher | `teacher` | Academic supervision: journal review, assignment grading, monitoring visits |
| Student | `student` | Program participation: attendance, logbook, assignments, certificate download |
| Supervisor | `supervisor` | Industry-side: attendance verification, journal review, competency evaluation |

Three **runtime-functional roles** are resolved via `Role::resolvesTo()` for business logic only:
`admin-group` (→ super_admin/admin), `mentor` (→ teacher/supervisor), `mentee` (→ student). Full
RBAC contract in [T4B26](T4B26-rbac-and-authorization.md).

### UC-QLHDO-1 — School Initializes

**Actor:** Super Admin → Admin
**Preconditions:** Server deployed
**Flow:** Super Admin runs `setup:install` → Admin configures branding/locale/school profile →
Admin creates departments and academic years → Admin registers companies and partnerships with slot
quotas.
**Postconditions:** School can enroll students; `superadmin` account exists.
**Governing specs:** [8NZAU](8NZAU-installation.md), [VEJCX](VEJCX-setup-wizard.md),
[C9ZB6](C9ZB6-recovery-ecosystem.md), [52O1I](52O1I-branding-theme-locale.md).

### UC-QLHDO-2 — Student Completes the PKL Lifecycle

**Actor:** Student
**Preconditions:** Registration open, placement slots available
**Flow:** Student registers → Admin verifies and places the student → Student clocks in/out,
keeps a reflective logbook, submits assignments, acknowledges handbooks → Student downloads
certificate after assessment and report sign-off.
**Postconditions:** Full digital trail of the internship exists.
**Governing specs:** [MBB5R](MBB5R-registration.md), [J9GBH](J9GBH-placement.md),
[1KSWL](1KSWL-daily-activity.md), [T657Z](T657Z-assignment.md), [J0M04](J0M04-certification.md).

### UC-QLHDO-3 — Teacher Supervises and Assesses

**Actor:** Teacher
**Preconditions:** Students placed, program active
**Flow:** Teacher supervises assigned students → reviews logbooks, logs monitoring visits →
Teacher or supervisor scores against rubrics → Teacher compiles and finalizes the grade card.
**Postconditions:** Grades aggregated; finalized artifacts immutable.
**Governing specs:** [2EHSE](2EHSE-supervision.md), [ARDA6](ARDA6-assessment.md),
[R6BMW](R6BMW-reports.md).

### UC-QLHDO-4 — Supervisor Evaluates Industry-Side Performance

**Actor:** Supervisor (DUDI)
**Preconditions:** Student active at the DUDI site
**Flow:** Supervisor verifies attendance and reviews logbook entries → submits competency
evaluations for assigned students → evaluations flow into final score aggregation.
**Postconditions:** Industry-side scores present in the final record.
**Governing specs:** [1KSWL](1KSWL-daily-activity.md), [ARDA6](ARDA6-assessment.md),
[T4B26](T4B26-rbac-and-authorization.md) §4.2 (Cross-Role Proxy).

### UC-QLHDO-5 — Admin Operates and Audits

**Actor:** Admin / Super Admin
**Preconditions:** System running
**Flow:** Admin manages users and announcements → monitors health checks and audit logs →
Admin runs backups and GDPR export/erasure per policy.
**Postconditions:** Operation observable and recoverable within RPO/RTO targets.
**Governing specs:** [95EVB](95EVB-user-crud-and-status.md), [3S55V](3S55V-announcement-system.md),
[E1MSJ](E1MSJ-system-maintenance.md), [HBXCI](HBXCI-backup-system.md).

---

## 4. Functional Requirements

Global defaults every feature spec inherits. A feature spec may tighten but never violate these.

### 4.1 Cross-Cutting (MVP Scoped)

| ID | Requirement | Owning spec | Priority |
|----|-------------|--------------|----------|
| FR-QLHDO-G1 | Every user-facing string uses the `__()` helper; all modules ship `lang/en/` and `lang/id/` | [YB22J](YB22J-settings-infrastructure.md) | P0 |
| FR-QLHDO-G2 | Exactly five stored roles — `super_admin`, `admin`, `teacher`, `student`, `supervisor` — plus three runtime-resolved functional roles via `Role::resolvesTo()` | [T4B26](T4B26-rbac-and-authorization.md) §4.2 | P0 |
| FR-QLHDO-G3 | `superadmin` account is immutable: name always `Super Admin`, username always `superadmin`, non-deletable | [8NZAU](8NZAU-installation.md) | P0 |
| FR-QLHDO-G4 | All administrative mutations are audit-logged (activity channel) with PII masking | [89SRA](89SRA-logging-and-error-handling.md) | P0 |
| FR-QLHDO-G5 | Sensitive endpoints are rate-limited: login 5/60s, forgot-password 3/3600s, reset 5/300s | [2CF4Y](2CF4Y-middleware-pipeline.md) | P0 |
| FR-QLHDO-G6 | `php artisan system:health` covers: PHP version, extensions, memory, DB, migrations, storage, queue, cache, APP_KEY | [J68GZ](J68GZ-system-requirements.md) | P0 |
| FR-QLHDO-G7 | All primary models use UUID primary keys via `BaseModel` / `HasUuids` | [SE5Q9](SE5Q9-base-classes.md) | P0 |
| FR-QLHDO-G8 | Authorization enforced at both Policy layer (gatekeeping) and Action/Entity layer (business rule via `RejectedException`) | [T4B26](T4B26-rbac-and-authorization.md) | P0 |
| FR-QLHDO-G9 | All user input validated server-side: Form Request classes for HTTP, validated DTOs for Actions | [D2FT3](D2FT3-architecture.md) | P0 |
| FR-QLHDO-G10 | Business-rule violations throw `RejectedException` with a translatable user-facing message; unexpected exceptions are logged with context and shown as generic failure | [89SRA](89SRA-logging-and-error-handling.md) | P0 |
| FR-QLHDO-G11 | File uploads validated server-side: MIME type, configurable size per module, filename safety; stored outside web root with non-guessable filenames | [WQGTP](WQGTP-file-uploads-media.md) | P0 |
| FR-QLHDO-G12 | Navigation layout renders menu groups from `config/menu.php` ordered by registration sequence; active route highlighted | [8XMYS](8XMYS-layout-and-ui-system.md) | P1 |
| FR-QLHDO-G13 | Attendance records are timestamped, immutable after admin sign-off, and include the clock-in/out timestamp with actor identity | [1KSWL](1KSWL-daily-activity.md) | P0 |
| FR-QLHDO-G14 | Logbook entries are daily, timestamped, and editable only within the same academic day by the student who created them | [1KSWL](1KSWL-daily-activity.md) | P0 |

### 4.2 Phase Inventory

Each phase's detail lives in its owning spec(s). This is a navigation index.

| Phase | Specs |
|-------|-------|
| Foundation | [D2FT3](D2FT3-architecture.md) · [FB792](FB792-tech-stack.md) · [ZT6VS](ZT6VS-core-infra-services.md) · [SE5Q9](SE5Q9-base-classes.md) · [C8F0D](C8F0D-shared-utilities.md) · [J68GZ](J68GZ-system-requirements.md) · [I1BCV](I1BCV-module-discovery.md) · [89SRA](89SRA-logging-and-error-handling.md) · [NUCY3](NUCY3-event-system.md) · [T4B26](T4B26-rbac-and-authorization.md) · [2CF4Y](2CF4Y-middleware-pipeline.md) · [1PGM4](1PGM4-security-headers.md) · [B114U](B114U-module-manager.md) |
| Configuration | [8NZAU](8NZAU-installation.md) · [VEJCX](VEJCX-setup-wizard.md) · [C9ZB6](C9ZB6-recovery-ecosystem.md) · [YB22J](YB22J-settings-infrastructure.md) · [52O1I](52O1I-branding-theme-locale.md) · [81SMS](81SMS-school-profile.md) |
| Identity & Auth | [8XMYS](8XMYS-layout-and-ui-system.md) · [YB7RG](YB7RG-authentication.md) · [TXR2H](TXR2H-notification-infrastructure.md) · [3S55V](3S55V-announcement-system.md) · [CKKZC](CKKZC-dashboard.md) · [D9TKW](D9TKW-password-reset.md) · [CQVSK](CQVSK-password-confirmation.md) · [SHQ1J](SHQ1J-account-recovery-slips.md) · [OCEMS](OCEMS-profile-management.md) |
| Institutional | [4HWSB](4HWSB-department-management.md) · [XW6F5](XW6F5-academic-year-management.md) · [81SMS](81SMS-school-profile.md) |
| Partnerships | [XI3LB](XI3LB-company-management.md) · [NTHQA](NTHQA-partnership-management.md) |
| Programs | [7C5WM](7C5WM-internship-lifecycle.md) · [IT0OE](IT0OE-internship-groups.md) |
| Enrollment | [MBB5R](MBB5R-registration.md) · [J9GBH](J9GBH-placement.md) · [920SO](920SO-account-application.md) · [95EVB](95EVB-user-crud-and-status.md) · [O2KCR](O2KCR-csv-import-export.md) · [EWCZ0](EWCZ0-account-slips.md) |
| Daily Ops | [1KSWL](1KSWL-daily-activity.md) · [2EHSE](2EHSE-supervision.md) · [3RU9S](3RU9S-incident.md) |
| Assessment | [ARDA6](ARDA6-assessment.md) · [AXKZW](AXKZW-evaluation.md) · [T657Z](T657Z-assignment.md) |
| Certification | [PKYX6](PKYX6-document-templates.md) · [ZUFG8](ZUFG8-handbooks.md) · [J0M04](J0M04-certification.md) · [WQGTP](WQGTP-file-uploads-media.md) · [7UB7S](7UB7S-pdf-generation.md) |
| Reporting | [R6BMW](R6BMW-reports.md) · [7H5D6](7H5D6-official-documents.md) |
| Maintenance | [8FVZA](8FVZA-job-queue-infrastructure.md) · [HBXCI](HBXCI-backup-system.md) · [7HNCF](7HNCF-gdpr-compliance.md) · [E1MSJ](E1MSJ-system-maintenance.md) · [06IB6](06IB6-deployment.md) · [3UOZP](3UOZP-dummy-data.md) · [9YUUK](9YUUK-data-archiving.md) |

---

## 5. Non-Functional Requirements

Project-level NFRs for MVP. Each row links to its owning spec for detailed acceptance criteria.
"N/A" in Target means the requirement is enforced architecturally and verified via tests.

| ID | Requirement | Target | Owning spec | Priority |
|----|-------------|--------|--------------|----------|
| NFR-QLHDO-S1 | Authorization at every layer: Policy (gatekeeping) + Action/Entity (business rules) | N/A | [T4B26](T4B26-rbac-and-authorization.md) | P0 |
| NFR-QLHDO-S2 | Input safety: no raw SQL concatenation (C3), Form Request/DTO validation, PII masked in logs | N/A | [89SRA](89SRA-logging-and-error-handling.md), [D2FT3](D2FT3-architecture.md) | P0 |
| NFR-QLHDO-S3 | Security headers on all responses: CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Referrer-Policy: strict-origin-when-cross-origin | N/A | [1PGM4](1PGM4-security-headers.md) | P0 |
| NFR-QLHDO-S4 | CSRF protection on all state-changing requests; API uses Sanctum token auth | N/A | [2CF4Y](2CF4Y-middleware-pipeline.md) | P0 |
| NFR-QLHDO-S5 | All dynamic output escaped; `{!! !!}` forbidden for user-generated content | N/A | [1PGM4](1PGM4-security-headers.md) | P0 |
| NFR-QLHDO-P1 | Pages render under 2 seconds at p95 under normal load (single-server, SQLite); Actions eager-load relations to prevent N+1 | <2s p95 | [ZT6VS](ZT6VS-core-infra-services.md) | P1 |
| NFR-QLHDO-P2 | Backup target: 4-hour RPO, under 1-hour RTO; backup retention configurable per policy | 4h RPO | [HBXCI](HBXCI-backup-system.md) | P1 |
| NFR-QLHDO-U1 | Responsive layout; WCAG AA contrast minimum on interactive elements; keyboard navigable on all forms and navigation | N/A | [8XMYS](8XMYS-layout-and-ui-system.md) | P1 |
| NFR-QLHDO-U2 | Every non-trivial workflow has an associated guide page at `guides/{feature}-guide.blade.php` | N/A | [8XMYS](8XMYS-layout-and-ui-system.md) | P1 |
| NFR-QLHDO-M1 | 4-layer module-first architecture: all code under `app/Modules/`; shared logic in `Core` | N/A | [D2FT3](D2FT3-architecture.md) | P0 |
| NFR-QLHDO-L1 | Indonesian primary + English secondary; locale stored in session and togglable at runtime | N/A | [YB22J](YB22J-settings-infrastructure.md), [52O1I](52O1I-branding-theme-locale.md) | P0 |
| NFR-QLHDO-D1 | SQLite (default) or MySQL; UUID PKs on all primary entities; migration-driven schema | N/A | [J68GZ](J68GZ-system-requirements.md) | P0 |
| NFR-QLHDO-G1 | GDPR: deletion logging and data-erasure workflows exist and are functional | N/A | [7HNCF](7HNCF-gdpr-compliance.md) | P1 |

### What is NOT an MVP NFR

The following were in the previous spec but are **deferred to post-MVP** based on MVP scope:

| What | Why deferred |
|------|-------------|
| p95 latency under 100 concurrent users | Requires load-test infrastructure; hand-tested at MVP scale |
| ≥80% code coverage on new code | Enforcement tooling exists; coverage % target set per-module post-MVP |
| ≥90% coverage on auth/audit/integrity paths | Same as above; set per-module targets post-MVP |
| Cross-browser pixel-perfect testing | Playwright smoke tests only at MVP; visual regression post-MVP |
| PWA / offline-first / service workers | Important for rural connectivity (BPS: 5–20% urban–rural gap) but scoped post-MVP |
| Formal WCAG audit by external tester | Internal AA checklist + keyboard nav tests at MVP |
| k6/jMeter load test in CI | p95 target hand-tested at MVP; automated load test post-MVP |
| Uptime SLA monitoring | Covered by `system:health`; dedicated uptime monitoring post-MVP |

---

## 6. API / Data Contracts

### 6.1 Identity Contract

- `users` table: `BaseModel` + `HasUuids` (UUID PK); one row per person; `role` column references `Role` enum.
- `Role` enum cases: `super_admin`, `admin`, `teacher`, `student`, `supervisor`; `Role::resolvesTo()` maps `admin-group`/`mentor`/`mentee` at runtime.

Full contract: [T4B26](T4B26-rbac-and-authorization.md) §4.2.

### 6.2 Global Helpers

```php
setting(string|array|null $key = null, mixed $default = null, bool $skipCache = false): mixed
brand(string $key, mixed $default = null): mixed
app_info(?string $key = null, mixed $default = null): mixed
```

Full contracts: [C8F0D](C8F0D-shared-utilities.md) (FR-QLHDO-SUP11), [YB22J](YB22J-settings-infrastructure.md).

### 6.3 Module Landscape

`app/` contains zero top-level business directories. All code lives in modules. Each module owns its
vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`,
`Notifications/`, `Policies/`, `Livewire/`, `Services/`, `Support/`, routes, and `lang/`. Module
dependency graph: [docs/refs/modules/index.md](../refs/modules/index.md).

---

## 7. Design Decisions

### DD-QLHDO-1 — Single-Tenant, Self-Hosted, MIT

**Decision:** Distribute as a self-packaged Laravel codebase on school-owned infrastructure.
**Rationale:** Data sovereignty, offline robustness, zero recurring cost, no vendor lock-in for
under-resourced SMK.
**Trade-off:** Per-school deployment cost accepted; no SaaS economics.

### DD-QLHDO-2 — Module-First Vertical Slicing

**Decision:** All code under `app/Modules/{Module}/Domain/{Domain}/`; shared infrastructure in `Core`.
**Rationale:** A business concept lives in one place — findable, independently testable, safe to
change without silent cross-module coupling.
**Trade-off:** Infrastructure must be deliberately extracted to Core.

### DD-QLHDO-3 — Primary Indonesian, Secondary English

**Decision:** Full translations in `lang/id/` (primary) and `lang/en/` (secondary); runtime toggle.
**Rationale:** PKL is an Indonesian curriculum mandate; English supports developers and bilingual schools.
**Trade-off:** Every user-facing string has a translation cost; enforced by D3 convention + scan.

### DD-QLHDO-4 — Spec-First, Testable Requirements Only

**Decision:** This spec contains only verifiable, testable requirements. Research inputs (regulation
text, field pain evidence, regulatory alignment) live in `docs/refs/articles/` and are explicitly
marked non-testable.
**Rationale:** Requirements without a test path are wishes, not specifications. Research informs
prioritization; it does not drive implementation.
**Trade-off:** Non-testable concerns (rural connectivity, government SOP variation) are addressed
post-MVP with explicit product decisions.

### DD-QLHDO-5 — No Tenant Isolation Overhead

**Decision:** Single-tenant by design; no `tenant_id` columns, no tenant-scoped scopes, no
multi-tenancy middleware.
**Rationale:** One school per deployment; keeps the model clean for MVP.
**Trade-off:** Multi-school deployments require separate installations (separate database + web root).

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Lifecycle coverage | All 12 phases have at least one spec with Full status | [index.md](index.md) phase tables |
| Architecture invariants | 0 violations in `scan_violations.py` | CI gate |
| Spec↔code alignment | 0 spec gaps in `scan_spec_tests.py` | Per-feature scan |
| Localization | 0 hardcoded user strings (D3 scan) | CI gate |
| Test suite | Full suite passes on every PR | CI |
| Backup | RTO drill confirms under 1 hour | Manual per-period drill |
| Module colocation | 100% of `app/` under `Modules/` | `scan_naming.py` |

---

## 9. Roadmap

### Prerequisites

None — this is spec-zero. All other specs depend on it; nothing depends on it.

### Build Order

Build in phase sequence per [index.md](index.md) §Build Order. Within each phase, build in the
listed spec order. The foundational specs (D2FT3 → FB792 → SE5Q9 → T4B26 → 89SRA) must be
completed before any business feature.

### Next Steps

| Order | Spec | Reason |
|-------|------|--------|
| 1 | [D2FT3-architecture](D2FT3-architecture.md) | Architecture contract |
| 2 | [FB792-tech-stack](FB792-tech-stack.md) | Pins dependency versions |
| 3 | [T4B26-rbac-and-authorization](T4B26-rbac-and-authorization.md) | Auth before any UI |
| 4 | [8XMYS-layout-and-ui-system](8XMYS-layout-and-ui-system.md) | UI foundation |
| 5 | [8NZAU-installation](8NZAU-installation.md) | Deployment first |
| 6 | [VEJCX-setup-wizard](VEJCX-setup-wizard.md) | Super admin onboarding |

---


## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
