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

### Goals

- **Digitize the complete PKL lifecycle** — placement → attendance → logbook → supervision → assessment → certificate. *Why:* ends the paper-and-chat workflow at scale.
- **Role-filtered canonical record** — every participant sees only what their role allows. *Why:* single source of truth, no informal data silos.
- **Anti-fraud attendance** — verifiable, timestamped, with evidence trail. *Why:* BAN-PDM evidence, certificate integrity.
- **Remote supervision support** — digital monitoring where physical visits are impractical. *Why:* addresses PS-3 for schools with geographically dispersed placements.
- **Self-hosted, MIT-licensed, zero vendor cost per school**. *Why:* data sovereignty, accessible to under-resourced SMK.
- **Bilingual** — Indonesian primary, English secondary, with `__()` on all user-facing strings. *Why:* natively supports SMK staff and students.
- **Single-tenant by design** — no `tenant_id` overhead. *Why:* MVP simplicity, no multi-tenancy complexity.

### Non-Goals

- **Multi-tenant SaaS**. *Why:* single-tenant is a design decision, not a limitation.
- **Telemetry / usage reporting / external API calls for core features**. *Why:* data sovereignty per [self-hosted ADR](../adr/adr-self-hosted-single-tenant.md).
- **HR / payroll features**. *Why:* out of PKL scope.
- **Real-time chat**. *Why:* WhatsApp is the existing platform; integration is not MVP.
- **Government database sync** (Dapodik/e-Rapor). *Why:* CSV import/export only.
- **Mobile native apps**. *Why:* responsive web covers the use case; BPS 2024: 72.78% internet access nationally.
- **Full WCAG AAA / formal accessibility audit**. *Why:* WCAG AA contrast + keyboard nav MVP only; full audit is post-MVP.
- **Offline-first / PWA**. *Why:* important for rural connectivity (5–20% gap vs urban) but scoped post-MVP; progressive enhancement acceptable for MVP.

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

Picture the first morning after deployment: a bare instance, an APP_KEY, and nobody able to log in yet. The super admin runs `setup:install`, which creates the immutable `superadmin` account (FR-GLB-003) and the baseline settings the whole school will read through `setting()` (FR-GLB-001). From there an admin takes over in the setup wizard — school profile and branding first ([52O1I](52O1I-branding-theme-locale.md)), then departments and the academic year, then companies with their partnership slot quotas. Enrollment is the proof it worked: if an admin can enroll the first student, setup is done. The full contracts live in [installation](8NZAU-installation.md), the [setup wizard](VEJCX-setup-wizard.md), and [recovery](C9ZB6-recovery-ecosystem.md), whose F-layer tests verify each leg.

#### UC-OPS-001 — Admin Operates and Audits

Once students are in the system, the admin's week settles into a rhythm: users and announcements on demand ([95EVB](95EVB-user-crud-and-status.md), [3S55V](3S55V-announcement-system.md)), health checks and audit logs as a habit ([E1MSJ](E1MSJ-system-maintenance.md)), backups and the occasional GDPR erasure as duty ([HBXCI](HBXCI-backup-system.md)). Two invariants make that rhythm trustworthy rather than theatrical. First, every governed mutation writes an audit entry (FR-GLB-004), so the log always answers "who changed what". Second, recovery is drilled, not assumed: the restore drill must land inside the 4-hour RPO / 1-hour RTO basis (NFR-BCK-001), and an erasure must actually remove the subject's rows. A health check that cannot reflect a broken dependency (FR-GLB-006) fails this journey outright.

### 3.2 PKL Lifecycle

#### UC-LCYC-001 — Student Completes the PKL Lifecycle

Follow one student from an active academic year through an open placement: registration ([MBB5R](MBB5R-registration.md)), verification and placement by an admin ([J9GBH](J9GBH-placement.md)), then months of clocking in and out, daily logbooks, assignment submissions, handbook acknowledgments ([1KSWL](1KSWL-daily-activity.md), [T657Z](T657Z-assignment.md)), and finally a downloaded certificate after assessment and sign-off ([J0M04](J0M04-certification.md)). The trail left behind is the product: attendance and logbooks bound by their integrity windows (FR-GLB-013/014), every step retrievable, the finalized artifacts immutable. No single test proves this journey — each governing spec verifies its own leg, and this row exists so the legs stay one journey instead of five features.

#### UC-SUP-001 — Teacher Supervises and Assesses

A teacher with assigned students for an active period works this loop all semester: review logbooks, log monitoring visits ([2EHSE](2EHSE-supervision.md)), score against rubrics alongside the industry supervisor ([ARDA6](ARDA6-assessment.md)), and finally compile the grade card ([R6BMW](R6BMW-reports.md)). The two properties that matter are at the edges — every review and scoring action audit-logged on the way in (FR-GLB-004), and the finalized card rejecting mutation on the way out. Anything softer turns the grade card into a draft that happens to have been printed.

#### UC-EVAL-001 — Supervisor Evaluates Industry-Side Performance

The industry supervisor sees what the school never can: how the student actually behaves on the workshop floor. Tied to the student's company, the supervisor verifies attendance, reviews the logbook ([1KSWL](1KSWL-daily-activity.md)), and submits competency evaluations that flow into the final aggregation ([ARDA6](ARDA6-assessment.md)). Those scores surface in the teacher's grade card only through role gating (FR-GLB-008) — and when the supervisor never opens the app, the covering teacher proxies the verification under the cross-role rules ([T4B26](T4B26-rbac-and-authorization.md) §4.2), with the proxy recorded rather than hidden. Either path ends with industry-side scores present in the final record; neither path lets them in ungated.

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
| FR-GLB-015 | Business logic lives in Action Triad classes (Command / Read / Process) with a single `execute()` each; Commands wrap writes in transactions and audit-log, Reads never mutate, Processes orchestrate via DI · [D2FT3](D2FT3-architecture.md) | P0 | A | Planned |
| FR-GLB-016 | Business rules live in `final readonly` Entity classes bridged via `fromModel()`; Entities never persist, Models never enforce business invariants · [D2FT3](D2FT3-architecture.md) | P0 | A | Planned |
| FR-GLB-017 | Every class extends its layer base (Model→`BaseModel`, Command/Process→`BaseAction`, Entity→`BaseEntity`, Policy→`BasePolicy`, Enum→`LabelEnum`); the sole exception is `User` which extends `Authenticatable` with manual `HasUuids` · [SE5Q9](SE5Q9-base-classes.md) | P0 | A | Planned |
| FR-GLB-018 | Failures use sibling exception trees: `AppException` for infrastructure, `ModuleException → RejectedException` for business-rule violations; the two trees are never mixed in a single `catch` · [89SRA](89SRA-logging-and-error-handling.md) | P0 | A | Planned |
| FR-GLB-019 | Single-model synchronous side effects (cache invalidation, deletion guards, status snapshots) use Eloquent Observers; everything cross-module or deferrable uses Events + Listeners · [NUCY3](NUCY3-event-system.md) | P1 | A | Planned |
| FR-GLB-020 | Cross-module imports are permitted; mandatory workflows prefer Action delegation, fire-and-forget side effects prefer Events, shared shapes prefer Core contracts · [D2FT3](D2FT3-architecture.md) | P1 | A | Planned |

#### FR-GLB-001 — Localized strings

A coordinator flipping the locale to Indonesian expects every button, toast, and notification to follow. A single hardcoded "Submit" inside a Blade template or Livewire component breaks that trust, so all user-facing strings in Blade, Livewire, and notifications pass through `__()` with no exceptions. Each module ships `lang/en/` and `lang/id/` with mirrored keys, enforced by the D3 scan.

Student and company names never get concatenated into sentences. A placement confirmation for a student assigned to a partner workshop renders through a placeholder parameter to `__()`, keeping word order and grammar intact in both languages.

#### FR-GLB-002 — Five-role RBAC

Only five stored roles exist as `Role` enum cases, and adding or renaming one requires a spec amendment — a school cannot invent a "vice-coordinator" role over a weekend to paper over a workflow gap. Business logic never branches on the stored names directly; `Role::resolvesTo()` resolves `admin-group` to `super_admin`/`admin`, `mentor` to `teacher`/`supervisor`, and `mentee` to `student`, exactly as contracted in T4B26 §4.2. The resolution map carries unit coverage for every role input at layer `A`, and an unresolvable input throws rather than silently granting access, so a typo in a role string fails closed.

#### FR-GLB-003 — Immutable superadmin

After `setup:install` a `superadmin` user exists with name `Super Admin` and username `superadmin`. The account is non-deletable and its name and username cannot be modified, with violations rejected at both the UI and the Action level — when a school IT operator tries to rename it to match a staff member or delete it during handover cleanup, the attempt bounces. A second account holding Super Admin privileges is simply an ordinary `admin_group` user; the named `superadmin` remains the single immutable singleton.

#### FR-GLB-004 — Audit-logged mutations

When a coordinator moves a student between partner companies mid-period, the governed mutation writes exactly one activity-log entry through the dual-channel SmartLogger activity channel, carrying the actor identity and a before/after change summary. PII fields appear masked in that entry and secrets never reach the logs at all. During an accreditation dispute months later, the trail shows which acting user id made the change and what shifted, without leaking anything a log reader should not see.

#### FR-GLB-005 — Rate-limited endpoints

Enrollment week brings credential-stuffing bursts against the login form from a single boarding-house IP. Throttling middleware from 2CF4Y holds the line: login allows 5 requests per 60s, forgot-password 3 per 3600s, and reset 5 per 300s, keyed per actor/IP, with anything beyond the window answered by HTTP 429. The limits stay configurable, and overriding the config keys needs no code change.

#### FR-GLB-006 — system:health coverage

A rural SMK deploys on shared hosting where a missing PHP extension only surfaces at midnight before attendance week. Running `php artisan system:health` reports PHP version, required extensions, memory limit, DB connectivity, pending migrations, storage writability, queue, cache, and APP_KEY presence, with every listed subsystem appearing in the output. A failing check marks that check failed in the summary and exits non-zero, so a deliberately broken dependency during a drill is impossible to miss.

#### FR-GLB-007 — UUID v7 primary keys

During the 45,000-row attendance import, random UUID v4 keys would scatter B-tree inserts and stall the enrollment-week queue. Every primary entity therefore extends `BaseModel` with `HasUuids` issuing ordered UUID v7 values, `id` serving as the UUID primary key with no auto-increment primary keys anywhere on primary entities. Migrations declare each foreign key with `foreignUuid()->constrained()` plus composite indexes, and mixed key types are forbidden under the UUID-primary-keys ADR. The layer `A` arch scan asserts the absence of auto-increment primary keys while migration review confirms the `foreignUuid()->constrained()` shape on every foreign key.

#### FR-GLB-008 — Dual-layer authorization

A student once crafted a direct Livewire call to finalize another group's grade card, bypassing the visible buttons entirely. The Policy layer gates route and resource access with a 403, and the Action/Entity layer re-validates the same business rule and throws `RejectedException` when violated, so calling an Action directly never bypasses authorization. Each protected mutation is exercised both ways: a direct Action call without authorization throws `RejectedException` at layer `A`, and an authenticated-but-unauthorized HTTP call returns 403 as the spot `F` check.

#### FR-GLB-009 — Server-side validation

A placement form once arrived with an extra `approved_at` field injected by a curious browser extension. HTTP entry points validate through Form Request classes and Action entry points validate through DTOs, so `$request->all()` never feeds `create()` or `update()` and the injected attribute dies before persistence. DTO validation runs inside `execute()` itself, which keeps even a direct Action call honest.

#### FR-GLB-010 — RejectedException contract

When PT Maju Jaya fills its quota for the period, the student sees a translatable "quota is full" message carried by `RejectedException` — the Action and Entity layer's business-failure voice. An external timeout during certificate PDF generation behaves differently: the unexpected exception is logged with context while the student only sees a generic failure, never a stack trace. That split keeps business rejections readable and operational failures diagnosable, with the user-safe message on one path and the context-rich log entry on the other.

#### FR-GLB-011 — Upload validation & storage

A student once renamed an executable to `laporan.pdf` and uploaded it as a weekly report. Server-side validation now checks MIME type, the per-module configurable maximum size, and filename safety, rejecting disallowed types and oversized files outright. Accepted files land outside the web root under non-guessable names, with public URLs derived from the owning entity rather than the stored path.

#### FR-GLB-012 — Menu from config

Navigation groups render from `config/menu.php` in registration order, with the active route highlighted. No menu group is hardcoded in Blade outside the config-driven layout component, so registering a new company-management section means adding one entry to `config/menu.php` and watching it appear without touching a template.

#### FR-GLB-013 — Attendance integrity

WhatsApp-reported attendance once let a whole group mark each other present from the boarding house. Records here carry clock-in/out timestamps together with actor identity, closing that shape of fraud. After admin sign-off the record turns immutable; before sign-off an edit is still possible but writes an audit entry, preserving both the correction and the fact that a correction happened.

#### FR-GLB-014 — Logbook edit window

A vocational school in Sintuk Toboh Gadang caught students back-filling a week's worth of logbooks the night before a supervision visit — every entry timestamped 08:00 sharp, clearly fabricated. The edit window exists because of that exact fraud shape. The academic day boundary follows the school's configured timezone (Asia/Jakarta by default, overridable per school profile), not UTC, so a student working a night shift at a partner workshop is still inside the same academic day until midnight local time. The owner check runs inside the Action, not just the policy: even if someone crafts a direct Livewire call, `UpdateLogbookAction` re-resolves ownership from the registration bridge and throws `RejectedException` for non-owners. Next-day edits are rejected even by admins — correction after the window requires a supervisor-annotated revision entry, never a silent rewrite, so the audit trail preserves what was originally claimed versus what was corrected.

#### FR-GLB-015 — Action Triad as the only home for business logic

Newcomers from classic Laravel reach for a `RegistrationService` with ten public methods. That file becomes a god class within two sprints: `register()`, `approve()`, `reject()`, `bulkApprove()`, each with different transaction needs, all sharing mutable state. The triad exists to make that shape impossible. A Command (`CreatePlacementAction extends BaseAction`) wraps its writes in `$this->transaction()`, calls `$this->log()` on success, and dispatches its event after commit — the PLACEMENT transaction either fully lands or fully rolls back, never a half-placed student with a slot decremented but no registration row. A Read (`ReadAvailableSlotsAction extends BaseReadAction`) is deliberately crippled: no `transaction()`, no `log()`, so a dashboard widget cannot accidentally lock rows during a morning attendance rush of 1,000 concurrent students. A Process (`CloseProgramProcess`) is the only place allowed to inject three other Actions and sequence them; without it that orchestration leaks into a Livewire component where it cannot be unit-tested. Real-world consequence: during enrollment week, placement writes contend heavily — Commands serialize correctly through transactions while Reads stay lock-free, which is exactly why the two types must never be confused.

#### FR-GLB-016 — Entities carry rules, Models carry persistence

Eloquent models are convenient liars: `$placement->approve()` looks like domain logic but it couples the approval invariant to the query builder, the connection, and the test database. An Entity (`PlacementState extends BaseEntity`, `final readonly`) is a frozen snapshot — construct it from any array in a millisecond unit test, no factory, no migration, no SQLite. The bridge `PlacementState::fromModel($placement)` is the single point where a column rename ripples; everything downstream (Actions, Policies, Livewire) talks to the Entity's named predicates (`canTransitionTo()`, `isOverCapacity()`), never to raw attributes. Framework pragmatism is deliberate: an Entity may use Carbon for date math or a Collection for mentor lists — banning those would trade velocity for purity nobody at a 500-student school needs. The one hard line: an Entity never calls `save()`, `create()`, or `dispatch()` — persistence and side effects belong to the Action that holds the Entity, so the rule stays testable in isolation from the write it guards.

#### FR-GLB-017 — One base class per layer, no opt-outs

Across 18 modules and 400+ files, consistency cannot survive on code-review memory. The mandate means a reviewer never asks "does this Action wrap in a transaction?" — if it extends `BaseAction`, the `transaction()` and `log()` contract is structural, and `scan_class_contracts.py` proves it. Each base pays for itself: `BaseModel` guarantees UUID v7 everywhere (one missed model would break every `foreignUuid` join against it), `BasePolicy` composes `AuthorizesRoles + AuthorizesOwnership` so no policy forgets the ownership check that prevents a student from grading another student's submission, `BaseRecordManager` gives every CRUD table search/filter/sort/pagination for free so the 40th admin table behaves like the first. The `User` exception is the scar that proves the rule: because Laravel's auth system demands `Authenticatable`, `User` manually replicates the `HasUuids` + non-incrementing contract, and every `BaseModel` change must be mirrored there by hand — documented here so nobody "simplifies" it back to auto-increment. Until `pest-plugin-arch` stabilizes, review enforces this; after that, arch tests do.

#### FR-GLB-018 — Two exception trees that never meet

A controller that catches `AppException` to render a 500 page must never accidentally swallow a `RejectedException` that should have been a friendly "slot is full" toast — that confusion is what the sibling-tree design kills. `ModuleException → RejectedException` carries a translatable, user-safe message ("Placement quota for PT Maju Jaya is full for this period") plus structured context for the activity log; it is the Action and Entity layer's only business-failure voice, and Livewire renders it as a toast without consulting the logs. `AppException` and its children (`ValidationFailedException`, `InfrastructureException`, `UnauthorizedException`) carry hints and debug context for operators instead; an external API timeout during certificate PDF generation surfaces as a generic "try again" to the student while the full payload lands in the system log with PII masked. Both trees share `HasExceptionContext` (`withHint`, `withContext`, `toCliOutput`) so `system:health` and artisan commands render either tree identically. The legacy trio (`ConflictException`, `NotFoundException`, `RateLimitException`) was folded into `RejectedException` precisely because three extra catch branches in every Livewire component taught developers to catch `Exception` — the exact habit this hierarchy exists to break.

#### FR-GLB-019 — Observers for the synchronous few, Events for the decoupled many

Cache invalidation is the canonical Observer case and it is a correctness bug when done as an event. When an admin renames a setting, the very next request — milliseconds later, during enrollment-week traffic — must see the new value; a deferred `SettingUpdated` listener that runs after commit leaves a window where the old quota renders and a student grabs a phantom slot. `SettingObserver` runs synchronously inside the same request, so correctness holds. The deletion guard is the mirror: `UserObserver::deleting()` throws `RejectedException` before the row disappears, which an after-commit event can never do. Everything else — welcome notifications, cross-module cache flushes, audit fan-out — belongs in Events + Listeners precisely because those want decoupling, queuing (`ShouldQueue`), and discard-on-rollback semantics. The three-gate test (same module? synchronous required? single-model scope?) keeps the two mechanisms from bleeding into each other: the day an "observer" needs a second model or a queue, it gets refactored into an event, no exceptions.

#### FR-GLB-020 — Pragmatic cross-module calls without circular rot

A strict-boundaries regime would force `CloseProgramProcess` (Program module) to communicate with Assessment and Certification through events and contracts — three new interfaces and two async listeners for what is, at MVP, a synchronous three-line sequence. That ceremony slows enrollment-week fixes to a crawl. The permitted default — direct import of another module's public `execute()` — keeps the call graph readable: `CloseProgramProcess` injects `FinalizeAssessmentsAction` and `IssueCertificatesAction` and the sequence reads top to bottom. The discipline sits in the preference order, not a ban: when the second listener appears for the same event, or when a side effect must survive rollback, the call graduates to an Event; when a shape is consumed by three modules, it graduates to a Core contract. Circular imports are the tripwire — Program importing Assessment while Assessment imports Program is a review-blocking smell that forces an event or a Core extraction. At single-tenant school scale, this yields traceable mandatory workflows (delegation) alongside decoupled notifications (events) without paying microservice-grade decoupling tax.

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
| NFR-PERF-001 | Tier-0 no-regret performance always on (UUID v7 PKs, composite FK indexes, eager-loading, cache-key registry, lock-free Reads); Tier 2/3 growth via `.env` swaps only, never rewrites · [J68GZ](J68GZ-system-requirements.md) | N/A | P1 | A | Planned |

### 5.1 Security

#### NFR-SEC-001 — Authorization at every layer

A supervisor from one partner company probing another company's evaluation form meets the same wall twice: the Policy denies the route and the Action rejects the direct call. The arch scan walks Policies and Actions together to confirm every protected mutation refuses unauthorized callers, while `scan_class_contracts.py` and per-role policy allow/deny unit tests pin the behavior for each role.

#### NFR-SEC-002 — Input safety & PII masking

A search box containing a quote and a semicolon should be a boring string, never a query fragment. Static scanning hunts raw SQL concatenation under C3 and `$request->all()` flowing into `create()` or `update()` under D5, with `scan_violations.py` clean on both, while Form Request and DTO validation plus log review close the remaining paths. SmartLogger masks the configured PII attributes so student identity numbers never settle into the log files.

#### NFR-SEC-003 — Security headers

Every HTTP response carries the same four headers: CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, and Referrer-Policy: strict-origin-when-cross-origin. A feature test over a representative sample of pages at layer `F` asserts their presence, catching the stray route that renders outside the standard middleware stack.

#### NFR-SEC-004 — CSRF & token auth

A state-changing web request without a valid CSRF token dies with a 419 or 403 before touching business logic, while API endpoints answer only to Sanctum token auth. Sanctum guards the API routes outright, so a browser session cookie borrowed by a script gains nothing against the API surface.

#### NFR-SEC-005 — Escaped output

A logbook reflection containing `<script>` tags from a copied web article renders as harmless text through `{{ }}`, because `{!! !!}` is forbidden for user-generated content. The layer `A` XSS checks in `scan_security.py` stay clean, which is the tripwire that catches a well-meaning developer reaching for unescaped output to fix a formatting quirk.

### 5.2 Backup

#### NFR-BCK-001 — Backup RPO/RTO

When a school server disk dies the week before certificate issuance, the question is how much attendance history is gone and how fast the office is back. Recovery targets RPO of at most 4 hours and RTO under 1 hour from the last backup, with retention configurable per policy under the governing [HBXCI](HBXCI-backup-system.md) spec. A periodic restore drill on staging proves the numbers instead of assuming them — the drill restores, boots, and hands back a working school record inside the hour.

### 5.3 UX

#### NFR-UX-001 — Responsive & accessible

A field advisor reviews logbooks from a low-end Android phone on a 3G connection at the workshop, then continues from a school desktop. Breakpoints render without horizontal scroll at both widths, interactive elements hold WCAG AA contrast, and every form plus the navigation answers to keyboard alone. The layer `B` browser smoke journey walks the primary flows end to end, with a manual contrast spot-check covering what automation cannot judge.

#### NFR-UX-002 — Guide pages

Each non-trivial workflow ships its own `guides/{feature}-guide.blade.php` beside the screens it explains, so a new coordinator learns placement quotas from the placement pages rather than a chat forward. The layer `A` arch scan asserts that presence for every workflow defined as non-trivial, keeping documentation from silently falling behind new screens.

### 5.4 Architecture & Data

#### NFR-MOD-001 — Module-first colocation

Every line of business code lives under `app/Modules/`, with shared infrastructure settled in `app/Modules/Core/` — a placement rule is found next to its model and Livewire screens, never in a distant top-level folder. Both `scan_naming.py` and `scan_module_boundaries.py` run clean against that layout, which is how a reviewer trusts that a change to supervision touched supervision alone.

#### NFR-DATA-001 — DB & PK strategy

The default install boots on SQLite while a larger school points the same migrations at MySQL, with UUID primary keys on every primary entity and no schema drift between the two. CI migrates fresh on both engines, and the layer `A` arch scan holds the UUID line. A migration that forgets the UUID shape fails long before it reaches a school server.

### 5.5 Globalization

#### NFR-I18N-001 — Bilingual runtime toggle

Indonesian is primary and English secondary, with the locale held in the session and togglable at runtime. Flipping the toggle re-renders strings immediately without a restart, so a bilingual supervisor can demo in English and hand the laptop back in Indonesian. Every D3 key exists in both `lang/` files, which keeps the toggle from revealing a half-translated screen.

### 5.6 Privacy

#### NFR-GDPR-001 — GDPR erasure

When a graduated student requests erasure, the data-erasure workflow under the governing [7HNCF](7HNCF-gdpr-compliance.md) spec removes the subject rows and writes a deletion record in their place. The record keeps the school honest about what was removed and when, while the personal data itself is gone rather than merely hidden.

### 5.7 Performance Tiers

#### NFR-PERF-001 — No-regret Tier 0, config-only growth

An SMK with 400 students on $5 shared hosting and an SMK with 1,800 students on a VPS run the same binary — that is the whole point. Tier 0 is non-negotiable at every scale: ordered UUID v7 keys keep B-tree inserts local during the 45,000-row attendance import, composite indexes on every foreign key keep the placement join fast, the N+1 eager-loading convention keeps the morning Livewire dashboard from firing 1,000 queries, the `config/cache-keys.php` registry keeps invalidation greppable, and Read Actions stay transaction-free so reporting never blocks enrollment writes. None of this requires a developer decision; it is structural. When sustained load crosses ~500 users or P95 passes a second, growth is an `.env` swap — `QUEUE_CONNECTION=redis` plus a worker, `CACHE_STORE=redis`, `SESSION_DRIVER=redis` — with zero code change, because every queue/cache/session call already goes through the framework drivers. Octane, sharding, CDN, and read replicas are explicitly deferred until evidence (Pulse metrics, §10 R-1/R-6) demands them; premature HA ceremony during MVP feature work is the failure mode this NFR exists to prevent.

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
(`app_info()` is FR-UTIL-002) and [YB22J](YB22J-settings-infrastructure.md)
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
| DD-ARCH-006 | Gradual migration over day-one purity — ship `array` inputs, inline side effects, and inline cache calls first; migrate to DTOs, Events, and registry-driven invalidation when the documented trigger fires | P1 | — | — |

### 7.1 Deployment & Tenancy

#### DD-ARCH-001 — Single-Tenant, Self-Hosted, MIT

An under-resourced SMK cannot pay per-seat SaaS fees, cannot trust student data to a vendor's cloud, and cannot assume the internet works during grading week — so Internara ships as a self-packaged Laravel codebase running on the school's own infrastructure, MIT-licensed. Data sovereignty, offline robustness, and zero recurring cost are the payoff; per-school deployment effort is the accepted price, and SaaS economics are explicitly not pursued.

#### DD-ARCH-005 — No Tenant Isolation Overhead

One school per deployment means the entire multi-tenancy apparatus — `tenant_id` columns on every table, scoped queries, tenant middleware, per-tenant config — buys nothing and complicates everything, so none of it exists. The model stays clean for MVP at exactly one cost, stated plainly: serving three schools means three installations, three databases, three web roots. If that ever stops being acceptable, it becomes a new product decision, not a quiet schema addition.

### 7.2 Architecture

#### DD-ARCH-002 — Module-First Vertical Slicing

Every business concept lives as one vertical slice under `app/Modules/{Module}/Domain/{Domain}/`, with shared infrastructure deliberately extracted to `Core`. Findability is the mechanism that enforces everything else: when placement logic sits in exactly one directory, it is independently testable and safe to change without silent cross-module coupling (NFR-MOD-001). The discipline runs one way — anything two modules genuinely share graduates to `Core` instead of being imported sideways.

### 7.3 Localization

#### DD-ARCH-003 — Primary Indonesian, Secondary English

PKL is an Indonesian curriculum mandate run by Indonesian staff, so `lang/id/` is primary and complete; `lang/en/` exists for developers and the occasional bilingual school, toggled at runtime. Every user-facing string pays the translation cost up front — the D3 convention plus its scan make untranslated strings a CI failure rather than a backlog item nobody reads.

### 7.4 Spec Discipline

#### DD-ARCH-004 — Spec-First, Testable Requirements Only

A requirement without a test path is a wish, not a specification — so this spec contains only verifiable, testable requirements, while regulation text, field pain evidence, and regulatory alignment live in `docs/refs/articles/` explicitly marked non-testable. Research still matters: it informs prioritization and every §1 statement traces to it. It simply never drives implementation directly. Genuinely non-testable concerns like rural connectivity or government SOP variation wait for explicit post-MVP product decisions (R-4) instead of smuggling themselves in as untestable rows.

#### DD-ARCH-006 — Good Enough Today Beats Perfect Next Week

A developer adding the first placement import should not be blocked designing a `PlacementImportData` DTO with twelve validated fields while the CSV shape is still changing weekly during pilot. They ship `execute(array $data)` today; when the shape settles, they widen to `execute(PlacementData|array $data)` with `fromArray()` keeping old callers green, and only then narrow to `execute(PlacementData $data)`. Same story for side effects: the first notification lives inline in the Action, the Event + listener pair appears when the second consumer arrives or when the Action test needs to assert state without side effects. Cache invalidation starts as `Cache::forget()` next to the write and graduates to listener-driven registry invalidation when two events touch the same key. This is not permission to stagnate — mixed phases are expected mid-migration, but a quarterly architecture pass hunts areas stalled at phase one. Velocity now, direction preserved.

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
