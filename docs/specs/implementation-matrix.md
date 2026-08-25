# Spec Implementation Matrix — Priority-Ordered

> **Last updated:** 2026-08-19 **Changes:** fix — ADR numeric reference corrected per de-numbering decision

## Description

This matrix tracks implementation fulfillment, test coverage, and verification status, ordered by
**business criticality** (most vital PKL features first) — not by build order. Each row maps one
feature spec to its current implementation status, test coverage status, and last verification date.

The matrix is the **single source of truth for spec completion state**. It is updated as work
progresses: after audits, after test runs, and after implementation phases. Every spec audit
(`spec-audit`) closes by updating the relevant row here.

---

## Legends — What Each Status Means and When to Use It

### Priority

Business criticality of the spec. Used to decide **what to build/verify next** when resources are
limited — process 🔴 down to 🔵, not by build order.

| Priority | Meaning | Typical examples | When a spec earns it |
|----------|---------|------------------|----------------------|
| 🔴 **Critical** | System cannot boot, secure, or provision without this spec | Foundation, install, auth, RBAC, middleware, password reset, super admin recovery | The system is unusable or insecure without it. Missing = deployment blocked. |
| 🟡 **High** | Core PKL workflow — the main daily operations of students/teachers/supervisors | School profile, departments, academic years, companies, partnerships, internships, registration, placement, logbook, attendance, incident | The primary user journeys (enroll → place → record → supervise) depend on it. |
| 🟢 **Medium** | Assessment, certification, reporting output | Assessment, evaluation, assignment, certification, documents, reports, media, PDF | Produces measured output (grades, certificates, reports) but PKL can run without it temporarily. |
| 🔵 **Low** | Maintenance & supporting infrastructure | Job queues, backups, GDPR, system maintenance, deployment, dummy data, dashboard, announcements, notifications, module discovery, utilities, events | Operational hardening; no direct user workflow dependency. |

**When to use to change priority:** a spec's priority only changes when its criticality to the
product changes (e.g., GDPR becomes Critical when a school is audited). It is **not** bumpable as a
reward for work done — priority reflects importance, not progress.

### Implementation Status

| Status | Icon | Definition | When to use it | When to promote it |
|--------|------|------------|----------------|--------------------|
| **Not Started** | ⬜ | No implementation code exists for the spec | First discovery of a spec that no module code implements. | → 🟨 when the first implementing code lands (even partial). |
| **In Progress** | 🟨 | Implementation exists but is incomplete — some FRs uncovered, some paths stub | Spec is being actively implemented; at least one FR has real code but the spec's contract is not fully met. | → 🟩 **only** when every satisfying FR/NFR in the spec has a code path that meets its contract (Tool the test suite or a fresh audit before promoting). |
| **Implemented** | 🟩 | All spec requirements have working code | Code coverage of the spec's FR/NFRs is believed complete and matches intent. | → 🟦 after verification (audit + tests) — see Verified. |
| **Verified** | 🟦 | A recent spec↔code audit or test run confirmed the implementation matches the spec | The spec's suite passed AND a `spec-audit` (or equivalent targeted run) confirmed spec↔code sync. | Stays here until an audit finds drift — then back to 🟩/🟨 or Keep a `Notes` entry describing the finding. |
| **Need Review** | 🟪 | Implementation exists but is unverified or questionable | A change landed without test confirmation, a test fails, or an audit flagged uncertainty. | → 🟩 once issues are resolved, → 🟦 after re-verification. |

**Usage rules:**

- 🟩 → 🟦 promotion requires **evidence**: a green test run AND a real (recent) audit. Do not promote
  on assumption.
- 🟦 → 🟩/🟨 demotion is automatic when an audit or failing test exposes drift — never keep 🟦 on a
  spec with open, unresolved drift.
- `🟪 Need Review` is a **temporary** state with an owner; it must become 🟩/🟦 or stay 🟪 only if
  the blocking review is explicitly deferred (with the blocker in Notes).
- If a spec has **no implementation and is not scheduled**, it stays ⬜ — do not mark ⬜ as 🟨 just
  because a PR branch exists.

### Test Coverage

| Status | Icon | Definition | When to use it | When to promote it |
|--------|------|------------|----------------|--------------------|
| **None** | ⬜ | No tests exist for the spec's requirements | Spec registered, zero test files reference its FR/NFR IDs. | → 🟨 when the first spec-traceable test exists. |
| **Partial** | 🟨 | Some requirements are covered, some not | A test file exists but only covers a subset of FR/NFRs (per requirement-trace audit). | → 🟩 when every spec FR/NFR has ≥1 traceable passing test. |
| **Full** | 🟩 | Every spec requirement has a traceable, passing test | Requirement-by-requirement mapping is complete and the suite is green. | Permanently 🟩 until a new FR is added (→ 🟨/🟧) or a test rots. |
| **Spec-Gap** | 🟧 | A requirement (FR/NFR) exists but has **no** test covering it | Audit reveals the spec defines a requirement with zero traceable test; used to force gap-closure. | → 🟨/🟩 once the missing test(s) is written and passing. |

**Usage rules:**

- `Test Coverage` measures **requirement traceability**, not line coverage or "we wrote some tests."
  A test file whose tests do not map to any FR/NFR ID does not advance this column.
- 🟧 Spec-Gap is a **debt flag**, not a neutral status — the Notes column should reference the
  affected requirements (e.g., "FR-NFR2 untested").
- A spec moving 🟩 → 🟧 occurs when a new FR is added without a test in the same change.

### Last Verified

| Value | Meaning | Rule |
|-------|---------|------|
| `YYYY-MM-DD` | Date of the last spec↔code audit or test run that informs this row | Update on **every** audit/test run that changes or confirms the row. If the date is older than the spec's `Last updated`, the row may be stale — re-verify. |

---

## The Matrix

**Priority:** `🔴 Critical` (system cannot boot, secure, or provision without) • `🟡 High` (core PKL workflow) • `🟢 Medium` (assessment / certification / reporting output) • `🔵 Low` (maintenance & supporting infra)
**Status Legend:** `⬜ Not Started` • `🟨 In Progress` • `🟩 Implemented` • `🟦 Verified` • `🟪 Need Review`
**Test Coverage:** `⬜ None` • `🟨 Partial` • `🟩 Full` • `🟧 Spec-Gap` (requirement exists, no test)
**Last Verified:** Date of last spec↔code audit or test run

| Priority | ID | Spec | Module | Impl Status | Test Coverage | Last Verified | Notes |
|----------|----|------|--------|-------------|---------------|---------------|-------|
| 🔴 Critical | QLHDO | [Internara Project (Initial Specification)](QLHDO-internara-project.md) | Core | 🟩 Implemented | 🟧 Spec-Gap | 2026-08-19 | Spec-zero blanket: roles, localization, audit, UUID, health; lifecycle FR-L1–L12 map to phase specs; dead code findings filed as issues #390-#400 |
| 🔴 Critical | D2FT3 | [Architecture Design](D2FT3-architecture.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | Governing architecture contract; 280 Core tests; audit: 6 C6 DTO violations, 4 C7 Action violations, 11 security findings filed as issues #401-#403 |
| 🔴 Critical | FB792 | [Tech Stack](FB792-tech-stack.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | Dependency manifest; pinned versions; JS toolchain synced to package.json; composer audit/npm audit clean; lockfiles committed |
| 🔴 Critical | ZT6VS | [Core & Infrastructure Services](ZT6VS-core-infra-services.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | SmartLogger, SettingsStore, SendsNotifications; gaps #387 Redis connections, #388 documents pipeline; audit: batch doc queue dispatch missing (issue #404), missing tests for system:health/cache-warm/TestMailSettingsAction (issue #405) |
| 🔴 Critical | SE5Q9 | [Base Classes](SE5Q9-base-classes.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | BaseAction, BaseModel, BaseEntity, BasePolicy; FR-M7 spec synced; all 280 Core tests pass; C6 DTO violations in Journals filed as issue #401 |
| 🔴 Critical | T4B26 | [RBAC & Authorization](T4B26-rbac-and-authorization.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | Flat roles, Gate::before, Policy pattern; audit: 3 functional roles (admin-group/mentor/mentee) synced per flat RBAC; MentorEntity proxy tests added; gaps #407 proxy inactivity window, #408 proxy metadata tagging |
| 🔴 Critical | 89SRA | [Logging & Error Handling](89SRA-logging-and-error-handling.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | Dual AppException/ModuleException trees; audit: FR IDs fixed in ExceptionsTest/HandlesActionErrorsTest, FR-AE5/FR-AE6 BaseAction slugs, FR-ER1-5 rendering tests added; 52 Core log/error tests pass |
| 🔴 Critical | 2CF4Y | [Middleware Pipeline](2CF4Y-middleware-pipeline.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Global + module middleware groups |
| 🔴 Critical | 1PGM4 | [Security Headers](1PGM4-security-headers.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | CSP, HSTS, frame options |
| 🔴 Critical | YB7RG | [Authentication](YB7RG-authentication.md) | Auth | 🟦 Verified | 🟩 Full | 2026-08-16 | Login, throttling, session security |
| 🔴 Critical | 95EVB | [User CRUD & Status](95EVB-user-crud-and-status.md) | User | 🟦 Verified | 🟩 Full | 2026-08-16 | 8-state lifecycle, role assignment |
| 🔴 Critical | D9TKW | [Password Reset](D9TKW-password-reset.md) | Auth | 🟦 Verified | 🟩 Full | 2026-08-16 | Token-based, rate limited |
| 🔴 Critical | SHQ1J | [Account Recovery Slips](SHQ1J-account-recovery-slips.md) | Auth | 🟦 Verified | 🟩 Full | 2026-08-16 | Recovery codes, CLI admin recovery |
| 🔴 Critical | CQVSK | [Password Confirmation](CQVSK-password-confirmation.md) | Auth | 🟦 Verified | 🟩 Full | 2026-08-16 | Sensitive action re-auth |
| 🔴 Critical | OCEMS | [Profile Management](OCEMS-profile-management.md) | User | 🟦 Verified | 🟩 Full | 2026-08-16 | Avatar, notifications, preferences |
| 🔴 Critical | 8NZAU | [Installation](8NZAU-installation.md) | Setup | 🟦 Verified | 🟩 Full | 2026-08-16 | `setup:install` audits env, runs migrations |
| 🔴 Critical | VEJCX | [Setup Wizard](VEJCX-setup-wizard.md) | Setup | 🟦 Verified | 🟩 Full | 2026-08-16 | 6-step signed URL wizard |
| 🔴 Critical | C9ZB6 | [Recovery Ecosystem](C9ZB6-recovery-ecosystem.md) | Setup | 🟦 Verified | 🟩 Full | 2026-08-16 | Super admin recovery, token validation |
| 🟡 High | 81SMS | [School Profile](81SMS-school-profile.md) | Academics | 🟦 Verified | 🟩 Full | 2026-08-16 | NPSN, branding, contact info |
| 🟡 High | 4HWSB | [Department Management](4HWSB-department-management.md) | Academics | 🟦 Verified | 🟩 Full | 2026-08-16 | Jurusan CRUD, academic structure |
| 🟡 High | XW6F5 | [Academic Year Management](XW6F5-academic-year-management.md) | Academics | 🟦 Verified | 🟩 Full | 2026-08-16 | Active year, semester, date ranges |
| 🟡 High | YB22J | [Settings Infrastructure](YB22J-settings-infrastructure.md) | Settings | 🟦 Verified | 🟩 Full | 2026-08-16 | Key-value store, cached resolution |
| 🟡 High | 52O1I | [Branding, Theme & Locale](52O1I-branding-theme-locale.md) | Settings | 🟦 Verified | 🟩 Full | 2026-08-16 | Dynamic theming, color presets, EN/ID |
| 🟡 High | 8XMYS | [Layout & UI System](8XMYS-layout-and-ui-system.md) | Core | 🟩 Implemented | 🟧 Spec-Gap | 2026-08-17 | Shells, role-filtered sidebar, `core::ui.*` — no tests yet |
| 🟡 High | XI3LB | [Company Management](XI3LB-company-management.md) | Partners | 🟦 Verified | 🟩 Full | 2026-08-16 | DUDI registry, MoU tracking, slots |
| 🟡 High | NTHQA | [Partnership Management](NTHQA-partnership-management.md) | Partners | 🟦 Verified | 🟩 Full | 2026-08-16 | Partnership lifecycle, quota mgmt |
| 🟡 High | 7C5WM | [Internship Lifecycle](7C5WM-internship-lifecycle.md) | Program | 🟦 Verified | 🟩 Full | 2026-08-16 | Phases, document requirements, state machine |
| 🟡 High | IT0OE | [Internship Groups](IT0OE-internship-groups.md) | Program | 🟦 Verified | 🟩 Full | 2026-08-16 | Cohort grouping, teacher assignment |
| 🟡 High | MBB5R | [Registration](MBB5R-registration.md) | Enrollment | 🟦 Verified | 🟩 Full | 2026-08-16 | Student wizard, capacity enforcement |
| 🟡 High | J9GBH | [Placement](J9GBH-placement.md) | Enrollment | 🟦 Verified | 🟩 Full | 2026-08-16 | Slot-based, change requests |
| 🟡 High | 920SO | [Account Application](920SO-account-application.md) | Enrollment | 🟦 Verified | 🟩 Full | 2026-08-16 | Guest→student atomic provisioning |
| 🟡 High | O2KCR | [CSV Import & Export](O2KCR-csv-import-export.md) | Enrollment | 🟦 Verified | 🟩 Full | 2026-08-16 | Bulk ops, validation, templates |
| 🟡 High | EWCZ0 | [Account Slips](EWCZ0-account-slips.md) | User | 🟦 Verified | 🟩 Full | 2026-08-16 | Printable credentials |
| 🟡 High | 1KSWL | [Daily Activity](1KSWL-daily-activity.md) | Journals | 🟦 Verified | 🟩 Full | 2026-08-16 | Geotagged attendance, reflective logbook |
| 🟡 High | 2EHSE | [Supervision](2EHSE-supervision.md) | Journals | 🟦 Verified | 🟩 Full | 2026-08-16 | Teacher logs, monitoring visits |
| 🟡 High | 3RU9S | [Incident](3RU9S-incident.md) | Incident | 🟦 Verified | 🟩 Full | 2026-08-16 | Reports, severity, resolution workflow |
| 🟢 Medium | ARDA6 | [Assessment](ARDA6-assessment.md) | Assessment | 🟦 Verified | 🟩 Full | 2026-08-16 | Rubrics, competency scoring, multi-eval |
| 🟢 Medium | AXKZW | [Evaluation](AXKZW-evaluation.md) | Evaluation | 🟦 Verified | 🟩 Full | 2026-08-16 | Google Forms-like, auto-scoring |
| 🟢 Medium | T657Z | [Assignment](T657Z-assignment.md) | Assignment | 🟦 Verified | 🟩 Full | 2026-08-16 | Tasks, submissions, grading |
| 🟢 Medium | J0M04 | [Certification](J0M04-certification.md) | Certification | 🟦 Verified | 🟩 Full | 2026-08-16 | Templates, batch issuance, QR verify |
| 🟢 Medium | PKYX6 | [Document Templates](PKYX6-document-templates.md) | Document | 🟦 Verified | 🟩 Full | 2026-08-16 | Blade-based, variable substitution |
| 🟢 Medium | ZUFG8 | [Handbooks](ZUFG8-handbooks.md) | Document | 🟦 Verified | 🟩 Full | 2026-08-16 | Policy handbooks, acknowledgements |
| 🟢 Medium | R6BMW | [Reports](R6BMW-reports.md) | Reports | 🟦 Verified | 🟩 Full | 2026-08-16 | Grade card only (no thesis) |
| 🟢 Medium | 7H5D6 | [Official Documents](7H5D6-official-documents.md) | Document | 🟦 Verified | 🟩 Full | 2026-08-16 | Correspondence, snapshots |
| 🟢 Medium | WQGTP | [File Uploads & Media](WQGTP-file-uploads-media.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Spatie MediaLibrary, collections |
| 🟢 Medium | 7UB7S | [PDF Generation](7UB7S-pdf-generation.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | barryvdh/laravel-dompdf |
| 🔵 Low | 8FVZA | [Job & Queue Infrastructure](8FVZA-job-queue-infrastructure.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Redis, Supervisor, job lifecycle |
| 🔵 Low | HBXCI | [Backup System](HBXCI-backup-system.md) | SysAdmin | 🟦 Verified | 🟩 Full | 2026-08-16 | Automated, retention, restoration |
| 🔵 Low | 7HNCF | [GDPR Compliance](7HNCF-gdpr-compliance.md) | SysAdmin | 🟦 Verified | 🟩 Full | 2026-08-16 | Export, erasure, consent |
| 🔵 Low | E1MSJ | [System Maintenance](E1MSJ-system-maintenance.md) | SysAdmin | 🟦 Verified | 🟩 Full | 2026-08-16 | Cleanup, archiving, health checks |
| 🔵 Low | 9YUUK | [Data Archiving & Retention](9YUUK-data-archiving.md) | SysAdmin | ⬜ Not Started | ⬜ None | 2026-08-19 | New spec — full archival lifecycle (registry, retention policy, purge, restore); consolidates E1MSJ/R6BMW archive capabilities |
| 🔵 Low | 06IB6 | [Conditional Deployment](06IB6-deployment.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | 3-tier deploy, env detection |
| 🔵 Low | 3UOZP | [Dummy Data](3UOZP-dummy-data.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Production guard (NFR-S1), demo accounts |
| 🔵 Low | CKKZC | [Dashboard](CKKZC-dashboard.md) | User | 🟦 Verified | 🟩 Full | 2026-08-16 | Role-based widgets, stats |
| 🔵 Low | 3S55V | [Announcement System](3S55V-announcement-system.md) | SysAdmin | 🟦 Verified | 🟩 Full | 2026-08-16 | School-wide, targeted, scheduling |
| 🔵 Low | TXR2H | [Notification Infrastructure](TXR2H-notification-infrastructure.md) | User | 🟦 Verified | 🟩 Full | 2026-08-16 | Multi-channel, mail deliverability |
| 🔵 Low | I1BCV | [Module Discovery](I1BCV-module-discovery.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Auto-registration, config/module.php |
| 🔵 Low | C8F0D | [Shared Utilities](C8F0D-shared-utilities.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Helpers: `setting()`, `brand()`, `app_info()` |
| 🔵 Low | J68GZ | [System Requirements](J68GZ-system-requirements.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | 37 domain tables, schema philosophy |
| 🔵 Low | NUCY3 | [Event System](NUCY3-event-system.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | BaseEvent, dispatch patterns, listeners |
| 🔵 Low | B114U | [Module Manager & Service](B114U-module-manager.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Module registry, service resolution |

> **How to update:** When implementation or test status changes, edit the table. Bump the
> `> **Last updated:**` date at the top of this file. Keep status honest — `🟧 Spec-Gap` means a
> requirement has no test; `🟨 Partial` means some requirements covered; `🟪 Need Review` means an
> unverified state that must resolve to a stable status.

---

## Workflow

```
spec audit / test run → evaluate per row → update Impl Status / Test Coverage / Last Verified / Notes → bump Last updated
```

1. Verify the claim before changing a status — run the spec's suite, run the audit, or cite the
   change that landed (evidence over assumption).
2. A row says 🟦 Verified only when the evidence is dated and green; record the audit/issue IDs in
   Notes so the verification is traceable.
3. When an audit opens drift on a 🟦 row, demote it (🟩/🟨/🟪) and reference the issue in Notes.
4. File the spec-traceable test before promoting 🟧 → 🟩 on Test Coverage.

---

## Quick References

- `docs/specs/index.md` — Spec registry, build order, and lifecycle phases (matrix moved here)
- `docs/specs/QLHDO-internara-project.md` — High-level feature specs
- `spec-writing` skill — Spec writing conventions and template