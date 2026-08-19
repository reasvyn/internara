# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-08-19 **Changes:** QLHDO spec title updated from "Umbrella" to "Initial Specification"; spec audit for QLHDO completed — dead code findings (11 Actions/Jobs unused) filed as GitHub issues #390-#400; spec audit for D2FT3 completed — 6 C6 DTO violations, 4 C7 Action violations, 11 security findings filed as GitHub issues #401-#403; top-5 matrix audit (QLHDO, D2FT3, FB792, ZT6VS, SE5Q9) — FB792 JS toolchain synced to package.json (plugin-php→tailwindcss), SE5Q9 FR-M7 corrected, ZT6VS gaps filed (#387 Redis connections, #388 documents pipeline). Prior: register QLHDO initial specification as first entry (Spec-Zero, Build Order + Implementation Checklist Matrix); add Layout & UI System spec (8XMYS); add Implementation Checklist Matrix; add Architecture Design spec (D2FT3); add Core & Infrastructure Services spec (ZT6VS); tech-stack (FB792) refocused on dependency manifest; Dummy Data spec (3UOZP); Conditional Deployment spec (06IB6); Foundation specs (FB792–B114U) verified against rewritten Core suite (266 tests); spec↔code deviations reconciled (security-headers, shared-utilities); migrated all specs to alphanumeric 5-char IDs

## Description

Comprehensive feature specifications for the Internara system. Each spec defines problem
statements, goals/non-goals, user stories, functional/non-functional requirements, API/data
contracts, design decisions, and success metrics.

Specs are the **authoritative source** for feature implementation. When code and spec disagree,
update the spec first, then implement.

---

## Build Order

Specs are grouped by **lifecycle phase** and ordered by **dependency depth** within each phase.
Build phases sequentially; specs within a phase may be built in listed order.

```
Phase 1         Phase 2           Phase 3            Phase 4         Phase 5
Foundation   →  Configuration  →  Identity & Auth →  Institutional →  Partnerships
(PHP/Laravel,   (install,         (auth, notify,      (departments,   (companies,
 base classes,   settings,         dashboard)          academic yrs)   partnerships)
 utilities)     branding)

Phase 6         Phase 7           Phase 8            Phase 9          Phase 10
Programs      →  Enrollment    →  Daily Ops       →  Assessment   →  Certification
(internship      (registration,    (logbook,           (rubrics,        (templates,
 structure)       placement)       attendance)         scoring)         credentials)

Phase 11         Phase 12
Reporting      → Maintenance
(grade cards,     (backup, GDPR, job queues,
 snapshots)        archiving, cleanup)
```

### Spec-Zero — Initial Specification

The initial project specification. A blanket spec-zero over all phase specs: product scope,
lifecycle, module landscape, role model, global (cross-cutting) requirements every feature spec
inherits, and the project-level roadmap. No dependency — everything depends on it.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| QLHDO | [Internara Project (Initial Specification)](QLHDO-internara-project.md) | Core | — |

### Phase 1 — Foundation

Core technology, architectural base classes, and shared utilities. Everything else depends on these.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| D2FT3 | [Architecture Design](D2FT3-architecture.md) | Core | —
| FB792 | [Tech Stack](FB792-tech-stack.md) | Core | D2FT3
| ZT6VS | [Core & Infrastructure Services](ZT6VS-core-infra-services.md) | Core | FB792
| SE5Q9 | [Base Classes](SE5Q9-base-classes.md) | Core | FB792, ZT6VS
| C8F0D | [Shared Utilities](C8F0D-shared-utilities.md) | Core | FB792, SE5Q9
| J68GZ | [System Requirements](J68GZ-system-requirements.md) | Core | FB792
| I1BCV | [Module Discovery](I1BCV-module-discovery.md) | Core | FB792, SE5Q9
| 89SRA | [Logging & Error Handling](89SRA-logging-and-error-handling.md) | Core | FB792, SE5Q9
| NUCY3 | [Event System](NUCY3-event-system.md) | Core | SE5Q9
| T4B26 | [RBAC & Authorization](T4B26-rbac-and-authorization.md) | Core | SE5Q9
| 2CF4Y | [Middleware Pipeline](2CF4Y-middleware-pipeline.md) | Core | SE5Q9
| 1PGM4 | [Security Headers](1PGM4-security-headers.md) | Core | 2CF4Y
| B114U | [Module Manager & Service](B114U-module-manager.md) | Core | SE5Q9, C8F0D, I1BCV

### Phase 2 — Configuration

System installation, settings, and visual identity. Depends on Phase 1.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 8NZAU | [Installation](8NZAU-installation.md) | Setup | FB792, SE5Q9
| VEJCX | [Setup Wizard](VEJCX-setup-wizard.md) | Setup | 8NZAU
| C9ZB6 | [Recovery Ecosystem](C9ZB6-recovery-ecosystem.md) | Setup | FB792, 8NZAU
| YB22J | [Settings Infrastructure](YB22J-settings-infrastructure.md) | Settings | SE5Q9
| 52O1I | [Branding, Theme & Locale](52O1I-branding-theme-locale.md) | Settings | YB22J
| 81SMS | [School Profile](81SMS-school-profile.md) | Academics | YB22J

### Phase 3 — Identity & Auth

Authentication, password management, profile, notifications, and dashboards. Depends on Phases 1–2.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 8XMYS | [Layout & UI System](8XMYS-layout-and-ui-system.md) | Core | SE5Q9, I1BCV, YB22J, 52O1I |
| YB7RG | [Authentication](YB7RG-authentication.md) | Auth | SE5Q9, T4B26
| TXR2H | [Notification Infrastructure](TXR2H-notification-infrastructure.md) | User | SE5Q9, NUCY3
| 3S55V | [Announcement System](3S55V-announcement-system.md) | SysAdmin | YB22J, TXR2H
| CKKZC | [Dashboard](CKKZC-dashboard.md) | User | YB7RG, TXR2H
| D9TKW | [Password Reset](D9TKW-password-reset.md) | Auth | SE5Q9, YB7RG
| CQVSK | [Password Confirmation](CQVSK-password-confirmation.md) | Auth | SE5Q9, YB7RG
| SHQ1J | [Account Recovery Slips](SHQ1J-account-recovery-slips.md) | Auth | SE5Q9, YB7RG, WQGTP
| OCEMS | [Profile Management](OCEMS-profile-management.md) | User | SE5Q9, T4B26, YB7RG, WQGTP

### Phase 4 — Institutional

Internal academic structure. Depends on Phase 2 (school profile).

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 4HWSB | [Department Management](4HWSB-department-management.md) | Academics | 81SMS
| XW6F5 | [Academic Year Management](XW6F5-academic-year-management.md) | Academics | 81SMS

### Phase 5 — Partnerships

External partners and formal collaborations. Depends on Phase 4 (departments).

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| XI3LB | [Company Management](XI3LB-company-management.md) | Partners | 81SMS, 4HWSB
| NTHQA | [Partnership Management](NTHQA-partnership-management.md) | Partners | XI3LB

### Phase 6 — Programs

Internship structure and grouping. Depends on Phases 4–5.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 7C5WM | [Internship Lifecycle](7C5WM-internship-lifecycle.md) | Program | XW6F5, NTHQA
| IT0OE | [Internship Groups](IT0OE-internship-groups.md) | Program | 7C5WM

### Phase 7 — Enrollment

Student intake, placement, user administration. Depends on Phase 6.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| MBB5R | [Registration](MBB5R-registration.md) | Enrollment | 7C5WM, IT0OE
| J9GBH | [Placement](J9GBH-placement.md) | Enrollment | MBB5R, XI3LB
| 920SO | [Account Application](920SO-account-application.md) | Enrollment | MBB5R
| 95EVB | [User CRUD & Status](95EVB-user-crud-and-status.md) | User | YB7RG
| O2KCR | [CSV Import & Export](O2KCR-csv-import-export.md) | Enrollment | 95EVB, XI3LB, 4HWSB
| EWCZ0 | [Account Slips](EWCZ0-account-slips.md) | User | 95EVB

### Phase 8 — Daily Operations

Active internship period. Depends on Phase 7 (placement active).

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 1KSWL | [Daily Activity](1KSWL-daily-activity.md) | Journals | J9GBH
| 2EHSE | [Supervision](2EHSE-supervision.md) | Journals | J9GBH, 1KSWL
| 3RU9S | [Incident](3RU9S-incident.md) | Incident | J9GBH

### Phase 9 — Assessment

Scoring, feedback, coursework. Depends on Phase 7 (placement active).

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| ARDA6 | [Assessment](ARDA6-assessment.md) | Assessment | J9GBH
| AXKZW | [Evaluation](AXKZW-evaluation.md) | Evaluation | J9GBH
| T657Z | [Assignment](T657Z-assignment.md) | Assignment | J9GBH

### Phase 10 — Certification

Credentials, documents, handbooks, media, PDF. Depends on Phases 8–9.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| PKYX6 | [Document Templates](PKYX6-document-templates.md) | Document | 8NZAU
| ZUFG8 | [Handbooks](ZUFG8-handbooks.md) | Document | PKYX6
| J0M04 | [Certification](J0M04-certification.md) | Certification | ARDA6, AXKZW
| WQGTP | [File Uploads & Media](WQGTP-file-uploads-media.md) | Core | SE5Q9
| 7UB7S | [PDF Generation](7UB7S-pdf-generation.md) | Core | WQGTP

### Phase 11 — Reporting

Archived snapshots, grade cards, official correspondence, and final lifecycle records. End of PKL lifecycle.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| R6BMW | [Reports](R6BMW-reports.md) | Reports | J0M04
| 7H5D6 | [Official Documents](7H5D6-official-documents.md) | Document | MBB5R, PKYX6, R6BMW

### Phase 12 — Maintenance

Backup, compliance, job queues, archiving, system cleanup, and demo/test data provisioning. Runs continuously after Phase 11.

| ID | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 8FVZA | [Job & Queue Infrastructure](8FVZA-job-queue-infrastructure.md) | Core | SE5Q9, NUCY3
| HBXCI | [Backup System](HBXCI-backup-system.md) | SysAdmin | NUCY3, T4B26, YB22J, TXR2H, 8FVZA
| 7HNCF | [GDPR Compliance](7HNCF-gdpr-compliance.md) | SysAdmin | YB22J, 95EVB
| E1MSJ | [System Maintenance](E1MSJ-system-maintenance.md) | SysAdmin | 89SRA, T4B26, 8FVZA, HBXCI
| 06IB6 | [Conditional Deployment](06IB6-deployment.md) | Core | J68GZ, 8NZAU, 8FVZA, E1MSJ
| 3UOZP | [Dummy Data](3UOZP-dummy-data.md) | Core | T4B26, 4HWSB, XW6F5, XI3LB, NTHQA, 7C5WM, IT0OE, MBB5R, J9GBH, 1KSWL, 2EHSE, 3RU9S, ARDA6, AXKZW, T657Z, J0M04, R6BMW

---

## Implementation Checklist Matrix — Priority-Ordered

This matrix tracks implementation fulfillment, test coverage, and verification status ordered by **business criticality** (most vital PKL features first), not build order. Update status as work progresses.

**Priority:** `🔴 Critical` (system cannot boot, secure, or provision without) • `🟡 High` (core PKL workflow) • `🟢 Medium` (assessment / certification / reporting output) • `🔵 Low` (maintenance & supporting infra)  
**Status Legend:** `⬜ Not Started` • `🟨 In Progress` • `🟩 Implemented` • `🟦 Verified`  
**Test Coverage:** `⬜ None` • `🟨 Partial` • `🟩 Full` • `🟧 Spec-Gap` (requirement exists, no test)  
**Last Verified:** Date of last spec↔code audit or test run

| Priority | ID | Spec | Module | Impl Status | Test Coverage | Last Verified | Notes |
|----------|----|------|--------|-------------|---------------|---------------|-------|
| 🔴 Critical | QLHDO | [Internara Project (Initial Specification)](QLHDO-internara-project.md) | Core | 🟩 Implemented | 🟧 Spec-Gap | 2026-08-19 | Spec-zero blanket: roles, localization, audit, UUID, health; lifecycle FR-L1–L12 map to phase specs; dead code findings filed as issues #390-#400 |
| 🔴 Critical | D2FT3 | [Architecture Design](D2FT3-architecture.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-19 | Governing architecture contract; 280 Core tests; audit: 6 C6 DTO violations, 4 C7 Action violations, 11 security findings filed as issues #401-#403 |
| 🔴 Critical | FB792 | [Tech Stack](FB792-tech-stack.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-18 | Dependency manifest; pinned versions; JS toolchain synced to package.json |
| 🔴 Critical | ZT6VS | [Core & Infrastructure Services](ZT6VS-core-infra-services.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-18 | SmartLogger, SettingsStore, SendsNotifications; gaps #387 Redis connections, #388 documents pipeline |
| 🔴 Critical | SE5Q9 | [Base Classes](SE5Q9-base-classes.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-18 | BaseAction, BaseModel, BaseEntity, BasePolicy; FR-M7 spec synced |
| 🔴 Critical | T4B26 | [RBAC & Authorization](T4B26-rbac-and-authorization.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Flat roles, Gate::before, Policy pattern |
| 🔴 Critical | 89SRA | [Logging & Error Handling](89SRA-logging-and-error-handling.md) | Core | 🟦 Verified | 🟩 Full | 2026-08-16 | Dual AppException/ModuleException trees |
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

> **How to update:** When implementation or test status changes, edit this table. Bump the `> **Last updated:**` date at the top of this file. Keep status honest — `🟧 Spec-Gap` means a requirement has no test; `🟨 Partial` means some requirements covered.

---

## How Specs Are Used

```
spec-writing → docs/specs/{feature}.md → feature-building → code-writing → pest-testing
```

1. **`spec-writing`** skill produces a spec document in this directory
2. **`feature-building`** skill reads the spec as the primary implementation guide
3. **`code-writing`** skill implements against the spec's FR/NFR IDs
4. **`pest-testing`** skill verifies implementation matches spec requirements

---

## Spec Template

Every spec follows the 11-section format defined in `.agents/skills/spec-writing/SKILL.md`:

1. Problem Statements
2. Goals & Non-Goals
3. User Stories / Use Cases
4. Functional Requirements (FR-IDs)
5. Non-Functional Requirements (NFR-IDs)
6. API / Data Contracts
7. Design Decisions (DD-IDs)
8. Success Metrics
9. Roadmap (prerequisites, build guide, next steps)
10. (Quick References)

---

## Quick References

- `.agents/skills/spec-writing/SKILL.md` — Spec writing conventions and template
- `.agents/skills/feature-building/SKILL.md` — How specs feed into implementation
- `docs/specs/QLHDO-internara-project.md` — High-level feature specs
- `docs/modules/index.md` — Module dependency graph
