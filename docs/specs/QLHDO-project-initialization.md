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
[docs/specs/index.md](index.md). The requirements here are project-global: each feature spec may
tighten but never violate them. Research evidence that *informs* these requirements lives in
[`../refs/articles/`](../refs/articles/) and is explicitly non-testable (DD-ARCH-004) — the
requirement statements below, by contrast, are verifiable.

---

## 1. Problem Statements

Each problem statement cites the field evidence it is grounded in. Evidence files are in
`docs/refs/articles/` (`pkl-operational-research.md`, `curriculum-compliance.md`); the `→ Requirement`
traces connect each statement to the global requirements (§4) that address it.

### PS-1 — Fragmented Administration at Scale

A medium-to-large SMK manages 500–1,000 active students across 150–300 partner companies (DUDI) per
placement period: ≈45,000 attendance records, 6,000 logbook entries, 1,500–2,500 submissions, and 500
evaluation forms per period. No manual or semi-digital process (chat + Excel) survives that volume
without data loss and delay. National context: 14,391 SMK and 4.88M students (2024/25), with
Gr.11+Gr.12 ≈ 3.16M PKL-candidate students/year — the same manual mechanics replicate across every
school *(Source: pkl-operational-research.md "Scale", POV 1–5, Sep 2026)*.
**→ Requirement:** FR-GLB-001 (localized, single UI), FR-GLB-013/014 (attendance & logbook integrity),
NFR-MOD-001 (module colocation). Research inputs stay non-testable (DD-ARCH-004).

### PS-2 — Anti-Fraud Accountability Gap

Paper logbooks and WhatsApp-reported attendance are trivially falsifiable: "Sering terjadi
ketidakjujuran siswa dalam pengisian absensi dan logbook" (SMKN 1 Sintuk Toboh Gadang 2022); paper
books are "mudah dimanipulasi" without attached documentation (SMKN 1 Gesi). A broad, industry-validated
fix-market converges on the same core: **QR-code/GPS/selfie check-in, daily e-logbook, real-time
supervisor visibility** (Sintuk Toboh Gadang, Gesi, Al Hidayah Cirebon, YPT Pringsewu, PKLTRACK, PKL
Smart). Schools need digital evidence of the educational process for BAN-PDM accreditation evidence
folders and industry certificate sign-off *(Source: pkl-operational-research.md POV 3, "Cross-Cutting
Synthesis" item 4, POV 3)*.
**→ Requirement:** FR-GLB-013 (timestamped, actor-identified attendance), FR-GLB-014 (immutable logbook
window), NFR-SEC-005 (escaped output).

### PS-3 — Supervision Degrades with Distance

City sites (<15 km): 3–4 visits/period. Out-of-town (50–150 km): 1 visit. Remote (>150 km): 0–1 visits
(Palangka Raya study, Kanderang Tingang Jan 2026; average 1.6 visits/period). The standard monitoring
visit (Panduan Monitoring PKL) requires attendance verification, work journal review, supervisor
interview, and documentation check — and the same distance effect degrades *industry* supervision too
("degradasi kualitas bimbingan seiring bertambahnya jarak"). Without digital tools, school supervision
collapses beyond city distance *(Source: pkl-operational-research.md POV 1, POV 3, POV 5)*.
**→ Requirement:** FR-GLB-004 (audit trail), NFR-UX-001/002 (usable, guided workflows), route through
[2EHSE](2EHSE-supervision.md) supervision tooling.

### PS-4 — Multi-System Re-Entry Burden

Coordinator (Pokja PKL) is the manual hub. The same data is re-typed across WhatsApp → Excel → e-Rapor
→ Dapodik (PKS records, rombel, teacher data), while assembling physical BAN-PDM evidence folders.
DIY stopgaps (SMART PKL AppSheet on a shared personal Gmail account; Google Sites hubs; e-jurnal portals)
proliferate with data-ownership and versioning risk *(Source: pkl-operational-research.md POV 2,
"Cross-Cutting Synthesis" item 2)*.
**→ Requirement:** UC-OPS-001 (operate & audit), FR-GLB-004 (audit logging).

### PS-5 — Grade Aggregation is Manual and Late

Final grades are assembled from separate 0–100 sheets (site leader, field advisor, seminar) across
paper and Google Docs/Scribd formats, then re-entered into e-Rapor: "Kurang efisien dalam mengolah
nilai… kesalahan… lambatnya penyerahan data" (JRAMI Unindra). Different stakeholders weight different
dimensions — schools weight administrative completeness (logbook), industry weights soft skills —
so the aggregate must be role-aware and auditable *(Source: pkl-operational-research.md POV 1, POV 5)*.
**→ Requirement:** [ARDA6](ARDA6-assessment.md) and [R6BMW](R6BMW-reports.md) feature specs; global
FR-GLB-008 (dual-layer authorization) guarantees the aggregation path is role-gated.

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
| **Non-goal:** Telemetry / usage reporting / external API calls for core features | Data sovereignty per [self-hosted ADR](../adr/adr-self-hosted-single-tenant.md) |
| **Non-goal:** HR / payroll features | Out of PKL scope |
| **Non-goal:** Real-time chat | WhatsApp is the existing platform; integration is not MVP |
| **Non-goal:** Government database sync (Dapodik/e-Rapor) | CSV import/export only |
| **Non-goal:** Mobile native apps | Responsive web covers the use case; BPS 2024: 72.78% internet access nationally |
| **Non-goal:** Full WCAG AAA / formal accessibility audit | WCAG AA contrast + keyboard nav MVP only; full audit is post-MVP |
| **Non-goal:** Offline-first / PWA | Important for rural connectivity (5–20% gap vs urban) but scoped post-MVP; progressive enhancement acceptable for MVP |

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). The table format matches FR/NFR/DD;
fill `Layer` / `Status` only when the UC has a verifiable, code-testable consequence at this spec's
level — otherwise they remain `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SETUP-001 | School Initializes: Super Admin runs `setup:install`; Admin configures branding/locale/school profile, departments, academic years, companies, and partnership slot quotas; `superadmin` account exists afterward | P0 | F | Partial |
| UC-LCYC-001 | Student Completes the PKL Lifecycle: registration → placement → attendance → logbook → assignments → certificate, leaving a full digital trail | P0 | — | — |
| UC-SUP-001 | Teacher Supervises and Assesses: reviews logbooks, logs monitoring visits, scores against rubrics, finalizes grade card | P0 | — | — |
| UC-EVAL-001 | Supervisor Evaluates Industry-Side Performance: verifies attendance, reviews logbook, submits competency evaluations into final aggregation | P1 | — | — |
| UC-OPS-001 | Admin Operates and Audits: manages users/announcements, monitors health + audit logs, runs backups and GDPR export/erasure | P0 | F | Partial |

> **UC → layer note:** UC-LCYC-001, UC-SUP-001, UC-EVAL-001 span multiple feature specs and are not
> code-verifiable at this spec's scope — their `Layer`/`Status` stay `—` (template §3). Their
> verifiable behavior is decomposed into the FR/NFR rows in §4/§5 and the governing feature specs.

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
**Preconditions:** Fresh deployment; no non-seed data; APP_KEY set.
**Flow:** 1. Super Admin runs `setup:install` → 2. Admin configures branding/locale/school profile → 3. Admin creates departments and academic years → 4. Admin registers companies and partnerships with slot quotas.
**Postconditions:** School can enroll students; `superadmin` account exists (FR-GLB-003); settings persist via `setting()` (FR-GLB-001).
**Acceptance basis:** After the flow, an authenticated Super Admin can reach the setup-complete milestone and an Admin can enroll a student; verified by the governing specs' F-layer tests.
**Governing specs:** [8NZAU](8NZAU-installation.md), [VEJCX](VEJCX-setup-wizard.md), [C9ZB6](C9ZB6-recovery-ecosystem.md), [52O1I](52O1I-branding-theme-locale.md).

#### UC-OPS-001 — Admin Operates and Audits

**Actor(s):** Admin / Super Admin
**Preconditions:** Setup complete (UC-SETUP-001); operator holds `admin` or `super_admin` role.
**Flow:** 1. Admin manages users and announcements → 2. Admin monitors health checks and audit logs → 3. Admin runs backups and GDPR export/erasure per policy.
**Postconditions:** Operation observable and recoverable within RPO/RTO targets (NFR-BCK-001); every governed mutation audit-logged (FR-GLB-004).
**Acceptance basis:** Health check reflects system state (FR-GLB-006); backup/restore RTO drill within target; GDPR erasure removes the subject's personal rows.
**Governing specs:** [95EVB](95EVB-user-crud-and-status.md), [3S55V](3S55V-announcement-system.md), [E1MSJ](E1MSJ-system-maintenance.md), [HBXCI](HBXCI-backup-system.md).

### 3.2 PKL Lifecycle

#### UC-LCYC-001 — Student Completes the PKL Lifecycle

**Actor(s):** Student
**Preconditions:** Student account exists; active academic year; placement open.
**Flow:** 1. Student registers → 2. Admin verifies and places the student → 3. Student clocks in/out, keeps a reflective logbook, submits assignments, acknowledges handbooks → 4. Student downloads certificate after assessment and report sign-off.
**Postconditions:** Full digital trail of the internship exists; attendance and logbook respect integrity windows (FR-GLB-013/014).
**Acceptance basis:** A completed lifecycle yields a retrievable, immutable artifact set; decomposed and verified per governing feature spec.
**Governing specs:** [MBB5R](MBB5R-registration.md), [J9GBH](J9GBH-placement.md), [1KSWL](1KSWL-daily-activity.md), [T657Z](T657Z-assignment.md), [J0M04](J0M04-certification.md).

#### UC-SUP-001 — Teacher Supervises and Assesses

**Actor(s):** Teacher
**Preconditions:** Teacher is assigned students for an active period.
**Flow:** 1. Teacher supervises assigned students → 2. Teacher reviews logbooks and logs monitoring visits → 3. Teacher or supervisor scores against rubrics → 4. Teacher compiles and finalizes the grade card.
**Postconditions:** Grades aggregated; finalized artifacts immutable.
**Acceptance basis:** Post-finalize grade card rejects mutation; review and scoring actions are audit-logged (FR-GLB-004).
**Governing specs:** [2EHSE](2EHSE-supervision.md), [ARDA6](ARDA6-assessment.md), [R6BMW](R6BMW-reports.md).

#### UC-EVAL-001 — Supervisor Evaluates Industry-Side Performance

**Actor(s):** Supervisor (DUDI)
**Preconditions:** Supervisor associated with the student's company/partnership.
**Flow:** 1. Supervisor verifies attendance and reviews logbook entries → 2. Supervisor submits competency evaluations for assigned students → 3. Evaluations flow into final score aggregation.
**Postconditions:** Industry-side scores present in the final record.
**Acceptance basis:** Evaluations appear in the aggregation subset visible to the teacher's grade card (FR-GLB-008 role gating).
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
| FR-GLB-007 | All primary models use UUID v7 primary keys via `BaseModel` / `HasUuids`; foreign keys use `foreignUuid()->constrained()` — no mixed key types · [SE5Q9](SE5Q9-base-classes.md) | P0 | A | Planned |
| FR-GLB-008 | Authorization enforced at both Policy layer (gatekeeping) and Action/Entity layer (business rule via `RejectedException`) · [T4B26](T4B26-rbac-and-authorization.md) | P0 | A | Planned |
| FR-GLB-009 | All user input validated server-side: Form Request classes for HTTP, validated DTOs for Actions · [D2FT3](D2FT3-architecture.md) | P0 | A | Planned |
| FR-GLB-010 | Business-rule violations throw `RejectedException` with a translatable user-facing message; unexpected exceptions are logged with context and shown as generic failure · [89SRA](89SRA-logging-and-error-handling.md) | P0 | A | Planned |
| FR-GLB-011 | File uploads validated server-side: MIME type, configurable size per module, filename safety; stored outside web root with non-guessable filenames · [WQGTP](WQGTP-file-uploads-media.md) | P0 | F | Planned |
| FR-GLB-012 | Navigation layout renders menu groups from `config/menu.php` ordered by registration sequence; active route highlighted · [8XMYS](8XMYS-layout-and-ui-system.md) | P1 | F | Planned |
| FR-GLB-013 | Attendance records are timestamped, immutable after admin sign-off, and include the clock-in/out timestamp with actor identity · [1KSWL](1KSWL-daily-activity.md) | P0 | F | Planned |
| FR-GLB-014 | Logbook entries are daily, timestamped, and editable only within the same academic day by the student who created them · [1KSWL](1KSWL-daily-activity.md) | P0 | F | Planned |

#### FR-GLB-001 — Localized strings

- Every user-facing string passes through `__()`; no hardcoded text in Blade templates, Livewire components, or notifications.
- Each module ships `lang/en/` and `lang/id/` with mirrored keys; enforced by the D3 scan.
- **Edge case:** dynamic content (student names, company names) is never concatenated into a message — pass it as a placeholder parameter to `__()`.

#### FR-GLB-002 — Five-role RBAC

- Exactly the five stored roles exist as `Role` enum cases; adding or renaming a stored role requires a spec amendment.
- `Role::resolvesTo()` resolves `admin-group` → `super_admin`/`admin`, `mentor` → `teacher`/`supervisor`, `mentee` → `student` for business logic only (contract in T4B26 §4.2).
- **Verification:** unit-test the resolution map for every role input; ensure unresolvable input throws/never silently grants (layer `A`).

#### FR-GLB-003 — Immutable superadmin

- After `setup:install`, a `superadmin` user exists with name `Super Admin` and username `superadmin`.
- The account is non-deletable; its name and username cannot be modified; violations are rejected at both UI and Action level.
- **Edge case:** a second Super Admin-role account is a normal `admin_group` user — the named `superadmin` is the only immutable singleton.

#### FR-GLB-004 — Audit-logged mutations

- Every administrative mutation on governed entities writes an activity-log entry including actor identity and a change summary.
- Entries mask PII; secrets never reach logs (dual-channel SmartLogger, activity channel).
- **Verification:** a governed mutation produces exactly one activity entry with the acting user id and a before/after diff; PII fields appear masked.

#### FR-GLB-005 — Rate-limited endpoints

- login: 5 requests per 60s; forgot-password: 3 per 3600s; reset: 5 per 300s; exceeding returns HTTP 429.
- Enforcement via throttling middleware (2CF4Y), keyed per actor/IP; limits configurable.
- **Verification:** burst requests beyond the window return 429; config keys are overridable without code change.

#### FR-GLB-006 — system:health coverage

- `php artisan system:health` reports PHP version, required extensions, memory limit, DB connectivity, pending migrations, storage writable, queue, cache, and APP_KEY presence.
- A failing check yields a non-zero exit code and a failed status in the summary.
- **Verification:** each listed subsystem appears in output; a deliberately broken dependency marks that check failed and non-zero exit.

#### FR-GLB-007 — UUID v7 primary keys

- All primary entities extend `BaseModel` (uses `HasUuids` with ordered UUID v7); `id` is a UUID PK; no auto-increment primary keys on primary entities.
- Foreign keys use `foreignUuid()->constrained()` in every migration with composite indexes; mixed key types are forbidden (ADR: UUID primary keys).
- **Verification:** arch scan (layer `A`) asserts no auto-increment PK on primary entities; migration review asserts `foreignUuid()->constrained()` FKs.

#### FR-GLB-008 — Dual-layer authorization

- The Policy layer gates route/resource access (403); the Action/Entity layer re-validates the business rule and throws `RejectedException` when violated.
- Calling an Action directly must not bypass authorization.
- **Verification:** for each protected mutation, a direct Action call without authorization throws `RejectedException`; an authenticated-but-unauthorized HTTP call returns 403 (layer `A` + spot `F`).

#### FR-GLB-009 — Server-side validation

- HTTP entry points validate via Form Request classes; Action entry points validate via DTOs; `$request->all()` never feeds `create()`/`update()`.
- **Verification:** invalid payloads reject before persistence; DTO validation runs inside `execute()`.

#### FR-GLB-010 — RejectedException contract

- Business-rule violations throw `RejectedException` carrying a translatable, user-facing message.
- Unexpected exceptions are logged with context and shown to the user as a generic failure (no stack traces in responses).
- **Verification:** a business-rule violation surfaces the translatable message; an unexpected exception surfaces a generic message and writes a context-log entry.

#### FR-GLB-011 — Upload validation & storage

- Uploads are validated on MIME type, per-module configurable max size, and filename safety.
- Files are stored outside the web root with non-guessable names; public URLs are entity-derived.
- **Verification:** a disallowed MIME or oversized file is rejected; stored path is outside web root and name is non-guessable.

#### FR-GLB-012 — Menu from config

- Navigation menu groups render from `config/menu.php` in registration order; active route is highlighted.
- No menu group is hardcoded in Blade outside the config-driven layout component.
- **Verification:** adding an entry to `config/menu.php` changes the rendered menu without a Blade edit.

#### FR-GLB-013 — Attendance integrity

- Attendance records carry clock-in/out timestamps and actor identity.
- Records are immutable after admin sign-off; any pre-sign-off edit is audit-logged.
- **Verification:** after sign-off the record is immutable; a pre-sign-off edit writes an audit entry.

#### FR-GLB-014 — Logbook edit window

- Logbook entries are daily and timestamped; editable only within the same academic day and only by the owning student; later edits are rejected.
- **Verification (edge cases):** same-day owner edit succeeds; same-day non-owner is rejected; next-day edit is rejected regardless of owner.

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

### 5.1 Security

#### NFR-SEC-001 — Authorization at every layer

- **Measurement:** arch scan over Policies and Actions; a direct Action call on a protected mutation is rejected when unauthorized.
- **Verification:** `scan_class_contracts.py` + policy-allow/deny unit tests per role.

#### NFR-SEC-002 — Input safety & PII masking

- **Measurement:** static scan for raw SQL concatenation (C3) and `$request->all()` → `create()`/`update()` (D5); log review for PII leakage.
- **Verification:** `scan_violations.py` clean on C3/D5; SmartLogger masks configured PII attributes.

#### NFR-SEC-003 — Security headers

- **Measurement:** every HTTP response carries the four headers.
- **Verification:** feature test asserts headers on a representative sample of pages (layer `F`).

#### NFR-SEC-004 — CSRF & token auth

- **Measurement:** all state-changing web requests carry a valid CSRF token; API endpoints use Sanctum.
- **Verification:** an unsigned state-changing request is rejected (419/403); Sanctum guards the API routes.

#### NFR-SEC-005 — Escaped output

- **Measurement:** arch scan asserts no `{!! !!}` on user-generated content; user input rendered via `{{ }}`.
- **Verification:** `scan_security.py` XSS checks clean (layer `A`).

### 5.2 Backup

#### NFR-BCK-001 — Backup RPO/RTO

- **Measurement:** RPO ≤ 4h, RTO < 1h from last backup.
- **Verification:** periodic restore drill on staging; backup retention configurable per policy (governing [HBXCI](HBXCI-backup-system.md)).

### 5.3 UX

#### NFR-UX-001 — Responsive & accessible

- **Measurement:** responsive breakpoints render without horizontal scroll; WCAG AA contrast on interactive elements; full keyboard navigation.
- **Verification:** browser smoke journey on primary flows (layer `B`); manual contrast spot-check (non-testable marker applies to pure-contrast rows).

#### NFR-UX-002 — Guide pages

- **Measurement:** each non-trivial workflow ships a `guides/{feature}-guide.blade.php`.
- **Verification:** arch scan asserts guide file presence for workflows defined as non-trivial (layer `A`).

### 5.4 Architecture & Data

#### NFR-MOD-001 — Module-first colocation

- **Measurement:** 100% of business code under `app/Modules/`; shared infra in `app/Modules/Core/`.
- **Verification:** `scan_naming.py` + `scan_module_boundaries.py` clean.

#### NFR-DATA-001 — DB & PK strategy

- **Measurement:** SQLite default; MySQL compatible; UUID PKs on primary entities; schema via migrations.
- **Verification:** fresh migrate on SQLite and MySQL in CI; arch scan for UUID PKs (layer `A`).

### 5.5 Globalization

#### NFR-I18N-001 — Bilingual runtime toggle

- **Measurement:** Indonesian primary, English secondary; locale in session; toggleable at runtime.
- **Verification:** switching locale re-renders strings without restart; all D3 keys present in both `lang/` files.

### 5.6 Privacy

#### NFR-GDPR-001 — GDPR erasure

- **Measurement:** deletion logged; data-erasure workflow functional.
- **Verification:** erasure removes subject rows and writes a deletion record (governing [7HNCF](7HNCF-gdpr-compliance.md)).

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against. Full per-feature contracts live in the
governing specs; this section fixes the project-global contracts every feature spec relies on.

### 6.1 Identity Contract

- `users` table: `BaseModel` + `HasUuids` (UUID v7 PK); one row per person; `role` column references `Role` enum.
- `Role` enum cases: `super_admin`, `admin`, `teacher`, `student`, `supervisor`.
- `Role::resolvesTo(): Role[]` maps each functional role to the stored roles it stands for —
  `admin-group` → `super_admin`/`admin`, `mentor` → `teacher`/`supervisor`, `mentee` → `student`; a
  stored role resolves to itself. `functionalRoles(): Role[]` and `is(Role): bool` complete the runtime
  contract (FR-GLB-002). Exact mapping and storage semantics: [T4B26](T4B26-rbac-and-authorization.md) §4.2.

### 6.2 Global Helpers

```php
setting(string|array|null $key = null, mixed $default = null, bool $skipCache = false): mixed
brand(string $key, mixed $default = null): mixed
app_info(?string $key = null, mixed $default = null): mixed
```

Signatures above are the fixed call contract. Full contracts: [C8F0D](C8F0D-shared-utilities.md)
(`app_info()` is FR-C8F0D-SUP11) and [YB22J](YB22J-settings-infrastructure.md)
(`setting()` / `brand()`).

### 6.3 Canonical PKL Record Shape

The PKL lifecycle (UC-LCYC-001) produces one retrievable, immutable artifact set. The cross-cutting
integrity contracts are fixed here; per-phase fields are owned by their feature specs:

- **Attendance** (FR-GLB-013): timestamps + actor identity + sign-off immutability — owner [1KSWL](1KSWL-daily-activity.md).
- **Logbook** (FR-GLB-014): daily, timestamped, same-day edit window — owner [1KSWL](1KSWL-daily-activity.md).
- **Evaluation aggregation** (PS-5 / UC-EVAL-001): role-aware, auditable subset feeding the grade card — owner [ARDA6](ARDA6-assessment.md), [R6BMW](R6BMW-reports.md).
- **Closure & archival** ([program-closure-archival ADR](../adr/adr-program-closure-archival.md)): terminal `COMPLETED → ARCHIVED` lifecycle, readiness verification, locked final grades, and model/policy/UI immutability belong to [7C5WM](7C5WM-internship-lifecycle.md) + [9YUUK](9YUUK-data-archiving.md); retention-pipeline numbers are post-MVP depth per [mvp-spec-trim ADR](../adr/adr-mvp-spec-trim.md) — QLHDO fixes none of them.

Each owner spec defines the exact columns/JSON for its artifact. QLHDO only fixes the integrity
invariants above so all feature specs stay consistent.

### 6.4 Module Landscape

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
under-resourced SMK (PS-1 scale, POV 4 device/connectivity constraints).
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
change without silent cross-module coupling (NFR-MOD-001).
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
prioritization; it does not drive implementation (see §1 tracebacks).
**Trade-off:** Non-testable concerns (rural connectivity, government SOP variation) are addressed
post-MVP with explicit product decisions (R-4).

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
- [PKL research](../refs/articles/pkl-operational-research.md) — field evidence, non-testable (input, not spec)
- [Curriculum compliance](../refs/articles/curriculum-compliance.md) — regulatory mapping, non-testable (input, not spec)
