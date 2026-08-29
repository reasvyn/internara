# Feature Specifications — `docs/specs/`

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
| 9YUUK | [Data Archiving & Retention](9YUUK-data-archiving.md) | SysAdmin | E1MSJ, HBXCI, 7HNCF, 8FVZA, YB22J, R6BMW
| 06IB6 | [Conditional Deployment](06IB6-deployment.md) | Core | J68GZ, 8NZAU, 8FVZA, E1MSJ
| 3UOZP | [Dummy Data](3UOZP-dummy-data.md) | Core | T4B26, 4HWSB, XW6F5, XI3LB, NTHQA, 7C5WM, IT0OE, MBB5R, J9GBH, 1KSWL, 2EHSE, 3RU9S, ARDA6, AXKZW, T657Z, J0M04, R6BMW

---

## Spec Implementation Matrix — Priority-Ordered

The implementation matrix — tracking business-criticality ordering, implementation status, test
coverage, last verification, and per-row notes — lives in its
**[own file](implementation-matrix.md)** with a comprehensive status-usage legend ("when to use /
when to promote each status").

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

Every spec follows the 11-section format defined by the `spec-writing` skill
(see [`spec-template.md`](spec-template.md) for the human-facing skeleton):

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

- `spec-writing` skill — Spec writing conventions and template
- `feature-building` skill — How specs feed into implementation
- `docs/specs/implementation-matrix.md` — Implementation status matrix (priority-ordered) with status-usage legend
- `docs/specs/QLHDO-internara-project.md` — High-level feature specs
- `docs/refs/modules/index.md` — Module dependency graph
