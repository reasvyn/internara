# QLHDO — Internara Project Initialization

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

| Goal | Why |
|------|-----|
| **Digitize the complete PKL lifecycle** — placement → attendance → logbook → supervision → assessment → certificate | Ends the paper-and-chat workflow at scale |
| **Role-filtered canonical record** — every participant sees only what their role allows | Single source of truth, no informal data silos |
| **Anti-fraud attendance** — verifiable, timestamped, with evidence trail | BAN-PDM evidence, certificate integrity |
| **Remote supervision support** — digital monitoring where physical visits are impractical | Addresses PS-3 for schools with geographically dispersed placements |
| **Self-hosted, MIT-licensed, zero vendor cost per school** | Data sovereignty, accessible to under-resourced SMK |
| **Bilingual** — Indonesian primary, English secondary, with `__()` on all user-facing strings | Natively supports SMK staff and students |
| **Single-tenant by design** — no `tenant_id` overhead | MVP simplicity, no multi-tenancy complexity |
| **Non-goal:** Multi-tenant SaaS | Single-tenant is a design decision, not a limitation |
| **Non-goal:** HR / payroll features | Out of PKL scope |
| **Non-goal:** Real-time chat | WhatsApp is the existing platform; integration is not MVP |
| **Non-goal:** Government database sync (Dapodik/e-Rapor) | CSV import/export only |
| **Non-goal:** Mobile native apps | Responsive web covers the use case; BPS 2024: 72.78% internet access nationally |
| **Non-goal:** Full WCAG AAA / formal accessibility audit | WCAG AA contrast + keyboard nav MVP only; full audit is post-MVP |
| **Non-goal:** Offline-first / PWA | Important for rural connectivity (5–20% gap vs urban) but scoped post-MVP; progressive enhancement acceptable for MVP |

---

## 3. User Stories / Use Cases

Use Cases are optional to test, like Design Decisions (§7). The table format matches FR/NFR/DD;
fill `Layer` / `Status` only when the UC has a verifiable, code-testable consequence at this
spec's level — otherwise they remain `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SETUP-001 | School Initializes: Super Admin runs `setup:install`; Admin configures branding/locale/school profile, departments, academic years, companies, and partnership slot quotas; `superadmin` account exists afterward | P0 | F | Partial |
| UC-LCYC-001 | Student Completes the PKL Lifecycle: registration → placement → attendance → logbook → assignments → certificate, leaving a full digital trail | P0 | — | — |
| UC-SUP-001 | Teacher Supervises and Assesses: reviews logbooks, logs monitoring visits, scores against rubrics, finalizes grade card | P0 | — | — |
| UC-EVAL-001 | Supervisor Evaluates Industry-Side Performance: verifies attendance, reviews logbook, submits competency evaluations into final aggregation | P1 | — | — |
| UC-OPS-001 | Admin Operates and Audits: manages users/announcements, monitors health + audit logs, runs backups and GDPR export/erasure | P0 | F | Partial |

### Role Model

- **Super Admin** (`super_admin`) — infrastructure and superuser access; name immutable `Super Admin`, username `superadmin`
- **Admin** (`admin`) — school operations: users, programs, companies, departments
- **Teacher** (`teacher`) — academic supervision: journal review, assignment grading, monitoring visits
- **Student** (`student`) — program participation: attendance, logbook, assignments, certificate download
- **Supervisor** (`supervisor`) — industry-side: attendance verification, journal review, competency evaluation

Three **runtime-functional roles** are resolved via `Role::resolvesTo()` for business logic only:
`admin-group` (→ super_admin/admin), `mentor` (→ teacher/supervisor), `mentee` (→ student). Full
RBAC contract in [T4B26](T4B26-rbac-and-authorization.md).

### 3.1 Setup & Operation

#### UC-SETUP-001 — School Initializes

**Actor(s):** Super Admin → Admin
**Flow:** 1. Super Admin runs `setup:install` → 2. Admin configures branding/locale/school profile → 3. Admin creates departments and academic years → 4. Admin registers companies and partnerships with slot quotas.
**Postconditions:** School can enroll students; `superadmin` account exists.
**Governing specs:** [8NZAU](8NZAU-installation.md), [VEJCX](VEJCX-setup-wizard.md), [C9ZB6](C9ZB6-recovery-ecosystem.md), [52O1I](52O1I-branding-theme-locale.md).

#### UC-OPS-001 — Admin Operates and Audits

**Actor(s):** Admin / Super Admin
**Flow:** 1. Admin manages users and announcements → 2. Admin monitors health checks and audit logs → 3. Admin runs backups and GDPR export/erasure per policy.
**Postconditions:** Operation observable and recoverable within RPO/RTO targets.
**Governing specs:** [95EVB](95EVB-user-crud-and-status.md), [3S55V](3S55V-announcement-system.md), [E1MSJ](E1MSJ-system-maintenance.md), [HBXCI](HBXCI-backup-system.md).

### 3.2 PKL Lifecycle

#### UC-LCYC-001 — Student Completes the PKL Lifecycle

**Actor(s):** Student
**Flow:** 1. Student registers → 2. Admin verifies and places the student → 3. Student clocks in/out, keeps a reflective logbook, submits assignments, acknowledges handbooks → 4. Student downloads certificate after assessment and report sign-off.
**Postconditions:** Full digital trail of the internship exists.
**Governing specs:** [MBB5R](MBB5R-registration.md), [J9GBH](J9GBH-placement.md), [1KSWL](1KSWL-daily-activity.md), [T657Z](T657Z-assignment.md), [J0M04](J0M04-certification.md).

#### UC-SUP-001 — Teacher Supervises and Assesses

**Actor(s):** Teacher
**Flow:** 1. Teacher supervises assigned students → 2. Teacher reviews logbooks and logs monitoring visits → 3. Teacher or supervisor scores against rubrics → 4. Teacher compiles and finalizes the grade card.
**Postconditions:** Grades aggregated; finalized artifacts immutable.
**Governing specs:** [2EHSE](2EHSE-supervision.md), [ARDA6](ARDA6-assessment.md), [R6BMW](R6BMW-reports.md).

#### UC-EVAL-001 — Supervisor Evaluates Industry-Side Performance

**Actor(s):** Supervisor (DUDI)
**Flow:** 1. Supervisor verifies attendance and reviews logbook entries → 2. Supervisor submits competency evaluations for assigned students → 3. Evaluations flow into final score aggregation.
**Postconditions:** Industry-side scores present in the final record.
**Governing specs:** [1KSWL](1KSWL-daily-activity.md), [ARDA6](ARDA6-assessment.md), [T4B26](T4B26-rbac-and-authorization.md) §4.2 (Cross-Role Proxy).

---

## 4. Functional Requirements

Global defaults every feature spec inherits. A feature spec may tighten but never violate these.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

### 4.1 Cross-Cutting (MVP Scoped)

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-GLB-001 | Every user-facing string uses the `__()` helper; all modules ship `lang/en/` and `lang/id/` · [YB22J](YB22J-settings-infrastructure.md) | P0 | A | Planned |
| FR-GLB-002 | Exactly five stored roles — `super_admin`, `admin`, `teacher`, `student`, `supervisor` — plus three runtime-resolved functional roles via `Role::resolvesTo()` · [T4B26](T4B26-rbac-and-authorization.md) §4.2 | P0 | A | Planned |
| FR-GLB-003 | `superadmin` account is immutable: name always `Super Admin`, username always `superadmin`, non-deletable · [8NZAU](8NZAU-installation.md) | P0 | F | Planned |
| FR-GLB-004 | All administrative mutations are audit-logged (activity channel) with PII masking · [89SRA](89SRA-logging-and-error-handling.md) | P0 | F | Planned |
| FR-GLB-005 | Sensitive endpoints are rate-limited: login 5/60s, forgot-password 3/3600s, reset 5/300s · [2CF4Y](2CF4Y-middleware-pipeline.md) | P0 | F | Planned |
| FR-GLB-006 | `php artisan system:health` covers: PHP version, extensions, memory, DB, migrations, storage, queue, cache, APP_KEY · [J68GZ](J68GZ-system-requirements.md) | P0 | F | Planned |
| FR-GLB-007 | All primary models use UUID primary keys via `BaseModel` / `HasUuids` · [SE5Q9](SE5Q9-base-classes.md) | P0 | A | Planned |
| FR-GLB-008 | Authorization enforced at both Policy layer (gatekeeping) and Action/Entity layer (business rule via `RejectedException`) · [T4B26](T4B26-rbac-and-authorization.md) | P0 | A | Planned |
| FR-GLB-009 | All user input validated server-side: Form Request classes for HTTP, validated DTOs for Actions · [D2FT3](D2FT3-architecture.md) | P0 | A | Planned |
| FR-GLB-010 | Business-rule violations throw `RejectedException` with a translatable user-facing message; unexpected exceptions are logged with context and shown as generic failure · [89SRA](89SRA-logging-and-error-handling.md) | P0 | A | Planned |
| FR-GLB-011 | File uploads validated server-side: MIME type, configurable size per module, filename safety; stored outside web root with non-guessable filenames · [WQGTP](WQGTP-file-uploads-media.md) | P0 | F | Planned |
| FR-GLB-012 | Navigation layout renders menu groups from `config/menu.php` ordered by registration sequence; active route highlighted · [8XMYS](8XMYS-layout-and-ui-system.md) | P1 | F | Planned |
| FR-GLB-013 | Attendance records are timestamped, immutable after admin sign-off, and include the clock-in/out timestamp with actor identity · [1KSWL](1KSWL-daily-activity.md) | P0 | F | Planned |
| FR-GLB-014 | Logbook entries are daily, timestamped, and editable only within the same academic day by the student who created them · [1KSWL](1KSWL-daily-activity.md) | P0 | F | Planned |

---

## 5. Non-Functional Requirements

Project-level NFRs for MVP. Each row's owning spec (linked in the requirement) carries detailed
acceptance criteria. `N/A` target means the requirement is enforced architecturally and verified
via tests. NFRs deliberately deferred to post-MVP are tracked in §10 (R-1 … R-7).

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SEC-001 | Authorization at every layer: Policy (gatekeeping) + Action/Entity (business rules) · [T4B26](T4B26-rbac-and-authorization.md) | N/A | P0 | A | Planned |
| NFR-SEC-002 | Input safety: no raw SQL concatenation (C3), Form Request/DTO validation, PII masked in logs · [89SRA](89SRA-logging-and-error-handling.md), [D2FT3](D2FT3-architecture.md) | N/A | P0 | A | Planned |
| NFR-SEC-003 | Security headers on all responses: CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Referrer-Policy: strict-origin-when-cross-origin · [1PGM4](1PGM4-security-headers.md) | N/A | P0 | F | Planned |
| NFR-SEC-004 | CSRF protection on all state-changing requests; API uses Sanctum token auth · [2CF4Y](2CF4Y-middleware-pipeline.md) | N/A | P0 | F | Planned |
| NFR-SEC-005 | All dynamic output escaped; `{!! !!}` forbidden for user-generated content · [1PGM4](1PGM4-security-headers.md) | N/A | P0 | A | Planned |
| NFR-BCK-001 | Backup target: 4-hour RPO, under 1-hour RTO; backup retention configurable per policy · [HBXCI](HBXCI-backup-system.md) | 4h RPO / 1h RTO | P1 | F | Planned |
| NFR-UX-001 | Responsive layout; WCAG AA contrast minimum on interactive elements; keyboard navigable on all forms and navigation · [8XMYS](8XMYS-layout-and-ui-system.md) | N/A | P1 | B | Planned |
| NFR-UX-002 | Every non-trivial workflow has an associated guide page at `guides/{feature}-guide.blade.php` · [8XMYS](8XMYS-layout-and-ui-system.md) | N/A | P1 | A | Planned |
| NFR-MOD-001 | 4-layer module-first architecture: all code under `app/Modules/`; shared logic in `Core` · [D2FT3](D2FT3-architecture.md) | N/A | P0 | A | Planned |
| NFR-DATA-001 | SQLite (default) or MySQL; UUID PKs on all primary entities; migration-driven schema · [J68GZ](J68GZ-system-requirements.md) | N/A | P0 | A | Planned |
| NFR-I18N-001 | Indonesian primary + English secondary; locale stored in session and togglable at runtime · [YB22J](YB22J-settings-infrastructure.md), [52O1I](52O1I-branding-theme-locale.md) | N/A | P0 | F | Planned |
| NFR-GDPR-001 | GDPR: deletion logging and data-erasure workflows exist and are functional · [7HNCF](7HNCF-gdpr-compliance.md) | N/A | P1 | F | Planned |

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

Full contracts: [C8F0D](C8F0D-shared-utilities.md) (`app_info()` is FR-C8F0D-SUP11) and
[YB22J](YB22J-settings-infrastructure.md) (`setting()` / `brand()`).

### 6.3 Module Landscape

`app/` contains zero top-level business directories. All code lives in modules. Each module owns its
vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`,
`Notifications/`, `Policies/`, `Livewire/`, `Services/`, `Support/`, routes, and `lang/`. Module
dependency graph: [docs/refs/modules/index.md](../refs/modules/index.md).

---

## 7. Design Decisions

Design Decisions are optional to test like Use Cases (§3); `Layer` / `Status` stay `—` unless a
decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ARCH-001 | Single-tenant, self-hosted, MIT — distributed as a self-packaged Laravel codebase on school-owned infrastructure | P0 | — | — |
| DD-ARCH-002 | Module-first vertical slicing — all code under `app/Modules/{Module}/Domain/{Domain}/`; shared infrastructure in `Core` | P0 | — | — |
| DD-ARCH-003 | Primary Indonesian, secondary English — full translations in `lang/id/` + `lang/en/` with runtime toggle | P1 | — | — |
| DD-ARCH-004 | Spec-first, testable requirements only — research inputs stay in `docs/refs/articles/` and are explicitly non-testable | P0 | — | — |
| DD-ARCH-005 | No tenant isolation overhead — no `tenant_id` columns, scopes, or multi-tenancy middleware | P0 | — | — |

### 7.1 Deployment & Tenancy

#### DD-ARCH-001 — Single-Tenant, Self-Hosted, MIT

**Decision:** Distribute as a self-packaged Laravel codebase on school-owned infrastructure.
**Rationale:** Data sovereignty, offline robustness, zero recurring cost, no vendor lock-in for
under-resourced SMK.
**Trade-off:** Per-school deployment cost accepted; no SaaS economics.

#### DD-ARCH-005 — No Tenant Isolation Overhead

**Decision:** Single-tenant by design; no `tenant_id` columns, no tenant-scoped scopes, no
multi-tenancy middleware.
**Rationale:** One school per deployment; keeps the model clean for MVP.
**Trade-off:** Multi-school deployments require separate installations (separate database + web root).

### 7.2 Architecture

#### DD-ARCH-002 — Module-First Vertical Slicing

**Decision:** All code under `app/Modules/{Module}/Domain/{Domain}/`; shared infrastructure in `Core`.
**Rationale:** A business concept lives in one place — findable, independently testable, safe to
change without silent cross-module coupling.
**Trade-off:** Infrastructure must be deliberately extracted to Core.

### 7.3 Localization

#### DD-ARCH-003 — Primary Indonesian, Secondary English

**Decision:** Full translations in `lang/id/` (primary) and `lang/en/` (secondary); runtime toggle.
**Rationale:** PKL is an Indonesian curriculum mandate; English supports developers and bilingual schools.
**Trade-off:** Every user-facing string has a translation cost; enforced by D3 convention + scan.

### 7.4 Spec Discipline

#### DD-ARCH-004 — Spec-First, Testable Requirements Only

**Decision:** This spec contains only verifiable, testable requirements. Research inputs (regulation
text, field pain evidence, regulatory alignment) live in `docs/refs/articles/` and are explicitly
marked non-testable.
**Rationale:** Requirements without a test path are wishes, not specifications. Research informs
prioritization; it does not drive implementation.
**Trade-off:** Non-testable concerns (rural connectivity, government SOP variation) are addressed
post-MVP with explicit product decisions.

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

### Phase Inventory

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

Items **not yet decided, explicitly deferred, or unverified** at the time of writing. The former
"Not an MVP NFR" list (§5 of the previous revision) is tracked here so no post-MVP candidate is lost.

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| R-1 | Deferred: p95 latency target under 100 concurrent users — needs load-test infrastructure; hand-tested at MVP scale | Deferred | Maintainer | — |
| R-2 | Deferred: ≥80% code coverage on new code and ≥90% on auth/audit/integrity paths — per-module targets set post-MVP | Deferred | Maintainer | — |
| R-3 | Deferred: cross-browser pixel-perfect testing — Playwright smoke tests only at MVP; visual regression post-MVP | Deferred | Maintainer | — |
| R-4 | Deferred: PWA / offline-first / service workers — important for rural connectivity (BPS: 5–20% urban–rural gap) but scoped post-MVP | Deferred | Maintainer | — |
| R-5 | Deferred: formal WCAG audit by external tester — internal AA checklist + keyboard-nav tests at MVP | Deferred | Maintainer | — |
| R-6 | Deferred: k6/jMeter load test in CI — p95 target hand-tested at MVP; automated load test post-MVP | Deferred | Maintainer | — |
| R-7 | Deferred: uptime SLA monitoring — covered by `system:health` at MVP; dedicated uptime monitoring post-MVP | Deferred | Maintainer | — |
| A-1 | We assume MVP scale (≈500–1,000 students, 150–300 DUDI partners, per PS-1) doesn't warrant load-test infrastructure at MVP | Accepted | Maintainer | — |
| A-2 | We assume the research evidence base (`pkl-operational-research.md`, POV 1–5, Sep 2026) remains valid through MVP | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — module-first 4-layer architecture (governing spec for FR-GLB-007/008/009)